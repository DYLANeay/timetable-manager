<?php

namespace Database\Seeders;

use App\Models\ShiftTemplate;
use Illuminate\Database\Seeder;

class ShiftTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['day_type' => 'weekday', 'shift_type' => 'morning', 'start_time' => '06:15', 'end_time' => '13:45'],
            ['day_type' => 'weekday', 'shift_type' => 'afternoon', 'start_time' => '13:45', 'end_time' => '21:15'],
            ['day_type' => 'sunday_holiday', 'shift_type' => 'morning', 'start_time' => '07:45', 'end_time' => '14:30'],
            ['day_type' => 'sunday_holiday', 'shift_type' => 'afternoon', 'start_time' => '14:30', 'end_time' => '21:15'],
        ];

        foreach ($templates as $template) {
            ShiftTemplate::updateOrCreate(
                ['day_type' => $template['day_type'], 'shift_type' => $template['shift_type']],
                $template,
            );
        }
    }
}
