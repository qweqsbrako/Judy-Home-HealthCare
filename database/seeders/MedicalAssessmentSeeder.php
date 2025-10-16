<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MedicalAssessment;
use App\Models\User;
use Carbon\Carbon;

class MedicalAssessmentSeeder extends Seeder
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
            $this->command->warn('Please ensure you have patients and nurses seeded before running this seeder.');
            return;
        }

        $this->command->info('Seeding medical assessments...');

        // Sample data arrays
        $occupations = [
            'Teacher', 'Engineer', 'Doctor', 'Nurse', 'Accountant', 'Trader', 
            'Farmer', 'Driver', 'Business Owner', 'Civil Servant', 'Retired', 
            'Student', 'Artisan', 'Lawyer', 'Banker'
        ];

        $religions = [
            'Christian', 'Muslim (Islam)', 'Traditional African Religion', 
            'Hindu', 'Buddhist', 'Other Religion'
        ];

        $presentingConditions = [
            'Post-operative care following hip replacement surgery',
            'Chronic diabetes management with wound care',
            'Stroke recovery and rehabilitation',
            'Congestive heart failure management',
            'Post-surgical wound care and monitoring',
            'Advanced stage cancer palliative care',
            'Chronic obstructive pulmonary disease (COPD) management',
            'Alzheimer\'s disease care and monitoring',
            'Parkinson\'s disease management',
            'Post-hospitalization recovery from pneumonia',
            'Pressure ulcer treatment and prevention',
            'Multiple sclerosis symptom management',
            'Post-chemotherapy care and support',
            'Chronic kidney disease monitoring',
            'Arthritis pain management and mobility support',
            'Post-COVID-19 recovery and rehabilitation',
            'Dementia care and behavioral management',
            'Spinal cord injury rehabilitation',
            'Terminal illness end-of-life care',
            'Chronic pain management'
        ];

        $pastMedicalHistory = [
            'Hypertension for 10 years, controlled with medication. Previous myocardial infarction 5 years ago.',
            'Type 2 Diabetes Mellitus for 15 years. History of diabetic neuropathy.',
            'Previous stroke 2 years ago with residual left-sided weakness. Hypertension.',
            'Chronic asthma since childhood. No recent hospitalizations.',
            'History of breast cancer, underwent mastectomy 3 years ago. Currently in remission.',
            'Coronary artery disease with previous bypass surgery 8 years ago.',
            'Osteoarthritis affecting both knees. Previous right knee replacement.',
            'Chronic renal failure on dialysis three times weekly.',
            'History of deep vein thrombosis. On anticoagulation therapy.',
            'Previous cholecystectomy and appendectomy. No current complications.',
            'COPD diagnosed 5 years ago. Former smoker.',
            'No significant past medical history. Generally healthy.',
            'History of depression and anxiety. On antidepressant medication.',
            'Previous hip fracture following a fall. Now healed.',
            'History of tuberculosis, completed treatment 10 years ago.'
        ];

        $allergies = [
            'Penicillin - causes severe rash and difficulty breathing',
            'Sulfa drugs - causes hives',
            'No known drug allergies',
            'Peanuts, shellfish - causes anaphylaxis',
            'Latex - causes skin irritation',
            'Aspirin - causes stomach upset',
            'Codeine - causes severe nausea',
            'Iodine contrast dye - causes allergic reaction',
            'No known allergies',
            'Eggs, milk products - causes digestive issues'
        ];

        $currentMedications = [
            'Metformin 500mg twice daily, Lisinopril 10mg daily, Atorvastatin 20mg at bedtime',
            'Insulin Glargine 20 units at bedtime, Metoprolol 50mg twice daily',
            'Aspirin 81mg daily, Clopidogrel 75mg daily, Furosemide 40mg daily',
            'Levothyroxine 100mcg daily, Calcium with Vitamin D twice daily',
            'Warfarin 5mg daily, Digoxin 0.25mg daily',
            'Albuterol inhaler as needed, Advair 250/50 twice daily',
            'Tramadol 50mg three times daily, Gabapentin 300mg three times daily',
            'Omeprazole 20mg daily, Multivitamin daily',
            'Currently taking no medications',
            'Amlodipine 5mg daily, Losartan 50mg daily, Hydrochlorothiazide 25mg daily'
        ];

        $specialNeeds = [
            'Wheelchair bound, requires assistance with all activities of daily living',
            'Uses walker for ambulation, needs help with bathing',
            'Requires oxygen therapy 2L per minute via nasal cannula',
            'Diabetic diet required, blood glucose monitoring four times daily',
            'Low sodium diet for heart failure management',
            'Wound vac dressing changes twice weekly',
            'Feeding tube for nutrition - G-tube feedings every 4 hours',
            'Foley catheter in place, requires catheter care',
            'No special needs at this time',
            'Uses cane for walking, requires assistance with stairs',
            'Hearing impaired - uses hearing aids',
            'Visually impaired - requires large print materials',
            'Requires pressure-relieving mattress and frequent repositioning',
            'Colostomy care required, bag changes as needed'
        ];

        $nursingImpressions = [
            'Patient appears comfortable at rest but reports moderate pain with movement. Vital signs are within acceptable limits. Wound shows signs of healing with no signs of infection. Patient demonstrates good understanding of medication regimen. Family members are supportive and engaged in care. Recommend continued monitoring of vital signs and pain management. Will reassess wound care needs at next visit.',
            
            'Patient is alert and oriented but appears fatigued. Reports difficulty sleeping due to pain. Blood pressure is slightly elevated today. Patient expresses concerns about managing care at home. Recommend discussion with physician regarding pain management optimization. Family education provided regarding medication administration and signs of complications to watch for.',
            
            'Patient demonstrates significant improvement in mobility compared to last assessment. Vital signs stable and within normal limits. Patient and family demonstrate good understanding of rehabilitation exercises. Continue current care plan with focus on increasing independence in activities of daily living. Patient motivated and engaged in recovery process.',
            
            'Patient reports increased shortness of breath with minimal exertion. Oxygen saturation adequate on current oxygen flow rate. Bilateral lower extremity edema noted. Weight increased by 3 kg since last visit. Recommend follow-up with physician regarding possible fluid overload. Patient needs reinforcement of low sodium diet instructions.',
            
            'Wound assessment reveals good granulation tissue with no signs of infection. Patient tolerating dressing changes well. Blood glucose levels have been variable, ranging from 120-280 mg/dL. Recommend diabetes education reinforcement and possible medication adjustment. Patient demonstrates compliance with treatment regimen.',
            
            'Patient appears withdrawn and reports feeling depressed. Vital signs stable. Family reports patient is sleeping more during the day and less engaged in activities. Recommend evaluation for depression and possible medication adjustment. Social support appears adequate but patient may benefit from counseling services.',
            
            'New patient assessment completed. Patient demonstrates good overall health status despite chronic conditions. Home environment is clean and safe with good family support. All necessary medical equipment is in place and functioning properly. Patient and family educated on care plan and emergency procedures. Follow-up visit scheduled for one week.',
            
            'Patient experiencing increased confusion today compared to previous visits. Family reports this is a change from baseline. Vital signs show low-grade fever of 37.8°C. Possible urinary tract infection suspected. Recommend physician evaluation and possible urinalysis. Family educated on signs of delirium and when to seek emergency care.',
            
            'Patient recovering well from recent hospitalization. Ambulating with walker independently. Appetite improved and patient is maintaining adequate hydration. Medication reconciliation completed with patient and family. No current concerns identified. Patient demonstrates good understanding of when to contact healthcare provider.',
            
            'Pain management appears inadequate with current regimen. Patient reports pain level 8/10 most of the day. Interfering with sleep and activities. Recommend pain management consultation and possible adjustment of analgesics. Patient appears anxious about pain and need for reassurance and support.'
        ];

        $relationships = [
            'Spouse', 'Son', 'Daughter', 'Brother', 'Sister', 
            'Mother', 'Father', 'Niece', 'Nephew', 'Friend', 
            'Cousin', 'Grandchild', 'Neighbor', 'Caregiver'
        ];

        // Create 50 medical assessments with realistic data
        $assessmentCount = 50;

        for ($i = 0; $i < $assessmentCount; $i++) {
            $patient = $patients->random();
            $nurse = $nurses->random();
            
            // Randomly determine if patient has wounds (30% chance)
            $hasWounds = rand(1, 100) <= 30;
            
            // Generate random vital signs
            $temperature = $this->randomFloat(35.5, 39.0, 1);
            $pulse = rand(50, 120);
            $respiratoryRate = rand(12, 28);
            $systolic = rand(90, 180);
            $diastolic = rand(60, 110);
            $bloodPressure = $systolic . '/' . $diastolic;
            $spo2 = rand(88, 100);
            $weight = $this->randomFloat(45.0, 120.0, 1);
            
            // Determine conditions based on vital signs for realism
            $generalCondition = 'stable';
            if ($temperature > 38.0 || $pulse > 100 || $spo2 < 92 || $systolic > 160) {
                $generalCondition = rand(1, 100) <= 60 ? 'unstable' : 'stable';
            }
            
            $hydrationStatus = rand(1, 100) <= 20 ? 'dehydrated' : 'adequate';
            $nutritionStatus = rand(1, 100) <= 25 ? 'malnourished' : 'adequate';
            $mobilityStatus = ['independent', 'assisted', 'bedridden'][rand(0, 2)];
            $painLevel = rand(0, 10);
            
            // Create the assessment
            MedicalAssessment::create([
                'patient_id' => $patient->id,
                'nurse_id' => $nurse->id,
                'physical_address' => $this->generateGhanaAddress(),
                'occupation' => $occupations[array_rand($occupations)],
                'religion' => $religions[array_rand($religions)],
                
                // Emergency contacts
                'emergency_contact_1_name' => $this->generateGhanaName(),
                'emergency_contact_1_relationship' => $relationships[array_rand($relationships)],
                'emergency_contact_1_phone' => $this->generateGhanaPhone(),
                'emergency_contact_2_name' => rand(1, 100) <= 70 ? $this->generateGhanaName() : null,
                'emergency_contact_2_relationship' => rand(1, 100) <= 70 ? $relationships[array_rand($relationships)] : null,
                'emergency_contact_2_phone' => rand(1, 100) <= 70 ? $this->generateGhanaPhone() : null,
                
                // Medical history
                'presenting_condition' => $presentingConditions[array_rand($presentingConditions)],
                'past_medical_history' => rand(1, 100) <= 80 ? $pastMedicalHistory[array_rand($pastMedicalHistory)] : null,
                'allergies' => $allergies[array_rand($allergies)],
                'current_medications' => rand(1, 100) <= 85 ? $currentMedications[array_rand($currentMedications)] : 'None',
                'special_needs' => rand(1, 100) <= 60 ? $specialNeeds[array_rand($specialNeeds)] : null,
                
                // Assessment findings
                'general_condition' => $generalCondition,
                'hydration_status' => $hydrationStatus,
                'nutrition_status' => $nutritionStatus,
                'mobility_status' => $mobilityStatus,
                'has_wounds' => $hasWounds,
                'wound_description' => $hasWounds ? $this->generateWoundDescription() : null,
                'pain_level' => $painLevel,
                
                // Vital signs
                'initial_vitals' => [
                    'temperature' => $temperature,
                    'pulse' => $pulse,
                    'respiratory_rate' => $respiratoryRate,
                    'blood_pressure' => $bloodPressure,
                    'spo2' => $spo2,
                    'weight' => $weight
                ],
                
                // Nursing impression
                'initial_nursing_impression' => $nursingImpressions[array_rand($nursingImpressions)],
                
                // Status
                'assessment_status' => ['completed', 'reviewed'][rand(0, 1)],
                'completed_at' => Carbon::now()->subDays(rand(0, 60)),
                'created_at' => Carbon::now()->subDays(rand(0, 60)),
                'updated_at' => Carbon::now()->subDays(rand(0, 30))
            ]);
        }

        $this->command->info("Successfully created {$assessmentCount} medical assessments!");
    }

    /**
     * Generate a random Ghana address
     */
    private function generateGhanaAddress(): string
    {
        $cities = [
            'Accra', 'Kumasi', 'Tamale', 'Takoradi', 'Cape Coast', 
            'Tema', 'Obuasi', 'Koforidua', 'Sunyani', 'Ho'
        ];
        
        $areas = [
            'East Legon', 'Cantonments', 'Labone', 'Dzorwulu', 'Airport Residential',
            'Ahodwo', 'Asokwa', 'Bantama', 'Nhyiaeso', 'Roman Hill',
            'Sakaman', 'Kwashieman', 'Dansoman', 'Teshie', 'Nungua',
            'Adenta', 'Madina', 'Spintex', 'Tema Community', 'Kanda'
        ];
        
        $streetTypes = ['Street', 'Road', 'Avenue', 'Close', 'Crescent'];
        
        $city = $cities[array_rand($cities)];
        $area = $areas[array_rand($areas)];
        $streetType = $streetTypes[array_rand($streetTypes)];
        $houseNumber = rand(1, 999);
        $streetName = chr(rand(65, 90)) . chr(rand(65, 90)); // Random 2 letters
        
        return "House No. {$houseNumber}, {$streetName} {$streetType}, {$area}, {$city}, Ghana";
    }

    /**
     * Generate a random Ghana name
     */
    private function generateGhanaName(): string
    {
        $firstNames = [
            'Kwame', 'Kofi', 'Yaw', 'Kwabena', 'Kwaku', 'Kojo', 'Kwasi',
            'Akosua', 'Abena', 'Afia', 'Afua', 'Ama', 'Akua', 'Esi',
            'Emmanuel', 'Joseph', 'Michael', 'Daniel', 'Samuel', 'David',
            'Mary', 'Grace', 'Comfort', 'Mercy', 'Joyce', 'Patience',
            'Nana', 'Osei', 'Mensah', 'Appiah'
        ];
        
        $lastNames = [
            'Mensah', 'Owusu', 'Boateng', 'Ansah', 'Osei', 'Asante',
            'Adjei', 'Amoah', 'Bonsu', 'Gyasi', 'Ofori', 'Opoku',
            'Darko', 'Agyei', 'Frimpong', 'Yeboah', 'Acheampong',
            'Ntim', 'Addo', 'Appiah', 'Kusi', 'Sarpong'
        ];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    /**
     * Generate a random Ghana phone number
     */
    private function generateGhanaPhone(): string
    {
        $prefixes = ['024', '054', '055', '020', '050', '027', '057', '026', '056', '059'];
        $prefix = $prefixes[array_rand($prefixes)];
        $number = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        
        return $prefix . $number;
    }

    /**
     * Generate wound description
     */
    private function generateWoundDescription(): string
    {
        $locations = [
            'left heel', 'right heel', 'sacrum', 'left lateral malleolus',
            'right lower leg', 'left foot', 'surgical site on abdomen',
            'right shoulder blade', 'left buttock'
        ];
        
        $sizes = [
            '2cm x 3cm', '3cm x 4cm', '4cm x 5cm', '1.5cm x 2cm',
            '5cm x 6cm', '2.5cm x 3.5cm'
        ];
        
        $stages = [
            'Stage II pressure injury',
            'Stage III pressure injury',
            'Diabetic foot ulcer',
            'Surgical wound dehiscence',
            'Venous stasis ulcer',
            'Post-surgical incision'
        ];
        
        $conditions = [
            'Clean, granulating well with no signs of infection',
            'Some slough present, moderate drainage',
            'Showing signs of healing, minimal drainage',
            'Redness and warmth around edges, possible infection',
            'Dry with eschar formation',
            'Pink and healing well, no drainage'
        ];
        
        $location = $locations[array_rand($locations)];
        $size = $sizes[array_rand($sizes)];
        $stage = $stages[array_rand($stages)];
        $condition = $conditions[array_rand($conditions)];
        
        return "{$stage} located on {$location}, measuring approximately {$size}. {$condition}.";
    }

    /**
     * Generate random float
     */
    private function randomFloat($min, $max, $decimals): float
    {
        $scale = pow(10, $decimals);
        return mt_rand($min * $scale, $max * $scale) / $scale;
    }
}