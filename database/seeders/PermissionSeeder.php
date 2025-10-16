<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // 1. User Management
            [
                'name' => 'user_management.view',
                'display_name' => 'View User Management',
                'description' => 'Can view user management section',
                'category' => 'user_management',
                'subcategory' => null,
                'sort_order' => 1,
            ],
             [
                'name' => 'user_management.users.view',
                'display_name' => 'View All Users',
                'description' => 'Can view users list and details',
                'category' => 'user_management',
                'subcategory' => 'users',
                'sort_order' => 1,
            ],
            [
                'name' => 'user_management.users.create',
                'display_name' => 'Create Users',
                'description' => 'Can create new user accounts',
                'category' => 'user_management',
                'subcategory' => 'users',
                'sort_order' => 2,
            ],
            [
                'name' => 'user_management.users.edit',
                'display_name' => 'Edit Users',
                'description' => 'Can edit user profiles and information',
                'category' => 'user_management',
                'subcategory' => 'users',
                'sort_order' => 3,
            ],
            [
                'name' => 'user_management.users.delete',
                'display_name' => 'Delete Users',
                'description' => 'Can delete user accounts',
                'category' => 'user_management',
                'subcategory' => 'users',
                'sort_order' => 4,
            ],
            [
                'name' => 'user_management.users.verify',
                'display_name' => 'Verify Users',
                'description' => 'Can verify user applications and credentials',
                'category' => 'user_management',
                'subcategory' => 'users',
                'sort_order' => 5,
            ],
            [
                'name' => 'user_management.nurses.view',
                'display_name' => 'View Nurses',
                'description' => 'Can view nurses list and details',
                'category' => 'user_management',
                'subcategory' => 'nurses',
                'sort_order' => 1,
            ],
            [
                'name' => 'user_management.nurses.create',
                'display_name' => 'Create Nurses',
                'description' => 'Can create new nurse accounts',
                'category' => 'user_management',
                'subcategory' => 'nurses',
                'sort_order' => 2,
            ],
            [
                'name' => 'user_management.nurses.edit',
                'display_name' => 'Edit Nurses',
                'description' => 'Can edit nurse profiles and information',
                'category' => 'user_management',
                'subcategory' => 'nurses',
                'sort_order' => 3,
            ],
            [
                'name' => 'user_management.nurses.delete',
                'display_name' => 'Delete Nurses',
                'description' => 'Can delete nurse accounts',
                'category' => 'user_management',
                'subcategory' => 'nurses',
                'sort_order' => 4,
            ],
            [
                'name' => 'user_management.nurses.verify',
                'display_name' => 'Verify Nurses',
                'description' => 'Can verify nurse applications and credentials',
                'category' => 'user_management',
                'subcategory' => 'nurses',
                'sort_order' => 5,
            ],
            [
                'name' => 'user_management.patients.view',
                'display_name' => 'View Patients',
                'description' => 'Can view patients list and details',
                'category' => 'user_management',
                'subcategory' => 'patients',
                'sort_order' => 1,
            ],
            [
                'name' => 'user_management.patients.create',
                'display_name' => 'Create Patients',
                'description' => 'Can create new patient accounts',
                'category' => 'user_management',
                'subcategory' => 'patients',
                'sort_order' => 2,
            ],
            [
                'name' => 'user_management.patients.edit',
                'display_name' => 'Edit Patients',
                'description' => 'Can edit patient profiles and information',
                'category' => 'user_management',
                'subcategory' => 'patients',
                'sort_order' => 3,
            ],
            [
                'name' => 'user_management.patients.delete',
                'display_name' => 'Delete Patients',
                'description' => 'Can delete patient accounts',
                'category' => 'user_management',
                'subcategory' => 'patients',
                'sort_order' => 4,
            ],
            [
                'name' => 'user_management.doctors.view',
                'display_name' => 'View Doctors',
                'description' => 'Can view doctors list and details',
                'category' => 'user_management',
                'subcategory' => 'doctors',
                'sort_order' => 1,
            ],
            [
                'name' => 'user_management.doctors.create',
                'display_name' => 'Create Doctors',
                'description' => 'Can create new doctor accounts',
                'category' => 'user_management',
                'subcategory' => 'doctors',
                'sort_order' => 2,
            ],
            [
                'name' => 'user_management.doctors.edit',
                'display_name' => 'Edit Doctors',
                'description' => 'Can edit doctor profiles and information',
                'category' => 'user_management',
                'subcategory' => 'doctors',
                'sort_order' => 3,
            ],
            [
                'name' => 'user_management.doctors.delete',
                'display_name' => 'Delete Doctors',
                'description' => 'Can delete doctor accounts',
                'category' => 'user_management',
                'subcategory' => 'doctors',
                'sort_order' => 4,
            ],
            [
                'name' => 'user_management.doctors.verify',
                'display_name' => 'Verify Doctors',
                'description' => 'Can verify doctor applications and credentials',
                'category' => 'user_management',
                'subcategory' => 'doctors',
                'sort_order' => 5,
            ],

            [
                'name' => 'user_management.pending.verification',
                'display_name' => 'Verify Pending Users',
                'description' => 'Can verify pending applications',
                'category' => 'user_management',
                'subcategory' => 'users',
                'sort_order' => 5,
            ],


            // 2. Care Management
            [
                'name' => 'care_management.view',
                'display_name' => 'View Care Management',
                'description' => 'Can view care management section',
                'category' => 'care_management',
                'subcategory' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'care_management.care_plans.view',
                'display_name' => 'View Care Plans',
                'description' => 'Can view patient care plans',
                'category' => 'care_management',
                'subcategory' => 'care_plans',
                'sort_order' => 1,
            ],
            [
                'name' => 'care_management.care_plans.create',
                'display_name' => 'Create Care Plans',
                'description' => 'Can create new care plans for patients',
                'category' => 'care_management',
                'subcategory' => 'care_plans',
                'sort_order' => 2,
            ],
            [
                'name' => 'care_management.care_plans.edit',
                'display_name' => 'Edit Care Plans',
                'description' => 'Can modify existing care plans',
                'category' => 'care_management',
                'subcategory' => 'care_plans',
                'sort_order' => 3,
            ],
            [
                'name' => 'care_management.care_plans.delete',
                'display_name' => 'Delete Care Plans',
                'description' => 'Can delete care plans',
                'category' => 'care_management',
                'subcategory' => 'care_plans',
                'sort_order' => 4,
            ],
            [
                'name' => 'care_management.care_plans.approve',
                'display_name' => 'Approve Care Plans',
                'description' => 'Can approve care plans',
                'category' => 'care_management',
                'subcategory' => 'care_plans',
                'sort_order' => 5,
            ],

            [
                'name' => 'care_management.schedules.view',
                'display_name' => 'View Schedules',
                'description' => 'Can view care schedules',
                'category' => 'care_management',
                'subcategory' => 'schedules',
                'sort_order' => 1,
            ],
            [
                'name' => 'care_management.schedules.create',
                'display_name' => 'Create Schedules',
                'description' => 'Can create care schedules',
                'category' => 'care_management',
                'subcategory' => 'schedules',
                'sort_order' => 2,
            ],
            [
                'name' => 'care_management.schedules.edit',
                'display_name' => 'Edit Schedules',
                'description' => 'Can modify care schedules',
                'category' => 'care_management',
                'subcategory' => 'schedules',
                'sort_order' => 3,
            ],
            [
                'name' => 'care_management.schedules.delete',
                'display_name' => 'Delete Schedules',
                'description' => 'Can delete care schedules',
                'category' => 'care_management',
                'subcategory' => 'schedules',
                'sort_order' => 4,
            ],

            [
                'name' => 'care_management.schedules.approve',
                'display_name' => 'Approve Schedules',
                'description' => 'Can approve care schedules',
                'category' => 'care_management',
                'subcategory' => 'schedules',
                'sort_order' => 4,
            ],

            // 3. Time Tracking
            [
                'name' => 'time_tracking.view',
                'display_name' => 'View Time Tracking',
                'description' => 'Can view time tracking data',
                'category' => 'time_tracking',
                'subcategory' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'time_tracking.clock_in_out',
                'display_name' => 'Clock In/Out',
                'description' => 'Can clock in and out for shifts',
                'category' => 'time_tracking',
                'subcategory' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'time_tracking.edit_own',
                'display_name' => 'Edit Own Time',
                'description' => 'Can edit own time entries',
                'category' => 'time_tracking',
                'subcategory' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'time_tracking.edit_others',
                'display_name' => 'Edit Others Time',
                'description' => 'Can edit time entries for other users',
                'category' => 'time_tracking',
                'subcategory' => null,
                'sort_order' => 4,
            ],
            [
                'name' => 'time_tracking.approve',
                'display_name' => 'Approve Time',
                'description' => 'Can approve time entries for payroll',
                'category' => 'time_tracking',
                'subcategory' => null,
                'sort_order' => 5,
            ],
            [
                'name' => 'time_tracking.reports',
                'display_name' => 'View Time Reports',
                'description' => 'Can view time tracking reports',
                'category' => 'time_tracking',
                'subcategory' => null,
                'sort_order' => 6,
            ],

            // 4. Daily Progress
            [
                'name' => 'daily_progress.view',
                'display_name' => 'View Daily Progress',
                'description' => 'Can view patient daily progress data',
                'category' => 'daily_progress',
                'subcategory' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'daily_progress.create',
                'display_name' => 'Create Daily Progress',
                'description' => 'Can create patient daily progress',
                'category' => 'daily_progress',
                'subcategory' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'daily_progress.edit',
                'display_name' => 'Edit Daily Progress',
                'description' => 'Can edit patient daily progress',
                'category' => 'daily_progress',
                'subcategory' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'daily_progress.delete',
                'display_name' => 'Delete Daily Progress',
                'description' => 'Can delete patient daily progress',
                'category' => 'daily_progress',
                'subcategory' => null,
                'sort_order' => 3,
            ],

            // 6. Payments & Billing
            [
                'name' => 'payments_billing.view',
                'display_name' => 'View Payments & Billing',
                'description' => 'Can view payment and billing information',
                'category' => 'payments_billing',
                'subcategory' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'payments_billing.process_payments',
                'display_name' => 'Process Payments',
                'description' => 'Can process patient payments',
                'category' => 'payments_billing',
                'subcategory' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'payments_billing.create_invoices',
                'display_name' => 'Create Invoices',
                'description' => 'Can create and send invoices',
                'category' => 'payments_billing',
                'subcategory' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'payments_billing.manage_pricing',
                'display_name' => 'Manage Pricing',
                'description' => 'Can manage service pricing and rates',
                'category' => 'payments_billing',
                'subcategory' => null,
                'sort_order' => 4,
            ],
            [
                'name' => 'payments_billing.financial_reports',
                'display_name' => 'View Financial Reports',
                'description' => 'Can view financial reports and analytics',
                'category' => 'payments_billing',
                'subcategory' => null,
                'sort_order' => 5,
            ],

            // 7. Transportation
            [
                'name' => 'transportation.drivers.manage',
                'display_name' => 'Manage Transportation Drivers',
                'description' => 'Can manage transportation drivers',
                'category' => 'transportation',
                'subcategory' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'transportation.vehicles.manage',
                'display_name' => 'Manage Transportation Vehicles',
                'description' => 'Can manage transportation vehicles',
                'category' => 'transportation',
                'subcategory' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'transportation.requests',
                'display_name' => 'Manage Transportation Requests',
                'description' => 'Can manage transport requests',
                'category' => 'transportation',
                'subcategory' => null,
                'sort_order' => 3,
            ],

            // 8. Reports & Analytics
            [
                'name' => 'reports_analytics.quality_assurance',
                'display_name' => 'View Quality Assurance Report ',
                'description' => 'Can view quality assurance reports',
                'category' => 'reports_analytics',
                'subcategory' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'reports_analytics.users',
                'display_name' => 'View User Report',
                'description' => 'Can view user reports',
                'category' => 'reports_analytics',
                'subcategory' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'reports_analytics.health',
                'display_name' => 'View Health Report',
                'description' => 'Can view health reports',
                'category' => 'reports_analytics',
                'subcategory' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'reports_analytics.care_nurse',
                'display_name' => 'View Care & Nurse Reports',
                'description' => 'Can view care & nurse reports',
                'category' => 'reports_analytics',
                'subcategory' => null,
                'sort_order' => 4,
            ],
            [
                'name' => 'reports_analytics.financial',
                'display_name' => 'View Financial Reports',
                'description' => 'Can view  financial reports',
                'category' => 'reports_analytics',
                'subcategory' => null,
                'sort_order' => 5,
            ],
            [
                'name' => 'reports_analytics.transport',
                'display_name' => 'View Transport Reports',
                'description' => 'Can view  transport reports',
                'category' => 'reports_analytics',
                'subcategory' => null,
                'sort_order' => 6,
            ],
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Create default roles
        $this->createDefaultRoles();
    }

    private function createDefaultRoles(): void
    {
        $roles = [
            [
                'name' => 'superadmin',
                'display_name' => 'Super Administrator',
                'description' => 'Full system access with all permissions',
                'is_system_role' => true,
                'permissions' => Permission::all()->pluck('name')->toArray(), // All permissions
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Administrative access to most system features',
                'is_system_role' => true,
                'permissions' => Permission::all()->pluck('name')->toArray() // All permissions
            ],
            [
                'name' => 'nurse',
                'display_name' => 'Nurse',
                'description' => 'Access for nurses to manage patient care',
                'is_system_role' => true,
                'permissions' => [
                    // User Management
                    'user_management.nurses.view',
                    
                    // Care Management
                    'care_management.view',
                    'care_management.care_plans.view',
                    'care_management.care_plans.create',
                    'care_management.care_plans.edit',
                    'care_management.schedules.view',
                    
                    // Time Tracking (all except approve)
                    'time_tracking.view',
                    'time_tracking.clock_in_out',
                    'time_tracking.edit_own',
                    'time_tracking.edit_others',
                    'time_tracking.reports',
                    
                    // Daily Progress
                    'daily_progress.view',
                    'daily_progress.create',
                    'daily_progress.edit',
                    
                    // Transportation
                    'transportation.requests',
                    
                    // Reports & Analytics
                    'reports_analytics.care_nurse',
                    'reports_analytics.quality_assurance',
                    'reports_analytics.health',
                ],
            ],
            [
                'name' => 'doctor',
                'display_name' => 'Doctor',
                'description' => 'Access for doctors to manage patient care and medical decisions',
                'is_system_role' => true,
                'permissions' => [
                    // User Management - Patients
                    'user_management.patients.view',
                    'user_management.patients.create',
                    'user_management.patients.edit',
                    'user_management.patients.delete',
                    
                    // Care Management - All
                    'care_management.view',
                    'care_management.care_plans.view',
                    'care_management.care_plans.create',
                    'care_management.care_plans.edit',
                    'care_management.care_plans.delete',
                    'care_management.care_plans.approve',
                    'care_management.schedules.view',
                    'care_management.schedules.create',
                    'care_management.schedules.edit',
                    'care_management.schedules.delete',
                    'care_management.schedules.approve',
                
                    
                    // Daily Progress - All
                    'daily_progress.view',
                    'daily_progress.create',
                    'daily_progress.edit',
                    'daily_progress.delete',
                    
                    // Payments & Billing - View
                    'payments_billing.view',
                    
                    // Transportation
                    'transportation.requests',
                    
                    // Reports & Analytics - All
                    'reports_analytics.quality_assurance',
                    'reports_analytics.users',
                    'reports_analytics.health',
                    'reports_analytics.care_nurse',
                    'reports_analytics.financial',
                    'reports_analytics.transport',
                ],
            ],
            [
                'name' => 'patient',
                'display_name' => 'Patient',
                'description' => 'Limited access for patients to view their own data',
                'is_system_role' => true,
                'permissions' => [
                    // Care Management
                    'care_management.view',
                    'care_management.schedules.view',
                    
                    // Daily Progress
                    'daily_progress.view',
                    
                    // Transportation
                    'transportation.requests',
                    
                    // Reports & Analytics
                    'reports_analytics.care_nurse',
                    'reports_analytics.health',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            // Assign permissions to role
            $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}