<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\DriverVehicleAssignment;
use App\Models\User;
use Carbon\Carbon;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting vehicle seeding...');
        
        $vehicles = [
            // Available Ambulances (10 vehicles)
            [
                'vehicle_type' => 'ambulance',
                'registration_number' => 'GR-1001-24',
                'vehicle_color' => 'White',
                'make' => 'Toyota',
                'model' => 'Hiace',
                'year' => 2024,
                'vin_number' => 'JTDKB20U197001001',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 12450.00,
                'insurance_policy' => 'AMB-2024-101',
                'insurance_expiry' => Carbon::now()->addMonths(10),
                'registration_expiry' => Carbon::now()->addYear(),
                'last_service_date' => Carbon::now()->subMonth(),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Advanced life support ambulance - Primary emergency response',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'ambulance',
                'registration_number' => 'GR-1002-24',
                'vehicle_color' => 'White',
                'make' => 'Mercedes-Benz',
                'model' => 'Sprinter',
                'year' => 2024,
                'vin_number' => 'WDB9063221N001002',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 8750.50,
                'insurance_policy' => 'AMB-2024-102',
                'insurance_expiry' => Carbon::now()->addMonths(11),
                'registration_expiry' => Carbon::now()->addYear(),
                'last_service_date' => Carbon::now()->subDays(20),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'ICU equipped ambulance with ventilator support',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'ambulance',
                'registration_number' => 'GR-1003-24',
                'vehicle_color' => 'White',
                'make' => 'Ford',
                'model' => 'Transit',
                'year' => 2024,
                'vin_number' => '1FTBW3XM5GKA01003',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 15680.25,
                'insurance_policy' => 'AMB-2024-103',
                'insurance_expiry' => Carbon::now()->addMonths(9),
                'registration_expiry' => Carbon::now()->addMonths(11),
                'last_service_date' => Carbon::now()->subDays(15),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Basic life support ambulance for routine transfers',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'ambulance',
                'registration_number' => 'GR-1004-24',
                'vehicle_color' => 'White',
                'make' => 'Toyota',
                'model' => 'Hiace',
                'year' => 2023,
                'vin_number' => 'JTDKB20U197001004',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 24350.75,
                'insurance_policy' => 'AMB-2023-104',
                'insurance_expiry' => Carbon::now()->addMonths(8),
                'registration_expiry' => Carbon::now()->addMonths(10),
                'last_service_date' => Carbon::now()->subDays(25),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Standard emergency ambulance with defibrillator',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'ambulance',
                'registration_number' => 'GR-1005-24',
                'vehicle_color' => 'White',
                'make' => 'Volkswagen',
                'model' => 'Crafter',
                'year' => 2024,
                'vin_number' => 'WV1ZZZ2KZLH001005',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 6890.00,
                'insurance_policy' => 'AMB-2024-105',
                'insurance_expiry' => Carbon::now()->addMonths(11),
                'registration_expiry' => Carbon::now()->addYear(),
                'last_service_date' => Carbon::now()->subDays(10),
                'next_service_date' => Carbon::now()->addMonths(3),
                'notes' => 'Bariatric ambulance with heavy-duty equipment',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'ambulance',
                'registration_number' => 'GR-1006-23',
                'vehicle_color' => 'White',
                'make' => 'Mercedes-Benz',
                'model' => 'Sprinter',
                'year' => 2023,
                'vin_number' => 'WDB9063221N001006',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 32450.50,
                'insurance_policy' => 'AMB-2023-106',
                'insurance_expiry' => Carbon::now()->addMonths(7),
                'registration_expiry' => Carbon::now()->addMonths(9),
                'last_service_date' => Carbon::now()->subMonth(),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Neonatal ambulance with incubator',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'ambulance',
                'registration_number' => 'GR-1007-24',
                'vehicle_color' => 'White',
                'make' => 'Chevrolet',
                'model' => 'Express',
                'year' => 2024,
                'vin_number' => '1GCWGBFG4K1001007',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 9560.25,
                'insurance_policy' => 'AMB-2024-107',
                'insurance_expiry' => Carbon::now()->addMonths(10),
                'registration_expiry' => Carbon::now()->addMonths(11),
                'last_service_date' => Carbon::now()->subDays(18),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Mobile intensive care unit',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'ambulance',
                'registration_number' => 'GR-1008-24',
                'vehicle_color' => 'White',
                'make' => 'Ford',
                'model' => 'Transit',
                'year' => 2024,
                'vin_number' => '1FTBW3XM5GKA01008',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 11240.00,
                'insurance_policy' => 'AMB-2024-108',
                'insurance_expiry' => Carbon::now()->addMonths(11),
                'registration_expiry' => Carbon::now()->addYear(),
                'last_service_date' => Carbon::now()->subDays(22),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Cardiac ambulance with ECG monitor',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'ambulance',
                'registration_number' => 'GR-1009-23',
                'vehicle_color' => 'White',
                'make' => 'Toyota',
                'model' => 'Hiace',
                'year' => 2023,
                'vin_number' => 'JTDKB20U197001009',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 28790.75,
                'insurance_policy' => 'AMB-2023-109',
                'insurance_expiry' => Carbon::now()->addMonths(6),
                'registration_expiry' => Carbon::now()->addMonths(8),
                'last_service_date' => Carbon::now()->subMonth(),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'General purpose emergency ambulance',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'ambulance',
                'registration_number' => 'GR-1010-24',
                'vehicle_color' => 'White',
                'make' => 'Volkswagen',
                'model' => 'Crafter',
                'year' => 2024,
                'vin_number' => 'WV1ZZZ2KZLH001010',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 7350.50,
                'insurance_policy' => 'AMB-2024-110',
                'insurance_expiry' => Carbon::now()->addMonths(11),
                'registration_expiry' => Carbon::now()->addYear(),
                'last_service_date' => Carbon::now()->subDays(12),
                'next_service_date' => Carbon::now()->addMonths(3),
                'notes' => 'Trauma response ambulance with surgical equipment',
                'assign_driver' => true
            ],

            // Available Regular Vehicles (10 vehicles)
            [
                'vehicle_type' => 'regular',
                'registration_number' => 'GR-2001-24',
                'vehicle_color' => 'Silver',
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2024,
                'vin_number' => '4T1B11HK5KU002001',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 5680.00,
                'insurance_policy' => 'REG-2024-201',
                'insurance_expiry' => Carbon::now()->addMonths(10),
                'registration_expiry' => Carbon::now()->addMonths(11),
                'last_service_date' => Carbon::now()->subDays(20),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Executive transport for patient families',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'regular',
                'registration_number' => 'GR-2002-24',
                'vehicle_color' => 'Black',
                'make' => 'Honda',
                'model' => 'Accord',
                'year' => 2024,
                'vin_number' => '1HGCV1F16KA002002',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 8920.50,
                'insurance_policy' => 'REG-2024-202',
                'insurance_expiry' => Carbon::now()->addMonths(11),
                'registration_expiry' => Carbon::now()->addYear(),
                'last_service_date' => Carbon::now()->subDays(15),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Nurse home visit vehicle',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'regular',
                'registration_number' => 'GR-2003-24',
                'vehicle_color' => 'White',
                'make' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2024,
                'vin_number' => '2T1BURHE5KC002003',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 12450.25,
                'insurance_policy' => 'REG-2024-203',
                'insurance_expiry' => Carbon::now()->addMonths(9),
                'registration_expiry' => Carbon::now()->addMonths(10),
                'last_service_date' => Carbon::now()->subDays(25),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Medical supplies delivery vehicle',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'regular',
                'registration_number' => 'GR-2004-24',
                'vehicle_color' => 'Blue',
                'make' => 'Nissan',
                'model' => 'Sentra',
                'year' => 2024,
                'vin_number' => '3N1AB7AP8KY002004',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 6780.75,
                'insurance_policy' => 'REG-2024-204',
                'insurance_expiry' => Carbon::now()->addMonths(10),
                'registration_expiry' => Carbon::now()->addMonths(11),
                'last_service_date' => Carbon::now()->subDays(18),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Staff transport vehicle',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'regular',
                'registration_number' => 'GR-2005-24',
                'vehicle_color' => 'Grey',
                'make' => 'Honda',
                'model' => 'Civic',
                'year' => 2024,
                'vin_number' => '2HGFC2F59KH002005',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 9340.00,
                'insurance_policy' => 'REG-2024-205',
                'insurance_expiry' => Carbon::now()->addMonths(11),
                'registration_expiry' => Carbon::now()->addYear(),
                'last_service_date' => Carbon::now()->subDays(12),
                'next_service_date' => Carbon::now()->addMonths(3),
                'notes' => 'General purpose transport',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'regular',
                'registration_number' => 'GR-2006-24',
                'vehicle_color' => 'Black',
                'make' => 'Volkswagen',
                'model' => 'Passat',
                'year' => 2024,
                'vin_number' => '1VWSA7A39KC002006',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 11560.50,
                'insurance_policy' => 'REG-2024-206',
                'insurance_expiry' => Carbon::now()->addMonths(10),
                'registration_expiry' => Carbon::now()->addMonths(11),
                'last_service_date' => Carbon::now()->subDays(20),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Executive patient transport',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'regular',
                'registration_number' => 'GR-2007-24',
                'vehicle_color' => 'White',
                'make' => 'Hyundai',
                'model' => 'Elantra',
                'year' => 2024,
                'vin_number' => '5NPD84LF1KH002007',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 4230.25,
                'insurance_policy' => 'REG-2024-207',
                'insurance_expiry' => Carbon::now()->addMonths(11),
                'registration_expiry' => Carbon::now()->addYear(),
                'last_service_date' => Carbon::now()->subDays(8),
                'next_service_date' => Carbon::now()->addMonths(3),
                'notes' => 'Non-emergency patient transport',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'regular',
                'registration_number' => 'GR-2008-24',
                'vehicle_color' => 'Silver',
                'make' => 'Mazda',
                'model' => 'Mazda3',
                'year' => 2024,
                'vin_number' => '3MZBM1U77KM002008',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 7890.75,
                'insurance_policy' => 'REG-2024-208',
                'insurance_expiry' => Carbon::now()->addMonths(9),
                'registration_expiry' => Carbon::now()->addMonths(10),
                'last_service_date' => Carbon::now()->subDays(22),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Home care nurse vehicle',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'regular',
                'registration_number' => 'GR-2009-24',
                'vehicle_color' => 'Red',
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2024,
                'vin_number' => '4T1B11HK5KU002009',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 10120.00,
                'insurance_policy' => 'REG-2024-209',
                'insurance_expiry' => Carbon::now()->addMonths(10),
                'registration_expiry' => Carbon::now()->addMonths(11),
                'last_service_date' => Carbon::now()->subDays(16),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Administrative staff vehicle',
                'assign_driver' => true
            ],
            [
                'vehicle_type' => 'regular',
                'registration_number' => 'GR-2010-24',
                'vehicle_color' => 'Blue',
                'make' => 'Honda',
                'model' => 'Accord',
                'year' => 2024,
                'vin_number' => '1HGCV1F16KA002010',
                'is_active' => true,
                'is_available' => true,
                'status' => 'available',
                'mileage' => 13670.50,
                'insurance_policy' => 'REG-2024-210',
                'insurance_expiry' => Carbon::now()->addMonths(11),
                'registration_expiry' => Carbon::now()->addYear(),
                'last_service_date' => Carbon::now()->subDays(14),
                'next_service_date' => Carbon::now()->addMonths(2),
                'notes' => 'Patient consultation transport',
                'assign_driver' => true
            ]
        ];

        // Get admin user for assignments (or create a system user)
        $adminUser = User::where('email', 'admin@homecare.com')->first() 
                     ?? User::first() 
                     ?? User::factory()->create(['name' => 'System Admin', 'email' => 'admin@homecare.com']);

        // Get available drivers
        $drivers = Driver::where('is_active', true)
                        ->where('is_suspended', false)
                        ->get();

        if ($drivers->isEmpty()) {
            $this->command->warn('No active drivers found. Please run DriverSeeder first.');
            $this->command->warn('Creating vehicles without driver assignments...');
        }

        $driverIndex = 0;
        $assignmentCount = 0;
        $createdVehicles = [];
        $updatedVehicles = [];

        foreach ($vehicles as $vehicleData) {
            $shouldAssignDriver = $vehicleData['assign_driver'] ?? false;
            unset($vehicleData['assign_driver']);

            // Use updateOrCreate to avoid duplicates
            $vehicle = Vehicle::updateOrCreate(
                ['registration_number' => $vehicleData['registration_number']], // Search by registration number
                $vehicleData // Update or create with this data
            );

            if ($vehicle->wasRecentlyCreated) {
                $createdVehicles[] = $vehicle;
                $this->command->info("✓ Created vehicle: {$vehicle->registration_number} ({$vehicle->make} {$vehicle->model})");
            } else {
                $updatedVehicles[] = $vehicle;
                $this->command->info("⟳ Updated vehicle: {$vehicle->registration_number} ({$vehicle->make} {$vehicle->model})");
            }

            // Assign driver if specified and drivers are available
            if ($shouldAssignDriver && !$drivers->isEmpty()) {
                // Check if this vehicle already has an active assignment
                $existingAssignment = DriverVehicleAssignment::where('vehicle_id', $vehicle->id)
                    ->where('is_active', true)
                    ->first();

                if (!$existingAssignment) {
                    $driver = $drivers[$driverIndex % $drivers->count()];
                    
                    // Check if driver already has an active assignment
                    $driverHasAssignment = DriverVehicleAssignment::where('driver_id', $driver->id)
                        ->where('is_active', true)
                        ->exists();

                    if (!$driverHasAssignment) {
                        // Determine assignment type and duration based on vehicle status
                        $assignmentData = $this->getAssignmentData($vehicle, $driver, $adminUser);
                        
                        DriverVehicleAssignment::create($assignmentData);
                        
                        $assignmentCount++;
                        $this->command->info("  → Assigned to {$driver->full_name}");
                    } else {
                        $this->command->warn("  ⚠ Driver {$driver->full_name} already has an active assignment");
                    }
                    
                    $driverIndex++;
                } else {
                    $assignedDriver = $existingAssignment->driver;
                    $this->command->info("  → Already assigned to {$assignedDriver->full_name}");
                }
            }
        }

        // Output summary
        $this->command->info('');
        $this->command->info('════════════════════════════════════════════════');
        $this->command->info('Vehicle Seeding Complete!');
        $this->command->info('════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('📊 Results:');
        $this->command->info('  • New Vehicles Created: ' . count($createdVehicles));
        $this->command->info('  • Existing Vehicles Updated: ' . count($updatedVehicles));
        $this->command->info('  • Total Vehicles: ' . (count($createdVehicles) + count($updatedVehicles)));
        $this->command->info('');
        $this->command->info('📍 Vehicle Types:');
        $this->command->info('  • Ambulances: ' . collect($vehicles)->where('vehicle_type', 'ambulance')->count());
        $this->command->info('  • Regular Vehicles: ' . collect($vehicles)->where('vehicle_type', 'regular')->count());
        $this->command->info('');
        $this->command->info('📍 Status Distribution:');
        $this->command->info('  • Available: ' . collect($vehicles)->where('status', 'available')->count());
        $this->command->info('  • In Use: ' . collect($vehicles)->where('status', 'in_use')->count());
        $this->command->info('  • In Maintenance: ' . collect($vehicles)->where('status', 'maintenance')->count());
        $this->command->info('  • Out of Service: ' . collect($vehicles)->where('status', 'out_of_service')->count());
        $this->command->info('');
        $this->command->info('👥 Driver Assignments:');
        $this->command->info('  • New Assignments Created: ' . $assignmentCount);
        $this->command->info('  • Vehicles Without Assignments: ' . (count($vehicles) - $assignmentCount));
        $this->command->info('════════════════════════════════════════════════');
    }

    /**
     * Get assignment data based on vehicle status
     */
    private function getAssignmentData($vehicle, $driver, $adminUser)
    {
        $baseData = [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'assigned_by' => $adminUser->id,
            'assigned_at' => Carbon::now()->subDays(rand(1, 30)),
        ];

        // All vehicles are available, so create permanent active assignments
        return array_merge($baseData, [
            'is_active' => true,
            'status' => 'active',
            'assignment_notes' => 'Permanent assignment for regular duties',
            'effective_from' => Carbon::now()->subDays(rand(1, 30)),
            'effective_until' => null,
        ]);
    }
}