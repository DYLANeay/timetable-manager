<?php

namespace Tests\Unit;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use Tests\TestCase;

class LeaveRequestBusinessDaysTest extends TestCase
{
    private function makeLeave(string $start, string $end): LeaveRequest
    {
        return new LeaveRequest([
            'start_date' => $start,
            'end_date'   => $end,
        ]);
    }

    public function test_single_weekday_counts_as_one_business_day(): void
    {
        // Monday 2026-06-01
        $leave = $this->makeLeave('2026-06-01', '2026-06-01');
        $this->assertEquals(1, $leave->business_days);
    }

    public function test_full_monday_to_friday_week_is_five_business_days(): void
    {
        $leave = $this->makeLeave('2026-06-01', '2026-06-05');
        $this->assertEquals(5, $leave->business_days);
    }

    public function test_monday_to_sunday_is_five_business_days_not_seven(): void
    {
        // Mon–Sun = 7 calendar days, 5 business days
        $leave = $this->makeLeave('2026-06-01', '2026-06-07');
        $this->assertEquals(5, $leave->business_days);
    }

    public function test_saturday_only_is_zero_business_days(): void
    {
        // 2026-06-06 is a Saturday
        $leave = $this->makeLeave('2026-06-06', '2026-06-06');
        $this->assertEquals(0, $leave->business_days);
    }

    public function test_two_full_weeks_is_ten_business_days(): void
    {
        $leave = $this->makeLeave('2026-06-01', '2026-06-14');
        $this->assertEquals(10, $leave->business_days);
    }

    public function test_same_start_and_end_on_friday_is_one_business_day(): void
    {
        // 2026-06-05 is a Friday
        $leave = $this->makeLeave('2026-06-05', '2026-06-05');
        $this->assertEquals(1, $leave->business_days);
    }

    public function test_weekend_only_range_is_zero_business_days(): void
    {
        // Sat–Sun 2026-06-06 to 2026-06-07
        $leave = $this->makeLeave('2026-06-06', '2026-06-07');
        $this->assertEquals(0, $leave->business_days);
    }
}
