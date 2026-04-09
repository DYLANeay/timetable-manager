<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:employee,manager'],
        ]);

        $user = User::create($validated);

        return response()->json(['data' => $user], 201);
    }

    public function update(Request $request, User $employee): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $employee->id],
            'password' => ['sometimes', 'string', 'min:8'],
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
