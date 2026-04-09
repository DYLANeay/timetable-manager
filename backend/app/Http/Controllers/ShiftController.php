<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['week' => ['required', 'date']]);

        $shifts = Shift::with(['user', 'shiftTemplate'])
            ->forWeek($request->week)
            ->orderBy('date')
            ->get();

        return ShiftResource::collection($shifts);
    }

    public function myShifts(Request $request)
    {
        $request->validate(['week' => ['required', 'date']]);

        $shifts = Shift::with('shiftTemplate')
            ->forWeek($request->week)
            ->forUser($request->user()->id)
            ->orderBy('date')
            ->get();

        return ShiftResource::collection($shifts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'shift_template_id' => ['required', 'exists:shift_templates,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $shift = Shift::create($validated);
        $shift->load(['user', 'shiftTemplate']);

        return (new ShiftResource($shift))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Shift $shift): ShiftResource
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $shift->update($validated);
        $shift->load(['user', 'shiftTemplate']);

        return new ShiftResource($shift);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        $shift->delete();

        return response()->json(null, 204);
    }

    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shifts' => ['required', 'array'],
            'shifts.*.user_id' => ['nullable', 'exists:users,id'],
            'shifts.*.shift_template_id' => ['required', 'exists:shift_templates,id'],
            'shifts.*.date' => ['required', 'date'],
            'shifts.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $created = [];
        foreach ($validated['shifts'] as $shiftData) {
            $created[] = Shift::updateOrCreate(
                [
                    'date' => $shiftData['date'],
                    'shift_template_id' => $shiftData['shift_template_id'],
                ],
                $shiftData,
            );
        }

        $shifts = Shift::with(['user', 'shiftTemplate'])
            ->whereIn('id', array_map(fn ($s) => $s->id, $created))
            ->get();

        return ShiftResource::collection($shifts)
            ->response()
            ->setStatusCode(201);
    }
}
