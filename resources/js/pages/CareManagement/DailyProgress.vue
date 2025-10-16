<template>
  <MainLayout>
    <div class="progress-notes-page">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
          <div class="page-header-content">
            <h1>Daily Progress Notes</h1>
            <p>Record and manage daily nursing progress notes for patient care</p>
          </div>
          <div class="page-header-actions">
            <button
              @click="exportNotes"
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
              Add Progress Note
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
                placeholder="Search by patient name, nurse, or notes..."
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
              <input
                type="date"
                v-model="dateFilter"
                class="filter-select"
                style="width: auto;"
              />
              <select
                v-model="dateType"
                class="filter-select"
                style="width: auto;"
              >
                <option value="visit">Visit Date</option>
                <option value="created">Created Date</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p class="loading-text">Loading progress notes...</p>
        </div>

        <!-- Progress Notes Table -->
        <div v-else class="progress-notes-table-container">
          <div class="overflow-x-auto">
            <table class="progress-notes-table">
              <thead>
                <tr>
                  <th>Patient</th>
                  <th>Nurse/Caregiver</th>
                  <th>Visit Date & Time</th>
                  <th>General Condition</th>
                  <th>Pain Level</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="note in filteredNotes" :key="note.id">
                  <td>
                    <div class="patient-info">
                      <div class="patient-avatar">
                        <img :src="note.patient_avatar_url" :alt="note.patient_name" />
                      </div>
                      <div class="patient-details">
                        <div class="patient-name">{{ note.patient_name }}</div>
                        <div class="patient-id">ID: {{ note.patient_id }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="nurse-info">
                      <div class="nurse-name">{{ note.nurse_name }}</div>
                      <div class="nurse-license">{{ note.nurse_license || 'N/A' }}</div>
                    </div>
                  </td>
                  <td>
                    <div class="visit-info">
                      <div class="visit-date">{{ formatDate(note.visit_date) }}</div>
                      <div class="visit-time">{{ note.visit_time }}</div>
                    </div>
                  </td>
                  <td>
                    <span :class="'badge ' + getConditionBadgeColor(note.general_condition)">
                      {{ capitalizeFirst(note.general_condition) }}
                    </span>
                  </td>
                  <td>
                    <div class="pain-level">
                      <span class="pain-score">{{ note.pain_level }}/10</span>
                      <div class="pain-indicator">
                        <div 
                          class="pain-bar" 
                          :style="{ width: (note.pain_level * 10) + '%', backgroundColor: getPainColor(note.pain_level) }"
                        ></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="action-dropdown">
                      <button
                        @click="toggleDropdown(note.id)"
                        class="btn btn-secondary btn-sm"
                        style="min-width: auto; padding: 0.5rem;"
                      >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                      </button>
                      <div v-show="activeDropdown === note.id" class="dropdown-menu">
                        <button
                          @click="openViewModal(note)"
                          class="dropdown-item"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                          View Details
                        </button>
                        <button
                          @click="openEditModal(note)"
                          class="dropdown-item"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                          Edit Note
                        </button>
                        <button
                          @click="duplicateNote(note)"
                          class="dropdown-item"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                          </svg>
                          Duplicate
                        </button>
                        <div class="dropdown-divider"></div>
                        <button
                          @click="openDeleteModal(note)"
                          class="dropdown-item dropdown-item-danger"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                          Delete Note
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div v-if="filteredNotes.length === 0" class="empty-state">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3>No progress notes found</h3>
            <p>
              {{ (searchQuery || patientFilter !== 'all' || nurseFilter !== 'all' || dateFilter) 
                ? 'Try adjusting your search or filters.' 
                : 'Get started by adding a new progress note.' }}
            </p>
          </div>
        </div>

        <!-- Create/Edit Progress Note Modal -->
        <div v-if="showNoteModal" class="modal-overlay">
          <div class="modal modal-xl">
            <div class="modal-header">
              <h2 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ isEditing ? 'Edit Progress Note' : 'New Progress Note' }}
              </h2>
              <button @click="closeNoteModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="saveNote" id="noteForm">
              <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="form-grid">
                  <!-- Basic Information -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">📘 Visit Information</h3>
                  </div>
                  
                  <div class="form-group">
                    <label>Patient *</label>
                    <SearchableSelect
                      v-model="noteForm.patient_id"
                      :options="patientOptions"
                      placeholder="Select a patient..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Nurse/Caregiver *</label>
                    <SearchableSelect
                      v-model="noteForm.nurse_id"
                      :options="nurseOptions"
                      placeholder="Select a nurse..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Visit Date *</label>
                    <input
                      type="date"
                      v-model="noteForm.visit_date"
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Visit Time *</label>
                    <input
                      type="time"
                      v-model="noteForm.visit_time"
                      required
                    />
                  </div>

                  <!-- Vital Signs -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">🩺 Vital Signs</h3>
                  </div>

                  <div class="form-group">
                    <label>Temperature (°C)</label>
                    <input
                      type="number"
                      step="0.1"
                      v-model="noteForm.vitals.temperature"
                      placeholder="36.5"
                    />
                  </div>

                  <div class="form-group">
                    <label>Pulse (bpm)</label>
                    <input
                      type="number"
                      v-model="noteForm.vitals.pulse"
                      placeholder="72"
                    />
                  </div>

                  <div class="form-group">
                    <label>Respiration (/min)</label>
                    <input
                      type="number"
                      v-model="noteForm.vitals.respiration"
                      placeholder="16"
                    />
                  </div>

                  <div class="form-group">
                    <label>Blood Pressure (mmHg)</label>
                    <input
                      type="text"
                      v-model="noteForm.vitals.blood_pressure"
                      placeholder="120/80"
                    />
                  </div>

                  <div class="form-group">
                    <label>SpO₂ (%)</label>
                    <input
                      type="number"
                      min="0"
                      max="100"
                      v-model="noteForm.vitals.spo2"
                      placeholder="98"
                    />
                  </div>

                  <!-- Interventions Provided -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">💊 Interventions Provided</h3>
                  </div>

                  <!-- Fixed Intervention Sections with proper styling -->
                  <div class="form-group form-grid-full">
                    <label class="checkbox-label">
                      <input type="checkbox" v-model="noteForm.interventions.medication_administered" />
                      <span class="checkmark"></span>
                      <span class="checkbox-text">Medication Administered</span>
                    </label>
                    <input
                      v-show="noteForm.interventions.medication_administered"
                      type="text"
                      v-model="noteForm.interventions.medication_details"
                      placeholder="List medications administered..."
                      class="intervention-input"
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label class="checkbox-label">
                      <input type="checkbox" v-model="noteForm.interventions.wound_care" />
                      <span class="checkmark"></span>
                      <span class="checkbox-text">Wound Care</span>
                    </label>
                    <input
                      v-show="noteForm.interventions.wound_care"
                      type="text"
                      v-model="noteForm.interventions.wound_care_details"
                      placeholder="Describe wound care provided..."
                      class="intervention-input"
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label class="checkbox-label">
                      <input type="checkbox" v-model="noteForm.interventions.physiotherapy" />
                      <span class="checkmark"></span>
                      <span class="checkbox-text">Physiotherapy/Exercise</span>
                    </label>
                    <input
                      v-show="noteForm.interventions.physiotherapy"
                      type="text"
                      v-model="noteForm.interventions.physiotherapy_details"
                      placeholder="Describe exercises/physiotherapy..."
                      class="intervention-input"
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label class="checkbox-label">
                      <input type="checkbox" v-model="noteForm.interventions.nutrition_support" />
                      <span class="checkmark"></span>
                      <span class="checkbox-text">Nutrition/Feeding Support</span>
                    </label>
                    <input
                      v-show="noteForm.interventions.nutrition_support"
                      type="text"
                      v-model="noteForm.interventions.nutrition_details"
                      placeholder="Describe nutrition support provided..."
                      class="intervention-input"
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label class="checkbox-label">
                      <input type="checkbox" v-model="noteForm.interventions.hygiene_care" />
                      <span class="checkmark"></span>
                      <span class="checkbox-text">Hygiene/Personal Care</span>
                    </label>
                    <input
                      v-show="noteForm.interventions.hygiene_care"
                      type="text"
                      v-model="noteForm.interventions.hygiene_details"
                      placeholder="Describe personal care provided..."
                      class="intervention-input"
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label class="checkbox-label">
                      <input type="checkbox" v-model="noteForm.interventions.counseling_education" />
                      <span class="checkmark"></span>
                      <span class="checkbox-text">Counseling/Education</span>
                    </label>
                    <input
                      v-show="noteForm.interventions.counseling_education"
                      type="text"
                      v-model="noteForm.interventions.counseling_details"
                      placeholder="Describe counseling/education provided..."
                      class="intervention-input"
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label class="checkbox-label">
                      <input type="checkbox" v-model="noteForm.interventions.other" />
                      <span class="checkmark"></span>
                      <span class="checkbox-text">Other Interventions</span>
                    </label>
                    <input
                      v-show="noteForm.interventions.other"
                      type="text"
                      v-model="noteForm.interventions.other_details"
                      placeholder="Specify other interventions..."
                      class="intervention-input"
                    />
                  </div>

                  <!-- Observations/Findings -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">👁️ Observations/Findings</h3>
                  </div>

                  <div class="form-group">
                    <label>General Condition *</label>
                    <SearchableSelect
                      v-model="noteForm.general_condition"
                      :options="conditionOptions"
                      placeholder="Select condition..."
                      required
                    />
                  </div>

                  <div class="form-group">
                    <label>Pain Level (0-10) *</label>
                    <input
                      type="number"
                      min="0"
                      max="10"
                      v-model="noteForm.pain_level"
                      required
                    />
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Wound Status (if any)</label>
                    <textarea
                      v-model="noteForm.wound_status"
                      rows="3"
                      placeholder="Describe wound status, healing progress, etc..."
                    ></textarea>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Other Significant Observations</label>
                    <textarea
                      v-model="noteForm.other_observations"
                      rows="3"
                      placeholder="Note any other significant observations..."
                    ></textarea>
                  </div>

                  <!-- Family/Client Communication -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">👨‍👩‍👧‍👦 Family/Client Communication</h3>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Education Provided</label>
                    <textarea
                      v-model="noteForm.education_provided"
                      rows="3"
                      placeholder="Describe education provided to patient/family..."
                    ></textarea>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Concerns Raised by Family/Client</label>
                    <textarea
                      v-model="noteForm.family_concerns"
                      rows="3"
                      placeholder="Note any concerns raised by family or client..."
                    ></textarea>
                  </div>

                  <!-- Plan/Next Steps -->
                  <div class="form-section-header">
                    <h3 class="form-section-title">📋 Plan / Next Steps</h3>
                  </div>

                  <div class="form-group form-grid-full">
                    <label>Plan for Next Visit</label>
                    <textarea
                      v-model="noteForm.next_steps"
                      rows="4"
                      placeholder="Outline plans for the next visit, follow-up care, adjustments needed..."
                    ></textarea>
                  </div>
                </div>
              </div>

              <div class="modal-actions">
                <button type="button" @click="closeNoteModal" class="btn btn-secondary">
                  Cancel
                </button>
                <button 
                  type="submit" 
                  form="noteForm" 
                  :disabled="isSaving" 
                  class="btn btn-primary"
                >
                  <div v-if="isSaving" class="spinner spinner-sm"></div>
                  {{ isEditing ? 'Update Note' : 'Save Note' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- View Progress Note Modal -->
        <div v-if="showViewModal && currentNote" class="modal-overlay">
          <div class="modal modal-xl">
            <div class="modal-header">
              <h2 class="modal-title">
                <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Daily Progress Note - {{ currentNote.patient_name }}
              </h2>
              <button @click="closeViewModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
              <div class="progress-note-view">
                <!-- Header Information -->
                <div class="note-header-card">
                  <div class="header-info">
                    <div class="patient-nurse-info">
                      <div class="info-item">
                        <label>Patient:</label>
                        <span>{{ currentNote.patient_name }}</span>
                      </div>
                      <div class="info-item">
                        <label>Nurse/Caregiver:</label>
                        <span>{{ currentNote.nurse_name }}</span>
                      </div>
                      <div class="info-item">
                        <label>Visit Date:</label>
                        <span>{{ formatDate(currentNote.visit_date) }}</span>
                      </div>
                      <div class="info-item">
                        <label>Visit Time:</label>
                        <span>{{ currentNote.visit_time }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Vital Signs -->
                <div class="note-section">
                  <h4 class="section-title">🩺 Vital Signs</h4>
                  <div class="vitals-grid">
                    <div class="vital-item">
                      <label>Temperature:</label>
                      <span>{{ currentNote.vitals?.temperature || 'N/A' }} °C</span>
                    </div>
                    <div class="vital-item">
                      <label>Pulse:</label>
                      <span>{{ currentNote.vitals?.pulse || 'N/A' }} bpm</span>
                    </div>
                    <div class="vital-item">
                      <label>Respiration:</label>
                      <span>{{ currentNote.vitals?.respiration || 'N/A' }} /min</span>
                    </div>
                    <div class="vital-item">
                      <label>Blood Pressure:</label>
                      <span>{{ currentNote.vitals?.blood_pressure || 'N/A' }} mmHg</span>
                    </div>
                    <div class="vital-item">
                      <label>SpO₂:</label>
                      <span>{{ currentNote.vitals?.spo2 || 'N/A' }} %</span>
                    </div>
                  </div>
                </div>

                <!-- Interventions -->
                <div class="note-section">
                  <h4 class="section-title">💊 Interventions Provided</h4>
                  <div class="interventions-list">
                    <div v-if="currentNote.interventions?.medication_administered" class="intervention-item">
                      <strong>✓ Medication Administered:</strong>
                      <span>{{ currentNote.interventions.medication_details || 'Details not provided' }}</span>
                    </div>
                    <div v-if="currentNote.interventions?.wound_care" class="intervention-item">
                      <strong>✓ Wound Care:</strong>
                      <span>{{ currentNote.interventions.wound_care_details || 'Details not provided' }}</span>
                    </div>
                    <div v-if="currentNote.interventions?.physiotherapy" class="intervention-item">
                      <strong>✓ Physiotherapy/Exercise:</strong>
                      <span>{{ currentNote.interventions.physiotherapy_details || 'Details not provided' }}</span>
                    </div>
                    <div v-if="currentNote.interventions?.nutrition_support" class="intervention-item">
                      <strong>✓ Nutrition/Feeding Support:</strong>
                      <span>{{ currentNote.interventions.nutrition_details || 'Details not provided' }}</span>
                    </div>
                    <div v-if="currentNote.interventions?.hygiene_care" class="intervention-item">
                      <strong>✓ Hygiene/Personal Care:</strong>
                      <span>{{ currentNote.interventions.hygiene_details || 'Details not provided' }}</span>
                    </div>
                    <div v-if="currentNote.interventions?.counseling_education" class="intervention-item">
                      <strong>✓ Counseling/Education:</strong>
                      <span>{{ currentNote.interventions.counseling_details || 'Details not provided' }}</span>
                    </div>
                    <div v-if="currentNote.interventions?.other" class="intervention-item">
                      <strong>✓ Other:</strong>
                      <span>{{ currentNote.interventions.other_details || 'Details not provided' }}</span>
                    </div>
                    <div v-if="!hasAnyInterventions(currentNote.interventions)" class="no-interventions">
                      <em>No specific interventions documented.</em>
                    </div>
                  </div>
                </div>

                <!-- Observations -->
                <div class="note-section">
                  <h4 class="section-title">👁️ Observations/Findings</h4>
                  <div class="observations-grid">
                    <div class="observation-item">
                      <label>General Condition:</label>
                      <span :class="'badge ' + getConditionBadgeColor(currentNote.general_condition)">
                        {{ capitalizeFirst(currentNote.general_condition) }}
                      </span>
                    </div>
                    <div class="observation-item">
                      <label>Pain Level:</label>
                      <span class="pain-display">
                        {{ currentNote.pain_level }}/10
                        <div class="pain-indicator-small">
                          <div 
                            class="pain-bar-small" 
                            :style="{ width: (currentNote.pain_level * 10) + '%', backgroundColor: getPainColor(currentNote.pain_level) }"
                          ></div>
                        </div>
                      </span>
                    </div>
                  </div>
                  <div v-if="currentNote.wound_status" class="observation-text">
                    <label>Wound Status:</label>
                    <p>{{ currentNote.wound_status }}</p>
                  </div>
                  <div v-if="currentNote.other_observations" class="observation-text">
                    <label>Other Observations:</label>
                    <p>{{ currentNote.other_observations }}</p>
                  </div>
                </div>

                <!-- Communication -->
                <div class="note-section">
                  <h4 class="section-title">👨‍👩‍👧‍👦 Family/Client Communication</h4>
                  <div v-if="currentNote.education_provided" class="communication-item">
                    <label>Education Provided:</label>
                    <p>{{ currentNote.education_provided }}</p>
                  </div>
                  <div v-if="currentNote.family_concerns" class="communication-item">
                    <label>Concerns Raised:</label>
                    <p>{{ currentNote.family_concerns }}</p>
                  </div>
                  <div v-if="!currentNote.education_provided && !currentNote.family_concerns" class="no-communication">
                    <em>No communication details documented.</em>
                  </div>
                </div>

                <!-- Plan/Next Steps -->
                <div class="note-section">
                  <h4 class="section-title">📋 Plan / Next Steps</h4>
                  <div v-if="currentNote.next_steps" class="plan-content">
                    <p>{{ currentNote.next_steps }}</p>
                  </div>
                  <div v-else class="no-plan">
                    <em>No plan documented for next visit.</em>
                  </div>
                </div>

                <!-- Signature & Timestamp -->
                <div class="note-footer">
                  <div class="signature-section">
                    <div class="signature-info">
                      <label>Nurse/Caregiver Signature:</label>
                      <span class="signature">{{ currentNote.nurse_name }}</span>
                    </div>
                    <div class="timestamp-info">
                      <label>Created:</label>
                      <span>{{ formatDateTime(currentNote.created_at) }}</span>
                    </div>
                    <div v-if="currentNote.updated_at !== currentNote.created_at" class="timestamp-info">
                      <label>Last Updated:</label>
                      <span>{{ formatDateTime(currentNote.updated_at) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button @click="closeViewModal" class="btn btn-secondary">
                Close
              </button>
              <button @click="printNote" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print
              </button>
              <button @click="editFromView" class="btn btn-primary">
                Edit Note
              </button>
            </div>
          </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal && currentNote" class="modal-overlay">
          <div class="modal modal-sm">
            <div class="modal-header modal-header-danger">
              <h3 class="modal-title">
                <svg class="modal-icon modal-icon-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                Delete Progress Note
              </h3>
              <button @click="closeDeleteModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <div class="modal-body">
              <p>
                Are you sure you want to delete this progress note for <strong>{{ currentNote.patient_name }}</strong>? 
                This action cannot be undone.
              </p>
              <div class="mt-3 p-3 bg-gray-50 rounded">
                <small class="text-gray-600">
                  <strong>Visit:</strong> {{ formatDate(currentNote.visit_date) }} at {{ currentNote.visit_time }}
                </small>
              </div>
            </div>

            <div class="modal-actions">
              <button @click="closeDeleteModal" class="btn btn-secondary">
                Cancel
              </button>
              <button
                @click="deleteNote"
                :disabled="isSaving"
                class="btn btn-danger"
              >
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                Delete Note
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
const progressNotes = ref([])
const patients = ref([])
const nurses = ref([])
const loading = ref(true)
const searchQuery = ref('')
const patientFilter = ref('all')
const nurseFilter = ref('all')
const dateFilter = ref('')
const dateType = ref('visit') // 'visit' for visit_date, 'created' for created_at

// Modal states
const showNoteModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const currentNote = ref(null)
const isSaving = ref(false)

// Dropdown state
const activeDropdown = ref(null)

// Options for select fields
const conditionOptions = [
  { value: 'improved', label: 'Improved' },
  { value: 'stable', label: 'Stable' },
  { value: 'deteriorating', label: 'Deteriorating' }
]

// Form data
const noteForm = ref({
  patient_id: '',
  nurse_id: '',
  visit_date: '',
  visit_time: '',
  vitals: {
    temperature: '',
    pulse: '',
    respiration: '',
    blood_pressure: '',
    spo2: ''
  },
  interventions: {
    medication_administered: false,
    medication_details: '',
    wound_care: false,
    wound_care_details: '',
    physiotherapy: false,
    physiotherapy_details: '',
    nutrition_support: false,
    nutrition_details: '',
    hygiene_care: false,
    hygiene_details: '',
    counseling_education: false,
    counseling_details: '',
    other: false,
    other_details: ''
  },
  general_condition: '',
  pain_level: 0,
  wound_status: '',
  other_observations: '',
  education_provided: '',
  family_concerns: '',
  next_steps: ''
})

// Computed properties
const filteredNotes = computed(() => {
  return progressNotes.value.filter(note => {
    const matchesSearch = !searchQuery.value || 
      note.patient_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      note.nurse_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      note.other_observations?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      note.next_steps?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchesPatient = patientFilter.value === 'all' || note.patient_id == patientFilter.value
    const matchesNurse = nurseFilter.value === 'all' || note.nurse_id == nurseFilter.value
    
    return matchesSearch && matchesPatient && matchesNurse
  })
})

const patientOptions = computed(() => {
  return patients.value.map(patient => ({
    value: patient.id,
    label: `${patient.first_name} ${patient.last_name}`
  }))
})

const nurseOptions = computed(() => {
  return nurses.value.map(nurse => ({
    value: nurse.id,
    label: `${nurse.first_name} ${nurse.last_name}`
  }))
})

// Methods
const loadProgressNotes = async () => {
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
    
    if (dateFilter.value) {
      params.append('date', dateFilter.value)
      params.append('date_type', dateType.value)
    }
    
    if (searchQuery.value) {
      params.append('search', searchQuery.value)
    }
    
    const queryString = params.toString()
    const url = `/api/progress-notes${queryString ? '?' + queryString : ''}`
    
    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      progressNotes.value = data.data || data
      console.log('Loaded progress notes:', progressNotes.value.length)
    } else {
      console.error('Failed to load progress notes')      
    }
  } catch (error) {
    console.error('Error loading progress notes:', error)
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

const getConditionBadgeColor = (condition) => {
  const colorMap = {
    'improved': 'badge-success',
    'stable': 'badge-warning',
    'deteriorating': 'badge-danger'
  }
  return colorMap[condition] || 'badge-secondary'
}

const getPainColor = (painLevel) => {
  if (painLevel <= 3) return '#10b981' // Green
  if (painLevel <= 6) return '#f59e0b' // Yellow
  return '#ef4444' // Red
}

const capitalizeFirst = (str) => {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

// Fix for Issue 1: Date formatting helper functions
const formatDateForInput = (dateString) => {
  if (!dateString) return ''
  
  // Handle different date formats that might come from backend
  const date = new Date(dateString)
  
  // Check if date is valid
  if (isNaN(date.getTime())) {
    console.warn('Invalid date received:', dateString)
    return ''
  }
  
  // Format as YYYY-MM-DD for HTML date input
  return date.toISOString().split('T')[0]
}

const formatTimeForInput = (timeString) => {
  if (!timeString) return ''
  
  // Handle different time formats
  if (timeString.includes(':')) {
    // If it's already in HH:MM format, use as is
    const timeParts = timeString.split(':')
    if (timeParts.length >= 2) {
      return `${timeParts[0].padStart(2, '0')}:${timeParts[1].padStart(2, '0')}`
    }
  }
  
  return timeString
}

const toggleDropdown = (noteId) => {
  activeDropdown.value = activeDropdown.value === noteId ? null : noteId
}

const openCreateModal = () => {
  isEditing.value = false
  currentNote.value = null
  resetForm()
  showNoteModal.value = true
}

const openEditModal = (note) => {
  isEditing.value = true
  currentNote.value = note
  populateForm(note)
  showNoteModal.value = true
  activeDropdown.value = null
}

const openViewModal = (note) => {
  currentNote.value = note
  showViewModal.value = true
  activeDropdown.value = null
}

const openDeleteModal = (note) => {
  currentNote.value = note
  showDeleteModal.value = true
  activeDropdown.value = null
}

const closeNoteModal = () => {
  showNoteModal.value = false
}

const closeViewModal = () => {
  showViewModal.value = false
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
}

const editFromView = () => {
  closeViewModal()
  openEditModal(currentNote.value)
}

const resetForm = () => {
  noteForm.value = {
    patient_id: '',
    nurse_id: '',
    visit_date: new Date().toISOString().split('T')[0],
    visit_time: new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }),
    vitals: {
      temperature: '',
      pulse: '',
      respiration: '',
      blood_pressure: '',
      spo2: ''
    },
    interventions: {
      medication_administered: false,
      medication_details: '',
      wound_care: false,
      wound_care_details: '',
      physiotherapy: false,
      physiotherapy_details: '',
      nutrition_support: false,
      nutrition_details: '',
      hygiene_care: false,
      hygiene_details: '',
      counseling_education: false,
      counseling_details: '',
      other: false,
      other_details: ''
    },
    general_condition: 'stable',
    pain_level: 0,
    wound_status: '',
    other_observations: '',
    education_provided: '',
    family_concerns: '',
    next_steps: ''
  }
}

// Fixed populateForm function with proper date formatting
const populateForm = (note) => {
  noteForm.value = {
    patient_id: note.patient_id,
    nurse_id: note.nurse_id,
    visit_date: formatDateForInput(note.visit_date), // Fixed date formatting
    visit_time: formatTimeForInput(note.visit_time), // Fixed time formatting
    vitals: { 
      temperature: note.vitals?.temperature || '',
      pulse: note.vitals?.pulse || '',
      respiration: note.vitals?.respiration || '',
      blood_pressure: note.vitals?.blood_pressure || '',
      spo2: note.vitals?.spo2 || ''
    },
    interventions: { 
      medication_administered: note.interventions?.medication_administered || false,
      medication_details: note.interventions?.medication_details || '',
      wound_care: note.interventions?.wound_care || false,
      wound_care_details: note.interventions?.wound_care_details || '',
      physiotherapy: note.interventions?.physiotherapy || false,
      physiotherapy_details: note.interventions?.physiotherapy_details || '',
      nutrition_support: note.interventions?.nutrition_support || false,
      nutrition_details: note.interventions?.nutrition_details || '',
      hygiene_care: note.interventions?.hygiene_care || false,
      hygiene_details: note.interventions?.hygiene_details || '',
      counseling_education: note.interventions?.counseling_education || false,
      counseling_details: note.interventions?.counseling_details || '',
      other: note.interventions?.other || false,
      other_details: note.interventions?.other_details || ''
    },
    general_condition: note.general_condition,
    pain_level: note.pain_level,
    wound_status: note.wound_status || '',
    other_observations: note.other_observations || '',
    education_provided: note.education_provided || '',
    family_concerns: note.family_concerns || '',
    next_steps: note.next_steps || ''
  }
}

const saveNote = async () => {
  isSaving.value = true
  
  try {
    const url = isEditing.value ? `/api/progress-notes/${currentNote.value.id}` : '/api/progress-notes'
    const method = isEditing.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(noteForm.value)
    })
    
    if (response.ok) {
      await loadProgressNotes()
      closeNoteModal()
      toast.showSuccess(isEditing.value ? 'Progress note updated successfully!' : 'Progress note created successfully!')
    } else {
      console.error('Failed to save progress note')
      toast.showError(isEditing.value ? 'Failed to update progress note. Please try again.' : 'Failed to create progress note. Please try again.')
    }
  } catch (error) {
    console.error('Error saving progress note:', error)
    toast.showError(isEditing.value ? 'An error occurred while updating the progress note.' : 'An error occurred while creating the progress note.')
  }
  
  isSaving.value = false
}

const deleteNote = async () => {
  isSaving.value = true
  
  try {
    const response = await fetch(`/api/progress-notes/${currentNote.value.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      await loadProgressNotes()
      closeDeleteModal()
      toast.showSuccess('Progress note deleted successfully!')
    } else {
      console.error('Failed to delete progress note')
      toast.showError('Failed to delete progress note. Please try again.')
    }
  } catch (error) {
    console.error('Error deleting progress note:', error)
    toast.showError('An error occurred while deleting the progress note.')
  }
  
  isSaving.value = false
}

const duplicateNote = async (note) => {
  // Create a new note based on the existing one
  isEditing.value = false
  currentNote.value = null
  populateForm(note)
  // Clear visit date and time for new entry
  noteForm.value.visit_date = new Date().toISOString().split('T')[0]
  noteForm.value.visit_time = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
  showNoteModal.value = true
  activeDropdown.value = null
}

const hasAnyInterventions = (interventions) => {
  if (!interventions) return false
  return Object.keys(interventions).some(key => 
    key !== 'medication_details' && 
    key !== 'wound_care_details' && 
    key !== 'physiotherapy_details' && 
    key !== 'nutrition_details' && 
    key !== 'hygiene_details' && 
    key !== 'counseling_details' && 
    key !== 'other_details' && 
    interventions[key] === true
  )
}

const printNote = () => {
  // Implementation for printing the note
  window.print()
}

const exportNotes = async () => {
  try {
    const loadingToast = toast.showInfo ? toast.showInfo('Preparing export...') : null
    
    // Build query parameters based on current filters
    const params = new URLSearchParams()
    
    if (patientFilter.value !== 'all') {
      params.append('patient_id', patientFilter.value)
    }
    
    if (nurseFilter.value !== 'all') {
      params.append('nurse_id', nurseFilter.value)
    }
    
    if (dateFilter.value) {
      params.append('date', dateFilter.value)
      params.append('date_type', dateType.value)
    }
    
    if (searchQuery.value) {
      params.append('search', searchQuery.value)
    }
    
    const queryString = params.toString()
    const url = `/api/progress-notes/export${queryString ? '?' + queryString : ''}`
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      }
    })
    
    if (response.ok) {
      const contentDisposition = response.headers.get('Content-Disposition')
      let filename = 'progress_notes_export.csv'
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
      
      toast.showSuccess('Progress notes exported successfully!')
    } else {
      const errorData = await response.json()
      console.error('Failed to export progress notes:', errorData)
      toast.showError(errorData.message || 'Failed to export progress notes. Please try again.')
    }
  } catch (error) {
    console.error('Error exporting progress notes:', error)
    toast.showError('An error occurred while exporting progress notes.')
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
    loadProgressNotes(),
    loadPatients(),
    loadNurses()
  ])
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Watch for filter changes and reload data
watch([patientFilter, nurseFilter, dateFilter, dateType, searchQuery], () => {
  // Debounce the search to avoid too many API calls
  if (searchQuery.value) {
    setTimeout(() => {
      loadProgressNotes()
    }, 500)
  } else {
    loadProgressNotes()
  }
}, { deep: true })
</script>

<style scoped>
/* Progress Notes Specific Styles */
.progress-notes-page {
  min-height: 100vh;
  background: #f8f9fa;
}

.progress-notes-table-container {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: visible;
}

.progress-notes-table {
  width: 100%;
  border-collapse: collapse;
}

.progress-notes-table thead {
  background: #f9fafb;
}

.progress-notes-table th {
  padding: 0.75rem 1.5rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.progress-notes-table tbody tr:hover {
  background: #f9fafb;
}

.progress-notes-table td {
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

/* Visit Info */
.visit-info .visit-date {
  font-weight: 500;
  color: #1f2937;
}

.visit-info .visit-time {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Pain Level Display */
.pain-level {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.pain-score {
  font-weight: 500;
  color: #1f2937;
}

.pain-indicator {
  width: 60px;
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
}

.pain-bar {
  height: 100%;
  transition: width 0.3s ease;
}

/* Form Sections */
.form-section-header {
  grid-column: 1 / -1;
  margin-top: 2rem;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
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

/* Fix for Issue 2: Intervention input styling */
.intervention-input {
  width: 100%;
  padding: 0.75rem;
  margin-top: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: white;
}

.intervention-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.intervention-input::placeholder {
  color: #9ca3af;
}

/* Improved checkbox styling for interventions */
.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  cursor: pointer;
  user-select: none;
  margin-bottom: 0.5rem;
}

.checkbox-label input[type="checkbox"] {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

.checkmark {
  width: 18px;
  height: 18px;
  background-color: #fff;
  border: 2px solid #d1d5db;
  border-radius: 4px;
  position: relative;
  transition: all 0.2s;
  flex-shrink: 0;
  margin-top: 2px;
}

.checkbox-label:hover input[type="checkbox"]:not(:disabled) ~ .checkmark {
  border-color: #3b82f6;
}

.checkbox-label input[type="checkbox"]:checked ~ .checkmark {
  background-color: #3b82f6;
  border-color: #3b82f6;
}

.checkbox-label input[type="checkbox"]:checked ~ .checkmark::after {
  content: '';
  position: absolute;
  left: 5px;
  top: 2px;
  width: 4px;
  height: 8px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.checkbox-text {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
  line-height: 1.5;
}

/* View Modal Styles */
.progress-note-view {
  space-y: 1.5rem;
}

.note-header-card {
  background: #f8fafc;
  border-radius: 0.75rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.patient-nurse-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
}

.info-item span {
  font-weight: 600;
  color: #1f2937;
}

.note-section {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
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

/* Vitals Grid */
.vitals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.vital-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.vital-item label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
}

.vital-item span {
  font-weight: 600;
  color: #1f2937;
}

/* Interventions List */
.interventions-list {
  space-y: 1rem;
}

.intervention-item {
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  border-left: 4px solid #10b981;
}

.intervention-item strong {
  display: block;
  color: #065f46;
  margin-bottom: 0.25rem;
}

.intervention-item span {
  color: #374151;
}

.no-interventions {
  padding: 1rem;
  text-align: center;
  color: #6b7280;
  background: #f9fafb;
  border-radius: 0.5rem;
}

/* Observations */
.observations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.observation-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.observation-item label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
}

.observation-text {
  margin-top: 1rem;
}

.observation-text label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
  display: block;
  margin-bottom: 0.5rem;
}

.observation-text p {
  margin: 0;
  color: #374151;
  line-height: 1.5;
}

/* Pain Display in View */
.pain-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pain-indicator-small {
  width: 40px;
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
}

.pain-bar-small {
  height: 100%;
  transition: width 0.3s ease;
}

/* Communication & Plan */
.communication-item {
  margin-bottom: 1rem;
}

.communication-item:last-child {
  margin-bottom: 0;
}

.communication-item label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
  display: block;
  margin-bottom: 0.5rem;
}

.communication-item p {
  margin: 0;
  color: #374151;
  line-height: 1.5;
}

.no-communication,
.no-plan {
  padding: 1rem;
  text-align: center;
  color: #6b7280;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.plan-content p {
  margin: 0;
  color: #374151;
  line-height: 1.5;
}

/* Note Footer */
.note-footer {
  background: #f8fafc;
  border-radius: 0.75rem;
  padding: 1.5rem;
  margin-top: 1.5rem;
}

.signature-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.signature-info,
.timestamp-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.signature-info label,
.timestamp-info label {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
}

.signature {
  font-weight: 600;
  color: #1f2937;
  font-style: italic;
}

.timestamp-info span {
  color: #374151;
  font-size: 0.875rem;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem;
}

.empty-state svg {
  margin: 0 auto 1rem;
  width: 3rem;
  height: 3rem;
  color: #9ca3af;
}

.empty-state h3 {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
  font-weight: 500;
  color: #1f2937;
}

.empty-state p {
  margin: 0;
  font-size: 0.875rem;
  color: #6b7280;
}

/* Responsive Design */
@media (max-width: 768px) {
  .progress-notes-table {
    font-size: 0.8125rem;
  }
  
  .progress-notes-table th,
  .progress-notes-table td {
    padding: 0.75rem 1rem;
  }
  
  .patient-details {
    margin-left: 0.75rem;
  }
  
  .patient-nurse-info {
    grid-template-columns: 1fr;
  }
  
  .vitals-grid {
    grid-template-columns: 1fr;
  }
  
  .observations-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .progress-notes-table-container {
    overflow-x: auto;
  }
  
  .progress-notes-table {
    min-width: 800px;
  }
}
</style>