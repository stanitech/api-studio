<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\ChatMessage;
use App\Models\User;

class ChatController extends Controller
{
    /**
     * GET /api/chat/messages
     * Fetch paginated messages for a channel.
     * ?channel=general|collection|endpoint
     * ?channel_key=collectionId or endpointId
     * ?before_id=  (for pagination)
     */
    public function index(Request $request): JsonResponse
    {
        $channel    = $request->query('channel', 'general');
        $channelKey = $request->query('channel_key');
        $beforeId   = $request->query('before_id');
        $limit      = min((int) $request->query('limit', 50), 100);

        $query = ChatMessage::with('user')
            ->where('channel', $channel)
            ->where(function ($q) use ($channelKey) {
                if ($channelKey) {
                    $q->where('channel_key', $channelKey);
                } else {
                    $q->whereNull('channel_key');
                }
            })
            ->orderByDesc('created_at');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->limit($limit)->get()->reverse()->values();

        return response()->json([
            'data'    => $messages->map->toApiArray(),
            'has_more'=> $messages->count() === $limit,
        ]);
    }

    /**
     * POST /api/chat/messages
     * Send a chat message. Triggers email to offline users if they are @mentioned
     * or if it's a broadcast message.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'message'     => 'required|string|max:2000',
            'channel'     => 'nullable|in:general,collection,endpoint',
            'channel_key' => 'nullable|string',
            'collection_id' => 'nullable|string',
            'mentions'    => 'nullable|array',
            'mentions.*'  => 'integer',
        ]);

        $user = Auth::user();

        $chatMsg = ChatMessage::create([
            'user_id'      => $user->id,
            'collection_id'=> $request->input('collection_id'),
            'channel'      => $request->input('channel', 'general'),
            'channel_key'  => $request->input('channel_key'),
            'message'      => $request->input('message'),
            'type'         => $this->detectType($request->input('message')),
            'mentions'     => $request->input('mentions', []),
        ]);

        // Load the user relationship
        $chatMsg->load('user');

        // Send email to mentioned or all-channel users who are offline
        $this->dispatchEmailNotifications($chatMsg, $user);

        return response()->json([
            'data'    => $chatMsg->toApiArray(),
            'message' => 'Message sent.',
        ], 201);
    }

    /**
     * DELETE /api/chat/messages/{id}
     * Delete own message (admins can delete any).
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        $msg  = ChatMessage::findOrFail($id);

        if ($msg->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Cannot delete another user\'s message.'], 403);
        }

        $msg->delete();
        return response()->json(['message' => 'Message deleted.']);
    }

    /**
     * GET /api/chat/poll
     * Long-poll endpoint — returns new messages since a given message ID.
     * Used when WebSockets are not available.
     */
    public function poll(Request $request): JsonResponse
    {
        $channel     = $request->query('channel', 'general');
        $channelKey  = $request->query('channel_key');
        $afterId     = (int) $request->query('after_id', 0);

        $messages = ChatMessage::with('user')
            ->where('channel', $channel)
            ->where(function ($q) use ($channelKey) {
                if ($channelKey) {
                    $q->where('channel_key', $channelKey);
                } else {
                    $q->whereNull('channel_key');
                }
            })
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(50)
            ->get();

        return response()->json([
            'data'       => $messages->map->toApiArray(),
            'last_id'    => $messages->last()?->id ?? $afterId,
            'timestamp'  => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/chat/users
     * List all users available to mention in chat.
     */
    public function users(): JsonResponse
    {
        $users = User::where('is_active', true)
            ->where('id', '!=', Auth::id())
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'     => $u->id,
                'name'   => $u->name,
                'email'  => $u->email,
                'role'   => $u->role,
                'avatar' => strtoupper(substr($u->name, 0, 1)),
            ]);

        return response()->json(['data' => $users]);
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function detectType(string $message): string
    {
        if (str_contains($message, '@')) return 'ai_mention';
        return 'text';
    }

    private function dispatchEmailNotifications(ChatMessage $msg, User $sender): void
    {
        try {
            $mentions = $msg->mentions ?? [];
            $recipients = collect();

            if (!empty($mentions)) {
                // Email explicitly @mentioned users
                $recipients = User::whereIn('id', $mentions)
                    ->where('id', '!=', $sender->id)
                    ->where('is_active', true)
                    ->get();
            } else {
                // For general channel messages, notify all active users except sender
                // (only if no mentions — avoids spam)
                if ($msg->channel === 'general') {
                    $recipients = User::where('id', '!=', $sender->id)
                        ->where('is_active', true)
                        ->get();
                }
            }

            foreach ($recipients as $recipient) {
                $this->sendEmailNotification($msg, $sender, $recipient);
            }

            // Mark as email-notified
            $msg->update(['email_sent' => true]);

        } catch (\Throwable $e) {
            Log::warning('Chat email notification failed: ' . $e->getMessage());
        }
    }

    private function sendEmailNotification(ChatMessage $msg, User $sender, User $recipient): void
    {
        $channelLabel = match($msg->channel) {
            'collection' => 'Collection Chat',
            'endpoint'   => 'Endpoint Discussion',
            default      => 'General Chat',
        };

        $subject = "[API Docs] {$sender->name} sent a message in {$channelLabel}";
        $appUrl  = config('app.url', 'http://localhost:8000');

        $html = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='font-family:sans-serif;background:#0d1117;color:#e6edf3;padding:0;margin:0'>
  <div style='max-width:560px;margin:30px auto;background:#161b22;border:1px solid #30363d;border-radius:12px;overflow:hidden'>
    <div style='background:linear-gradient(135deg,#58a6ff,#a78bfa);padding:20px 28px'>
      <h2 style='margin:0;color:#000;font-size:1.1rem'>💬 New Message in API Docs</h2>
    </div>
    <div style='padding:24px 28px'>
      <div style='display:flex;align-items:center;gap:10px;margin-bottom:16px'>
        <div style='width:36px;height:36px;border-radius:50%;background:#58a6ff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#000;font-size:.9rem'>" . strtoupper(substr($sender->name, 0, 1)) . "</div>
        <div>
          <div style='font-weight:600;font-size:.9rem'>" . htmlspecialchars($sender->name) . "</div>
          <div style='font-size:.75rem;color:#8b949e'>" . htmlspecialchars($channelLabel) . " · " . now()->format('M j, Y g:i A') . "</div>
        </div>
      </div>
      <div style='background:#21262d;border:1px solid #30363d;border-radius:8px;padding:14px 16px;font-size:.88rem;line-height:1.6;color:#e6edf3'>
        " . nl2br(htmlspecialchars($msg->message)) . "
      </div>
      <div style='margin-top:20px'>
        <a href='{$appUrl}' style='display:inline-block;background:linear-gradient(135deg,#58a6ff,#a78bfa);color:#000;text-decoration:none;padding:10px 20px;border-radius:7px;font-weight:700;font-size:.85rem'>
          Open API Docs →
        </a>
      </div>
      <p style='font-size:.72rem;color:#8b949e;margin-top:16px'>
        You're receiving this because you're a member of this API Docs workspace.
        Reply by opening the app and joining the conversation.
      </p>
    </div>
  </div>
</body>
</html>";

        Mail::html($html, function ($m) use ($recipient, $subject) {
            $m->to($recipient->email, $recipient->name)
              ->subject($subject)
              ->from(
                  config('mail.from.address', 'noreply@apidocs.dev'),
                  config('mail.from.name', 'API Docs')
              );
        });
    }
}
