<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Driver;
use App\Models\User;
use Faker\Factory as Faker;
use Carbon\Carbon;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get a super admin user for suspended_by field
        $superAdmin = User::where('role', 'super_admin')->first();

        // Create 20 active drivers
        for ($i = 1; $i <= 20; $i++) {
            $totalTrips = $faker->numberBetween(50, 500);
            $completedTrips = (int) ($totalTrips * $faker->randomFloat(2, 0.85, 0.98));
            $cancelledTrips = $totalTrips - $completedTrips;

            Driver::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'phone' => $faker->unique()->numerify('+233#########'),
                'email' => $faker->unique()->safeEmail,
                'date_of_birth' => $faker->dateTimeBetween('-55 years', '-25 years'),
                'is_active' => true,
                'average_rating' => $faker->randomFloat(2, 3.5, 5.0),
                'total_trips' => $totalTrips,
                'completed_trips' => $completedTrips,
                'cancelled_trips' => $cancelledTrips,
                'is_suspended' => false,
                'notes' => $faker->optional(0.3)->sentence
            ]);
        }

        // Create 5 suspended drivers
        for ($i = 1; $i <= 5; $i++) {
            $totalTrips = $faker->numberBetween(10, 100);
            $completedTrips = (int) ($totalTrips * $faker->randomFloat(2, 0.70, 0.85));
            $cancelledTrips = $totalTrips - $completedTrips;

            Driver::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'phone' => $faker->unique()->numerify('+233#########'),
                'email' => $faker->unique()->safeEmail,
                'date_of_birth' => $faker->dateTimeBetween('-55 years', '-25 years'),
                'is_active' => false,
                'average_rating' => $faker->randomFloat(2, 2.0, 4.5),
                'total_trips' => $totalTrips,
                'completed_trips' => $completedTrips,
                'cancelled_trips' => $cancelledTrips,
                'is_suspended' => true,
                'suspended_at' => $faker->dateTimeBetween('-30 days', 'now'),
                'suspension_reason' => $faker->randomElement([
                    'Multiple customer complaints',
                    'Failed vehicle inspection',
                    'Repeated late arrivals',
                    'Unprofessional conduct',
                    'Safety violation'
                ]),
                'suspended_by' => $superAdmin?->id,
                'notes' => $faker->sentence
            ]);
        }

        // Create 5 new/inactive drivers (recently registered, no trips yet)
        for ($i = 1; $i <= 5; $i++) {
            Driver::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'phone' => $faker->unique()->numerify('+233#########'),
                'email' => $faker->unique()->safeEmail,
                'date_of_birth' => $faker->dateTimeBetween('-45 years', '-23 years'),
                'is_active' => true,
                'average_rating' => null,
                'total_trips' => 0,
                'completed_trips' => 0,
                'cancelled_trips' => 0,
                'is_suspended' => false,
                'notes' => 'New driver - pending first assignment'
            ]);
        }

        // Create 3 high-performing drivers with excellent stats
        for ($i = 1; $i <= 3; $i++) {
            $totalTrips = $faker->numberBetween(500, 1000);
            $completedTrips = (int) ($totalTrips * $faker->randomFloat(2, 0.95, 0.99));
            $cancelledTrips = $totalTrips - $completedTrips;

            Driver::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'phone' => $faker->unique()->numerify('+233#########'),
                'email' => $faker->unique()->safeEmail,
                'date_of_birth' => $faker->dateTimeBetween('-50 years', '-30 years'),
                'is_active' => true,
                'average_rating' => $faker->randomFloat(2, 4.8, 5.0),
                'total_trips' => $totalTrips,
                'completed_trips' => $completedTrips,
                'cancelled_trips' => $cancelledTrips,
                'is_suspended' => false,
                'notes' => 'Top performer - excellent track record'
            ]);
        }

        $this->command->info('✅ Created 33 drivers: 20 active, 5 suspended, 5 new, 3 high-performers');
    }
}