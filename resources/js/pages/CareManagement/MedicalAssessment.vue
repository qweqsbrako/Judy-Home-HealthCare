<template>
  <MainLayout>
    <div class="medical-assessment-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Medical Assessments</h1>
          <p>Patient admission registration and initial medical assessment</p>
        </div>
        <div class="page-header-actions">
          <button @click="exportAssessments" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button @click="openCreateModal" class="btn-modern btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            New Assessment
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Assessments</div>
            <div class="stat-value">{{ totalAssessments }}</div>
            <div class="stat-change positive">All time</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Unique Patients</div>
            <div class="stat-value">{{ uniquePatients }}</div>
            <div class="stat-change positive">Assessed</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-.834-1.964-.834-2.732 0L3.732 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">High Risk</div>
            <div class="stat-value">{{ highRiskCount }}</div>
            <div class="stat-change neutral">Patients flagged</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Stable Patients</div>
            <div class="stat-value">{{ stableCount }}</div>
            <div class="stat-change positive">Current status</div>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="filters-section">
        <div class="search-wrapper">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Search by patient, nurse, condition, or observations..."
            v-model="searchQuery"
            class="search-input"
          />
        </div>
        <div class="filters-group">
          <select v-model="patientFilter" class="filter-select">
            <option value="all">All Patients</option>
            <option v-for="patient in patients" :key="patient.id" :value="patient.id">
              {{ patient.first_name }} {{ patient.last_name }}
            </option>
          </select>
          
          <select v-model="nurseFilter" class="filter-select">
            <option value="all">All Nurses</option>
            <option v-for="nurse in nurses" :key="nurse.id" :value="nurse.id">
              {{ nurse.first_name }} {{ nurse.last_name }}
            </option>
          </select>
          
          <select v-model="conditionFilter" class="filter-select">
            <option value="all">All Conditions</option>
            <option value="stable">Stable</option>
            <option value="unstable">Unstable</option>
          </select>
          
          <select v-model="riskFilter" class="filter-select">
            <option value="all">All Risk Levels</option>
            <option value="high">High Risk</option>
            <option value="medium">Medium Risk</option>
            <option value="low">Low Risk</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading medical assessments...</p>
      </div>

      <!-- Assessments Table -->
      <div v-else-if="!loading" class="assessments-table-container">
        <div v-if="filteredAssessments.length > 0" class="table-wrapper">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Patient</th>
                <th>Nurse/Caregiver</th>
                <th>Assessment Date</th>
                <th>General Condition</th>
                <th>Risk Level</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="assessment in filteredAssessments" :key="assessment.id">
                <td>
                  <div class="user-cell">
                    <img :src="getPatientAvatar(assessment.patient)" class="user-avatar-table" />
                    <div class="user-details-table">
                      <div class="user-name-table">{{ assessment.patient_name }}</div>
                      <div class="user-id-table">{{ assessment.patient?.ghana_card_number || 'N/A' }}</div>
                    </div>
                  </div>
                </td>
                
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ assessment.nurse_name }}</div>
                    <div class="contact-secondary">{{ assessment.nurse?.license_number || 'N/A' }}</div>
                  </div>
                </td>
                
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ formatDate(assessment.created_at) }}</div>
                    <div class="contact-secondary">{{ formatTime(assessment.created_at) }}</div>
                  </div>
                </td>
                
                <td>
                  <span :class="'modern-badge ' + getConditionBadgeColor(assessment.general_condition)">
                    {{ capitalizeFirst(assessment.general_condition) }}
                  </span>
                </td>
                
                <td>
                  <span :class="'modern-badge ' + getRiskBadgeColor(assessment.risk_level)">
                    {{ capitalizeFirst(assessment.risk_level) }} Risk
                  </span>
                </td>
                
                <td>
                  <div class="action-cell">
                    <button @click="toggleDropdown(assessment.id)" class="action-btn">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                      </svg>
                    </button>
                    
                    <div v-show="activeDropdown === assessment.id" class="modern-dropdown">
                      <button @click="openViewModal(assessment)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>
                      
                      <button @click="openEditModal(assessment)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Assessment
                      </button>
                      
                      <div class="dropdown-divider"></div>
                      
                      <button @click="openDeleteModal(assessment)" class="dropdown-item-modern danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
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

        <!-- Empty State -->
        <div v-else class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3>No medical assessments found</h3>
          <p>
            {{ (searchQuery || patientFilter !== 'all' || nurseFilter !== 'all' || conditionFilter !== 'all' || riskFilter !== 'all') 
              ? 'Try adjusting your search or filters.' 
              : 'Get started by creating a new medical assessment.' }}
          </p>
          <button @click="openCreateModal" class="btn btn-primary">
            Add First Assessment
          </button>
        </div>
      </div>

      <!-- Create/Edit Assessment Modal -->
      <div v-if="showAssessmentModal" class="modal-overlay" @click.self="closeAssessmentModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">
              <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              {{ isEditing ? 'Edit Medical Assessment' : 'Client Admission & Assessment Form' }}
            </h2>
            <button @click="closeAssessmentModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveAssessment" id="assessmentForm">
            <div class="modal-body">
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
                  <label>Select Existing Patient <span class="required">*</span></label>
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
                    <label>First Name <span class="required">*</span></label>
                    <input
                      type="text"
                      v-model="assessmentForm.patient_first_name"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Last Name <span class="required">*</span></label>
                    <input
                      type="text"
                      v-model="assessmentForm.patient_last_name"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Age <span class="required">*</span></label>
                    <input
                      type="number"
                      min="0"
                      max="120"
                      v-model="assessmentForm.patient_age"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Gender <span class="required">*</span></label>
                    <SearchableSelect
                      v-model="assessmentForm.patient_gender"
                      :options="genderOptions"
                      placeholder="Select gender..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Date of Birth <span class="required">*</span></label>
                    <input
                      type="date"
                      v-model="assessmentForm.patient_date_of_birth"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Phone Number <span class="required">*</span></label>
                    <input
                      type="tel"
                      v-model="assessmentForm.patient_phone"
                      required
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Ghana Card Number <span class="required">*</span></label>
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
                  <label>Nurse/Caregiver Performing Assessment <span class="required">*</span></label>
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
                  <label>Physical Address <span class="required">*</span></label>
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
                  <label>Emergency Contact 1 - Name <span class="required">*</span></label>
                  <input
                    type="text"
                    v-model="assessmentForm.emergency_contact_1_name"
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Relationship <span class="required">*</span></label>
                  <input
                    type="text"
                    v-model="assessmentForm.emergency_contact_1_relationship"
                    placeholder="e.g., Spouse, Child, Parent"
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Phone Number <span class="required">*</span></label>
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
                  <label>Presenting Condition/Diagnosis <span class="required">*</span></label>
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
                  <label>General Condition <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="assessmentForm.general_condition"
                    :options="conditionOptions"
                    placeholder="Select condition..."
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Hydration Status <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="assessmentForm.hydration_status"
                    :options="hydrationOptions"
                    placeholder="Select hydration status..."
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Nutrition Status <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="assessmentForm.nutrition_status"
                    :options="nutritionOptions"
                    placeholder="Select nutrition status..."
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Mobility Status <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="assessmentForm.mobility_status"
                    :options="mobilityOptions"
                    placeholder="Select mobility status..."
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Pain Level (0-10) <span class="required">*</span></label>
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
                    <span class="checkbox-text">Patient has wounds or ulcers</span>
                  </label>
                </div>

                <div v-if="assessmentForm.has_wounds" class="form-group form-grid-full">
                  <label>Wound Description <span class="required">*</span></label>
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
                  <label>Temperature (°C) <span class="required">*</span></label>
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
                  <label>Pulse (bpm) <span class="required">*</span></label>
                  <input
                    type="number"
                    min="30"
                    max="200"
                    v-model="assessmentForm.initial_vitals.pulse"
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Respiratory Rate (/min) <span class="required">*</span></label>
                  <input
                    type="number"
                    min="8"
                    max="40"
                    v-model="assessmentForm.initial_vitals.respiratory_rate"
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Blood Pressure (mmHg) <span class="required">*</span></label>
                  <input
                    type="text"
                    v-model="assessmentForm.initial_vitals.blood_pressure"
                    placeholder="120/80"
                    required
                  />
                </div>

                <div class="form-group">
                  <label>SpO₂ (%) <span class="required">*</span></label>
                  <input
                    type="number"
                    min="70"
                    max="100"
                    v-model="assessmentForm.initial_vitals.spo2"
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Weight (kg) <span class="required">*</span></label>
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
                  <label>Nursing Impression <span class="required">*</span></label>
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
      <div v-if="showViewModal && currentAssessment" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">
              <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Medical Assessment - {{ currentAssessment.patient_name }}
            </h2>
            <button @click="closeViewModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
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
                    <span :class="'modern-badge badge-lg ' + getRiskBadgeColor(currentAssessment.risk_level)">
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
                        <span :class="'modern-badge ' + getConditionBadgeColor(currentAssessment.general_condition)">
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
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
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
      <div v-if="showDeleteModal && currentAssessment" class="modal-overlay" @click.self="closeDeleteModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">
              <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-.834-1.964-.834-2.732 0L3.732 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              Delete Medical Assessment
            </h3>
            <button @click="closeDeleteModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
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
import * as medicalAssessmentsService from '../../services/medicalAssessmentsService'

const router = useRouter()
const toast = inject('toast')

// Reactive data
const medicalAssessments = ref([])
const patients = ref([])
const nurses = ref([])
const statistics = ref({})
const loading = ref(true)
const searchQuery = ref('')
const patientFilter = ref('all')
const nurseFilter = ref('all')
const conditionFilter = ref('all')
const riskFilter = ref('all')

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
  { value: 'christian', label: 'Christian' },
  { value: 'muslim', label: 'Muslim (Islam)' },
  { value: 'traditional_african', label: 'Traditional African Religion' },
  { value: 'other', label: 'Other Religion' }
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
      assessment.patient_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      assessment.nurse_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      assessment.presenting_condition?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      assessment.initial_nursing_impression?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchesPatient = patientFilter.value === 'all' || assessment.patient_id == patientFilter.value
    const matchesNurse = nurseFilter.value === 'all' || assessment.nurse_id == nurseFilter.value
    const matchesCondition = conditionFilter.value === 'all' || assessment.general_condition === conditionFilter.value
    const matchesRisk = riskFilter.value === 'all' || assessment.risk_level === riskFilter.value
    
    return matchesSearch && matchesPatient && matchesNurse && matchesCondition && matchesRisk
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

// Statistics computed properties
const totalAssessments = computed(() => {
  return statistics.value?.total_assessments || medicalAssessments.value.length || 0
})

const uniquePatients = computed(() => {
  if (statistics.value?.unique_patients) return statistics.value.unique_patients
  const uniquePatientIds = new Set(medicalAssessments.value.map(a => a.patient_id))
  return uniquePatientIds.size
})

const highRiskCount = computed(() => {
  if (statistics.value?.high_risk_count !== undefined) return statistics.value.high_risk_count
  return medicalAssessments.value.filter(a => a.risk_level === 'high').length
})

const stableCount = computed(() => {
  if (statistics.value?.stable_count !== undefined) return statistics.value.stable_count
  return medicalAssessments.value.filter(a => a.general_condition === 'stable').length
})

// Methods
const loadMedicalAssessments = async () => {
  loading.value = true
  try {
    const filters = {
      patient_id: patientFilter.value !== 'all' ? patientFilter.value : undefined,
      nurse_id: nurseFilter.value !== 'all' ? nurseFilter.value : undefined,
      condition: conditionFilter.value !== 'all' ? conditionFilter.value : undefined,
      search: searchQuery.value || undefined
    }

    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])

    const data = await medicalAssessmentsService.getMedicalAssessments(filters)
    
    if (data && data.success) {
      medicalAssessments.value = data.data || []
      
      // Add risk level to each assessment
      medicalAssessments.value.forEach(assessment => {
        assessment.risk_level = medicalAssessmentsService.calculateRiskLevel(assessment)
      })
    }
  } catch (error) {
    console.error('Error loading medical assessments:', error)
    toast.showError('Failed to load medical assessments')
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const endDate = new Date()
    const startDate = new Date()
    startDate.setDate(startDate.getDate() - 30)
    
    const filters = {
      start_date: startDate.toISOString().split('T')[0],
      end_date: endDate.toISOString().split('T')[0]
    }
    
    const data = await medicalAssessmentsService.getStatistics(filters)
    
    if (data && data.success && data.data) {
      statistics.value = data.data
    } else {
      calculateStatisticsFromAssessments()
    }
  } catch (error) {
    console.error('Error loading statistics:', error)
    calculateStatisticsFromAssessments()
  }
}

const calculateStatisticsFromAssessments = () => {
  if (!medicalAssessments.value || medicalAssessments.value.length === 0) {
    statistics.value = {
      total_assessments: 0,
      unique_patients: 0,
      high_risk_count: 0,
      stable_count: 0
    }
    return
  }
  
  const assessments = medicalAssessments.value
  const uniquePatientIds = new Set(assessments.map(a => a.patient_id))
  const highRisk = assessments.filter(a => a.risk_level === 'high').length
  const stable = assessments.filter(a => a.general_condition === 'stable').length
  
  statistics.value = {
    total_assessments: assessments.length,
    unique_patients: uniquePatientIds.size,
    high_risk_count: highRisk,
    stable_count: stable
  }
}

const loadPatients = async () => {
  try {
    const data = await medicalAssessmentsService.getAvailablePatients()
    if (data && data.success) {
      patients.value = data.data || []
    }
  } catch (error) {
    console.error('Error loading patients:', error)
  }
}

const loadNurses = async () => {
  try {
    const data = await medicalAssessmentsService.getAvailableNurses()
    if (data && data.success) {
      nurses.value = data.data || []
    }
  } catch (error) {
    console.error('Error loading nurses:', error)
  }
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
      payload.patient_id = assessmentForm.value.patient_id
    }
    
    let response
    if (isEditing.value) {
      response = await medicalAssessmentsService.updateMedicalAssessment(currentAssessment.value.id, payload)
    } else {
      response = await medicalAssessmentsService.createMedicalAssessment(payload)
    }
    
    if (response && response.success) {
      await loadMedicalAssessments()
      await loadStatistics()
      closeAssessmentModal()
      toast.showSuccess(isEditing.value ? 'Medical assessment updated successfully!' : 'Medical assessment completed successfully!')
    } else {
      if (response?.errors) {
        const firstError = Object.values(response.errors)[0][0]
        toast.showError(firstError)
      } else {
        toast.showError(response?.message || 'Failed to save medical assessment')
      }
    }
  } catch (error) {
    console.error('Error saving medical assessment:', error)
    toast.showError('An error occurred while saving the medical assessment')
  }
  
  isSaving.value = false
}

const deleteAssessment = async () => {
  isSaving.value = true
  
  try {
    const response = await medicalAssessmentsService.deleteMedicalAssessment(currentAssessment.value.id)
    
    if (response && response.success) {
      await loadMedicalAssessments()
      await loadStatistics()
      closeDeleteModal()
      toast.showSuccess('Medical assessment deleted successfully!')
    } else {
      toast.showError(response?.message || 'Failed to delete medical assessment')
    }
  } catch (error) {
    console.error('Error deleting medical assessment:', error)
    toast.showError('An error occurred while deleting the medical assessment')
  }
  
  isSaving.value = false
}

const printAssessment = () => {
  window.print()
}

const exportAssessments = async () => {
  try {
    toast.showInfo('Preparing export...')
    
    const filters = {
      patient_id: patientFilter.value !== 'all' ? patientFilter.value : undefined,
      nurse_id: nurseFilter.value !== 'all' ? nurseFilter.value : undefined,
      condition: conditionFilter.value !== 'all' ? conditionFilter.value : undefined,
      search: searchQuery.value || undefined
    }
    
    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])
    
    const { blob, filename } = await medicalAssessmentsService.exportMedicalAssessments(filters)
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    toast.showSuccess('Medical assessments exported successfully!')
  } catch (error) {
    console.error('Error exporting assessments:', error)
    toast.showError('Failed to export medical assessments')
  }
}

const handleClickOutside = (event) => {
  if (!event.target.closest('.action-cell')) {
    activeDropdown.value = null
  }
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadMedicalAssessments(),
    loadStatistics(),
    loadPatients(),
    loadNurses()
  ])
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Watchers
let searchDebounceTimer = null

watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    loadMedicalAssessments()
  }, 500)
})

watch([patientFilter, nurseFilter, conditionFilter, riskFilter], () => {
  loadMedicalAssessments()
})

watch(
  () => assessmentForm.value.patient_id,
  (newPatientId) => {
    if (newPatientId && !assessmentForm.value.is_new_patient) {
      const selectedPatient = patients.value.find(patient => patient.id == newPatientId)
      
      if (selectedPatient) {
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

watch(
  () => assessmentForm.value.is_new_patient,
  (isNewPatient) => {
    if (isNewPatient) {
      assessmentForm.value.patient_id = ''
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
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.medical-assessment-page {
  padding: 32px;
  background: #f8fafc;
  min-height: 100vh;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
}

.page-header-content h1 {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 6px 0;
  letter-spacing: -0.8px;
}

.page-header-content p {
  font-size: 15px;
  color: #64748b;
  margin: 0;
  font-weight: 400;
}

.page-header-actions {
  display: flex;
  gap: 12px;
}

/* Modern Buttons */
.btn-modern {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-modern svg {
  width: 18px;
  height: 18px;
}

.btn-modern.btn-primary {
  background: #667eea;
  color: white;
}

.btn-modern.btn-primary:hover {
  background: #5a67d8;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-modern.btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-modern.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  display: flex;
  gap: 16px;
  transition: all 0.2s;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon svg {
  width: 28px;
  height: 28px;
  color: white;
}

.stat-icon.blue {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.green {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-icon.yellow {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon.purple {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 600;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
  margin-bottom: 6px;
  letter-spacing: -1px;
}

.stat-change {
  font-size: 13px;
  font-weight: 500;
}

.stat-change.positive {
  color: #10b981;
}

.stat-change.neutral {
  color: #f59e0b;
}

/* Filters Section */
.filters-section {
  background: white;
  padding: 20px;
  border-radius: 16px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.search-wrapper {
  flex: 1;
  min-width: 300px;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 10px 14px 10px 44px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  transition: all 0.2s;
  font-weight: 500;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filters-group {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.filter-select {
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 150px;
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Loading State */
.loading-state {
  background: white;
  padding: 60px;
  border-radius: 16px;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.loading-spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f1f5f9;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
}

/* Table Styles */
.assessments-table-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  overflow: visible;
}

.modern-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.modern-table thead {
  background: #f8fafc;
}

.modern-table th {
  padding: 16px 20px;
  text-align: left;
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  border-bottom: 1px solid #e2e8f0;
}

.modern-table tbody tr {
  transition: all 0.2s;
  border-bottom: 1px solid #f1f5f9;
}

.modern-table tbody tr:hover {
  background: #f8fafc;
}

.modern-table td {
  padding: 16px 20px;
  font-size: 14px;
  color: #334155;
  vertical-align: middle;
}

/* User Cell */
.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar-table {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  object-fit: cover;
  border: 2px solid #e2e8f0;
  flex-shrink: 0;
}

.user-name-table {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 2px;
}

.user-id-table {
  font-size: 12px;
  color: #94a3b8;
  font-weight: 500;
}

/* Contact Cell */
.contact-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.contact-primary {
  font-size: 14px;
  color: #334155;
  font-weight: 500;
}

.contact-secondary {
  font-size: 13px;
  color: #94a3b8;
}

/* Modern Badges */
.modern-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  text-transform: capitalize;
  letter-spacing: 0.3px;
}

.modern-badge.badge-success {
  background: #d1fae5;
  color: #065f46;
}

.modern-badge.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

.modern-badge.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.modern-badge.badge-secondary {
  background: #f1f5f9;
  color: #475569;
}

.modern-badge.badge-lg {
  padding: 8px 16px;
  font-size: 14px;
}

/* Action Cell */
.action-cell {
  position: relative;
}

.action-btn {
  width: 36px;
  height: 36px;
  background: transparent;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.action-btn svg {
  width: 18px;
  height: 18px;
  color: #64748b;
}

/* Modern Dropdown */
.modern-dropdown {
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
  min-width: 200px;
  z-index: 1000;
  padding: 8px;
  animation: slideIn 0.2s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-item-modern {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 12px;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  color: #334155;
  font-size: 14px;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
}

.dropdown-item-modern:hover {
  background: #f8fafc;
}

.dropdown-item-modern svg {
  width: 18px;
  height: 18px;
}

.dropdown-item-modern.danger {
  color: #dc2626;
}

.dropdown-item-modern.danger:hover {
  background: #fef2f2;
}

.dropdown-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 8px 0;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 24px;
}

.empty-state svg {
  width: 64px;
  height: 64px;
  color: #cbd5e1;
  margin: 0 auto 16px;
}

.empty-state h3 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 8px 0;
}

.empty-state p {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 16px 0;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: white;
  border-radius: 20px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-xl {
  max-width: 1000px;
}

.modal-sm {
  max-width: 450px;
}

.modal-header {
  padding: 24px 28px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.4px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-icon {
  width: 24px;
  height: 24px;
  color: #667eea;
}

.modal-close {
  width: 36px;
  height: 36px;
  border: none;
  background: #f8fafc;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.modal-close:hover {
  background: #f1f5f9;
  transform: scale(1.05);
}

.modal-close svg {
  width: 20px;
  height: 20px;
  color: #64748b;
}

.modal-body {
  padding: 28px;
  max-height: calc(90vh - 160px);
  overflow-y: auto;
}

.modal-actions {
  padding: 20px 28px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: #f8fafc;
}

/* Form Styles */
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.form-group label .required {
  color: #ef4444;
  font-weight: 700;
  margin-left: 2px;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
  resize: vertical;
  font-family: inherit;
}

.form-grid-full {
  grid-column: 1 / -1;
}

.form-section-header {
  grid-column: 1 / -1;
  margin-top: 24px;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.form-section-header:first-child {
  margin-top: 0;
}

.form-section-title {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  margin-bottom: 8px;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.checkbox-text {
  font-size: 14px;
  color: #334155;
  font-weight: 600;
}

.form-help {
  font-size: 12px;
  color: #64748b;
  margin-top: 4px;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5a67d8;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.spinner-sm {
  width: 14px;
  height: 14px;
}

/* View Styles */
.assessment-view {
  space-y: 24px;
}

.patient-info-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 24px;
  color: white;
}

.patient-header {
  display: flex;
  align-items: center;
  gap: 20px;
}

.patient-avatar-large img {
  width: 80px;
  height: 80px;
  border-radius: 16px;
  border: 4px solid rgba(255, 255, 255, 0.3);
}

.patient-info-details {
  flex: 1;
}

.patient-name-large {
  font-size: 24px;
  font-weight: 800;
  margin: 0 0 8px 0;
}

.patient-meta {
  display: flex;
  gap: 12px;
  margin-bottom: 8px;
  font-size: 14px;
  opacity: 0.95;
  flex-wrap: wrap;
}

.meta-item {
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 12px;
  border-radius: 8px;
}

.assessment-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
  opacity: 0.95;
}

.risk-indicator {
  flex-shrink: 0;
}

/* Assessment Sections */
.assessment-sections {
  space-y: 20px;
}

.assessment-section {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.section-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 16px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-content {
  color: #374151;
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.info-item label {
  font-weight: 600;
  color: #64748b;
  font-size: 12px;
  display: block;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item p {
  margin: 0;
  color: #0f172a;
  font-weight: 500;
}

/* Emergency Contacts */
.emergency-contacts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.emergency-contact {
  background: #f9fafb;
  padding: 16px;
  border-radius: 10px;
}

.contact-header {
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.contact-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
}

.contact-details strong {
  color: #0f172a;
  font-weight: 600;
}

.contact-details span {
  color: #64748b;
}

/* Medical History */
.medical-history {
  space-y: 16px;
}

.history-item label {
  font-weight: 600;
  color: #64748b;
  font-size: 12px;
  display: block;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.history-item p {
  margin: 0;
  color: #0f172a;
  line-height: 1.6;
}

/* Assessment Results */
.assessment-results {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.result-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f9fafb;
  border-radius: 10px;
}

.result-item label {
  font-weight: 600;
  color: #64748b;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.result-value {
  font-weight: 700;
  color: #0f172a;
}

.wound-description {
  margin-top: 16px;
  padding: 16px;
  background: #fef7f7;
  border-left: 4px solid #dc2626;
  border-radius: 10px;
}

.wound-description label {
  font-weight: 600;
  color: #991b1b;
  font-size: 12px;
  display: block;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.wound-description p {
  margin: 0;
  color: #7f1d1d;
  line-height: 1.6;
}

/* Vitals Grid */
.vitals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
}

.vital-card {
  background: #f9fafb;
  padding: 16px;
  border-radius: 10px;
  text-align: center;
}

.vital-label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.vital-value {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
}

/* Nursing Impression */
.nursing-impression p {
  margin: 0;
  line-height: 1.7;
  color: #374151;
}

.mt-3 {
  margin-top: 12px;
}

.p-3 {
  padding: 12px;
}

.bg-gray-50 {
  background: #f9fafb;
}

.rounded {
  border-radius: 8px;
}

.text-gray-600 {
  color: #64748b;
}

/* Responsive Design */
@media (max-width: 768px) {
  .medical-assessment-page {
    padding: 16px;
  }
  
  .page-header {
    flex-direction: column;
    gap: 16px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-section {
    flex-direction: column;
  }
  
  .search-wrapper {
    min-width: 100%;
  }
  
  .filters-group {
    flex-direction: column;
  }
  
  .filter-select {
    width: 100%;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .patient-header {
    flex-direction: column;
    text-align: center;
  }
  
  .patient-meta {
    flex-direction: column;
    gap: 8px;
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
  .vitals-grid {
    grid-template-columns: 1fr;
  }
}
</style>