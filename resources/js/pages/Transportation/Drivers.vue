<template>
 <MainLayout>
<div class="drivers-page">
<div class="max-w-7xl mx-auto px-4 py-8">
<!-- Page Header -->
<div class="page-header">
<div class="page-header-content">
<h1>Drivers Management</h1>
<p>Manage transportation drivers and their assignments</p>
</div>
<div class="page-header-actions">
<button @click="exportDrivers" class="btn btn-secondary">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
</svg>
 Export
</button>
<button @click="openCreateModal" class="btn btn-primary">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
</svg>
 Add Driver
</button>
</div>
</div>
<!-- Filters and Search -->
<div class="filters-section">
<div class="filters-content">
<div class="search-wrapper">
<svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
</svg>
<input
type="text"
placeholder="Search by name, phone, email..."
v-model="searchQuery"
class="search-input"
/>
</div>
<div class="filters-group">
<select v-model="activeFilter" class="filter-select">
<option value="all">All Status</option>
<option value="active">Active</option>
<option value="inactive">Inactive</option>
</select>
<select v-model="suspendedFilter" class="filter-select">
<option value="all">All Drivers</option>
<option value="false">Not Suspended</option>
<option value="true">Suspended</option>
</select>
<select v-model="vehicleAssignedFilter" class="filter-select">
<option value="all">All Assignments</option>
<option value="true">With Vehicle</option>
<option value="false">Without Vehicle</option>
</select>
</div>
</div>
</div>
<!-- Loading State -->
<div v-if="loading" class="loading-state">
<div class="loading-spinner"></div>
<p class="loading-text">Loading drivers...</p>
</div>
<!-- Drivers Table -->
<div v-else class="drivers-table-container">
<div class="overflow-x-auto">
<table class="drivers-table">
<thead>
<tr>
<th>Driver</th>
<th>Contact</th>
<th>Age</th>
<th>Status</th>
<th>Assigned Vehicle</th>
<th>Performance</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<tr v-for="driver in filteredDrivers" :key="driver.id">
<td>
<div class="driver-info">
<div class="driver-avatar">
<img :src="driver.avatar_url" :alt="driver.full_name" />
</div>
<div class="driver-details">
<div class="driver-name">{{ driver.first_name }} {{ driver.last_name }}</div>
<div class="driver-id">ID: {{ driver.driver_id }}</div>
</div>
</div>
</td>
<td>
<div class="contact-info">
<div class="primary">{{ driver.phone }}</div>
<div class="secondary">{{ driver.email || 'N/A' }}</div>
</div>
</td>
<td>
<span class="age-info">{{ driver.age }} years</span>
</td>
<td>
<div class="status-info">
<span :class="'badge ' + getStatusBadgeColor(driver)">
 {{ getStatusLabel(driver) }}
</span>
</div>
</td>
<td>
<div class="vehicle-assignment">
<div v-if="driver.current_vehicle" class="assigned-vehicle">
<div class="vehicle-number">{{ driver.current_vehicle.registration_number }}</div>
<span :class="'badge badge-sm ' + getVehicleTypeBadgeColor(driver.current_vehicle.vehicle_type)">
 {{ capitalizeFirst(driver.current_vehicle.vehicle_type) }}
</span>
</div>
<div v-else class="no-vehicle">
<span class="text-gray-500">Not assigned</span>
</div>
</div>
</td>
<td>
<div class="performance-info">
<div class="rating">
<span class="rating-score">{{ driver.average_rating || 'N/A' }}</span>
<div v-if="driver.average_rating" class="rating-stars">
<span v-for="star in 5" :key="star" :class="star <= Math.floor(driver.average_rating) ? 'star filled' : 'star'">★</span>
</div>
</div>
<div class="trips-count">{{ driver.total_trips || 0 }} trips</div>
</div>
</td>
<td>
<div class="action-dropdown">
<button
 @click="toggleDropdown(driver.id)"
class="btn btn-secondary btn-sm"
style="min-width: auto; padding: 0.5rem;"
>
<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
<path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
</svg>
</button>
<div v-show="activeDropdown === driver.id" class="dropdown-menu">
<button @click="openViewModal(driver)" class="dropdown-item">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
</svg>
 View Details
</button>
<button @click="openEditModal(driver)" class="dropdown-item">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
</svg>
 Edit Driver
</button>
<div class="dropdown-divider"></div>
<button
v-if="!driver.current_vehicle"
 @click="openAssignVehicleModal(driver)"
class="dropdown-item dropdown-item-success"
>
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
</svg>
 Assign Vehicle
</button>
<button
v-if="driver.current_vehicle"
 @click="openUnassignVehicleModal(driver)"
class="dropdown-item dropdown-item-warning"
>
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
</svg>
 Unassign Vehicle
</button>
<div class="dropdown-divider"></div>
<button
v-if="!driver.is_suspended"
 @click="openSuspendModal(driver)"
class="dropdown-item dropdown-item-danger"
>
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
</svg>
 Suspend Driver
</button>
<button
v-if="driver.is_suspended"
 @click="openReactivateModal(driver)"
class="dropdown-item dropdown-item-success"
>
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
</svg>
 Reactivate Driver
</button>
</div>
</div>
</td>
</tr>
</tbody>
</table>
</div>
<div v-if="filteredDrivers.length === 0" class="empty-state">
<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
</svg>
<h3>No drivers found</h3>
<p>
 {{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'Get started by adding a new driver.' }}
</p>
</div>
</div>
<!-- Create/Edit Driver Modal -->
<div v-if="showDriverModal" class="modal-overlay">
<div class="modal modal-lg">
<div class="modal-header">
<h2 class="modal-title">
<svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
</svg>
 {{ isEditing ? 'Edit Driver' : 'Add New Driver' }}
</h2>
<button @click="closeDriverModal" class="modal-close">
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
</svg>
</button>
</div>
<form @submit.prevent="saveDriver" id="driverForm">
<div class="modal-body">
<div class="form-grid">
<div class="form-group">
<label>First Name *</label>
<input type="text" v-model="driverForm.first_name" required />
</div>
<div class="form-group">
<label>Last Name *</label>
<input type="text" v-model="driverForm.last_name" required />
</div>
<div class="form-group">
<label>Phone Number *</label>
<input type="tel" v-model="driverForm.phone" required />
</div>
<div class="form-group">
<label>Email</label>
<input type="email" v-model="driverForm.email" />
</div>
<div class="form-group">
<label>Date of Birth *</label>
<input type="date" v-model="driverForm.date_of_birth" required />
</div>
<div class="form-group">
<label>Photo</label>
<!-- Show current image preview when editing -->
<div v-if="isEditing && currentDriver?.avatar_url && !newImagePreview" class="current-image-preview">
<img :src="currentDriver.avatar_url" alt="Current photo" class="current-avatar" />
<p class="image-help-text">Current photo (upload a new one to replace)</p>
</div>
<!-- Show new image preview -->
<div v-if="newImagePreview" class="new-image-preview">
<img :src="newImagePreview" alt="New photo preview" class="preview-avatar" />
<p class="image-help-text">New photo preview</p>
<button type="button" @click="clearImagePreview" class="btn btn-secondary btn-sm">
 Remove
</button>
</div>
<input
type="file"
ref="avatarInput"
accept="image/*"
 @change="handleAvatarChange"
class="form-control"
/>
<p class="form-help">Upload a clear photo of the driver</p>
</div>
<div class="form-group form-grid-full">
<label>Notes</label>
<textarea
v-model="driverForm.notes"
rows="3"
placeholder="Any additional information about the driver..."
></textarea>
</div>
</div>
</div>
<div class="modal-actions">
<button type="button" @click="closeDriverModal" class="btn btn-secondary">
 Cancel
</button>
<button
type="submit"
form="driverForm"
 :disabled="isSaving"
class="btn btn-primary"
>
<div v-if="isSaving" class="spinner spinner-sm"></div>
 {{ isEditing ? 'Update Driver' : 'Add Driver' }}
</button>
</div>
</form>
</div>
</div>
<!-- View Driver Modal -->
<div v-if="showViewModal && currentDriver" class="modal-overlay">
<div class="modal modal-xl">
<div class="modal-header">
<h2 class="modal-title">
<svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
</svg>
 Driver Details - {{ currentDriver.first_name }} {{ currentDriver.last_name }}
</h2>
<button @click="closeViewModal" class="modal-close">
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
</svg>
</button>
</div>
<div class="modal-body">
<div class="driver-view">
<!-- Driver Profile Card -->
<div class="driver-profile-card">
<div class="driver-header">
<div class="driver-avatar-large">
<img :src="currentDriver.avatar_url" :alt="currentDriver.full_name" />
</div>
<div class="driver-info-details">
<h3 class="driver-name-large">{{ currentDriver.full_name }}</h3>
<div class="driver-meta">
<span class="meta-item">{{ currentDriver.driver_id }}</span>
<span class="meta-item">{{ currentDriver.age }} years old</span>
</div>
<div class="performance-meta">
<span>Rating: <strong>{{ currentDriver.average_rating || 'N/A' }}</strong></span>
<span>Total Trips: <strong>{{ currentDriver.total_trips || 0 }}</strong></span>
</div>
</div>
<div class="status-indicators">
<span :class="'badge badge-lg ' + getStatusBadgeColor(currentDriver)">
 {{ getStatusLabel(currentDriver) }}
</span>
<span v-if="currentDriver.is_suspended" class="badge badge-lg badge-danger">
 Suspended
</span>
</div>
</div>
</div>
<!-- Driver Details Sections -->
<div class="driver-details-sections">
<!-- Contact Information -->
<div class="details-section">
<h4 class="section-title">Contact Information</h4>
<div class="details-grid">
<div class="detail-item">
<label>Phone:</label>
<span>{{ currentDriver.phone }}</span>
</div>
<div class="detail-item">
<label>Email:</label>
<span>{{ currentDriver.email || 'Not provided' }}</span>
</div>
<div class="detail-item">
<label>Date of Birth:</label>
<span>{{ formatDate(currentDriver.date_of_birth) }}</span>
</div>
<div class="detail-item">
<label>Age:</label>
<span>{{ currentDriver.age }} years</span>
</div>
</div>
</div>
<!-- Vehicle Assignment -->
<div class="details-section">
<h4 class="section-title">Vehicle Assignment</h4>
<div v-if="currentDriver.current_vehicle" class="vehicle-assignment-info">
<div class="assigned-vehicle-card">
<div class="vehicle-details">
<h5>{{ currentDriver.current_vehicle.registration_number }}</h5>
<p>{{ currentDriver.current_vehicle.vehicle_type }} - {{ currentDriver.current_vehicle.vehicle_color }}</p>
</div>
<span :class="'badge ' + getVehicleTypeBadgeColor(currentDriver.current_vehicle.vehicle_type)">
 {{ capitalizeFirst(currentDriver.current_vehicle.vehicle_type) }}
</span>
</div>
</div>
<div v-else class="no-vehicle-assigned">
<p class="text-gray-500">No vehicle currently assigned</p>
</div>
</div>
<!-- Performance Metrics -->
<div class="details-section">
<h4 class="section-title">Performance Metrics</h4>
<div class="performance-grid">
<div class="metric-card">
<div class="metric-value">{{ currentDriver.average_rating || 'N/A' }}</div>
<div class="metric-label">Average Rating</div>
</div>
<div class="metric-card">
<div class="metric-value">{{ currentDriver.total_trips || 0 }}</div>
<div class="metric-label">Total Trips</div>
</div>
<div class="metric-card">
<div class="metric-value">{{ currentDriver.completed_trips || 0 }}</div>
<div class="metric-label">Completed</div>
</div>
<div class="metric-card">
<div class="metric-value">{{ currentDriver.completion_rate || 0 }}%</div>
<div class="metric-label">Success Rate</div>
</div>
</div>
</div>
<!-- Additional Notes -->
<div v-if="currentDriver.notes" class="details-section">
<h4 class="section-title">Additional Notes</h4>
<p>{{ currentDriver.notes }}</p>
</div>
</div>
</div>
</div>
<div class="modal-actions">
<button @click="closeViewModal" class="btn btn-secondary">
 Close
</button>
<button @click="editFromView" class="btn btn-primary">
 Edit Driver
</button>
</div>
</div>
</div>
<!-- Assign Vehicle Modal -->
<div v-if="showAssignVehicleModal" class="modal-overlay">
<div class="modal modal-lg">
<div class="modal-header">
<h3 class="modal-title">
<svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
</svg>
 Assign Vehicle to Driver
</h3>
<button @click="closeAssignVehicleModal" class="modal-close">
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
</svg>
</button>
</div>
<form @submit.prevent="assignVehicle">
<div class="modal-body">
<div class="driver-assignment-info">
<div class="assignment-header">
<h4>Assigning vehicle to:</h4>
<div class="driver-card">
<div class="driver-info">
<span class="driver-name">{{ currentDriver?.full_name }}</span>
<span class="driver-details">{{ currentDriver?.phone }}</span>
<span class="badge badge-info">Driver ID: {{ currentDriver?.driver_id }}</span>
</div>
</div>
</div>
</div>

<div class="form-grid">
<div class="form-group form-grid-full">
<label>Select Vehicle *</label>
<SearchableSelect
v-model="assignmentForm.vehicle_id"
:options="vehicleOptions"
placeholder="Search for available vehicles..."
required
:loading="loadingVehicles"
/>
<p class="form-help">
{{ vehicleOptions.length }} available vehicles found
</p>
</div>

<div class="form-group form-grid-full">
<label>Assignment Notes</label>
<textarea
v-model="assignmentForm.notes"
rows="3"
placeholder="Optional notes about this assignment (e.g., shift schedule, special instructions)..."
></textarea>
</div>
</div>

<!-- Vehicle Preview -->
<div v-if="selectedVehiclePreview" class="selected-vehicle-preview">
<h5>Selected Vehicle:</h5>
<div class="vehicle-preview-card">
<div class="vehicle-info">
<div class="vehicle-reg">{{ selectedVehiclePreview.registration_number }}</div>
<div class="vehicle-details">
<span>{{ selectedVehiclePreview.make }} {{ selectedVehiclePreview.model }}</span>
<span v-if="selectedVehiclePreview.year"> ({{ selectedVehiclePreview.year }})</span>
</div>
<div class="vehicle-details">
<span>Color: {{ selectedVehiclePreview.vehicle_color }}</span>
</div>
</div>
<div class="vehicle-stats">
<div class="stat-item">
<span class="stat-value">{{ selectedVehiclePreview.mileage || 'N/A' }}</span>
<span class="stat-label">Mileage</span>
</div>
<span :class="'badge ' + getVehicleTypeBadgeColor(selectedVehiclePreview.vehicle_type)">
{{ capitalizeFirst(selectedVehiclePreview.vehicle_type) }}
</span>
</div>
</div>
</div>
</div>
<div class="modal-actions">
<button type="button" @click="closeAssignVehicleModal" class="btn btn-secondary">
 Cancel
</button>
<button
type="submit"
 :disabled="isSaving || !assignmentForm.vehicle_id"
class="btn btn-primary"
>
<div v-if="isSaving" class="spinner spinner-sm"></div>
 Assign Vehicle
</button>
</div>
</form>
</div>
</div>

<!-- Unassign Vehicle Modal -->
<div v-if="showUnassignVehicleModal && currentDriver" class="modal-overlay">
<div class="modal modal-lg">
<div class="modal-header modal-header-danger">
<h3 class="modal-title">
<svg class="modal-icon modal-icon-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
</svg>
 Unassign Vehicle from Driver
</h3>
<button @click="closeUnassignVehicleModal" class="modal-close">
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
</svg>
</button>
</div>
            
<form @submit.prevent="unassignVehicle">
<div class="modal-body">
<div class="unassign-confirmation-content">
<p class="confirmation-message">
Are you sure you want to unassign the vehicle from this driver? This action will remove the current vehicle assignment.
</p>

<!-- Driver Information -->
<div class="assignment-details">
<div class="detail-section">
<h5>Driver</h5>
<div class="driver-summary">
<div class="driver-name">{{ currentDriver.full_name }}</div>
<div class="driver-contact">
<span>{{ currentDriver.phone }}</span>
<span v-if="currentDriver.email">{{ currentDriver.email }}</span>
</div>
<div class="driver-id">ID: {{ currentDriver.driver_id }}</div>
</div>
</div>

<!-- Current Vehicle Information -->
<div v-if="currentDriver.current_vehicle" class="detail-section">
<h5>Current Vehicle</h5>
<div class="vehicle-summary">
<div class="vehicle-reg">{{ currentDriver.current_vehicle.registration_number }}</div>
<div class="vehicle-info">{{ currentDriver.current_vehicle.make }} {{ currentDriver.current_vehicle.model }} {{ currentDriver.current_vehicle.year }}</div>
<span :class="'badge ' + getVehicleTypeBadgeColor(currentDriver.current_vehicle.vehicle_type)">
{{ capitalizeFirst(currentDriver.current_vehicle.vehicle_type) }}
</span>
</div>
</div>
</div>

<div class="warning-note">
<svg class="warning-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
</svg>
<div>
<p><strong>Note:</strong> After unassigning, this vehicle will become available for assignment to other drivers. The driver will no longer have access to this vehicle.</p>
</div>
</div>
</div>
</div>

<div class="modal-actions">
<button type="button" @click="closeUnassignVehicleModal" class="btn btn-secondary">
 Cancel
</button>
<button
type="submit"
 :disabled="isUnassigning"
class="btn btn-danger"
>
<div v-if="isUnassigning" class="spinner spinner-sm"></div>
 Unassign Vehicle
</button>
</div>
</form>
</div>
</div>

<!-- Suspend Driver Modal -->
<div v-if="showSuspendModal && currentDriver" class="modal-overlay">
<div class="modal modal-sm">
<div class="modal-header modal-header-danger">
<h3 class="modal-title">
<svg class="modal-icon modal-icon-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
</svg>
 Suspend Driver
</h3>
<button @click="closeSuspendModal" class="modal-close">
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
</svg>
</button>
</div>
<form @submit.prevent="suspendDriver">
<div class="modal-body">
<p>
 Are you sure you want to suspend <strong>{{ currentDriver.full_name }}</strong>?
</p>
<div class="form-group">
<label>Reason for Suspension *</label>
<textarea
v-model="suspensionReason"
rows="3"
placeholder="Please provide a reason for suspension..."
required
></textarea>
</div>
</div>
<div class="modal-actions">
<button type="button" @click="closeSuspendModal" class="btn btn-secondary">
 Cancel
</button>
<button
type="submit"
 :disabled="isSaving"
class="btn btn-danger"
>
<div v-if="isSaving" class="spinner spinner-sm"></div>
 Suspend Driver
</button>
</div>
</form>
</div>
</div>
<!-- Reactivate Driver Modal -->
<div v-if="showReactivateModal && currentDriver" class="modal-overlay">
<div class="modal modal-sm">
<div class="modal-header">
<h3 class="modal-title">
<svg class="modal-icon" style="color: #059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
</svg>
 Reactivate Driver
</h3>
<button @click="closeReactivateModal" class="modal-close">
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
</svg>
</button>
</div>
<div class="modal-body">
<p>
 Are you sure you want to reactivate <strong>{{ currentDriver.full_name }}</strong>?
</p>
<p class="text-sm text-gray-600 mt-2">
 This driver will regain access to the system and be able to accept transport assignments again.
</p>
</div>
<div class="modal-actions">
<button @click="closeReactivateModal" class="btn btn-secondary">
 Cancel
</button>
<button
 @click="reactivateDriver"
 :disabled="isSaving"
class="btn btn-primary"
>
<div v-if="isSaving" class="spinner spinner-sm"></div>
 Reactivate Driver
</button>
</div>
</div>
</div>
</div>
</div>
<!-- Toast Component -->
<Toast />
</MainLayout>
</template>
<script setup>
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import SearchableSelect from '../../common/components/SearchableSelect.vue'

const router = useRouter()
const toast = inject('toast')
// Reactive data
const drivers = ref([])
const availableVehicles = ref([])
const loading = ref(true)
const loadingVehicles = ref(false)
const searchQuery = ref('')
const activeFilter = ref('all')
const suspendedFilter = ref('all')
const vehicleAssignedFilter = ref('all')
// Modal states
const showDriverModal = ref(false)
const showViewModal = ref(false)
const showAssignVehicleModal = ref(false)
const showUnassignVehicleModal = ref(false)
const showSuspendModal = ref(false)
const showReactivateModal = ref(false)
const isEditing = ref(false)
const currentDriver = ref(null)
const isSaving = ref(false)
const isUnassigning = ref(false)
const suspensionReason = ref('')
// Image preview states
const newImagePreview = ref(null)
// Dropdown state
const activeDropdown = ref(null)
// Form data
const driverForm = ref({
first_name: '',
last_name: '',
phone: '',
email: '',
date_of_birth: '',
notes: '',
avatar: null
})
const assignmentForm = ref({
vehicle_id: '',
notes: ''
})

// Computed properties
const filteredDrivers = computed(() => {
return drivers.value.filter(driver => {
const matchesSearch = !searchQuery.value ||
driver.first_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
driver.last_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
driver.phone.includes(searchQuery.value) ||
 (driver.email && driver.email.toLowerCase().includes(searchQuery.value.toLowerCase()))
const matchesActive = activeFilter.value === 'all' ||
 (activeFilter.value === 'active' && driver.is_active) ||
 (activeFilter.value === 'inactive' && !driver.is_active)
const matchesSuspended = suspendedFilter.value === 'all' ||
 (suspendedFilter.value === 'true' && driver.is_suspended) ||
 (suspendedFilter.value === 'false' && !driver.is_suspended)
const matchesVehicleAssigned = vehicleAssignedFilter.value === 'all' ||
 (vehicleAssignedFilter.value === 'true' && driver.current_vehicle) ||
 (vehicleAssignedFilter.value === 'false' && !driver.current_vehicle)
return matchesSearch && matchesActive && matchesSuspended && matchesVehicleAssigned
 })
})
const hasActiveFilters = computed(() => {
return searchQuery.value || activeFilter.value !== 'all' ||
suspendedFilter.value !== 'all' || vehicleAssignedFilter.value !== 'all'
})

// Convert available vehicles to SearchableSelect format
const vehicleOptions = computed(() => {
return availableVehicles.value.map(vehicle => ({
value: vehicle.id,
label: `${vehicle.registration_number} (${vehicle.vehicle_type})`,
searchText: `${vehicle.registration_number} ${vehicle.make || ''} ${vehicle.model || ''} ${vehicle.vehicle_type} ${vehicle.vehicle_color || ''}`.toLowerCase()
}))
})

// Get selected vehicle details for preview
const selectedVehiclePreview = computed(() => {
if (!assignmentForm.value.vehicle_id) return null
return availableVehicles.value.find(vehicle => vehicle.id == assignmentForm.value.vehicle_id)
})

// Watch for changes to selected vehicle to show preview
watch(() => assignmentForm.value.vehicle_id, (newVehicleId) => {
if (newVehicleId) {
console.log('Selected vehicle:', selectedVehiclePreview.value)
}
})

// Methods
const loadDrivers = async () => {
loading.value = true
try {
const response = await fetch('/api/drivers', {
headers: {
'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
'Content-Type': 'application/json'
 }
 })
if (response.ok) {
const data = await response.json()
drivers.value = data.data || data
 } else {
console.error('Failed to load drivers')
 }
 } catch (error) {
console.error('Error loading drivers:', error)
 }
loading.value = false
}
const loadAvailableVehicles = async () => {
loadingVehicles.value = true
try {
const response = await fetch('/api/vehicles/available', {
headers: {
'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
'Content-Type': 'application/json'
 }
 })
if (response.ok) {
const data = await response.json()
availableVehicles.value = data.data || []
 }
 } catch (error) {
console.error('Error loading available vehicles:', error)
toast.showError('Failed to load available vehicles')
 }
loadingVehicles.value = false
}
// Helper function to format date for HTML date input
const formatDateForInput = (dateString) => {
if (!dateString) return ''
// Handle different date formats
const date = new Date(dateString)
// Check if date is valid
if (isNaN(date.getTime())) return ''
// Return in YYYY-MM-DD format
return date.toISOString().split('T')[0]
}
const getStatusBadgeColor = (driver) => {
if (driver.is_suspended) return 'badge-danger'
if (!driver.is_active) return 'badge-secondary'
return 'badge-success'
}
const getStatusLabel = (driver) => {
if (driver.is_suspended) return 'Suspended'
if (!driver.is_active) return 'Inactive'
return 'Active'
}
const getVehicleTypeBadgeColor = (type) => {
const colorMap = {
'ambulance': 'badge-danger',
'regular': 'badge-primary'
}
return colorMap[type] || 'badge-secondary'
}
const capitalizeFirst = (str) => {
return str ? str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ') : ''
}
const formatDate = (dateString) => {
if (!dateString) return 'N/A'
return new Date(dateString).toLocaleDateString()
}
const toggleDropdown = (driverId) => {
activeDropdown.value = activeDropdown.value === driverId ? null : driverId
}
const openCreateModal = () => {
isEditing.value = false
currentDriver.value = null
resetForm()
showDriverModal.value = true
}
const openEditModal = (driver) => {
isEditing.value = true
currentDriver.value = driver
populateForm(driver)
showDriverModal.value = true
activeDropdown.value = null
}
const openViewModal = (driver) => {
currentDriver.value = driver
showViewModal.value = true
activeDropdown.value = null
}
const openSuspendModal = (driver) => {
currentDriver.value = driver
suspensionReason.value = ''
showSuspendModal.value = true
activeDropdown.value = null
}
const openReactivateModal = (driver) => {
currentDriver.value = driver
showReactivateModal.value = true
activeDropdown.value = null
}
const openAssignVehicleModal = async (driver) => {
currentDriver.value = driver
assignmentForm.value = { vehicle_id: '', notes: '' }
showAssignVehicleModal.value = true
await loadAvailableVehicles()
activeDropdown.value = null
}

const openUnassignVehicleModal = (driver) => {
currentDriver.value = driver
showUnassignVehicleModal.value = true
activeDropdown.value = null
}

const closeDriverModal = () => {
showDriverModal.value = false
clearImagePreview()
}
const closeViewModal = () => {
showViewModal.value = false
}
const closeSuspendModal = () => {
showSuspendModal.value = false
}
const closeReactivateModal = () => {
showReactivateModal.value = false
}
const closeAssignVehicleModal = () => {
showAssignVehicleModal.value = false
}

const closeUnassignVehicleModal = () => {
showUnassignVehicleModal.value = false
currentDriver.value = null
}

const editFromView = () => {
closeViewModal()
openEditModal(currentDriver.value)
}
const resetForm = () => {
driverForm.value = {
first_name: '',
last_name: '',
phone: '',
email: '',
date_of_birth: '',
notes: '',
avatar: null
 }
clearImagePreview()
}
const populateForm = (driver) => {
driverForm.value = {
first_name: driver.first_name,
last_name: driver.last_name,
phone: driver.phone,
email: driver.email || '',
date_of_birth: formatDateForInput(driver.date_of_birth), // Fixed: properly format date
notes: driver.notes || '',
avatar: null
 }
clearImagePreview()
}
const handleAvatarChange = (event) => {
const file = event.target.files[0]
if (file) {
// Validate file type and size
const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']
const maxSize = 2 * 1024 * 1024 // 2MB
if (!allowedTypes.includes(file.type)) {
toast.showError('Please select a valid image file (JPEG, PNG, JPG, WEBP)')
event.target.value = '' // Clear the input
return
 }
if (file.size > maxSize) {
toast.showError('Image size must be less than 2MB')
event.target.value = '' // Clear the input
return
 }
driverForm.value.avatar = file
// Create preview
const reader = new FileReader()
reader.onload = (e) => {
newImagePreview.value = e.target.result
 }
reader.readAsDataURL(file)
 } else {
clearImagePreview()
 }
}
const clearImagePreview = () => {
newImagePreview.value = null
if (driverForm.value) {
driverForm.value.avatar = null
 }
// Clear the file input
if (document.querySelector('input[type="file"]')) {
document.querySelector('input[type="file"]').value = ''
 }
}
const saveDriver = async () => {
isSaving.value = true
try {
const formData = new FormData()
// Append text fields
const textFields = ['first_name', 'last_name', 'phone', 'email', 'date_of_birth', 'notes']
textFields.forEach(field => {
if (driverForm.value[field] !== null && driverForm.value[field] !== '') {
formData.append(field, driverForm.value[field])
 }
 })
// Handle file upload separately
if (driverForm.value.avatar && driverForm.value.avatar instanceof File) {
formData.append('avatar', driverForm.value.avatar)
console.log('Avatar file added to FormData:', driverForm.value.avatar.name) // Debug log
 }
// For editing, Laravel needs _method=PUT with POST request when using FormData
if (isEditing.value) {
formData.append('_method', 'PUT')
 }
// Debug: Log FormData contents
console.log('FormData contents:')
for (let [key, value] of formData.entries()) {
console.log(`${key}:`, value)
 }
const url = isEditing.value ? `/api/drivers/${currentDriver.value.id}` : '/api/drivers'
const response = await fetch(url, {
method: 'POST', // Always use POST for FormData, even when editing
headers: {
'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
// Don't set Content-Type for FormData - browser will set it with boundary
 },
body: formData
 })
if (response.ok) {
await loadDrivers()
closeDriverModal()
toast.showSuccess(isEditing.value ? 'Driver updated successfully!' : 'Driver added successfully!')
 } else {
// Handle different response types
let errorData
const contentType = response.headers.get('content-type')
if (contentType && contentType.includes('application/json')) {
errorData = await response.json()
 } else {
// If response is not JSON, get text
const errorText = await response.text()
console.error('Non-JSON error response:', errorText)
errorData = { message: 'Server error occurred' }
 }
console.error('Failed to save driver:', errorData)
if (errorData.errors) {
const firstError = Object.values(errorData.errors)[0][0]
toast.showError(firstError)
 } else {
toast.showError(errorData.message || 'Failed to save driver. Please try again.')
 }
 }
 } catch (error) {
console.error('Error saving driver:', error)
toast.showError('An error occurred while saving the driver.')
 }
isSaving.value = false
}
const assignVehicle = async () => {
isSaving.value = true
try {
const response = await fetch(`/api/drivers/${currentDriver.value.id}/assign-vehicle`, {
method: 'POST',
headers: {
'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
'Content-Type': 'application/json'
 },
body: JSON.stringify(assignmentForm.value)
 })
if (response.ok) {
await loadDrivers()
closeAssignVehicleModal()
toast.showSuccess('Vehicle assigned successfully!')
 } else {
const errorData = await response.json()
toast.showError(errorData.message || 'Failed to assign vehicle. Please try again.')
 }
 } catch (error) {
console.error('Error assigning vehicle:', error)
toast.showError('An error occurred while assigning vehicle.')
 }
isSaving.value = false
}

const unassignVehicle = async () => {
isUnassigning.value = true
  
try {
const response = await fetch(`/api/drivers/${currentDriver.value.id}/unassign-vehicle`, {
method: 'POST',
headers: {
'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
'Content-Type': 'application/json'
}
})
    
if (response.ok) {
await loadDrivers()
closeUnassignVehicleModal()
toast.showSuccess('Vehicle unassigned successfully!')
} else {
const errorData = await response.json()
toast.showError(errorData.message || 'Failed to unassign vehicle.')
}
} catch (error) {
console.error('Error unassigning vehicle:', error)
toast.showError('An error occurred while unassigning vehicle.')
}
  
isUnassigning.value = false
}

const suspendDriver = async () => {
isSaving.value = true
try {
const response = await fetch(`/api/drivers/${currentDriver.value.id}/suspend`, {
method: 'POST',
headers: {
'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
'Content-Type': 'application/json'
 },
body: JSON.stringify({ reason: suspensionReason.value })
 })
if (response.ok) {
await loadDrivers()
closeSuspendModal()
toast.showSuccess('Driver suspended successfully!')
 } else {
const errorData = await response.json()
toast.showError(errorData.message || 'Failed to suspend driver. Please try again.')
 }
 } catch (error) {
console.error('Error suspending driver:', error)
toast.showError('An error occurred while suspending the driver.')
 }
isSaving.value = false
}
const reactivateDriver = async () => {
isSaving.value = true
try {
const response = await fetch(`/api/drivers/${currentDriver.value.id}/reactivate`, {
method: 'POST',
headers: {
'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
'Content-Type': 'application/json'
 }
 })
if (response.ok) {
await loadDrivers()
closeReactivateModal()
toast.showSuccess('Driver reactivated successfully!')
 } else {
const errorData = await response.json()
toast.showError(errorData.message || 'Failed to reactivate driver.')
 }
 } catch (error) {
console.error('Error reactivating driver:', error)
toast.showError('An error occurred while reactivating the driver.')
 }
isSaving.value = false
}
const exportDrivers = async () => {
try {
const params = new URLSearchParams()
if (activeFilter.value !== 'all') {
params.append('active', activeFilter.value)
 }
if (suspendedFilter.value !== 'all') {
params.append('suspended', suspendedFilter.value)
 }
if (searchQuery.value) {
params.append('search', searchQuery.value)
 }
const queryString = params.toString()
const url = `/api/drivers/export${queryString ? '?' + queryString : ''}`
const response = await fetch(url, {
method: 'GET',
headers: {
'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
 }
 })
if (response.ok) {
const contentDisposition = response.headers.get('Content-Disposition')
let filename = 'drivers_export.csv'
if (contentDisposition) {
const filenameMatch = contentDisposition.match(/filename=(.+)/)
if (filenameMatch) {
filename = filenameMatch[1]
 }
 }
const blob = await response.blob()
const downloadUrl = window.URL.createObjectURL(blob)
const link = document.createElement('a')
link.href = downloadUrl
link.download = filename
document.body.appendChild(link)
link.click()
link.remove()
window.URL.revokeObjectURL(downloadUrl)
toast.showSuccess('Drivers exported successfully!')
 } else {
const errorData = await response.json()
console.error('Failed to export drivers:', errorData)
toast.showError(errorData.message || 'Failed to export drivers. Please try again.')
 }
 } catch (error) {
console.error('Error exporting drivers:', error)
toast.showError('An error occurred while exporting drivers.')
 }
}
// Close dropdown when clicking outside
const handleClickOutside = (event) => {
if (!event.target.closest('.action-dropdown')) {
activeDropdown.value = null
 }
}
// Lifecycle
onMounted(() => {
loadDrivers()
document.addEventListener('click', handleClickOutside)
})
onUnmounted(() => {
document.removeEventListener('click', handleClickOutside)
})
// Watch for filter changes and reload data
watch([activeFilter, suspendedFilter, vehicleAssignedFilter], () => {
// Filters are applied client-side, no need to reload
})
</script>
<style scoped>
/* Driver specific styles */
.drivers-page {
min-height: 100vh;
background: #f8f9fa;
}
.drivers-table-container {
background: white;
border-radius: 0.75rem;
box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
border: 1px solid #e5e7eb;
overflow: visible;
}
.drivers-table {
width: 100%;
border-collapse: collapse;
}
.drivers-table thead {
background: #f9fafb;
}
.drivers-table th {
padding: 0.75rem 1rem;
text-align: left;
font-size: 0.75rem;
font-weight: 500;
color: #6b7280;
text-transform: uppercase;
letter-spacing: 0.05em;
border-bottom: 1px solid #e5e7eb;
}
.drivers-table tbody tr:hover {
background: #f9fafb;
}
.drivers-table td {
padding: 1rem;
white-space: nowrap;
font-size: 0.875rem;
border-bottom: 1px solid #e5e7eb;
}
/* Driver Info */
.driver-info {
display: flex;
align-items: center;
}
.driver-avatar {
flex-shrink: 0;
width: 2.5rem;
height: 2.5rem;
}
.driver-avatar img {
width: 2.5rem;
height: 2.5rem;
border-radius: 50%;
object-fit: cover;
}
.driver-details {
margin-left: 1rem;
}
.driver-name {
font-weight: 500;
color: #1f2937;
}
.driver-id {
color: #6b7280;
font-size: 0.875rem;
}
/* Vehicle Assignment */
.vehicle-assignment .assigned-vehicle .vehicle-number {
font-weight: 500;
color: #1f2937;
margin-bottom: 0.25rem;
}
.no-vehicle {
color: #6b7280;
font-style: italic;
}
/* Performance Info */

.rating {
margin-bottom: 0.25rem;
}
.rating-score {
font-weight: 600;
color: #1f2937;
margin-right: 0.5rem;
}
.rating-stars {
display: inline-block;
}
.star {
color: #d1d5db;
font-size: 0.875rem;
}
.star.filled {
color: #fbbf24;
}
.trips-count {
color: #6b7280;
font-size: 0.875rem;
}
/* Image Preview Styles */
.current-image-preview,
.new-image-preview {
margin-bottom: 1rem;
padding: 1rem;
background: #f9fafb;
border: 1px solid #e5e7eb;
border-radius: 0.5rem;
text-align: center;
}
.current-avatar,
.preview-avatar {
width: 80px;
height: 80px;
border-radius: 50%;
object-fit: cover;
border: 2px solid #e5e7eb;
margin-bottom: 0.5rem;
}
.image-help-text {
font-size: 0.875rem;
color: #6b7280;
margin: 0.5rem 0;
}
.new-image-preview button {
margin-top: 0.5rem;
}
/* Driver Profile Card */
.driver-profile-card {
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
border-radius: 0.75rem;
padding: 2rem;
margin-bottom: 2rem;
color: white;
}
.driver-header {
display: flex;
align-items: center;
gap: 1.5rem;
}
.driver-avatar-large img {
width: 80px;
height: 80px;
border-radius: 50%;
border: 4px solid rgba(255, 255, 255, 0.3);
}
.driver-info-details {
flex: 1;
}
.driver-name-large {
font-size: 1.75rem;
font-weight: 700;
margin: 0 0 0.5rem 0;
}
.driver-meta {
display: flex;
gap: 1rem;
margin-bottom: 0.75rem;
font-size: 0.875rem;
opacity: 0.9;
}
.meta-item {
background: rgba(255, 255, 255, 0.2);
padding: 0.25rem 0.75rem;
border-radius: 1rem;
}
.performance-meta {
display: flex;
flex-direction: column;
gap: 0.25rem;
font-size: 0.875rem;
opacity: 0.9;
}
.status-indicators {
flex-shrink: 0;
display: flex;
flex-direction: column;
gap: 0.5rem;
}
/* Driver Details Sections */
.driver-details-sections {
 space-y: 1.5rem;
}
.details-section {
background: white;
border: 1px solid #e5e7eb;
border-radius: 0.75rem;
padding: 1.5rem;
margin-bottom: 1.5rem;
}
.section-title {
font-size: 1.125rem;
font-weight: 600;
color: #1f2937;
margin: 0 0 1rem 0;
}
.details-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
gap: 1rem;
}
.detail-item {
display: flex;
flex-direction: column;
gap: 0.25rem;
}
.detail-item label {
font-weight: 500;
color: #6b7280;
font-size: 0.875rem;
}
.detail-item span {
color: #1f2937;
}
/* Vehicle Assignment Info */
.assigned-vehicle-card {
background: #f8fafc;
border: 1px solid #e2e8f0;
border-radius: 0.5rem;
padding: 1rem;
display: flex;
justify-content: space-between;
align-items: center;
}
.vehicle-details h5 {
font-weight: 600;
color: #1f2937;
margin: 0 0 0.25rem 0;
}
.vehicle-details p {
color: #6b7280;
margin: 0;
font-size: 0.875rem;
}
/* Performance Grid */
.performance-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
gap: 1rem;
}
.metric-card {
background: #f8fafc;
border: 1px solid #e2e8f0;
border-radius: 0.5rem;
padding: 1rem;
text-align: center;
}
.metric-value {
font-size: 2rem;
font-weight: 700;
color: #1f2937;
margin-bottom: 0.25rem;
}
.metric-label {
font-size: 0.875rem;
color: #6b7280;
text-transform: uppercase;
letter-spacing: 0.05em;
}

/* Assignment Modal Styles */
.driver-assignment-info {
margin-bottom: 1.5rem;
}

.assignment-header h4 {
margin: 0 0 1rem 0;
color: #1f2937;
font-size: 1.125rem;
font-weight: 600;
}

.driver-card {
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
border-radius: 0.75rem;
padding: 1.5rem;
color: white;
}

.driver-info .driver-name {
display: block;
font-size: 1.25rem;
font-weight: 700;
margin-bottom: 0.5rem;
}

.driver-info .driver-details {
display: block;
font-size: 1rem;
opacity: 0.9;
margin-bottom: 0.75rem;
}

/* Selected Vehicle Preview */
.selected-vehicle-preview {
margin-top: 1.5rem;
padding-top: 1.5rem;
border-top: 1px solid #e5e7eb;
}

.selected-vehicle-preview h5 {
margin: 0 0 1rem 0;
color: #1f2937;
font-size: 1rem;
font-weight: 600;
}

.vehicle-preview-card {
background: #f8fafc;
border: 1px solid #e2e8f0;
border-radius: 0.75rem;
padding: 1.5rem;
display: flex;
justify-content: space-between;
align-items: center;
}

.vehicle-info .vehicle-reg {
font-size: 1.125rem;
font-weight: 600;
color: #1f2937;
margin-bottom: 0.5rem;
}

.vehicle-details {
display: flex;
flex-direction: column;
gap: 0.25rem;
margin-bottom: 0.75rem;
}

.vehicle-details span {
color: #6b7280;
font-size: 0.875rem;
}

.vehicle-stats {
display: flex;
gap: 2rem;
align-items: center;
text-align: center;
}

.stat-item {
display: flex;
flex-direction: column;
}

.stat-value {
font-size: 1.5rem;
font-weight: 700;
color: #1f2937;
line-height: 1;
}

.stat-label {
font-size: 0.75rem;
color: #6b7280;
text-transform: uppercase;
letter-spacing: 0.05em;
margin-top: 0.25rem;
}

/* Unassign Modal Styles */
.unassign-confirmation-content .confirmation-message {
font-size: 1rem;
color: #374151;
margin-bottom: 1.5rem;
line-height: 1.6;
}

.assignment-details {
background: #f9fafb;
border: 1px solid #e5e7eb;
border-radius: 0.75rem;
padding: 1.5rem;
margin-bottom: 1.5rem;
}

.detail-section {
margin-bottom: 1.5rem;
}

.detail-section:last-child {
margin-bottom: 0;
}

.detail-section h5 {
font-size: 0.875rem;
font-weight: 600;
color: #6b7280;
text-transform: uppercase;
letter-spacing: 0.05em;
margin: 0 0 0.75rem 0;
}

.driver-summary {
display: flex;
flex-direction: column;
gap: 0.5rem;
}

.driver-summary .driver-name {
font-size: 1.125rem;
font-weight: 700;
color: #1f2937;
}

.driver-contact {
display: flex;
flex-direction: column;
gap: 0.25rem;
}

.driver-contact span {
color: #6b7280;
font-size: 0.875rem;
}

.driver-id {
color: #6b7280;
font-size: 0.8125rem;
}

.vehicle-summary {
display: flex;
flex-direction: column;
gap: 0.5rem;
}

.vehicle-summary .vehicle-reg {
font-size: 1.125rem;
font-weight: 700;
color: #1f2937;
}

.vehicle-summary .vehicle-info {
color: #6b7280;
font-size: 0.875rem;
}

.warning-note {
display: flex;
align-items: flex-start;
gap: 0.75rem;
background: #fef3c7;
border: 1px solid #fbbf24;
border-radius: 0.5rem;
padding: 1rem;
}

.warning-icon {
width: 1.25rem;
height: 1.25rem;
color: #d97706;
flex-shrink: 0;
margin-top: 0.125rem;
}

.warning-note p {
margin: 0;
color: #92400e;
font-size: 0.875rem;
line-height: 1.5;
}

/* Responsive Design */
@media (max-width: 768px) {
.drivers-table {
font-size: 0.8125rem;
 }
.drivers-table th,
.drivers-table td {
padding: 0.75rem 0.5rem;
 }
.driver-header {
flex-direction: column;
text-align: center;
gap: 1rem;
 }
.driver-meta {
flex-direction: column;
gap: 0.5rem;
 }
.status-indicators {
flex-direction: row;
justify-content: center;
 }
.details-grid {
grid-template-columns: 1fr;
 }
.performance-grid {
grid-template-columns: repeat(2, 1fr);
 }

.vehicle-preview-card {
flex-direction: column;
gap: 1rem;
align-items: flex-start;
}

.vehicle-stats {
align-self: stretch;
justify-content: space-around;
}
}
@media (max-width: 640px) {
.drivers-table-container {
overflow-x: auto;
 }
.drivers-table {
min-width: 800px;
 }
.performance-grid {
grid-template-columns: 1fr;
 }

.vehicle-stats {
gap: 1rem;
}
}
</style>