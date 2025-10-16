<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       
        // Create Super Admin
        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@judyhomecare.com',
            'password' => Hash::make('AdminJudy2024!'),
            'phone' => '+233501234567',
            'role' => 'superadmin',
            'date_of_birth' => '1980-01-01',
            'gender' => 'other',
            'ghana_card_number' => 'GHA-000000000-0',
            'is_active' => true,
            'is_verified' => true,
            'verification_status' => 'verified',
            'email_verified_at' => now(),
            'verified_at' => now()
        ]);

        // Create Sample Admin
        User::create([
            'first_name' => 'John',
            'last_name' => 'Manager',
            'email' => 'theophilusboateng7@gmail.com',
            'password' => Hash::make('VODAfone020'),
            'phone' => '+233507654321',
            'role' => 'admin',
            'date_of_birth' => '1985-05-15',
            'gender' => 'male',
            'ghana_card_number' => 'GHA-111111111-1',
            'is_active' => true,
            'is_verified' => true,
            'verification_status' => 'verified',
            'email_verified_at' => now(),
            'verified_at' => now()
        ]);

        // Create Sample Doctor
        User::create([
            'first_name' => 'Dr. Sarah',
            'last_name' => 'Wilson',
            'email' => 'doctor@judyhomecare.com',
            'password' => Hash::make('Doctor123!'),
            'phone' => '+233509876543',
            'role' => 'doctor',
            'date_of_birth' => '1978-03-20',
            'gender' => 'female',
            'ghana_card_number' => 'GHA-222222222-2',
            'license_number' => 'MD-12345-GH',
            'specialization' => 'internal_medicine',
            'years_experience' => 15,
            'is_active' => true,
            'is_verified' => true,
            'verification_status' => 'verified',
            'email_verified_at' => now(),
            'verified_at' => now()
        ]);

        // Create Sample Nurse
        User::create([
            'first_name' => 'Mary',
            'last_name' => 'Johnson',
            'email' => 'nurse@judyhomecare.com',
            'password' => Hash::make('Nurse123!'),
            'phone' => '+233502468135',
            'role' => 'nurse',
            'date_of_birth' => '1990-07-10',
            'gender' => 'female',
            'ghana_card_number' => 'GHA-333333333-3',
            'license_number' => 'RN-67890-GH',
            'specialization' => 'general_care',
            'years_experience' => 8,
            'is_active' => true,
            'is_verified' => true,
            'verification_status' => 'verified',
            'email_verified_at' => now(),
            'verified_at' => now()
        ]);

        // Create Sample Patient
        User::create([
            'first_name' => 'Robert',
            'last_name' => 'Brown',
            'email' => 'patient@judyhomecare.com',
            'password' => Hash::make('Patient123!'),
            'phone' => '+233503692581',
            'role' => 'patient',
            'date_of_birth' => '1965-12-05',
            'gender' => 'male',
            'ghana_card_number' => 'GHA-444444444-4',
            'emergency_contact_name' => 'Jane Brown',
            'emergency_contact_phone' => '+233504567890',
            'medical_conditions' => ['diabetes', 'hypertension'],
            'allergies' => ['penicillin'],
            'current_medications' => ['metformin', 'lisinopril'],
            'is_active' => true,
            'is_verified' => true,
            'verification_status' => 'verified',
            'email_verified_at' => now(),
            'verified_at' => now()
        ]);

         $this->call([
            PermissionSeeder::class,
            UserSeeder::class,
            ScheduleSeeder::class,
            ProgressNoteSeeder::class,
            MedicalAssessmentSeeder::class,
        ]);
    }
}