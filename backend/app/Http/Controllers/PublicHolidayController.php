<?php

namespace App\Http\Controllers;

use App\Models\PublicHoliday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['year' => ['sometimes', 'integer', 'min:2020', 'max:2100']]);

        $year = $request->integer('year', now()->year);

        $holidays = PublicHoliday::whereYear('date', $year)
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $holidays]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'unique:public_holidays,date'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $holiday = PublicHoliday::create($validated);

        return response()->json(['data' => $holiday], 201);
    }

    public function destroy(PublicHoliday $publicHoliday): JsonResponse
    {
        $publicHoliday->delete();

        return response()->json(null, 204);
    }
}
