<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // User helpers
    // -------------------------------------------------------------------------

    protected function createEmployee(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'      => Role::Employee,
            'is_active' => true,
        ], $attrs));
    }

    protected function createManager(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'      => Role::Manager,
            'is_active' => true,
        ], $attrs));
    }

    protected function asEmployee(array $attrs = []): User
    {
        $user = $this->createEmployee($attrs);
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    protected function asManager(array $attrs = []): User
    {
        $user = $this->createManager($attrs);
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    // -------------------------------------------------------------------------
    // Shift template helpers
    // -------------------------------------------------------------------------

    protected function makeTemplate(string $dayType = 'weekday', string $shiftType = 'morning'): ShiftTemplate
    {
        static $times = [
            'weekday'        => ['morning' => ['06:15', '13:45'], 'afternoon' => ['13:45', '21:15']],
            'sunday_holiday' => ['morning' => ['07:45', '14:30'], 'afternoon' => ['14:30', '21:15']],
        ];

        [$start, $end] = $times[$dayType][$shiftType];

        return ShiftTemplate::firstOrCreate(
            ['day_type' => $dayType, 'shift_type' => $shiftType],
            ['start_time' => $start, 'end_time' => $end],
        );
    }

    protected function makeShift(User $user, string $date = '2026-06-02', string $shiftType = 'morning'): Shift
    {
        $template = $this->makeTemplate('weekday', $shiftType);

        return Shift::create([
            'user_id'          => $user->id,
            'shift_template_id' => $template->id,
            'date'             => $date,
        ]);
    }
}
