<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'user_id', 'collection_id', 'channel', 'channel_key',
        'message', 'type', 'mentions', 'email_sent',
    ];

    protected $casts = [
        'mentions'   => 'array',
        'email_sent' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toApiArray(): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'user_name'   => $this->user->name ?? 'Unknown',
            'user_role'   => $this->user->role ?? 'viewer',
            'collection_id' => $this->collection_id,
            'channel'     => $this->channel,
            'channel_key' => $this->channel_key,
            'message'     => $this->message,
            'type'        => $this->type,
            'mentions'    => $this->mentions ?? [],
            'created_at'  => $this->created_at->toIso8601String(),
            'time_ago'    => $this->created_at->diffForHumans(),
        ];
    }
}
