<?php

namespace Database\Seeders;

use App\Enums\LeaveStatus;
use App\Enums\Role;
use App\Enums\SwapRequestStatus;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use App\Models\SwapRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ShiftTemplateSeeder::class);

        // ── Users ──────────────────────────────────────────────────────────
        $manager = User::firstOrCreate(
            ['email' => 'vanessa@station.com'],
            ['name' => 'Vanessa', 'role' => Role::Manager, 'password' => bcrypt('password')],
        );

        $employeeNames = ['Dylan', 'Pamela', 'Daisy', 'Nine', 'Yvanka', 'Véronique', 'Dragana'];
        $employees = [];
        foreach ($employeeNames as $name) {
            $employees[] = User::firstOrCreate(
                ['email' => strtolower($name) . '@station.com'],
                ['name' => $name, 'role' => Role::Employee, 'password' => bcrypt('password')],
            );
        }

        // Deactivate old demo users if they exist
        User::whereIn('email', ['manager@station.com', 'employee@station.com'])->update(['is_active' => false]);

        // ── Public holidays (CH/FR) ────────────────────────────────────────
        $holidays = [
            ['2026-05-01', 'Fête du travail'],
            ['2026-05-14', 'Ascension'],
            ['2026-05-25', 'Lundi de Pentecôte'],
            ['2026-07-14', 'Fête nationale'],
            ['2026-08-01', 'Fête nationale suisse'],
            ['2026-08-15', 'Assomption'],
        ];
        foreach ($holidays as [$date, $name]) {
            PublicHoliday::firstOrCreate(['date' => $date], ['name' => $name]);
        }

        $holidayDates = collect($holidays)->pluck(0)->toArray();

        // ── Shift templates ────────────────────────────────────────────────
        $weekdayMorning = ShiftTemplate::where('day_type', 'weekday')->where('shift_type', 'morning')->first();
        $weekdayAfternoon = ShiftTemplate::where('day_type', 'weekday')->where('shift_type', 'afternoon')->first();
        $sundayMorning = ShiftTemplate::where('day_type', 'sunday_holiday')->where('shift_type', 'morning')->first();
        $sundayAfternoon = ShiftTemplate::where('day_type', 'sunday_holiday')->where('shift_type', 'afternoon')->first();

        // ── Generate shifts for 4 months ───────────────────────────────────
        $start = Carbon::parse('2026-04-01');
        $end = Carbon::parse('2026-07-31');

        $allEmployees = collect($employees);
        $day = $start->copy();

        while ($day->lte($end)) {
            $dateStr = $day->toDateString();
            $isSunday = $day->isSunday();
            $isHoliday = in_array($dateStr, $holidayDates);
            $isSundayOrHoliday = $isSunday || $isHoliday;

            if ($isSundayOrHoliday) {
                $morningTemplate = $sundayMorning;
                $afternoonTemplate = $sundayAfternoon;
            } else {
                $morningTemplate = $weekdayMorning;
                $afternoonTemplate = $weekdayAfternoon;
            }

            // Pick 2 employees for morning, 2 different for afternoon
            $shuffled = $allEmployees->shuffle();
            $morningWorkers = $shuffled->take(2);
            $afternoonWorkers = $shuffled->slice(2)->take(2);

            foreach ($morningWorkers as $emp) {
                Shift::firstOrCreate([
                    'date' => $dateStr,
                    'shift_template_id' => $morningTemplate->id,
                    'user_id' => $emp->id,
                ]);
            }

            foreach ($afternoonWorkers as $emp) {
                Shift::firstOrCreate([
                    'date' => $dateStr,
                    'shift_template_id' => $afternoonTemplate->id,
                    'user_id' => $emp->id,
                ]);
            }

            $day->addDay();
        }

        // ── Leave requests ─────────────────────────────────────────────────
        $leaves = [
            // Approved leaves
            [$employees[0], '2026-04-20', '2026-04-24', LeaveStatus::Approved],  // Dylan - 1 week
            [$employees[1], '2026-05-18', '2026-05-22', LeaveStatus::Approved],  // Pamela - 1 week
            [$employees[3], '2026-06-01', '2026-06-12', LeaveStatus::Approved],  // Nine - 2 weeks
            [$employees[5], '2026-07-06', '2026-07-17', LeaveStatus::Approved],  // Véronique - 2 weeks
            // Pending leave
            [$employees[2], '2026-05-04', '2026-05-08', LeaveStatus::Pending],   // Daisy - 1 week
            [$employees[4], '2026-06-22', '2026-06-26', LeaveStatus::Pending],   // Yvanka - 1 week
            // Denied leave
            [$employees[6], '2026-04-27', '2026-04-30', LeaveStatus::Denied],    // Dragana
        ];

        foreach ($leaves as [$employee, $startDate, $endDate, $status]) {
            LeaveRequest::firstOrCreate(
                ['user_id' => $employee->id, 'start_date' => $startDate, 'end_date' => $endDate],
                [
                    'status' => $status,
                    'note' => $status === LeaveStatus::Pending ? 'Vacances' : null,
                    'manager_id' => $status !== LeaveStatus::Pending ? $manager->id : null,
                    'decided_at' => $status !== LeaveStatus::Pending ? now() : null,
                ],
            );
        }

        // ── Swap requests ──────────────────────────────────────────────────
        // Approved swap: Dylan <-> Pamela
        $dylanShift = Shift::where('user_id', $employees[0]->id)
            ->where('date', '>=', '2026-04-18')->orderBy('date')->first();
        $pamelaShift = Shift::where('user_id', $employees[1]->id)
            ->where('date', '>=', '2026-04-18')->orderBy('date')->first();

        if ($dylanShift && $pamelaShift) {
            SwapRequest::firstOrCreate(
                ['requester_shift_id' => $dylanShift->id],
                [
                    'requester_id' => $employees[0]->id,
                    'target_id' => $employees[1]->id,
                    'target_shift_id' => $pamelaShift->id,
                    'type' => 'swap',
                    'status' => SwapRequestStatus::ManagerApproved,
                    'note' => 'Rendez-vous médical',
                    'peer_responded_at' => now()->subDays(2),
                    'manager_decided_at' => now()->subDay(),
                    'manager_id' => $manager->id,
                ],
            );
        }

        // Pending swap: Daisy <-> Nine
        $daisyShift = Shift::where('user_id', $employees[2]->id)
            ->where('date', '>=', '2026-04-22')->orderBy('date')->first();
        $nineShift = Shift::where('user_id', $employees[3]->id)
            ->where('date', '>=', '2026-04-22')->orderBy('date')->first();

        if ($daisyShift && $nineShift) {
            SwapRequest::firstOrCreate(
                ['requester_shift_id' => $daisyShift->id],
                [
                    'requester_id' => $employees[2]->id,
                    'target_id' => $employees[3]->id,
                    'target_shift_id' => $nineShift->id,
                    'type' => 'swap',
                    'status' => SwapRequestStatus::PendingPeer,
                    'note' => 'Échange de shift svp',
                ],
            );
        }

        // Peer-accepted swap waiting for manager: Dragana <-> Véronique
        $draganaShift = Shift::where('user_id', $employees[6]->id)
            ->where('date', '>=', '2026-05-01')->orderBy('date')->first();
        $veroniqueShift = Shift::where('user_id', $employees[5]->id)
            ->where('date', '>=', '2026-05-01')->orderBy('date')->first();

        if ($draganaShift && $veroniqueShift) {
            SwapRequest::firstOrCreate(
                ['requester_shift_id' => $draganaShift->id],
                [
                    'requester_id' => $employees[6]->id,
                    'target_id' => $employees[5]->id,
                    'target_shift_id' => $veroniqueShift->id,
                    'type' => 'swap',
                    'status' => SwapRequestStatus::PeerAccepted,
                    'note' => 'Je préfère ce créneau',
                    'peer_responded_at' => now()->subDay(),
                ],
            );
        }

        // Giveaway: Yvanka gives away a shift (open)
        $yvankaShift = Shift::where('user_id', $employees[4]->id)
            ->where('date', '>=', '2026-04-25')->orderBy('date')->first();

        if ($yvankaShift) {
            SwapRequest::firstOrCreate(
                ['requester_shift_id' => $yvankaShift->id],
                [
                    'requester_id' => $employees[4]->id,
                    'type' => 'giveaway',
                    'status' => SwapRequestStatus::Open,
                    'note' => 'Empêchement personnel',
                ],
            );
        }
    }
}
