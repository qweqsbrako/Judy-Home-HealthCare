<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TransportRequest;
use App\Models\User;
use App\Models\Driver;
use Carbon\Carbon;

class TransportRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get patients, admin/nurses, and drivers
        $patients = User::where('role', 'patient')->get();
        $requesters = User::whereIn('role', ['admin', 'nurse', 'super_admin'])->get();
        $drivers = Driver::where('is_active', true)->where('is_suspended', false)->get();

        if ($patients->isEmpty() || $requesters->isEmpty()) {
            $this->command->warn('Please seed users first. Need patients and admin/nurse users.');
            return;
        }

        $statuses = ['requested', 'assigned', 'in_progress', 'completed', 'cancelled'];
        $transportTypes = ['ambulance', 'regular'];
        $priorities = ['emergency', 'urgent', 'routine'];

        // Pickup locations in Accra, Ghana
        $pickupLocations = [
            [
                'name' => 'Patient\'s Home - East Legon',
                'address' => 'House No. 12, American House Street, East Legon, Accra'
            ],
            [
                'name' => 'Patient\'s Home - Cantonments',
                'address' => 'Plot 45, Independence Avenue, Cantonments, Accra'
            ],
            [
                'name' => 'Patient\'s Home - Osu',
                'address' => 'Liberation Road, Osu RE, Accra'
            ],
            [
                'name' => 'Patient\'s Home - Labone',
                'address' => '23 Labone Crescent, Labone, Accra'
            ],
            [
                'name' => 'Patient\'s Home - Airport Residential',
                'address' => '67 Airport West, Airport Residential Area, Accra'
            ],
            [
                'name' => 'Patient\'s Home - Dzorwulu',
                'address' => '15 Dzorwulu Street, Dzorwulu, Accra'
            ],
            [
                'name' => 'Patient\'s Home - Adabraka',
                'address' => 'Farrar Avenue, Adabraka, Accra'
            ],
            [
                'name' => 'Patient\'s Home - Tema',
                'address' => 'Community 9, Tema, Greater Accra'
            ],
            [
                'name' => 'Ridge Hospital - Ward 3A',
                'address' => 'Castle Road, Ridge, Accra'
            ],
            [
                'name' => '37 Military Hospital - Ward 2B',
                'address' => 'Liberation Avenue, 37 Military Hospital, Accra'
            ],
        ];

        // Destination locations - Hospitals in Accra
        $destinations = [
            [
                'name' => 'Korle-Bu Teaching Hospital',
                'address' => 'Guggisberg Avenue, Korle-Bu, Accra'
            ],
            [
                'name' => 'Ridge Hospital',
                'address' => 'Castle Road, Ridge, Accra'
            ],
            [
                'name' => '37 Military Hospital',
                'address' => 'Liberation Avenue, Airport Residential Area, Accra'
            ],
            [
                'name' => 'Greater Accra Regional Hospital',
                'address' => 'Ridge Hospital Road, Ridge, Accra'
            ],
            [
                'name' => 'Ga East Municipal Hospital',
                'address' => 'Abokobi Road, Dome, Accra'
            ],
            [
                'name' => 'Legon Hospital',
                'address' => 'University of Ghana, Legon, Accra'
            ],
            [
                'name' => 'Police Hospital',
                'address' => 'Cantonments Road, Cantonments, Accra'
            ],
            [
                'name' => 'Trust Hospital',
                'address' => 'Osu Badu Street, Osu, Accra'
            ],
            [
                'name' => 'Nyaho Medical Centre',
                'address' => 'Airport West, Airport Residential Area, Accra'
            ],
            [
                'name' => 'Tema General Hospital',
                'address' => 'Community 2, Tema, Greater Accra'
            ],
        ];

        // Reasons for transport
        $reasons = [
            'Scheduled cardiology appointment',
            'Emergency admission for chest pain',
            'Routine dialysis treatment',
            'Follow-up consultation with specialist',
            'Urgent laboratory tests required',
            'Scheduled surgery appointment',
            'Post-operative check-up',
            'Physiotherapy session',
            'Diagnostic imaging (MRI/CT Scan)',
            'Oncology treatment session',
            'Maternity check-up',
            'Emergency diabetes management',
            'Orthopedic consultation',
            'Respiratory therapy appointment',
            'Cardiac stress test',
            'Wound care and dressing',
            'Chemotherapy session',
            'Blood transfusion required',
            'Neurological assessment',
            'Geriatric care consultation'
        ];

        // Special requirements
        $specialRequirements = [
            'Wheelchair access required',
            'Oxygen support needed throughout transport',
            'Patient requires stretcher - cannot sit upright',
            'Medical escort required',
            'IV drip in progress - careful handling needed',
            'Patient has mobility issues - assistance needed',
            'Cardiac monitor required during transport',
            'Family member accompanying patient',
            'Fragile patient - smooth driving essential',
            'Diabetic patient - glucose monitoring needed',
            null, // Some requests may not have special requirements
            null,
            null,
        ];

        // Contact persons
        $contactPersons = [
            'Mrs. Adjoa Mensah - 0244123456',
            'Mr. Kwame Asante - 0201234567',
            'Dr. Yaw Osei - 0501234567',
            'Mrs. Akosua Frimpong - 0551234567',
            'Mr. Kofi Owusu - 0241234567',
            'Miss Abena Boateng - 0261234567',
            'Mr. Yaw Addo - 0202345678',
            'Mrs. Efua Darko - 0542345678',
            null, // Some may not have contact person
            null,
        ];

        // Feedback comments for completed transports
        $feedbackComments = [
            'Excellent service. Driver was very professional and careful.',
            'Transport was smooth. Patient arrived safely and comfortably.',
            'Driver was punctual and helpful. Vehicle was clean.',
            'Very satisfied with the service. Driver showed great care.',
            'Good service overall. Driver knew the routes well.',
            'Professional driver. Handled emergency situation well.',
            'Transport was comfortable. Would recommend this service.',
            'Driver was courteous and patient-focused.',
            'Smooth journey. Patient felt safe throughout.',
            'Excellent care during transport. Very satisfied.',
        ];

        $this->command->info('Creating 20 transport requests...');

        for ($i = 0; $i < 20; $i++) {
            $patient = $patients->random();
            $requester = $requesters->random();
            $pickup = $pickupLocations[array_rand($pickupLocations)];
            $destination = $destinations[array_rand($destinations)];
            $transportType = $transportTypes[array_rand($transportTypes)];
            $priority = $priorities[array_rand($priorities)];
            
            // Determine status and dates based on pattern
            $statusIndex = $i % 5;
            $status = $statuses[$statusIndex];
            
            // Calculate scheduled time based on status
            $scheduledTime = match($statusIndex) {
                0 => Carbon::now()->addDays(rand(1, 7))->addHours(rand(8, 17)), // Future - requested
                1 => Carbon::now()->addDays(rand(1, 3))->addHours(rand(8, 17)), // Soon - assigned
                2 => Carbon::now()->addMinutes(rand(-120, -30)), // Recently started - in_progress
                3 => Carbon::now()->subDays(rand(1, 30))->addHours(rand(8, 17)), // Past - completed
                4 => Carbon::now()->subDays(rand(1, 14))->addHours(rand(8, 17)), // Past - cancelled
            };

            // Calculate estimated cost
            $baseCost = $transportType === 'ambulance' ? 50 : 20;
            $priorityMultiplier = match($priority) {
                'emergency' => 2.0,
                'urgent' => 1.5,
                'routine' => 1.0
            };
            $distanceKm = rand(5, 50);
            $estimatedCost = ($baseCost + ($distanceKm * 2)) * $priorityMultiplier;

            // Base transport data
            $transportData = [
                'patient_id' => $patient->id,
                'requested_by_id' => $requester->id,
                'transport_type' => $transportType,
                'priority' => $priority,
                'scheduled_time' => $scheduledTime,
                'pickup_location' => $pickup['name'],
                'pickup_address' => $pickup['address'],
                'destination_location' => $destination['name'],
                'destination_address' => $destination['address'],
                'reason' => $reasons[array_rand($reasons)],
                'special_requirements' => $specialRequirements[array_rand($specialRequirements)],
                'contact_person' => $contactPersons[array_rand($contactPersons)],
                'status' => $status,
                'estimated_cost' => round($estimatedCost, 2),
                'distance_km' => $distanceKm,
                'created_at' => $scheduledTime->copy()->subDays(rand(1, 3)),
                'updated_at' => Carbon::now(),
            ];

            // Add driver and status-specific data
            if (in_array($status, ['assigned', 'in_progress', 'completed'])) {
                if ($drivers->isNotEmpty()) {
                    $driver = $drivers->random();
                    $transportData['driver_id'] = $driver->id;
                }
            }

            // Add pickup time for in_progress and completed
            if (in_array($status, ['in_progress', 'completed'])) {
                $transportData['actual_pickup_time'] = $scheduledTime->copy()->addMinutes(rand(-10, 10));
            }

            // Add completion data for completed status
            if ($status === 'completed') {
                $actualPickupTime = $scheduledTime->copy()->addMinutes(rand(-10, 10));
                $actualArrivalTime = $actualPickupTime->copy()->addMinutes(rand(20, 90));
                
                $transportData['actual_pickup_time'] = $actualPickupTime;
                $transportData['actual_arrival_time'] = $actualArrivalTime;
                $transportData['completed_at'] = $actualArrivalTime;
                $transportData['actual_cost'] = round($estimatedCost * rand(90, 110) / 100, 2); // Within 10% of estimate
                $transportData['rating'] = rand(3, 5); // Ratings between 3-5
                $transportData['feedback'] = $feedbackComments[array_rand($feedbackComments)];
            }

            // Add cancellation data for cancelled status
            if ($status === 'cancelled') {
                $cancellationReasons = [
                    'Patient condition improved - transport no longer needed',
                    'Appointment rescheduled by hospital',
                    'Patient admitted to nearby facility',
                    'Family arranged alternative transport',
                    'Weather conditions unsafe',
                    'Patient condition deteriorated - ambulance called',
                    'Duplicate request created by mistake',
                    'Driver unavailable - could not find replacement',
                ];
                
                $transportData['cancelled_at'] = $scheduledTime->copy()->subHours(rand(1, 24));
                $transportData['cancellation_reason'] = $cancellationReasons[array_rand($cancellationReasons)];
            }

            TransportRequest::create($transportData);
        }

        $this->command->info('Successfully created 20 transport requests!');
        $this->command->info('Distribution:');
        $this->command->info('- Requested: ' . TransportRequest::where('status', 'requested')->count());
        $this->command->info('- Assigned: ' . TransportRequest::where('status', 'assigned')->count());
        $this->command->info('- In Progress: ' . TransportRequest::where('status', 'in_progress')->count());
        $this->command->info('- Completed: ' . TransportRequest::where('status', 'completed')->count());
        $this->command->info('- Cancelled: ' . TransportRequest::where('status', 'cancelled')->count());
    }
}