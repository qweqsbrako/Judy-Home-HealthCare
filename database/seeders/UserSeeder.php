<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 35 Patients
        $this->createPatients(35);
        
        // Create 15 Nurses
        $this->createNurses(15);
        
        // Create 10 Doctors
        $this->createDoctors(10);
    }

    /**
     * Create patient users
     */
    private function createPatients(int $count): void
    {
        $this->command->info("Creating {$count} patients...");

        for ($i = 1; $i <= $count; $i++) {
            $firstName = fake()->firstName();
            $lastName = fake()->lastName();
            
            User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($firstName . '.' . $lastName . $i . '@patient.com'),
                'phone' => $this->generateGhanaianPhone(),
                'role' => 'patient',
                'gender' => fake()->randomElement(['male', 'female']),
                'date_of_birth' => fake()->date('Y-m-d', '-25 years'),
                'ghana_card_number' => $this->generateGhanaCardNumber(),
                'password' => Hash::make('password123'),
                'verification_status' => fake()->randomElement(['verified', 'pending']),
                'is_verified' => fake()->boolean(80), // 80% verified
                'is_active' => true,
                'verified_at' => fake()->boolean(80) ? now()->subDays(rand(1, 30)) : null,
                'verified_by' => fake()->boolean(80) ? 1 : null,
                'registered_ip' => fake()->ipv4(),
                'emergency_contact_name' => fake()->name(),
                'emergency_contact_phone' => $this->generateGhanaianPhone(),
                'last_login_at' => fake()->boolean(70) ? now()->subDays(rand(0, 7)) : null,
                'created_at' => now()->subDays(rand(1, 90)),
                'updated_at' => now()->subDays(rand(0, 30)),
            ]);
        }

        $this->command->info("✓ {$count} patients created successfully!");
    }

    /**
     * Create nurse users
     */
    private function createNurses(int $count): void
    {
        $this->command->info("Creating {$count} nurses...");

        $nurseSpecializations = [
            'general_care',
            'pediatric_care',
            'geriatric_care',
            'critical_care',
            'emergency_care',
            'mental_health',
            'palliative_care',
        ];

        for ($i = 1; $i <= $count; $i++) {
            $firstName = fake()->firstName();
            $lastName = fake()->lastName();
            
            User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($firstName . '.' . $lastName . $i . '@nurse.com'),
                'phone' => $this->generateGhanaianPhone(),
                'role' => 'nurse',
                'gender' => fake()->randomElement(['male', 'female']),
                'date_of_birth' => fake()->date('Y-m-d', '-35 years'),
                'ghana_card_number' => $this->generateGhanaCardNumber(),
                'password' => Hash::make('password123'),
                'verification_status' => 'verified',
                'is_verified' => true,
                'is_active' => true,
                'verified_at' => now()->subDays(rand(1, 30)),
                'verified_by' => 1,
                'registered_ip' => fake()->ipv4(),
                'license_number' => $this->generateNurseLicenseNumber(),
                'specialization' => fake()->randomElement($nurseSpecializations),
                'years_experience' => rand(1, 15),
                'last_login_at' => fake()->boolean(85) ? now()->subDays(rand(0, 3)) : null,
                'created_at' => now()->subDays(rand(30, 180)),
                'updated_at' => now()->subDays(rand(0, 15)),
            ]);
        }

        $this->command->info("✓ {$count} nurses created successfully!");
    }

    /**
     * Create doctor users
     */
    private function createDoctors(int $count): void
    {
        $this->command->info("Creating {$count} doctors...");

        $doctorSpecializations = [
            'cardiology',
            'pediatric_care',
            'neurology',
            'orthopedics',
            'oncology',
            'psychiatry',
            'general_medicine',
            'emergency_medicine',
        ];

        for ($i = 1; $i <= $count; $i++) {
            $firstName = fake()->firstName();
            $lastName = fake()->lastName();
            
            User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower('dr.' . $firstName . '.' . $lastName . $i . '@doctor.com'),
                'phone' => $this->generateGhanaianPhone(),
                'role' => 'doctor',
                'gender' => fake()->randomElement(['male', 'female']),
                'date_of_birth' => fake()->date('Y-m-d', '-40 years'),
                'ghana_card_number' => $this->generateGhanaCardNumber(),
                'password' => Hash::make('password123'),
                'verification_status' => 'verified',
                'is_verified' => true,
                'is_active' => true,
                'verified_at' => now()->subDays(rand(1, 30)),
                'verified_by' => 1,
                'registered_ip' => fake()->ipv4(),
                'license_number' => $this->generateDoctorLicenseNumber(),
                'specialization' => fake()->randomElement($doctorSpecializations),
                'years_experience' => rand(5, 25),
                'last_login_at' => fake()->boolean(90) ? now()->subDays(rand(0, 2)) : null,
                'created_at' => now()->subDays(rand(60, 365)),
                'updated_at' => now()->subDays(rand(0, 10)),
            ]);
        }

        $this->command->info("✓ {$count} doctors created successfully!");
    }

    /**
     * Generate a realistic Ghanaian phone number
     */
    private function generateGhanaianPhone(): string
    {
        $prefixes = ['024', '054', '055', '020', '050', '026', '027', '056', '057'];
        $prefix = fake()->randomElement($prefixes);
        $number = fake()->numerify('#######');
        
        return $prefix . $number;
    }

    /**
     * Generate a Ghana Card Number
     */
    private function generateGhanaCardNumber(): string
    {
        return 'GHA-' . fake()->numerify('#########') . '-' . fake()->randomDigit();
    }

    /**
     * Generate a Nurse License Number
     */
    private function generateNurseLicenseNumber(): string
    {
        return 'NUR-' . fake()->numerify('######');
    }

    /**
     * Generate a Doctor License Number
     */
    private function generateDoctorLicenseNumber(): string
    {
        return 'MDC-' . fake()->numerify('######');
    }
}