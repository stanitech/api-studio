<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    /** GET /api/users — admin sees all, others see themselves */
    public function index(): JsonResponse
    {
        $this->requirePermission('manage_users');

        $users = User::orderBy('name')->get()->map->toApiArray();
        return response()->json(['data' => $users]);
    }

    /** POST /api/users — admin creates developer accounts */
    public function store(Request $request): JsonResponse
    {
        $this->requirePermission('manage_users');

        $request->validate([
            'name'                  => 'required|string|max:120',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8',
            'role'                  => 'required|in:admin,editor,viewer',
            'permissions'           => 'nullable|array',
            'permissions.*'         => 'string|in:read,write,run,ai,manage_users',
            'default_collection_id' => 'nullable|string',
        ]);

        $user = User::create([
            'name'                  => $request->name,
            'email'                 => $request->email,
            'password'              => Hash::make($request->password),
            'role'                  => $request->role,
            'permissions'           => $request->input('permissions', User::defaultPermissions($request->role)),
            'default_collection_id' => $request->default_collection_id,
            'is_active'             => true,
        ]);

        return response()->json([
            'data'    => $user->toApiArray(),
            'message' => 'Developer account created.',
        ], 201);
    }

    /** PUT /api/users/{id} — update role, permissions, collection, active status */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->requirePermission('manage_users');

        $user = User::findOrFail($id);

        $request->validate([
            'name'                  => 'nullable|string|max:120',
            'email'                 => 'nullable|email|unique:users,email,'.$id,
            'password'              => 'nullable|string|min:8',
            'role'                  => 'nullable|in:admin,editor,viewer',
            'permissions'           => 'nullable|array',
            'permissions.*'         => 'string|in:read,write,run,ai,manage_users',
            'default_collection_id' => 'nullable|string',
            'is_active'             => 'nullable|boolean',
        ]);

        if ($request->filled('name'))                  $user->name = $request->name;
        if ($request->filled('email'))                 $user->email = $request->email;
        if ($request->filled('password'))              $user->password = Hash::make($request->password);
        if ($request->filled('role'))                  $user->role = $request->role;
        if ($request->has('permissions'))              $user->permissions = $request->permissions;
        if ($request->has('default_collection_id'))    $user->default_collection_id = $request->default_collection_id;
        if ($request->has('is_active'))                $user->is_active = $request->boolean('is_active');

        $user->save();

        return response()->json([
            'data'    => $user->toApiArray(),
            'message' => 'User updated.',
        ]);
    }

    /** DELETE /api/users/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->requirePermission('manage_users');

        if (Auth::id() === $id) {
            return response()->json(['error' => 'You cannot delete yourself.'], 422);
        }

        User::findOrFail($id)->delete();
        return response()->json(['message' => 'User deleted.']);
    }

    /** POST /api/users/change-password — any user can change their own */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8',
        ]);

        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect.'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed.']);
    }

    private function requirePermission(string $permission): void
    {
        if (!Auth::check() || !Auth::user()->hasPermission($permission)) {
            abort(403, 'Insufficient permissions.');
        }
    }
}

