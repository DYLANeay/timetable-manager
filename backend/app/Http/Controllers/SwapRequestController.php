<?php

namespace App\Http\Controllers;

use App\Enums\SwapRequestStatus;
use App\Http\Resources\SwapRequestResource;
use App\Models\Shift;
use App\Models\SwapRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SwapRequestController extends Controller
{
    private array $eagerLoad = [
        'requester', 'target',
        'requesterShift.shiftTemplate', 'requesterShift.user',
        'targetShift.shiftTemplate', 'targetShift.user',
        'manager',
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        $query = SwapRequest::with($this->eagerLoad)
            ->orderByDesc('created_at');

        if ($user->isEmployee()) {
            $query->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)
                    ->orWhere('target_id', $user->id);
            });
        }

        return SwapRequestResource::collection($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requester_shift_id' => ['required', 'exists:shifts,id'],
            'target_shift_id' => ['required', 'exists:shifts,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $requesterShift = Shift::findOrFail($validated['requester_shift_id']);
        $targetShift = Shift::findOrFail($validated['target_shift_id']);

        abort_if($requesterShift->user_id !== $request->user()->id, 403, 'You can only swap your own shifts.');
        abort_if($targetShift->user_id === null, 422, 'Target shift has no assigned employee.');
        abort_if($requesterShift->user_id === $targetShift->user_id, 422, 'Cannot swap with yourself.');

        $swapRequest = SwapRequest::create([
            'requester_id' => $request->user()->id,
            'target_id' => $targetShift->user_id,
            'requester_shift_id' => $requesterShift->id,
            'target_shift_id' => $targetShift->id,
            'status' => SwapRequestStatus::PendingPeer,
            'note' => $validated['note'] ?? null,
        ]);

        $swapRequest->load($this->eagerLoad);

        return (new SwapRequestResource($swapRequest))
            ->response()
            ->setStatusCode(201);
    }

    public function respond(Request $request, SwapRequest $swapRequest): SwapRequestResource
    {
        abort_if($swapRequest->target_id !== $request->user()->id, 403, 'Only the target employee can respond.');
        abort_if($swapRequest->status !== SwapRequestStatus::PendingPeer, 422, 'This request is no longer pending.');

        $validated = $request->validate([
            'accept' => ['required', 'boolean'],
        ]);

        $swapRequest->update([
            'status' => $validated['accept'] ? SwapRequestStatus::PeerAccepted : SwapRequestStatus::PeerDeclined,
            'peer_responded_at' => now(),
        ]);

        $swapRequest->load($this->eagerLoad);

        return new SwapRequestResource($swapRequest);
    }

    public function decide(Request $request, SwapRequest $swapRequest): SwapRequestResource
    {
        abort_if($swapRequest->status !== SwapRequestStatus::PeerAccepted, 422, 'This request must be accepted by the peer first.');

        $validated = $request->validate([
            'approve' => ['required', 'boolean'],
        ]);

        if ($validated['approve']) {
            DB::transaction(function () use ($swapRequest, $request) {
                $requesterShift = Shift::findOrFail($swapRequest->requester_shift_id);
                $targetShift = Shift::findOrFail($swapRequest->target_shift_id);

                $tempUserId = $requesterShift->user_id;
                $requesterShift->update(['user_id' => $targetShift->user_id]);
                $targetShift->update(['user_id' => $tempUserId]);

                $swapRequest->update([
                    'status' => SwapRequestStatus::ManagerApproved,
                    'manager_decided_at' => now(),
                    'manager_id' => $request->user()->id,
                ]);
            });
        } else {
            $swapRequest->update([
                'status' => SwapRequestStatus::ManagerDenied,
                'manager_decided_at' => now(),
                'manager_id' => $request->user()->id,
            ]);
        }

        $swapRequest->load($this->eagerLoad);

        return new SwapRequestResource($swapRequest);
    }

    public function cancel(Request $request, SwapRequest $swapRequest): SwapRequestResource
    {
        abort_if($swapRequest->requester_id !== $request->user()->id, 403, 'Only the requester can cancel.');
        abort_if(
            in_array($swapRequest->status, [SwapRequestStatus::ManagerApproved, SwapRequestStatus::ManagerDenied, SwapRequestStatus::Cancelled]),
            422,
            'This request can no longer be cancelled.',
        );

        $swapRequest->update(['status' => SwapRequestStatus::Cancelled]);
        $swapRequest->load($this->eagerLoad);

        return new SwapRequestResource($swapRequest);
    }
}
