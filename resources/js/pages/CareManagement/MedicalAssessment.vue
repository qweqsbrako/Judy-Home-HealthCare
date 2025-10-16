<template>
  <MainLayout>
    <div class="medical-assessment-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Medical Assessments</h1>
            <p>Patient admission registration and initial medical assessment</p>
          </div>
          <div class="page-header-actions">
            <button
              @click="exportAssessments"
              class="btn btn-secondary"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export
            </button>
            <button
              @click="openCreateModal"
              class="btn btn-primary"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              New Assessment
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
                placeholder="Search by patient name, nurse, or condition..."
                v-model="searchQuery"
                class="search-input"
              />
            </div>
            <div class="filters-group">
              <select
                v-model="patientFilter"
                class="filter-select"
              >
                <option value="all">All Patients</option>
                <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                  {{ patient.first_name }} {{ patient.last_name }}
                </option>
              </select>
              <select
                v-model="nurseFilter"
                class="filter-select"
              >
                <option value="all">All Nurses</option>
                <option v-for="nurse in nurses" :key="nurse.id" :value="nurse.id">
                  {{ nurse.first_name }} {{ nurse.last_name }}
                </option>
              </select>
              <select
                v-model="conditionFilter"
                class="filter-select"
              >
                <option value="all">All Conditions</option>
                <option value="stable">Stable</option>
                <option value="unstable">Unstable</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p class="loading-text">Loading medical assessments...</p>
        </div>

        <!-- Assessments Table -->
        <div v-else class="assessments-table-container">
          <div class="overflow-x-auto">
            <table class="assessments-table">
              <thead>
                <tr>
                  <th>Patient</th>
                  <th>Nurse</th>
                  <th>Assessment Date</th>
                  <th>General Condition</th>
                  <th>Risk Level</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="assessment in filteredAssessments" :key="assessment.id">
                  <td>
                    <div class="patient-info">
                      <div class="patient-avatar">
                        <img :src="getPatientAvatar(assessment.patient)" :alt="assessment.patient_name" />
                      </div>
                      <div class="patient-details">
                        <div class="patient-name">{{ assessment.patient_name }}</div>
                        <div class="patient-id">{{ assessment.patient?.ghana_card_number || 'N/A' }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="nurse-info">
                      <div class="nurse-name">{{ assessment.nurse_name }}</div>
                      <div class="nurse-license">{{ assessment.nurse?.license_number || 'N/A' }}</div>
                    </div>
                  </td>
                  <td>
                    <div class="assessment-date">
                      <div class="date">{{ formatDate(assessment.created_at) }}</div>
                      <div class="time">{{ formatTime(assessment.created_at) }}</div>
                    </div>
                  </td>
                  <td>
                    <span :class="'badge ' + getConditionBadgeColor(assessment.general_condition)">
                      {{ capitalizeFirst(assessment.general_condition) }}
                    </span>
                  </td>
                  <td>
                    <span :class="'badge ' + getRiskBadgeColor(assessment.risk_level)">
                      {{ capitalizeFirst(assessment.risk_level) }} Risk
                    </span>
                  </td>
                  <td>
                    <div class="action-dropdown">
                      <button
                        @click="toggleDropdown(assessment.id)"
                        class="btn btn-secondary btn-sm"
                        style="min-width: auto; padding: 0.5rem;"
                      >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                      </button>
                      <div v-show="activeDropdown === assessment.id" class="dropdown-menu">
                        <button
                          @click="openViewModal(assessment)"
                          class="dropdown-item"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                          View Assessment
                        </button>
                        <button
                          @click="openEditModal(assessment)"
                          class="dropdown-item"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                          Edit Assessment
                        </button>
                        <!-- <button
                          v-if="assessment.assessment_status === 'completed'"
                          @click="markReviewed(assessment)"
                          class="dropdown-item dropdown-item-success"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          Mark Reviewed
                        </button> -->
                        <div class="dropdown-divider"></div>
                        <button
                          @click="openDeleteModal(assessment)"
                          class="dropdown-item dropdown-item-danger"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                          Delete Assessment
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div v-if="filteredAssessments.length === 0" class="empty-state">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3>No medical assessments found</h3>
            <p>
              {{ (searchQuery || patientFilter !== 'all' || nurseFilter !== 'all' || conditionFilter !== 'all') 
                ? 'Try adjusting your search or filters.' 
                : 'Get started by creating a new medical assessment.' }}
            </p>
          </div>
        </div>

        <!-- Create/Edit Assessment Modal -->
        <div v-if="showAssessmentModal" class="modal-overlay">
          <div class="modal modal-xl">
            <div class="modal-header">
              <h2 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ isEditing ? 'Edit Medical Assessment' : 'Client Admission & Assessment Form' }}
              </h2>
              <button @click="closeAssessmentModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="saveAssessment" id="assessmentForm">
              <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                <div class="form-grid">
                  
                  <!-- Patient Selection -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">👤 Patient Information</h3>
                  </div>

                  <div class="form-grid-full">
                    <label class="checkbox-label">
                      <input
                        type="checkbox"
                        v-model="assessmentForm.is_new_patient"
                        :disabled="isEditing"
                      />
                      <span class="checkmark"></span>
                      <span class="checkbox-text">New Patient Registration</span>
                    </label>
                    <p class="form-help">
                      {{ assessmentForm.is_new_patient 
                        ? 'Fill in new patient details below' 
                        : 'Select an existing patient' }}
                    </p>
                  </div>

                  <!-- Existing Patient Selection -->
                  <div v-if="!assessmentForm.is_new_patient" class="form-group form-grid-full">
                    <label>Select Existing Patient *</label>
                    <SearchableSelect
                      v-model="assessmentForm.patient_id"
                      :options="patientOptions"
                      placeholder="Search and select a patient..."
                      required
                    />
                  </div>

                  <!-- New Patient Details -->
                  <template v-if="assessmentForm.is_new_patient">
                    <div class="form-group">
                      <label>First Name *</label>
                      <input
                        type="text"
                        v-model="assessmentForm.patient_first_name"
                        required
                      />
                    </div>

                    <div class="form-group">
                      <label>Last Name *</label>
                      <input
                        type="text"
                        v-model="assessmentForm.patient_last_name"
                        required
                      />
                    </div>

                    <div class="form-group">
                      <label>Age *</label>
                      <input
                        type="number"
                        min="0"
                        max="120"
                        v-model="assessmentForm.patient_age"
                        required
                      />
                    </div>

                    <div class="form-group">
                      <label>Gender *</label>
                      <SearchableSelect
                        v-model="assessmentForm.patient_gender"
                        :options="genderOptions"
                        placeholder="Select gender..."
                        required
                      />
                    </div>

                    <div class="form-group">
                      <label>Date of Birth *</label>
                      <input
                        type="date"
                        v-model="assessmentForm.patient_date_of_birth"
                        required
                      />
                    </div>

                    <div class="form-group">
                      <label>Phone Number *</label>
                      <input
                        type="tel"
                        v-model="assessmentForm.patient_phone"
                        required
                      />
                    </div>

                    <div class="form-group form-grid-full">
                      <label>Ghana Card Number *</label>
                      <input
                        type="text"
                        v-model="assessmentForm.patient_ghana_card"
                        placeholder="GHA-123456789-1"
                        required
                      />
                    </div>
                  </template>

                  <!-- Assessment Details -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">🏥 Assessment Information</h3>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Nurse/Caregiver Performing Assessment *</label>
                    <SearchableSelect
                      v-model="assessmentForm.nurse_id"
                      :options="nurseOptions"
                      placeholder="Select nurse or caregiver..."
                      required
                    />
                  </div>

                  <!-- Client Extended Information -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">📍 Extended Client Information</h3>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Physical Address *</label>
                    <textarea
                      v-model="assessmentForm.physical_address"
                      rows="3"
                      placeholder="Complete physical address..."
                      required
                    ></textarea>
                  </div>

                  <div class="form-group">
                    <label>Occupation</label>
                    <input
                      type="text"
                      v-model="assessmentForm.occupation"
                      placeholder="Patient's occupation"
                    />
                  </div>

                    <div class="form-group">
                      <label>Religion</label>
                      <SearchableSelect
                        v-model="assessmentForm.religion"
                        :options="religionOptions"
                        placeholder="Search and select religion..."
                        :allow-custom="true"
                      />
                      <p class="form-help">Type to search religions or add a custom entry</p>
                    </div>

                  <!-- Emergency Contacts -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">🚨 Emergency Contacts</h3>
                  </div>

                  <div class="form-group">
                    <label>Emergency Contact 1 - Name *</label>
                    <input
                      type="text"
                      v-model="assessmentForm.emergency_contact_1_name"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Relationship *</label>
                    <input
                      type="text"
                      v-model="assessmentForm.emergency_contact_1_relationship"
                      placeholder="e.g., Spouse, Child, Parent"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Phone Number *</label>
                    <input
                      type="tel"
                      v-model="assessmentForm.emergency_contact_1_phone"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Emergency Contact 2 - Name</label>
                    <input
                      type="text"
                      v-model="assessmentForm.emergency_contact_2_name"
                    />
                  </div>

                  <div class="form-group">
                    <label>Relationship</label>
                    <input
                      type="text"
                      v-model="assessmentForm.emergency_contact_2_relationship"
                      placeholder="e.g., Sibling, Friend"
                    />
                  </div>

                  <div class="form-group">
                    <label>Phone Number</label>
                    <input
                      type="tel"
                      v-model="assessmentForm.emergency_contact_2_phone"
                    />
                  </div>

                  <!-- Medical History -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">🩺 Medical History</h3>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Presenting Condition/Diagnosis *</label>
                    <textarea
                      v-model="assessmentForm.presenting_condition"
                      rows="3"
                      placeholder="Current medical condition requiring home care..."
                      required
                    ></textarea>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Past Medical History</label>
                    <textarea
                      v-model="assessmentForm.past_medical_history"
                      rows="3"
                      placeholder="Previous medical conditions, surgeries, hospitalizations..."
                    ></textarea>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Allergies</label>
                    <textarea
                      v-model="assessmentForm.allergies"
                      rows="2"
                      placeholder="Food allergies, drug allergies, environmental allergies..."
                    ></textarea>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Current Medications</label>
                    <textarea
                      v-model="assessmentForm.current_medications"
                      rows="3"
                      placeholder="List all current medications with dosages and frequency..."
                    ></textarea>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Special Needs (Mobility, Nutrition, Devices)</label>
                    <textarea
                      v-model="assessmentForm.special_needs"
                      rows="3"
                      placeholder="Wheelchairs, walkers, special diet requirements, medical devices..."
                    ></textarea>
                  </div>

                  <!-- Initial Assessment -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">📋 Initial Assessment</h3>
                  </div>

                  <div class="form-group">
                    <label>General Condition *</label>
                    <SearchableSelect
                      v-model="assessmentForm.general_condition"
                      :options="conditionOptions"
                      placeholder="Select condition..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Hydration Status *</label>
                    <SearchableSelect
                      v-model="assessmentForm.hydration_status"
                      :options="hydrationOptions"
                      placeholder="Select hydration status..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Nutrition Status *</label>
                    <SearchableSelect
                      v-model="assessmentForm.nutrition_status"
                      :options="nutritionOptions"
                      placeholder="Select nutrition status..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Mobility Status *</label>
                    <SearchableSelect
                      v-model="assessmentForm.mobility_status"
                      :options="mobilityOptions"
                      placeholder="Select mobility status..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Pain Level (0-10) *</label>
                    <input
                      type="number"
                      min="0"
                      max="10"
                      v-model="assessmentForm.pain_level"
                      required
                    />
                  </div>

                  <!-- Wound Assessment -->
                  <div class="form-grid-full">
                    <label class="checkbox-label">
                      <input
                        type="checkbox"
                        v-model="assessmentForm.has_wounds"
                      />
                      <span class="checkmark"></span>
                      <span class="checkbox-text">Patient has wounds or ulcers</span>
                    </label>
                  </div>

                  <div v-if="assessmentForm.has_wounds" class="form-group form-grid-full">
                    <label>Wound Description *</label>
                    <textarea
                      v-model="assessmentForm.wound_description"
                      rows="3"
                      placeholder="Describe location, size, condition of wounds..."
                    ></textarea>
                  </div>

                  <!-- Initial Vital Signs -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">🩺 Initial Vital Signs</h3>
                  </div>

                  <div class="form-group">
                    <label>Temperature (°C) *</label>
                    <input
                      type="number"
                      step="0.1"
                      min="30"
                      max="45"
                      v-model="assessmentForm.initial_vitals.temperature"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Pulse (bpm) *</label>
                    <input
                      type="number"
                      min="30"
                      max="200"
                      v-model="assessmentForm.initial_vitals.pulse"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Respiratory Rate (/min) *</label>
                    <input
                      type="number"
                      min="8"
                      max="40"
                      v-model="assessmentForm.initial_vitals.respiratory_rate"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Blood Pressure (mmHg) *</label>
                    <input
                      type="text"
                      v-model="assessmentForm.initial_vitals.blood_pressure"
                      placeholder="120/80"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>SpO₂ (%) *</label>
                    <input
                      type="number"
                      min="70"
                      max="100"
                      v-model="assessmentForm.initial_vitals.spo2"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Weight (kg) *</label>
                    <input
                      type="number"
                      step="0.1"
                      min="1"
                      max="500"
                      v-model="assessmentForm.initial_vitals.weight"
                      required
                    />
                  </div>

                  <!-- Nursing Impression -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">📝 Initial Nursing Impression</h3>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Nursing Impression *</label>
                    <textarea
                      v-model="assessmentForm.initial_nursing_impression"
                      rows="4"
                      placeholder="Provide detailed nursing assessment and initial care plan recommendations..."
                      required
                    ></textarea>
                    <p class="form-help">Minimum 50 characters required</p>
                  </div>
                </div>
              </div>

              <div class="modal-actions">
                <button type="button" @click="closeAssessmentModal" class="btn btn-secondary">
                  Cancel
                </button>
                <button 
                  type="submit" 
                  form="assessmentForm" 
                  :disabled="isSaving" 
                  class="btn btn-primary"
                >
                  <div v-if="isSaving" class="spinner spinner-sm"></div>
                  {{ isEditing ? 'Update Assessment' : 'Complete Assessment' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- View Assessment Modal -->
        <div v-if="showViewModal && currentAssessment" class="modal-overlay">
          <div class="modal modal-xl">
            <div class="modal-header">
              <h2 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Medical Assessment - {{ currentAssessment.patient_name }}
              </h2>
              <button @click="closeViewModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
              <div class="assessment-view">
                <!-- Patient Information Card -->
                <div class="patient-info-card">
                  <div class="patient-header">
                    <div class="patient-avatar-large">
                      <img :src="getPatientAvatar(currentAssessment.patient)" :alt="currentAssessment.patient_name" />
                    </div>
                    <div class="patient-info-details">
                      <h3 class="patient-name-large">{{ currentAssessment.patient_name }}</h3>
                      <div class="patient-meta">
                        <span class="meta-item">{{ currentAssessment.patient?.ghana_card_number }}</span>
                        <span class="meta-item">{{ calculateAge(currentAssessment.patient?.date_of_birth) }} years old</span>
                        <span class="meta-item">{{ capitalizeFirst(currentAssessment.patient?.gender) }}</span>
                      </div>
                      <div class="assessment-meta">
                        <span>Assessed by: <strong>{{ currentAssessment.nurse_name }}</strong></span>
                        <span>Date: <strong>{{ formatDate(currentAssessment.created_at) }}</strong></span>
                      </div>
                    </div>
                    <div class="risk-indicator">
                      <span :class="'badge badge-lg ' + getRiskBadgeColor(currentAssessment.risk_level)">
                        {{ capitalizeFirst(currentAssessment.risk_level) }} Risk
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Assessment Sections -->
                <div class="assessment-sections">
                  <!-- Contact Information -->
                  <div class="assessment-section">
                    <h4 class="section-title">📍 Contact Information</h4>
                    <div class="section-content">
                      <div class="info-grid">
                        <div class="info-item">
                          <label>Physical Address:</label>
                          <p>{{ currentAssessment.physical_address }}</p>
                        </div>
                        <div class="info-item">
                          <label>Phone:</label>
                          <p>{{ currentAssessment.patient?.phone }}</p>
                        </div>
                        <div class="info-item">
                          <label>Occupation:</label>
                          <p>{{ currentAssessment.occupation || 'Not provided' }}</p>
                        </div>
                        <div class="info-item">
                          <label>Religion:</label>
                          <p>{{ currentAssessment.religion || 'Not provided' }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Emergency Contacts -->
                  <div class="assessment-section">
                    <h4 class="section-title">🚨 Emergency Contacts</h4>
                    <div class="section-content">
                      <div class="emergency-contacts">
                        <div class="emergency-contact">
                          <div class="contact-header">Primary Contact</div>
                          <div class="contact-details">
                            <strong>{{ currentAssessment.emergency_contact_1_name }}</strong>
                            <span>{{ currentAssessment.emergency_contact_1_relationship }}</span>
                            <span>{{ currentAssessment.emergency_contact_1_phone }}</span>
                          </div>
                        </div>
                        <div v-if="currentAssessment.emergency_contact_2_name" class="emergency-contact">
                          <div class="contact-header">Secondary Contact</div>
                          <div class="contact-details">
                            <strong>{{ currentAssessment.emergency_contact_2_name }}</strong>
                            <span>{{ currentAssessment.emergency_contact_2_relationship }}</span>
                            <span>{{ currentAssessment.emergency_contact_2_phone }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Medical History -->
                  <div class="assessment-section">
                    <h4 class="section-title">🩺 Medical History</h4>
                    <div class="section-content">
                      <div class="medical-history">
                        <div class="history-item">
                          <label>Presenting Condition:</label>
                          <p>{{ currentAssessment.presenting_condition }}</p>
                        </div>
                        <div v-if="currentAssessment.past_medical_history" class="history-item">
                          <label>Past Medical History:</label>
                          <p>{{ currentAssessment.past_medical_history }}</p>
                        </div>
                        <div v-if="currentAssessment.allergies" class="history-item">
                          <label>Allergies:</label>
                          <p>{{ currentAssessment.allergies }}</p>
                        </div>
                        <div v-if="currentAssessment.current_medications" class="history-item">
                          <label>Current Medications:</label>
                          <p>{{ currentAssessment.current_medications }}</p>
                        </div>
                        <div v-if="currentAssessment.special_needs" class="history-item">
                          <label>Special Needs:</label>
                          <p>{{ currentAssessment.special_needs }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Assessment Results -->
                  <div class="assessment-section">
                    <h4 class="section-title">📋 Assessment Results</h4>
                    <div class="section-content">
                      <div class="assessment-results">
                        <div class="result-item">
                          <label>General Condition:</label>
                          <span :class="'badge ' + getConditionBadgeColor(currentAssessment.general_condition)">
                            {{ capitalizeFirst(currentAssessment.general_condition) }}
                          </span>
                        </div>
                        <div class="result-item">
                          <label>Hydration:</label>
                          <span class="result-value">{{ capitalizeFirst(currentAssessment.hydration_status) }}</span>
                        </div>
                        <div class="result-item">
                          <label>Nutrition:</label>
                          <span class="result-value">{{ capitalizeFirst(currentAssessment.nutrition_status) }}</span>
                        </div>
                        <div class="result-item">
                          <label>Mobility:</label>
                          <span class="result-value">{{ capitalizeFirst(currentAssessment.mobility_status) }}</span>
                        </div>
                        <div class="result-item">
                          <label>Pain Level:</label>
                          <span class="result-value">{{ currentAssessment.pain_level }}/10</span>
                        </div>
                        <div class="result-item">
                          <label>Wounds/Ulcers:</label>
                          <span class="result-value">{{ currentAssessment.has_wounds ? 'Yes' : 'No' }}</span>
                        </div>
                      </div>
                      <div v-if="currentAssessment.wound_description" class="wound-description">
                        <label>Wound Description:</label>
                        <p>{{ currentAssessment.wound_description }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Vital Signs -->
                  <div class="assessment-section">
                    <h4 class="section-title">🩺 Initial Vital Signs</h4>
                    <div class="section-content">
                      <div class="vitals-grid">
                        <div class="vital-card">
                          <div class="vital-label">Temperature</div>
                          <div class="vital-value">{{ currentAssessment.initial_vitals?.temperature }}°C</div>
                        </div>
                        <div class="vital-card">
                          <div class="vital-label">Pulse</div>
                          <div class="vital-value">{{ currentAssessment.initial_vitals?.pulse }} bpm</div>
                        </div>
                        <div class="vital-card">
                          <div class="vital-label">Respiration</div>
                          <div class="vital-value">{{ currentAssessment.initial_vitals?.respiratory_rate }} /min</div>
                        </div>
                        <div class="vital-card">
                          <div class="vital-label">Blood Pressure</div>
                          <div class="vital-value">{{ currentAssessment.initial_vitals?.blood_pressure }} mmHg</div>
                        </div>
                        <div class="vital-card">
                          <div class="vital-label">SpO₂</div>
                          <div class="vital-value">{{ currentAssessment.initial_vitals?.spo2 }}%</div>
                        </div>
                        <div class="vital-card">
                          <div class="vital-label">Weight</div>
                          <div class="vital-value">{{ currentAssessment.initial_vitals?.weight }} kg</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Nursing Impression -->
                  <div class="assessment-section">
                    <h4 class="section-title">📝 Nursing Impression</h4>
                    <div class="section-content">
                      <div class="nursing-impression">
                        <p>{{ currentAssessment.initial_nursing_impression }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button @click="closeViewModal" class="btn btn-secondary">
                Close
              </button>
              <button @click="printAssessment" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print
              </button>
              <button @click="editFromView" class="btn btn-primary">
                Edit Assessment
              </button>
            </div>
          </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal && currentAssessment" class="modal-overlay">
          <div class="modal modal-sm">
            <div class="modal-header modal-header-danger">
              <h3 class="modal-title">
                <svg class="modal-icon modal-icon-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                Delete Medical Assessment
              </h3>
              <button @click="closeDeleteModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <div class="modal-body">
              <p>
                Are you sure you want to delete the medical assessment for <strong>{{ currentAssessment.patient_name }}</strong>? 
                This action cannot be undone.
              </p>
              <div class="mt-3 p-3 bg-gray-50 rounded">
                <small class="text-gray-600">
                  <strong>Assessment Date:</strong> {{ formatDate(currentAssessment.created_at) }}
                </small>
              </div>
            </div>

            <div class="modal-actions">
              <button @click="closeDeleteModal" class="btn btn-secondary">
                Cancel
              </button>
              <button
                @click="deleteAssessment"
                :disabled="isSaving"
                class="btn btn-danger"
              >
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                Delete Assessment
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
const medicalAssessments = ref([])
const patients = ref([])
const nurses = ref([])
const loading = ref(true)
const searchQuery = ref('')
const patientFilter = ref('all')
const nurseFilter = ref('all')
const conditionFilter = ref('all')

// Modal states
const showAssessmentModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const currentAssessment = ref(null)
const isSaving = ref(false)

// Dropdown state
const activeDropdown = ref(null)

// Options for select fields
const genderOptions = [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' }
]

const conditionOptions = [
  { value: 'stable', label: 'Stable' },
  { value: 'unstable', label: 'Unstable' }
]

const hydrationOptions = [
  { value: 'adequate', label: 'Adequate' },
  { value: 'dehydrated', label: 'Dehydrated' }
]

const nutritionOptions = [
  { value: 'adequate', label: 'Adequate' },
  { value: 'malnourished', label: 'Malnourished' }
]

const mobilityOptions = [
  { value: 'independent', label: 'Independent' },
  { value: 'assisted', label: 'Assisted' },
  { value: 'bedridden', label: 'Bedridden' }
]

const religionOptions = [
  // Christianity (Major denominations in Ghana)
  { value: 'christian', label: 'Christian' },
  { value: 'muslim', label: 'Muslim (Islam)' },
  { value: 'traditional_african', label: 'Traditional African Religion' },
  { value: 'other', label: 'Other Religion' },

]

// Form data
const assessmentForm = ref({
  is_new_patient: true,
  patient_id: '',
  patient_first_name: '',
  patient_last_name: '',
  patient_age: '',
  patient_gender: '',
  patient_date_of_birth: '',
  patient_phone: '',
  patient_ghana_card: '',
  nurse_id: '',
  physical_address: '',
  occupation: '',
  religion: '',
  emergency_contact_1_name: '',
  emergency_contact_1_relationship: '',
  emergency_contact_1_phone: '',
  emergency_contact_2_name: '',
  emergency_contact_2_relationship: '',
  emergency_contact_2_phone: '',
  presenting_condition: '',
  past_medical_history: '',
  allergies: '',
  current_medications: '',
  special_needs: '',
  general_condition: 'stable',
  hydration_status: 'adequate',
  nutrition_status: 'adequate',
  mobility_status: 'independent',
  has_wounds: false,
  wound_description: '',
  pain_level: 0,
  initial_vitals: {
    temperature: '',
    pulse: '',
    respiratory_rate: '',
    blood_pressure: '',
    spo2: '',
    weight: ''
  },
  initial_nursing_impression: ''
})

// Computed properties
const filteredAssessments = computed(() => {
  return medicalAssessments.value.filter(assessment => {
    const matchesSearch = !searchQuery.value || 
      assessment.patient_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      assessment.nurse_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      assessment.presenting_condition?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      assessment.initial_nursing_impression?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchesPatient = patientFilter.value === 'all' || assessment.patient_id == patientFilter.value
    const matchesNurse = nurseFilter.value === 'all' || assessment.nurse_id == nurseFilter.value
    const matchesCondition = conditionFilter.value === 'all' || assessment.general_condition === conditionFilter.value
    
    return matchesSearch && matchesPatient && matchesNurse && matchesCondition
  })
})


const patientOptions = computed(() => {
  return patients.value.map(patient => ({
    value: patient.id,
    label: `${patient.first_name} ${patient.last_name} (${patient.ghana_card_number})`
  }))
})

const nurseOptions = computed(() => {
  return nurses.value.map(nurse => ({
    value: nurse.id,
    label: `${nurse.first_name} ${nurse.last_name} ${nurse.license_number ? '(' + nurse.license_number + ')' : ''}`
  }))
})

// Methods
const loadMedicalAssessments = async () => {
  loading.value = true
  try {
    // Build query parameters
    const params = new URLSearchParams()
    
    if (patientFilter.value !== 'all') {
      params.append('patient_id', patientFilter.value)
    }
    
    if (nurseFilter.value !== 'all') {
      params.append('nurse_id', nurseFilter.value)
    }
    
    if (conditionFilter.value !== 'all') {
      params.append('condition', conditionFilter.value)
    }
    
    if (searchQuery.value) {
      params.append('search', searchQuery.value)
    }
    
    const queryString = params.toString()
    const url = `/api/medical-assessments${queryString ? '?' + queryString : ''}`
    
    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      medicalAssessments.value = data.data || data
      // Add risk level to each assessment
      medicalAssessments.value.forEach(assessment => {
        assessment.risk_level = calculateRiskLevel(assessment)
      })
    } else {
      console.error('Failed to load medical assessments')      
    }
  } catch (error) {
    console.error('Error loading medical assessments:', error)
  }
  loading.value = false
}

const loadPatients = async () => {
  try {
    const response = await fetch('/api/users?role=patient', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      patients.value = data.data || data
    }
  } catch (error) {
    console.error('Error loading patients:', error)
  }
}

const loadNurses = async () => {
  try {
    const response = await fetch('/api/users?role=nurse', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      nurses.value = data.data || data
    }
  } catch (error) {
    console.error('Error loading nurses:', error)
  }
}

const calculateRiskLevel = (assessment) => {
  let riskFactors = 0

  if (assessment.general_condition === 'unstable') riskFactors += 3
  if (assessment.hydration_status === 'dehydrated') riskFactors += 2
  if (assessment.nutrition_status === 'malnourished') riskFactors += 2
  if (assessment.mobility_status === 'bedridden') riskFactors += 2
  if (assessment.has_wounds) riskFactors += 1
  if (assessment.pain_level >= 7) riskFactors += 2

  if (riskFactors >= 6) return 'high'
  if (riskFactors >= 3) return 'medium'
  return 'low'
}

const getConditionBadgeColor = (condition) => {
  const colorMap = {
    'stable': 'badge-success',
    'unstable': 'badge-danger'
  }
  return colorMap[condition] || 'badge-secondary'
}

const getRiskBadgeColor = (riskLevel) => {
  const colorMap = {
    'low': 'badge-success',
    'medium': 'badge-warning',
    'high': 'badge-danger'
  }
  return colorMap[riskLevel] || 'badge-secondary'
}

const getPatientAvatar = (patient) => {
  if (!patient) return 'https://ui-avatars.com/api/?name=Unknown&color=667eea&background=f8f9fa&size=200&font-size=0.6'
  
  return patient.avatar 
    ? `/storage/${patient.avatar}`
    : `https://ui-avatars.com/api/?name=${encodeURIComponent(patient.first_name + ' ' + patient.last_name)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
}

const calculateAge = (dateOfBirth) => {
  if (!dateOfBirth) return 'N/A'
  const today = new Date()
  const birth = new Date(dateOfBirth)
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--
  }
  return age
}

const capitalizeFirst = (str) => {
  return str ? str.charAt(0).toUpperCase() + str.slice(1) : ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const formatTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
}

const toggleDropdown = (assessmentId) => {
  activeDropdown.value = activeDropdown.value === assessmentId ? null : assessmentId
}

const openCreateModal = () => {
  isEditing.value = false
  currentAssessment.value = null
  resetForm()
  showAssessmentModal.value = true
}

const openEditModal = (assessment) => {
  isEditing.value = true
  currentAssessment.value = assessment
  populateForm(assessment)
  showAssessmentModal.value = true
  activeDropdown.value = null
}

const openViewModal = (assessment) => {
  currentAssessment.value = assessment
  showViewModal.value = true
  activeDropdown.value = null
}

const openDeleteModal = (assessment) => {
  currentAssessment.value = assessment
  showDeleteModal.value = true
  activeDropdown.value = null
}

const closeAssessmentModal = () => {
  showAssessmentModal.value = false
}

const closeViewModal = () => {
  showViewModal.value = false
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
}

const editFromView = () => {
  closeViewModal()
  openEditModal(currentAssessment.value)
}

const resetForm = () => {
  assessmentForm.value = {
    is_new_patient: true,
    patient_id: '',
    patient_first_name: '',
    patient_last_name: '',
    patient_age: '',
    patient_gender: 'male',
    patient_date_of_birth: '',
    patient_phone: '',
    patient_ghana_card: '',
    nurse_id: '',
    physical_address: '',
    occupation: '',
    religion: '',
    emergency_contact_1_name: '',
    emergency_contact_1_relationship: '',
    emergency_contact_1_phone: '',
    emergency_contact_2_name: '',
    emergency_contact_2_relationship: '',
    emergency_contact_2_phone: '',
    presenting_condition: '',
    past_medical_history: '',
    allergies: '',
    current_medications: '',
    special_needs: '',
    general_condition: 'stable',
    hydration_status: 'adequate',
    nutrition_status: 'adequate',
    mobility_status: 'independent',
    has_wounds: false,
    wound_description: '',
    pain_level: 0,
    initial_vitals: {
      temperature: '',
      pulse: '',
      respiratory_rate: '',
      blood_pressure: '',
      spo2: '',
      weight: ''
    },
    initial_nursing_impression: ''
  }
}

const populateForm = (assessment) => {
  assessmentForm.value = {
    is_new_patient: false,
    patient_id: assessment.patient_id,
    patient_first_name: assessment.patient?.first_name || '',
    patient_last_name: assessment.patient?.last_name || '',
    patient_age: calculateAge(assessment.patient?.date_of_birth) || '',
    patient_gender: assessment.patient?.gender || '',
    patient_date_of_birth: assessment.patient?.date_of_birth || '',
    patient_phone: assessment.patient?.phone || '',
    patient_ghana_card: assessment.patient?.ghana_card_number || '',
    nurse_id: assessment.nurse_id,
    physical_address: assessment.physical_address,
    occupation: assessment.occupation || '',
    religion: assessment.religion || '',
    emergency_contact_1_name: assessment.emergency_contact_1_name,
    emergency_contact_1_relationship: assessment.emergency_contact_1_relationship,
    emergency_contact_1_phone: assessment.emergency_contact_1_phone,
    emergency_contact_2_name: assessment.emergency_contact_2_name || '',
    emergency_contact_2_relationship: assessment.emergency_contact_2_relationship || '',
    emergency_contact_2_phone: assessment.emergency_contact_2_phone || '',
    presenting_condition: assessment.presenting_condition,
    past_medical_history: assessment.past_medical_history || '',
    allergies: assessment.allergies || '',
    current_medications: assessment.current_medications || '',
    special_needs: assessment.special_needs || '',
    general_condition: assessment.general_condition,
    hydration_status: assessment.hydration_status,
    nutrition_status: assessment.nutrition_status,
    mobility_status: assessment.mobility_status,
    has_wounds: assessment.has_wounds,
    wound_description: assessment.wound_description || '',
    pain_level: assessment.pain_level,
    initial_vitals: { ...assessment.initial_vitals },
    initial_nursing_impression: assessment.initial_nursing_impression
  }
}

const saveAssessment = async () => {
  isSaving.value = true
  
  try {
    const url = isEditing.value ? `/api/medical-assessments/${currentAssessment.value.id}` : '/api/medical-assessments'
    const method = isEditing.value ? 'PUT' : 'POST'
    
    // Prepare the payload based on whether it's a new patient or existing patient
    let payload = {
      nurse_id: assessmentForm.value.nurse_id,
      physical_address: assessmentForm.value.physical_address,
      occupation: assessmentForm.value.occupation,
      religion: assessmentForm.value.religion,
      emergency_contact_1_name: assessmentForm.value.emergency_contact_1_name,
      emergency_contact_1_relationship: assessmentForm.value.emergency_contact_1_relationship,
      emergency_contact_1_phone: assessmentForm.value.emergency_contact_1_phone,
      emergency_contact_2_name: assessmentForm.value.emergency_contact_2_name,
      emergency_contact_2_relationship: assessmentForm.value.emergency_contact_2_relationship,
      emergency_contact_2_phone: assessmentForm.value.emergency_contact_2_phone,
      presenting_condition: assessmentForm.value.presenting_condition,
      past_medical_history: assessmentForm.value.past_medical_history,
      allergies: assessmentForm.value.allergies,
      current_medications: assessmentForm.value.current_medications,
      special_needs: assessmentForm.value.special_needs,
      general_condition: assessmentForm.value.general_condition,
      hydration_status: assessmentForm.value.hydration_status,
      nutrition_status: assessmentForm.value.nutrition_status,
      mobility_status: assessmentForm.value.mobility_status,
      has_wounds: assessmentForm.value.has_wounds,
      wound_description: assessmentForm.value.wound_description,
      pain_level: assessmentForm.value.pain_level,
      initial_vitals: assessmentForm.value.initial_vitals,
      initial_nursing_impression: assessmentForm.value.initial_nursing_impression,
      is_new_patient: assessmentForm.value.is_new_patient
    }
    
    if (assessmentForm.value.is_new_patient) {
      // For new patients, include all patient detail fields
      payload = {
        ...payload,
        patient_first_name: assessmentForm.value.patient_first_name,
        patient_last_name: assessmentForm.value.patient_last_name,
        patient_age: assessmentForm.value.patient_age,
        patient_gender: assessmentForm.value.patient_gender,
        patient_date_of_birth: assessmentForm.value.patient_date_of_birth,
        patient_phone: assessmentForm.value.patient_phone,
        patient_ghana_card: assessmentForm.value.patient_ghana_card
      }
    } else {
      // For existing patients, only send the patient_id
      payload.patient_id = assessmentForm.value.patient_id
    }
    
    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    })
    
    if (response.ok) {
      await loadMedicalAssessments()
      closeAssessmentModal()
      toast.showSuccess(isEditing.value ? 'Medical assessment updated successfully!' : 'Medical assessment completed successfully!')
    } else {
      const errorData = await response.json()
      console.error('Failed to save medical assessment:', errorData)
      
      if (errorData.errors) {
        // Show validation errors
        const firstError = Object.values(errorData.errors)[0][0]
        toast.showError(firstError)
      } else {
        toast.showError(errorData.message || 'Failed to save medical assessment. Please try again.')
      }
    }
  } catch (error) {
    console.error('Error saving medical assessment:', error)
    toast.showError('An error occurred while saving the medical assessment.')
  }
  
  isSaving.value = false
}

const deleteAssessment = async () => {
  isSaving.value = true
  
  try {
    const response = await fetch(`/api/medical-assessments/${currentAssessment.value.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      await loadMedicalAssessments()
      closeDeleteModal()
      toast.showSuccess('Medical assessment deleted successfully!')
    } else {
      console.error('Failed to delete medical assessment')
      toast.showError('Failed to delete medical assessment. Please try again.')
    }
  } catch (error) {
    console.error('Error deleting medical assessment:', error)
    toast.showError('An error occurred while deleting the medical assessment.')
  }
  
  isSaving.value = false
}

const markReviewed = async (assessment) => {
  try {
    const response = await fetch(`/api/medical-assessments/${assessment.id}/mark-reviewed`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      await loadMedicalAssessments()
      toast.showSuccess('Assessment marked as reviewed!')
    } else {
      toast.showError('Failed to mark assessment as reviewed.')
    }
  } catch (error) {
    console.error('Error marking assessment as reviewed:', error)
    toast.showError('An error occurred while marking the assessment as reviewed.')
  }
  activeDropdown.value = null
}

const printAssessment = () => {
  window.print()
}

const exportAssessments = async () => {
  try {
    const params = new URLSearchParams()
    
    if (patientFilter.value !== 'all') {
      params.append('patient_id', patientFilter.value)
    }
    
    if (nurseFilter.value !== 'all') {
      params.append('nurse_id', nurseFilter.value)
    }
    
    if (conditionFilter.value !== 'all') {
      params.append('condition', conditionFilter.value)
    }
    
    if (searchQuery.value) {
      params.append('search', searchQuery.value)
    }
    
    const queryString = params.toString()
    const url = `/api/medical-assessments/export${queryString ? '?' + queryString : ''}`
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      }
    })
    
    if (response.ok) {
      const contentDisposition = response.headers.get('Content-Disposition')
      let filename = 'medical_assessments_export.csv'
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
      
      toast.showSuccess('Medical assessments exported successfully!')
    } else {
      const errorData = await response.json()
      console.error('Failed to export medical assessments:', errorData)
      toast.showError(errorData.message || 'Failed to export medical assessments. Please try again.')
    }
  } catch (error) {
    console.error('Error exporting medical assessments:', error)
    toast.showError('An error occurred while exporting medical assessments.')
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.action-dropdown')) {
    activeDropdown.value = null
  }
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadMedicalAssessments(),
    loadPatients(),
    loadNurses()
  ])
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Watch for filter changes and reload data
watch([patientFilter, nurseFilter, conditionFilter, searchQuery], () => {
  if (searchQuery.value) {
    setTimeout(() => {
      loadMedicalAssessments()
    }, 500)
  } else {
    loadMedicalAssessments()
  }
}, { deep: true })

watch(
  () => assessmentForm.value.patient_id,
  (newPatientId) => {
    if (newPatientId && !assessmentForm.value.is_new_patient) {
      // Find the selected patient from the patients array
      const selectedPatient = patients.value.find(patient => patient.id == newPatientId)
      
      if (selectedPatient) {
        // Populate the patient fields
        assessmentForm.value.patient_first_name = selectedPatient.first_name
        assessmentForm.value.patient_last_name = selectedPatient.last_name
        assessmentForm.value.patient_age = calculateAge(selectedPatient.date_of_birth)
        assessmentForm.value.patient_gender = selectedPatient.gender
        assessmentForm.value.patient_date_of_birth = selectedPatient.date_of_birth
        assessmentForm.value.patient_phone = selectedPatient.phone
        assessmentForm.value.patient_ghana_card = selectedPatient.ghana_card_number
      }
    }
  }
)

// Also add a watcher for is_new_patient to clear fields when switching modes
watch(
  () => assessmentForm.value.is_new_patient,
  (isNewPatient) => {
    if (isNewPatient) {
      // Clear existing patient selection
      assessmentForm.value.patient_id = ''
      // Clear patient detail fields
      assessmentForm.value.patient_first_name = ''
      assessmentForm.value.patient_last_name = ''
      assessmentForm.value.patient_age = ''
      assessmentForm.value.patient_gender = 'male'
      assessmentForm.value.patient_date_of_birth = ''
      assessmentForm.value.patient_phone = ''
      assessmentForm.value.patient_ghana_card = ''
    }
  }
)
</script>

<style scoped>
/* Medical Assessment Specific Styles */
.medical-assessment-page {
  min-height: 100vh;
  background: #f8f9fa;
}

.assessments-table-container {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: visible;
}

.assessments-table {
  width: 100%;
  border-collapse: collapse;
}

.assessments-table thead {
  background: #f9fafb;
}

.assessments-table th {
  padding: 0.75rem 1.5rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.assessments-table tbody tr:hover {
  background: #f9fafb;
}

.assessments-table td {
  padding: 1rem 1.5rem;
  white-space: nowrap;
  font-size: 0.875rem;
  border-bottom: 1px solid #e5e7eb;
}

/* Patient Info */
.patient-info {
  display: flex;
  align-items: center;
}

.patient-avatar {
  flex-shrink: 0;
  width: 2.5rem;
  height: 2.5rem;
}

.patient-avatar img {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  object-fit: cover;
}

.patient-details {
  margin-left: 1rem;
}

.patient-name {
  font-weight: 500;
  color: #1f2937;
}

.patient-id {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Nurse Info */
.nurse-info .nurse-name {
  font-weight: 500;
  color: #1f2937;
}

.nurse-info .nurse-license {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Assessment Date */
.assessment-date .date {
  font-weight: 500;
  color: #1f2937;
}

.assessment-date .time {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Patient Info Card in View Modal */
.patient-info-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 0.75rem;
  padding: 2rem;
  margin-bottom: 2rem;
  color: white;
}

.patient-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.patient-avatar-large img {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: 4px solid rgba(255, 255, 255, 0.3);
}

.patient-info-details {
  flex: 1;
}

.patient-name-large {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.patient-meta {
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

.assessment-meta {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.875rem;
  opacity: 0.9;
}

.risk-indicator {
  flex-shrink: 0;
}

/* Assessment Sections */
.assessment-sections {
  space-y: 1.5rem;
}

.assessment-section {
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
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-content {
  color: #374151;
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.info-item label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
  display: block;
  margin-bottom: 0.25rem;
}

.info-item p {
  margin: 0;
  color: #1f2937;
}

/* Emergency Contacts */
.emergency-contacts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.emergency-contact {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
}

.contact-header {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.contact-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.875rem;
}

.contact-details strong {
  color: #1f2937;
}

.contact-details span {
  color: #6b7280;
}

/* Medical History */
.medical-history {
  space-y: 1rem;
}

.history-item label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
  display: block;
  margin-bottom: 0.5rem;
}

.history-item p {
  margin: 0;
  color: #1f2937;
  line-height: 1.5;
}

/* Assessment Results */
.assessment-results {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.result-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.result-item label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
}

.result-value {
  font-weight: 600;
  color: #1f2937;
}

.wound-description {
  margin-top: 1rem;
  padding: 1rem;
  background: #fef7f7;
  border-left: 4px solid #dc2626;
  border-radius: 0.5rem;
}

.wound-description label {
  font-weight: 500;
  color: #991b1b;
  font-size: 0.875rem;
  display: block;
  margin-bottom: 0.5rem;
}

.wound-description p {
  margin: 0;
  color: #7f1d1d;
  line-height: 1.5;
}

/* Vitals Grid */
.vitals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.vital-card {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  text-align: center;
}

.vital-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.vital-value {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

/* Nursing Impression */
.nursing-impression p {
  margin: 0;
  line-height: 1.6;
  color: #374151;
}

/* Form Sections */
.form-section-header {
  grid-column: 1 / -1;
  margin-top: 1.5rem;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f3f4f6;
}

.form-section-header:first-child {
  margin-top: 0;
}

.form-section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .assessments-table {
    font-size: 0.8125rem;
  }
  
  .assessments-table th,
  .assessments-table td {
    padding: 0.75rem 1rem;
  }
  
  .patient-header {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .patient-meta {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .vitals-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .assessment-results {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .assessments-table-container {
    overflow-x: auto;
  }
  
  .assessments-table {
    min-width: 800px;
  }
  
  .vitals-grid {
    grid-template-columns: 1fr;
  }
}
</style>