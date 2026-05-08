<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        return Inertia::render('Users/Index', [
            'users' => User::select('id', 'name', 'email', 'role', 'status', 'created_at')
                ->latest()
                ->paginate($perPage)
                ->withQueryString(),
            'filters' => [
                'per_page' => (int)$perPage
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:owner,kasir',
            'status' => 'required|in:active,inactive',
        ]);

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'status' => $request->status,
        ]);

        ActivityLog::log('Tambah User', "Menambahkan akun baru: {$newUser->name} ({$newUser->email})", $newUser);

        return redirect()->route('users.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Users/Edit', [
            'user' => $user
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:owner,kasir',
            'status' => 'required|in:active,inactive',
        ]);

        $user->update($request->only('name', 'email', 'role', 'status'));

        ActivityLog::log('Edit User', "Memperbarui data akun: {$user->name} ({$user->email})", $user);

        return redirect()->back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        ActivityLog::log('Hapus User', "Menghapus akun: {$user->name} ({$user->email})");

        $user->delete();

        return redirect()->back()->with('success', 'Akun berhasil dihapus.');
    }
}
