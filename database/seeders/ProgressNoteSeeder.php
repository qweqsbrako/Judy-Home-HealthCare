<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProgressNote;
use App\Models\User;
use Carbon\Carbon;

class ProgressNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all patients and nurses
        $patients = User::where('role', 'patient')->get();
        $nurses = User::where('role', 'nurse')->get();

        if ($patients->isEmpty() || $nurses->isEmpty()) {
            $this->command->warn('No patients or nurses found. Please seed users first.');
            return;
        }

        $this->command->info('Creating progress notes...');

        $conditions = ['improved', 'stable', 'deteriorating'];
        $interventionTypes = [
            'medication_administered',
            'wound_care',
            'physiotherapy',
            'nutrition_support',
            'hygiene_care',
            'counseling_education',
            'other'
        ];

        // Create 50 progress notes with varied data
        for ($i = 0; $i < 50; $i++) {
            $patient = $patients->random();
            $nurse = $nurses->random();
            
            // Random date within last 60 days
            $visitDate = Carbon::now()->subDays(rand(0, 60));
            
            // Random time between 8 AM and 6 PM
            $visitTime = sprintf('%02d:%02d', rand(8, 18), rand(0, 59));

            // Generate random vitals
            $vitals = [
                'temperature' => round(35.5 + (rand(0, 40) / 10), 1), // 35.5 to 39.5
                'pulse' => rand(55, 120), // 55 to 120 bpm
                'respiration' => rand(12, 25), // 12 to 25 per min
                'blood_pressure' => rand(100, 140) . '/' . rand(60, 90), // Systolic/Diastolic
                'spo2' => rand(92, 100), // 92% to 100%
            ];

            // Randomly select 2-4 interventions
            $selectedInterventions = [];
            $numInterventions = rand(2, 4);
            $randomInterventions = array_rand(array_flip($interventionTypes), $numInterventions);
            
            if (!is_array($randomInterventions)) {
                $randomInterventions = [$randomInterventions];
            }

            foreach ($randomInterventions as $intervention) {
                $selectedInterventions[$intervention] = true;
                
                // Add details for each intervention
                $detailsKey = $intervention . '_details';
                $selectedInterventions[$detailsKey] = $this->getInterventionDetails($intervention);
            }

            // Random condition
            $condition = $conditions[array_rand($conditions)];

            // Pain level based on condition
            $painLevel = match($condition) {
                'improved' => rand(0, 3),
                'stable' => rand(2, 6),
                'deteriorating' => rand(5, 10),
            };

            // Create the progress note
            ProgressNote::create([
                'patient_id' => $patient->id,
                'nurse_id' => $nurse->id,
                'visit_date' => $visitDate->format('Y-m-d'),
                'visit_time' => $visitTime,
                'vitals' => $vitals,
                'interventions' => $selectedInterventions,
                'general_condition' => $condition,
                'pain_level' => $painLevel,
                'wound_status' => rand(0, 1) ? $this->getWoundStatus() : null,
                'other_observations' => $this->getObservations($condition),
                'education_provided' => rand(0, 1) ? $this->getEducation() : null,
                'family_concerns' => rand(0, 1) ? $this->getFamilyConcerns() : null,
                'next_steps' => $this->getNextSteps($condition),
                'signed_at' => $visitDate->addHours(rand(1, 3)),
                'signature_method' => 'digital',
                'created_at' => $visitDate,
                'updated_at' => $visitDate,
            ]);

            if (($i + 1) % 10 == 0) {
                $this->command->info("Created " . ($i + 1) . " progress notes...");
            }
        }

        $this->command->info('Progress notes seeded successfully!');
    }

    /**
     * Get intervention details based on type
     */
    private function getInterventionDetails(string $intervention): string
    {
        $details = [
            'medication_administered' => [
                'Administered prescribed medications as per care plan',
                'Given pain medication: Paracetamol 500mg, 2 tablets',
                'Insulin injection administered - 10 units subcutaneously',
                'Blood pressure medication: Amlodipine 5mg taken with water',
                'Antibiotic course continued - Amoxicillin 500mg',
            ],
            'wound_care' => [
                'Cleaned and dressed surgical wound on right leg',
                'Changed dressing on pressure ulcer - showing signs of healing',
                'Applied topical antibiotic ointment to minor abrasion',
                'Wound inspection completed - no signs of infection',
                'Removed old dressing, cleaned with saline, applied new sterile dressing',
            ],
            'physiotherapy' => [
                'Assisted with range of motion exercises for 30 minutes',
                'Walking exercise completed - patient walked 50 meters with walker',
                'Leg strengthening exercises performed as per physio plan',
                'Breathing exercises completed to improve lung capacity',
                'Mobility exercises: assisted transfers from bed to chair',
            ],
            'nutrition_support' => [
                'Assisted with meal preparation and feeding',
                'Ensured adequate fluid intake - 1.5 liters consumed',
                'High protein supplement provided as prescribed',
                'Monitored food intake - patient ate 75% of meals',
                'Nutritional counseling provided regarding diabetic diet',
            ],
            'hygiene_care' => [
                'Assisted with bed bath and personal hygiene',
                'Helped patient with shower and hair washing',
                'Oral care completed - teeth brushed and mouth rinsed',
                'Changed bed linens and patient gown',
                'Skin care routine completed - moisturizer applied',
            ],
            'counseling_education' => [
                'Provided education on medication management',
                'Discussed importance of regular exercise and diet',
                'Counseled on fall prevention strategies',
                'Educated family on signs of complications to watch for',
                'Reviewed wound care instructions with patient',
            ],
            'other' => [
                'Emotional support provided during difficult period',
                'Coordinated with family members regarding care schedule',
                'Accompanied patient to medical appointment',
                'Assisted with household chores and light cleaning',
                'Provided companionship and social interaction',
            ],
        ];

        return $details[$intervention][array_rand($details[$intervention])];
    }

    /**
     * Get wound status description
     */
    private function getWoundStatus(): string
    {
        $statuses = [
            'Surgical wound healing well, no signs of infection, sutures intact',
            'Pressure ulcer on sacrum - Stage 2, measuring 3cm x 2cm, showing improvement',
            'Minor abrasion on left elbow, cleaned and dressed, healing normally',
            'Post-operative wound dry and clean, no drainage or redness observed',
            'Leg ulcer showing signs of healing, reduced exudate, granulation tissue present',
            'Diabetic foot wound being monitored closely, no signs of infection currently',
        ];

        return $statuses[array_rand($statuses)];
    }

    /**
     * Get observations based on condition
     */
    private function getObservations(string $condition): string
    {
        $observations = [
            'improved' => [
                'Patient showing significant improvement in mobility and energy levels',
                'Pain levels decreased, patient more comfortable and resting well',
                'Appetite has improved, patient eating regular meals without difficulty',
                'Mental alertness and cognitive function noticeably better',
                'Patient expressing positive outlook and improved mood',
            ],
            'stable' => [
                'Patient condition remains stable with no significant changes',
                'Vital signs within normal limits, patient comfortable',
                'No new concerns reported, continuing with current care plan',
                'Patient managing activities of daily living with minimal assistance',
                'Sleep pattern regular, patient well-rested',
            ],
            'deteriorating' => [
                'Patient appears more fatigued than previous visit',
                'Increased confusion noted, patient less responsive to verbal cues',
                'Mobility has decreased, patient requiring more assistance',
                'Pain levels increasing despite medication adjustments',
                'Patient expressing increased anxiety and discomfort',
            ],
        ];

        return $observations[$condition][array_rand($observations[$condition])];
    }

    /**
     * Get education provided
     */
    private function getEducation(): string
    {
        $education = [
            'Reviewed medication schedule and importance of adherence',
            'Demonstrated proper wound care techniques to family members',
            'Provided information on nutrition and hydration requirements',
            'Educated on fall prevention measures in the home',
            'Discussed signs and symptoms that require immediate medical attention',
            'Explained exercises to be performed between nursing visits',
            'Reviewed diabetes management including blood sugar monitoring',
        ];

        return $education[array_rand($education)];
    }

    /**
     * Get family concerns
     */
    private function getFamilyConcerns(): string
    {
        $concerns = [
            'Family concerned about patient\'s decreased appetite',
            'Daughter worried about mother\'s mobility and risk of falls',
            'Spouse questions effectiveness of current pain management',
            'Family asking about progression of condition and prognosis',
            'Caregiver expressing feeling overwhelmed with care responsibilities',
            'Family requesting additional support services or respite care',
            'Concerns raised about patient\'s mental state and mood changes',
        ];

        return $concerns[array_rand($concerns)];
    }

    /**
     * Get next steps based on condition
     */
    private function getNextSteps(string $condition): string
    {
        $nextSteps = [
            'improved' => [
                'Continue current medication regimen and monitor progress',
                'Gradually increase activity level as tolerated by patient',
                'Schedule follow-up visit in 3 days to reassess condition',
                'Encourage family to maintain current level of support',
                'Consider reducing frequency of visits if improvement continues',
            ],
            'stable' => [
                'Maintain current care plan and continue monitoring',
                'Regular follow-up visits as scheduled, no changes needed',
                'Continue with prescribed medications and exercises',
                'Monitor for any changes in condition and report immediately',
                'Next visit scheduled for routine assessment and care',
            ],
            'deteriorating' => [
                'Consult with physician regarding medication adjustments',
                'Increase frequency of nursing visits for closer monitoring',
                'Consider referral to specialist for further evaluation',
                'Implement additional safety measures to prevent complications',
                'Schedule urgent follow-up within 24-48 hours',
                'Notify family of changes and discuss care plan adjustments',
            ],
        ];

        return $nextSteps[$condition][array_rand($nextSteps[$condition])];
    }
}