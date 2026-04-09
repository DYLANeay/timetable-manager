<?php

namespace App\Http\Controllers;

use App\Mail\EmployeeInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active']);

        return response()->json(['data' => $employees]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'in:employee,manager'],
        ]);

        $temporaryPassword = Str::password(12, symbols: false);

        $user = User::create([
            ...$validated,
            'password' => $temporaryPassword,
        ]);

        Mail::to($user->email)->send(new EmployeeInvitation($user, $temporaryPassword));

        return response()->json(['data' => $user], 201);
    }

    public function update(Request $request, User $employee): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $employee->id],
            'role' => ['sometimes', 'in:employee,manager'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $employee->update($validated);

        return response()->json(['data' => $employee]);
    }

    public function destroy(User $employee): JsonResponse
    {
        $employee->update(['is_active' => false]);

        return response()->json(null, 204);
    }
}
