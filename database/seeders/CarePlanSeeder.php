<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarePlan;
use App\Models\User;
use Carbon\Carbon;

class CarePlanSeeder extends Seeder
{
    public function run(): void
    {
        $patients = User::where('role', 'patient')->where('is_verified', true)->get();
        $doctors = User::where('role', 'doctor')->where('is_verified', true)->get();
        $nurses = User::where('role', 'nurse')->where('is_verified', true)->get();
        $admins = User::whereIn('role', ['admin', 'superadmin'])->where('is_verified', true)->get();

        if ($patients->isEmpty() || $doctors->isEmpty()) {
            $this->command->error('Please seed users first before running care plan seeder.');
            return;
        }

        if ($nurses->isEmpty()) {
            $this->command->error('Please seed nurses first. 40 care plans require nurse assignments.');
            return;
        }

        $careTypes = ['general_care', 'elderly_care', 'pediatric_care', 'chronic_disease_management', 'palliative_care', 'rehabilitation_care'];
        $priorities = ['low', 'medium', 'high', 'critical'];
        $frequencies = ['once_daily', 'twice_daily', 'three_times_daily', 'every_12_hours', 'every_8_hours', 'every_6_hours', 'every_4_hours', 'weekly', 'twice_weekly', 'as_needed', 'custom'];

        // Define status distribution: 5 draft, 5 pending, 40 with nurses
        $statusDistribution = [
            // 5 draft - no nurses
            'draft' => 5,
            // 5 pending_approval - no nurses
            'pending_approval' => 5,
            // 40 with nurses assigned
            'active' => 30,
            'completed' => 10,
        ];

        $carePlanTemplates = $this->getCarePlanTemplates();

        $this->command->info('Creating 50 care plans with specific distribution...');
        $this->command->info('- 5 Draft (no nurses)');
        $this->command->info('- 5 Pending Approval (no nurses)');
        $this->command->info('- 40 with Nurses (Active: 30, Completed: 10)');

        $carePlansCreated = 0;

        foreach ($statusDistribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $carePlansCreated++;
                
                $patient = $patients->random();
                $doctor = $doctors->random();
                $careType = $careTypes[array_rand($careTypes)];
                $priority = $priorities[array_rand($priorities)];
                $frequency = $frequencies[array_rand($frequencies)];
                $template = $carePlanTemplates[$careType];

                $startDate = $this->getStartDate($status);
                $endDate = $this->getEndDate($startDate, $careType);
                $completionPercentage = $this->getCompletionPercentage($status, $startDate);

                // Assign nurses only if NOT draft or pending_approval
                $shouldAssignNurse = !in_array($status, ['draft', 'pending_approval']);
                $primaryNurse = $shouldAssignNurse ? $nurses->random() : null;
                
                // 40% chance of having a secondary nurse when primary is assigned
                $secondaryNurse = ($shouldAssignNurse && rand(0, 100) < 40 && $nurses->count() > 1) 
                    ? $nurses->where('id', '!=', $primaryNurse->id)->random() 
                    : null;

                // Set approval details for approved statuses (active, completed with nurses)
                $isApproved = $shouldAssignNurse; // All plans with nurses are considered approved
                $approvedBy = $isApproved ? ($admins->isNotEmpty() ? $admins->random() : $doctor) : null;
                $approvedAt = $isApproved ? Carbon::parse($startDate)->subDays(rand(1, 3)) : null;

                // Created by is usually the doctor or an admin
                $createdBy = rand(1, 100) <= 80 ? $doctor : ($admins->isNotEmpty() ? $admins->random() : $doctor);

                $carePlan = [
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'created_by' => $createdBy->id,
                    'approved_by' => $approvedBy?->id,
                    'primary_nurse_id' => $primaryNurse?->id,
                    'secondary_nurse_id' => $secondaryNurse?->id,
                    'title' => $template['title'] . ' - ' . $patient->first_name . ' ' . $patient->last_name,
                    'description' => $template['description'],
                    'care_type' => $careType,
                    'status' => $status,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'frequency' => $frequency,
                    'custom_frequency_details' => $frequency === 'custom' ? $this->getCustomFrequency() : null,
                    'care_tasks' => $template['care_tasks'],
                    'priority' => $priority,
                    'approved_at' => $approvedAt,
                    'completion_percentage' => $completionPercentage,
                    'assignment_notes' => $shouldAssignNurse ? $this->getAssignmentNotes($careType) : null,
                    'created_at' => Carbon::parse($startDate)->subDays(rand(7, 20)),
                    'updated_at' => Carbon::now(),
                ];

                CarePlan::create($carePlan);

                if ($carePlansCreated % 10 === 0) {
                    $this->command->info("Created {$carePlansCreated} care plans...");
                }
            }
        }

        $this->command->info('Successfully created 50 care plans!');
        
        // Display summary
        $summary = CarePlan::selectRaw('status, COUNT(*) as count')->groupBy('status')->get();
        $nursesAssigned = CarePlan::whereNotNull('primary_nurse_id')->count();
        
        $this->command->info("\n=== Summary ===");
        foreach ($summary as $item) {
            $this->command->info("Status '{$item->status}': {$item->count} care plans");
        }
        $this->command->info("Care plans with nurses assigned: {$nursesAssigned}/50");
    }

    private function getCarePlanTemplates(): array
    {
        return [
            'general_care' => [
                'title' => 'General Home Care Plan',
                'description' => 'Comprehensive general care including daily living assistance, medication management, and basic health monitoring. This plan focuses on maintaining patient independence while providing necessary support for activities of daily living.',
                'care_tasks' => [
                    'Assist with personal hygiene and grooming',
                    'Help with meal preparation and feeding as needed',
                    'Administer prescribed medications on schedule',
                    'Monitor and record vital signs daily',
                    'Assist with mobility and safe transfers',
                    'Provide companionship and emotional support',
                    'Maintain clean and safe living environment',
                    'Document daily activities and observations',
                ],
            ],
            'elderly_care' => [
                'title' => 'Elderly Care Plan',
                'description' => 'Specialized care for elderly patients focusing on dignity, independence, and quality of life. Includes comprehensive support for age-related needs, fall prevention, cognitive engagement, and social interaction.',
                'care_tasks' => [
                    'Assist with bathing and personal care with dignity',
                    'Help with dressing and grooming routines',
                    'Prepare nutritious meals appropriate for dietary needs',
                    'Administer medications and track medication schedule',
                    'Provide mobility support and fall prevention measures',
                    'Engage in cognitive stimulation activities',
                    'Monitor for signs of confusion or changes in behavior',
                    'Encourage social interaction and meaningful activities',
                    'Regular position changes to prevent pressure sores',
                    'Document any changes in condition immediately',
                ],
            ],
            'pediatric_care' => [
                'title' => 'Pediatric Home Care Plan',
                'description' => 'Specialized care for children including developmental support, medication management, family education, and age-appropriate activities. Focus on child safety, comfort, and supporting normal development.',
                'care_tasks' => [
                    'Administer age-appropriate medications safely',
                    'Monitor growth, development, and vital signs',
                    'Assist with feeding and ensure proper nutrition',
                    'Provide age-appropriate play and learning activities',
                    'Monitor for signs of illness, pain, or discomfort',
                    'Educate parents on care routines and procedures',
                    'Maintain clean, safe, and child-friendly environment',
                    'Support developmental milestones and activities',
                    'Provide comfort measures and emotional support',
                    'Document feeding, elimination, and behavior patterns',
                ],
            ],
            'chronic_disease_management' => [
                'title' => 'Chronic Disease Management Plan',
                'description' => 'Comprehensive management of chronic conditions with focus on medication compliance, symptom monitoring, lifestyle modifications, and preventing complications. Coordinated care approach with healthcare team.',
                'care_tasks' => [
                    'Administer complex medication regimens accurately',
                    'Monitor disease-specific symptoms and vital signs',
                    'Record detailed health metrics and trends',
                    'Assist with specialized medical equipment',
                    'Support prescribed dietary modifications',
                    'Coordinate with healthcare providers and specialists',
                    'Provide patient education on disease self-management',
                    'Monitor for medication side effects or complications',
                    'Encourage adherence to treatment plans',
                    'Document all health changes and communicate promptly',
                ],
            ],
            'palliative_care' => [
                'title' => 'Palliative Care Plan',
                'description' => 'Comfort-focused care emphasizing pain and symptom management, quality of life, and dignity for patients with serious illness. Holistic approach including physical, emotional, and spiritual support for patient and family.',
                'care_tasks' => [
                    'Provide comprehensive pain assessment and management',
                    'Manage symptoms for maximum comfort (nausea, breathlessness, etc.)',
                    'Assist with all personal care needs gently and respectfully',
                    'Provide emotional and spiritual support',
                    'Support family members and answer questions',
                    'Coordinate with hospice or palliative care team',
                    'Maintain patient dignity and respect wishes',
                    'Create peaceful, comfortable environment',
                    'Offer comfort measures (positioning, massage, music)',
                    'Document comfort levels and symptom management',
                ],
            ],
            'rehabilitation_care' => [
                'title' => 'Rehabilitation Care Plan',
                'description' => 'Goal-oriented care focused on recovering function, improving strength, and regaining independence after illness or injury. Progressive approach with therapeutic exercises, mobility training, and continuous progress monitoring.',
                'care_tasks' => [
                    'Assist with prescribed rehabilitation exercises',
                    'Support physical therapy activities and goals',
                    'Help with occupational therapy tasks',
                    'Monitor progress toward specific recovery goals',
                    'Encourage increasing independence in daily activities',
                    'Prevent complications during recovery period',
                    'Provide motivation and positive reinforcement',
                    'Assist with adaptive equipment and techniques',
                    'Document functional improvements and setbacks',
                    'Coordinate with therapy team on progress',
                ],
            ],
        ];
    }

    private function getStartDate(string $status): string
    {
        switch ($status) {
            case 'draft':
            case 'pending_approval':
                return Carbon::now()->addDays(rand(1, 14))->format('Y-m-d');
            case 'active':
                return Carbon::now()->subDays(rand(1, 60))->format('Y-m-d');
            case 'completed':
                return Carbon::now()->subDays(rand(90, 180))->format('Y-m-d');
            default:
                return Carbon::now()->format('Y-m-d');
        }
    }

    private function getEndDate(string $startDate, string $careType): ?string
    {
        $start = Carbon::parse($startDate);
        
        // Define typical duration ranges for each care type (in days)
        $durationMap = [
            'general_care' => [30, 90],
            'elderly_care' => [60, 365],
            'pediatric_care' => [14, 60],
            'chronic_disease_management' => [90, 365],
            'palliative_care' => [30, 180],
            'rehabilitation_care' => [21, 90],
        ];

        $duration = $durationMap[$careType] ?? [30, 90];
        
        // 20% chance of no end date (ongoing care)
        if (rand(1, 100) <= 20) {
            return null;
        }
        
        return $start->addDays(rand($duration[0], $duration[1]))->format('Y-m-d');
    }

    private function getCompletionPercentage(string $status, string $startDate): int
    {
        switch ($status) {
            case 'draft':
            case 'pending_approval':
                return 0;
            case 'active':
                $start = Carbon::parse($startDate);
                $daysSinceStart = max(1, $start->diffInDays(Carbon::now()));
                return min(95, max(10, $daysSinceStart * 2));
            case 'completed':
                return 100;
            default:
                return 0;
        }
    }

    private function getAssignmentNotes(string $careType): string
    {
        $notes = [
            'general_care' => 'Patient requires gentle, patient-centered care. Please maintain consistent schedule and document any changes in condition or behavior.',
            'elderly_care' => 'Patient may have cognitive changes and requires patience and reassurance. Fall risk precautions must be maintained at all times. Encourage independence while ensuring safety.',
            'pediatric_care' => 'Child requires age-appropriate communication and activities. Keep parents informed of all care activities, changes, and concerns. Maintain safe, child-friendly environment.',
            'chronic_disease_management' => 'Strict medication adherence is critical for disease management. Monitor symptoms closely and report any changes immediately to the healthcare team. Patient education ongoing.',
            'palliative_care' => 'Focus on comfort, dignity, and quality of life. Family involvement is important. Follow pain management protocol carefully. Respect patient and family wishes at all times.',
            'rehabilitation_care' => 'Patient is motivated but may experience frustration during recovery. Provide encouragement and positive reinforcement. Follow therapy protocols precisely and document progress.',
        ];

        return $notes[$careType] ?? 'Standard care protocols apply. Please document all observations and communicate any concerns promptly.';
    }

    private function getCustomFrequency(): string
    {
        $frequencies = [
            'Every other day at 9:00 AM and 3:00 PM',
            'Monday, Wednesday, and Friday mornings at 8:00 AM',
            'Weekdays only: twice daily (morning and evening)',
            'Every 4 hours during waking hours (7 AM - 11 PM)',
            'Three times weekly on alternate days',
            'Tuesday and Thursday evenings, plus Saturday morning',
            'Daily except Sundays: once in the morning',
            'Flexible schedule based on patient need, minimum twice weekly',
            'Monday through Friday: morning and evening visits',
            'Weekends only: comprehensive care sessions',
        ];

        return $frequencies[array_rand($frequencies)];
    }
}