<?php

namespace App\Http\Controllers;

use App\Enums\LeaveStatus;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'year' => ['sometimes', 'integer', 'min:2020', 'max:2100'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
        ]);

        $year = $request->integer('year', now()->year);

        $query = LeaveRequest::with(['user', 'manager'])
            ->where(function ($q) use ($year) {
                $q->whereYear('start_date', $year)
                  ->orWhereYear('end_date', $year);
            })
            ->orderBy('start_date');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        return LeaveRequestResource::collection($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $leave = LeaveRequest::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'status' => LeaveStatus::Pending,
        ]);

        $leave->load('user');

        return (new LeaveRequestResource($leave))
            ->response()
            ->setStatusCode(201);
    }

    public function decide(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,denied'],
        ]);

        $leaveRequest->update([
            'status' => $validated['status'],
            'manager_id' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $leaveRequest->load(['user', 'manager']);

        return (new LeaveRequestResource($leaveRequest))
            ->response();
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        if ($leaveRequest->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($leaveRequest->status !== LeaveStatus::Pending) {
            abort(422, 'Only pending requests can be cancelled.');
        }

        $leaveRequest->delete();

        return response()->json(null, 204);
    }

    public function balance(Request $request)
    {
        $request->validate([
            'year' => ['sometimes', 'integer', 'min:2020', 'max:2100'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
        ]);

        $year = $request->integer('year', now()->year);
        $userId = $request->filled('user_id')
            ? $request->integer('user_id')
            : $request->user()->id;

        $approved = LeaveRequest::where('user_id', $userId)
            ->where('status', LeaveStatus::Approved)
            ->where(function ($q) use ($year) {
                $q->whereYear('start_date', $year)
                  ->orWhereYear('end_date', $year);
            })
            ->get();

        $usedDays = $approved->sum('business_days');

        return response()->json([
            'total_days' => 25,
            'used_days' => $usedDays,
            'remaining_days' => 25 - $usedDays,
            'year' => $year,
        ]);
    }
}
