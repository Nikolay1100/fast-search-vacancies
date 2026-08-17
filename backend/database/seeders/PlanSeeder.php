<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::firstOrCreate(
            ['name' => '1 Week'],
            [
                'price' => 350,
                'duration_days' => 7,
                'offer_id' => 'offer-1-week-fake-id', // REPLACE THIS
                'is_active' => true,
            ]
        );

        Plan::firstOrCreate(
            ['name' => '2 Weeks'],
            [
                'price' => 450,
                'duration_days' => 14,
                'offer_id' => 'offer-2-weeks-fake-id', // REPLACE THIS
                'is_active' => true,
            ]
        );

        Plan::firstOrCreate(
            ['name' => '1 Month'],
            [
                'price' => 750,
                'duration_days' => 30,
                'offer_id' => 'offer-1-month-fake-id', // REPLACE THIS
                'is_active' => true,
            ]
        );
    }
}
