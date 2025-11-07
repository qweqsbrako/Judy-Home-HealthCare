<template>
  <MainLayout>
    <div class="progress-notes-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Daily Progress Notes</h1>
          <p>Record and manage daily nursing progress notes for patient care</p>
        </div>
        <div class="page-header-actions">
          <button @click="exportNotes" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button @click="openCreateModal" class="btn-modern btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Progress Note
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
            <div class="stat-label">Total Notes</div>
            <div class="stat-value">{{ totalNotes }}</div>
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
            <div class="stat-change positive">Served</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
              <path d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Avg Pain Level</div>
            <div class="stat-value">{{ avgPainLevel }}/10</div>
            <div class="stat-change neutral">Current average</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Abnormal Vitals</div>
            <div class="stat-value">{{ abnormalVitals }}</div>
            <div class="stat-change neutral">Flagged notes</div>
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
            placeholder="Search by patient, nurse, or observations..."
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
            <option value="improved">Improved</option>
            <option value="stable">Stable</option>
            <option value="deteriorating">Deteriorating</option>
          </select>
          
          <input type="date" v-model="dateFilter" class="filter-select" />
          
          <select v-model="dateType" class="filter-select">
            <option value="visit">Visit Date</option>
            <option value="created">Created Date</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading progress notes...</p>
      </div>

      <!-- Progress Notes Table -->
      <div v-else-if="!loading" class="progress-notes-table-container">
        <div v-if="progressNotes.data && progressNotes.data.length > 0" class="table-wrapper">
          <table class="modern-table">
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
              <tr v-for="note in progressNotes.data" :key="note.id">
                <td>
                  <div class="user-cell">
                    <img :src="note.patient_avatar_url || generateAvatar(note.patient)" class="user-avatar-table" />
                    <div class="user-details-table">
                      <div class="user-name-table">{{ note.patient_name }}</div>
                      <div class="user-id-table">ID: {{ note.patient_id }}</div>
                    </div>
                  </div>
                </td>
                
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ note.nurse_name }}</div>
                    <div class="contact-secondary">{{ note.nurse_license || 'N/A' }}</div>
                  </div>
                </td>
                
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ formatDate(note.visit_date) }}</div>
                    <div class="contact-secondary">{{ note.visit_time }}</div>
                  </div>
                </td>
                
                <td>
                  <span :class="'modern-badge ' + getConditionBadgeColor(note.general_condition)">
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
                  <div class="action-cell">
                    <button @click="toggleDropdown(note.id)" class="action-btn">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                      </svg>
                    </button>
                    
                    <div v-show="activeDropdown === note.id" class="modern-dropdown">
                      <button @click="openViewModal(note)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>
                      
                      <button @click="openEditModal(note)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Note
                      </button>
                      
                      <button @click="duplicateNote(note)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Duplicate
                      </button>
                      
                      <div class="dropdown-divider"></div>
                      
                      <button @click="openDeleteModal(note)" class="dropdown-item-modern danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
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

          <!-- Pagination -->
          <div v-if="progressNotes.pagination && progressNotes.pagination.last_page > 1" class="pagination-container">
            <div class="pagination-info">
              Showing {{ progressNotes.pagination.from || 0 }} to {{ progressNotes.pagination.to || 0 }} of {{ progressNotes.pagination.total || 0 }} notes
            </div>
            <div class="pagination-controls">
              <button 
                @click="prevPage" 
                :disabled="progressNotes.pagination.current_page === 1"
                class="pagination-btn"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Previous
              </button>
              
              <div class="pagination-pages">
                <button
                  v-for="page in getPaginationPages()"
                  :key="page"
                  @click="goToPage(page)"
                  :class="['pagination-page', { active: page === progressNotes.pagination.current_page }]"
                >
                  {{ page }}
                </button>
              </div>
              
              <button 
                @click="nextPage" 
                :disabled="progressNotes.pagination.current_page === progressNotes.pagination.last_page"
                class="pagination-btn"
              >
                Next
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3>No progress notes found</h3>
          <p>
            {{ (searchQuery || patientFilter !== 'all' || nurseFilter !== 'all' || conditionFilter !== 'all' || dateFilter) 
              ? 'Try adjusting your search or filters.' 
              : 'Start your first note to begin tracking patient care.' }}
          </p>
          <button @click="openCreateModal" class="btn btn-primary">
            Add First Note
          </button>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <div v-if="showNoteModal" class="modal-overlay" @click.self="closeNoteModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">
              <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              {{ isEditing ? 'Edit Progress Note' : 'New Progress Note' }}
            </h2>
            <button @click="closeNoteModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveNote">
            <div class="modal-body">
              <div class="form-grid">
                <!-- Visit Information -->
                <div class="form-section-header">
                  <h3 class="form-section-title">📘 Visit Information</h3>
                </div>
                
                <div class="form-group">
                  <label>Patient <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="noteForm.patient_id"
                    :options="patientOptions"
                    placeholder="Select a patient..."
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Nurse/Caregiver <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="noteForm.nurse_id"
                    :options="nurseOptions"
                    placeholder="Select a nurse..."
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Visit Date <span class="required">*</span></label>
                  <input type="date" v-model="noteForm.visit_date" required />
                </div>

                <div class="form-group">
                  <label>Visit Time <span class="required">*</span></label>
                  <input type="time" v-model="noteForm.visit_time" required />
                </div>

                <!-- Vital Signs -->
                <div class="form-section-header">
                  <h3 class="form-section-title">🩺 Vital Signs</h3>
                </div>

                <div class="form-group">
                  <label>Temperature (°C)</label>
                  <input type="number" step="0.1" v-model="noteForm.vitals.temperature" placeholder="36.5" />
                </div>

                <div class="form-group">
                  <label>Pulse (bpm)</label>
                  <input type="number" v-model="noteForm.vitals.pulse" placeholder="72" />
                </div>

                <div class="form-group">
                  <label>Respiration (/min)</label>
                  <input type="number" v-model="noteForm.vitals.respiration" placeholder="16" />
                </div>

                <div class="form-group">
                  <label>Blood Pressure (mmHg)</label>
                  <input type="text" v-model="noteForm.vitals.blood_pressure" placeholder="120/80" />
                </div>

                <div class="form-group">
                  <label>SpO₂ (%)</label>
                  <input type="number" min="0" max="100" v-model="noteForm.vitals.spo2" placeholder="98" />
                </div>

                <!-- Interventions -->
                <div class="form-section-header">
                  <h3 class="form-section-title">💊 Interventions Provided</h3>
                </div>

                <div class="form-group form-grid-full">
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="noteForm.interventions.medication_administered" />
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

                <!-- Observations -->
                <div class="form-section-header">
                  <h3 class="form-section-title">👁️ Observations/Findings</h3>
                </div>

                <div class="form-group">
                  <label>General Condition <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="noteForm.general_condition"
                    :options="conditionOptions"
                    placeholder="Select condition..."
                    required
                  />
                </div>

                <div class="form-group">
                  <label>Pain Level (0-10) <span class="required">*</span></label>
                  <input type="number" min="0" max="10" v-model="noteForm.pain_level" required />
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

                <!-- Communication -->
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

                <!-- Plan -->
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
              <button type="submit" :disabled="isSaving" class="btn btn-primary">
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                {{ isEditing ? 'Update Note' : 'Save Note' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- View Modal -->
      <div v-if="showViewModal && currentNote" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">
              <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Progress Note - {{ currentNote.patient_name }}
            </h2>
            <button @click="closeViewModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="progress-note-view">
              <!-- Header Info -->
              <div class="note-header-card">
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
                    <span :class="'modern-badge ' + getConditionBadgeColor(currentNote.general_condition)">
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

              <!-- Plan -->
              <div class="note-section">
                <h4 class="section-title">📋 Plan / Next Steps</h4>
                <div v-if="currentNote.next_steps" class="plan-content">
                  <p>{{ currentNote.next_steps }}</p>
                </div>
                <div v-else class="no-plan">
                  <em>No plan documented for next visit.</em>
                </div>
              </div>

              <!-- Footer -->
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
            <button @click="closeViewModal" class="btn btn-secondary">Close</button>
            <button @click="editFromView" class="btn btn-primary">Edit Note</button>
          </div>
        </div>
      </div>

      <!-- Delete Modal -->
      <div v-if="showDeleteModal && currentNote" class="modal-overlay" @click.self="closeDeleteModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">
              <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-.834-1.964-.834-2.732 0L3.732 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              Delete Progress Note
            </h3>
            <button @click="closeDeleteModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
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
            <button @click="closeDeleteModal" class="btn btn-secondary">Cancel</button>
            <button @click="deleteNote" :disabled="isSaving" class="btn btn-danger">
              <div v-if="isSaving" class="spinner spinner-sm"></div>
              Delete Note
            </button>
          </div>
        </div>
      </div>
    </div>

    <Toast />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import SearchableSelect from '../../common/components/SearchableSelect.vue'
import * as progressNotesService from '../../services/progressNotesService'

const toast = inject('toast')

// Reactive data
const progressNotes = ref({ data: [], pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0 } })
const patients = ref([])
const nurses = ref([])
const statistics = ref({})
const loading = ref(true)
const isSaving = ref(false)

// Filters
const searchQuery = ref('')
const patientFilter = ref('all')
const nurseFilter = ref('all')
const conditionFilter = ref('all')
const dateFilter = ref('')
const dateType = ref('visit')

// Modal states
const showNoteModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const currentNote = ref(null)
const activeDropdown = ref(null)

// Options
const conditionOptions = [
  { value: 'improved', label: 'Improved' },
  { value: 'stable', label: 'Stable' },
  { value: 'deteriorating', label: 'Deteriorating' }
]

// Form
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

// Computed
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

// Safe statistics accessors
const totalNotes = computed(() => {
  return statistics.value?.total_notes || 0
})

const uniquePatients = computed(() => {
  return statistics.value?.unique_patients || 0
})

const avgPainLevel = computed(() => {
  const avg = statistics.value?.pain_level_average || 0
  return parseFloat(avg).toFixed(1)
})

const abnormalVitals = computed(() => {
  return statistics.value?.notes_with_abnormal_vitals || 0
})

// Methods
const loadProgressNotes = async (page = 1) => {
  loading.value = true
  try {
    const filters = {
      page,
      per_page: 15,
      patient_id: patientFilter.value !== 'all' ? patientFilter.value : undefined,
      nurse_id: nurseFilter.value !== 'all' ? nurseFilter.value : undefined,
      condition: conditionFilter.value !== 'all' ? conditionFilter.value : undefined,
      date: dateFilter.value || undefined,
      date_type: dateType.value,
      search: searchQuery.value || undefined
    }

    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])

    const data = await progressNotesService.getProgressNotes(filters)
    
    if (data && data.success) {
      progressNotes.value = {
        data: data.data || [],
        pagination: data.pagination || { current_page: 1, last_page: 1, per_page: 15, total: 0 }
      }
      console.log('Loaded progress notes:', progressNotes.value.data.length) // Debug log
      
      // If we have notes but statistics are all zero, recalculate
      if (progressNotes.value.data.length > 0 && statistics.value.total_notes === 0) {
        console.log('Recalculating statistics from loaded notes')
        calculateStatisticsFromNotes()
      }
    }
  } catch (error) {
    console.error('Error loading progress notes:', error)
    toast.showError('Failed to load progress notes')
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    // Get date range for last 30 days
    const endDate = new Date()
    const startDate = new Date()
    startDate.setDate(startDate.getDate() - 30)
    
    const filters = {
      start_date: startDate.toISOString().split('T')[0],
      end_date: endDate.toISOString().split('T')[0]
    }
    
    const data = await progressNotesService.getStatistics(filters)
    console.log('Statistics API response:', data) // Debug log
    
    if (data && data.success && data.data) {
      statistics.value = data.data
      console.log('Statistics loaded from API:', statistics.value) // Debug log
    } else {
      console.warn('Statistics API failed, calculating from loaded notes')
      calculateStatisticsFromNotes()
    }
  } catch (error) {
    console.error('Error loading statistics:', error)
    console.warn('Calculating statistics from loaded notes as fallback')
    calculateStatisticsFromNotes()
  }
}

// Calculate statistics from loaded progress notes (fallback)
const calculateStatisticsFromNotes = () => {
  if (!progressNotes.value.data || progressNotes.value.data.length === 0) {
    statistics.value = {
      total_notes: 0,
      unique_patients: 0,
      unique_nurses: 0,
      pain_level_average: 0,
      notes_with_abnormal_vitals: 0
    }
    return
  }
  
  const notes = progressNotes.value.data
  
  // Calculate unique patients
  const uniquePatientIds = new Set(notes.map(note => note.patient_id))
  
  // Calculate unique nurses
  const uniqueNurseIds = new Set(notes.map(note => note.nurse_id))
  
  // Calculate average pain level
  const painLevels = notes.map(note => note.pain_level || 0)
  const avgPain = painLevels.length > 0 
    ? painLevels.reduce((sum, pain) => sum + pain, 0) / painLevels.length 
    : 0
  
  // Count notes with abnormal vitals (simple heuristic)
  const abnormalCount = notes.filter(note => {
    if (!note.vitals) return false
    const temp = parseFloat(note.vitals.temperature)
    const pulse = parseFloat(note.vitals.pulse)
    const spo2 = parseFloat(note.vitals.spo2)
    
    return (temp && (temp < 36 || temp > 38)) ||
           (pulse && (pulse < 60 || pulse > 100)) ||
           (spo2 && spo2 < 95)
  }).length
  
  statistics.value = {
    total_notes: notes.length,
    unique_patients: uniquePatientIds.size,
    unique_nurses: uniqueNurseIds.size,
    pain_level_average: avgPain,
    notes_with_abnormal_vitals: abnormalCount
  }
  
  console.log('Statistics calculated from notes:', statistics.value)
}

// Debug method to check statistics
const debugStatistics = () => {
  console.log('=== DEBUG STATISTICS ===')
  console.log('Raw statistics.value:', statistics.value)
  console.log('Total Notes:', totalNotes.value)
  console.log('Unique Patients:', uniquePatients.value)
  console.log('Avg Pain:', avgPainLevel.value)
  console.log('Abnormal Vitals:', abnormalVitals.value)
  console.log('Progress Notes Data:', progressNotes.value)
  alert('Check console for debug information')
}

const loadPatients = async () => {
  try {
    const data = await progressNotesService.getAvailablePatients()
    if (data && data.success) {
      patients.value = data.data || []
    }
  } catch (error) {
    console.error('Error loading patients:', error)
  }
}

const loadNurses = async () => {
  try {
    const data = await progressNotesService.getAvailableNurses()
    if (data && data.success) {
      nurses.value = data.data || []
    }
  } catch (error) {
    console.error('Error loading nurses:', error)
  }
}

const formatAvgPain = (value) => {
  return value ? parseFloat(value).toFixed(1) : '0.0'
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
  if (painLevel <= 3) return '#10b981'
  if (painLevel <= 6) return '#f59e0b'
  return '#ef4444'
}

const capitalizeFirst = (str) => {
  return str ? str.charAt(0).toUpperCase() + str.slice(1) : ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

const formatDateForInput = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return ''
  return date.toISOString().split('T')[0]
}

const formatTimeForInput = (timeString) => {
  if (!timeString) return ''
  if (timeString.includes(':')) {
    const timeParts = timeString.split(':')
    if (timeParts.length >= 2) {
      return `${timeParts[0].padStart(2, '0')}:${timeParts[1].padStart(2, '0')}`
    }
  }
  return timeString
}

const generateAvatar = (user) => {
  if (!user) {
    return 'https://ui-avatars.com/api/?name=N+A&color=667eea&background=f8f9fa&size=200&font-size=0.6'
  }
  const name = `${user.first_name || ''} ${user.last_name || ''}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=667eea&background=f8f9fa&size=200&font-size=0.6`
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

const populateForm = (note) => {
  noteForm.value = {
    patient_id: note.patient_id,
    nurse_id: note.nurse_id,
    visit_date: formatDateForInput(note.visit_date),
    visit_time: formatTimeForInput(note.visit_time),
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
    let response
    if (isEditing.value) {
      response = await progressNotesService.updateProgressNote(currentNote.value.id, noteForm.value)
    } else {
      response = await progressNotesService.createProgressNote(noteForm.value)
    }
    
    if (response && response.success) {
      await loadProgressNotes()
      await loadStatistics()
      closeNoteModal()
      toast.showSuccess(isEditing.value ? 'Progress note updated successfully!' : 'Progress note created successfully!')
    } else {
      toast.showError(response?.message || 'Failed to save progress note')
    }
  } catch (error) {
    console.error('Error saving progress note:', error)
    toast.showError('An error occurred while saving the progress note')
  }
  
  isSaving.value = false
}

const deleteNote = async () => {
  isSaving.value = true
  
  try {
    const response = await progressNotesService.deleteProgressNote(currentNote.value.id)
    
    if (response && response.success) {
      await loadProgressNotes()
      await loadStatistics()
      closeDeleteModal()
      toast.showSuccess('Progress note deleted successfully!')
    } else {
      toast.showError(response?.message || 'Failed to delete progress note')
    }
  } catch (error) {
    console.error('Error deleting progress note:', error)
    toast.showError('An error occurred while deleting the progress note')
  }
  
  isSaving.value = false
}

const duplicateNote = async (note) => {
  try {
    const response = await progressNotesService.duplicateProgressNote(note.id)
    
    if (response && response.success) {
      await loadProgressNotes()
      await loadStatistics()
      toast.showSuccess('Progress note duplicated successfully!')
      activeDropdown.value = null
    } else {
      toast.showError(response?.message || 'Failed to duplicate progress note')
    }
  } catch (error) {
    console.error('Error duplicating progress note:', error)
    toast.showError('An error occurred while duplicating the progress note')
  }
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

const exportNotes = async () => {
  try {
    toast.showInfo('Preparing export...')
    
    const filters = {
      patient_id: patientFilter.value !== 'all' ? patientFilter.value : undefined,
      nurse_id: nurseFilter.value !== 'all' ? nurseFilter.value : undefined,
      condition: conditionFilter.value !== 'all' ? conditionFilter.value : undefined,
      date: dateFilter.value || undefined,
      date_type: dateType.value,
      search: searchQuery.value || undefined
    }
    
    Object.keys(filters).forEach(key => filters[key] === undefined && delete filters[key])
    
    const { blob, filename } = await progressNotesService.exportProgressNotes(filters)
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    toast.showSuccess('Progress notes exported successfully!')
  } catch (error) {
    console.error('Error:', error)
    toast.showError('Failed to export')
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= progressNotes.value.pagination.last_page) {
    loadProgressNotes(page)
  }
}

const nextPage = () => {
  if (progressNotes.value.pagination.current_page < progressNotes.value.pagination.last_page) {
    goToPage(progressNotes.value.pagination.current_page + 1)
  }
}

const prevPage = () => {
  if (progressNotes.value.pagination.current_page > 1) {
    goToPage(progressNotes.value.pagination.current_page - 1)
  }
}

const getPaginationPages = () => {
  const pages = []
  const maxVisible = 5
  const current = progressNotes.value.pagination.current_page
  const last = progressNotes.value.pagination.last_page
  
  let start = Math.max(1, current - Math.floor(maxVisible / 2))
  let end = Math.min(last, start + maxVisible - 1)
  
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
}

const handleClickOutside = (event) => {
  if (!event.target.closest('.action-cell')) {
    activeDropdown.value = null
  }
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadProgressNotes(),
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
    loadProgressNotes(1)
  }, 500)
})

watch([patientFilter, nurseFilter, conditionFilter, dateFilter, dateType], () => {
  loadProgressNotes(1)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.progress-notes-page {
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
/* Medium-Large Screens (13-15 inch laptops: 1366px - 1440px) */
@media (max-width: 1440px) {
  .progress-notes-page {
    padding: 24px;
  }

  .page-header {
    margin-bottom: 24px;
  }

  .page-header-content h1 {
    font-size: 28px;
  }

  .page-header-content p {
    font-size: 14px;
  }

  .btn-modern {
    padding: 9px 16px;
    font-size: 13px;
    min-height: 40px;
  }

  .btn-modern svg {
    width: 16px;
    height: 16px;
  }

  .stats-grid {
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    padding: 20px;
  }

  .stat-icon {
    width: 52px;
    height: 52px;
  }

  .stat-icon svg {
    width: 26px;
    height: 26px;
  }

  .stat-label {
    font-size: 12px;
  }

  .stat-value {
    font-size: 28px;
  }

  .stat-change {
    font-size: 12px;
  }

  .filters-section {
    padding: 18px;
    margin-bottom: 20px;
  }

  .search-input,
  .filter-select {
    padding: 9px 12px 9px 40px;
    font-size: 13px;
    min-height: 40px;
  }

  .search-icon {
    left: 12px;
    width: 18px;
    height: 18px;
  }

  .filter-select {
    min-width: 140px;
    padding: 9px 12px;
  }

  .modern-table th {
    padding: 14px 18px;
    font-size: 11px;
  }

  .modern-table td {
    padding: 14px 18px;
    font-size: 13px;
  }

  .user-avatar-table {
    width: 40px;
    height: 40px;
  }

  .user-name-table {
    font-size: 13px;
  }

  .user-id-table {
    font-size: 11px;
  }

  .contact-primary {
    font-size: 13px;
  }

  .contact-secondary {
    font-size: 12px;
  }

  .modern-badge {
    padding: 5px 10px;
    font-size: 11px;
  }

  .action-btn {
    width: 36px;
    height: 36px;
  }

  .action-btn svg {
    width: 16px;
    height: 16px;
  }

  .dropdown-item-modern {
    padding: 9px 10px;
    font-size: 13px;
    min-height: 40px;
  }

  .dropdown-item-modern svg {
    width: 16px;
    height: 16px;
  }

  .pagination-container {
    padding: 18px 20px;
  }

  .pagination-info {
    font-size: 13px;
  }

  .pagination-btn {
    padding: 7px 12px;
    font-size: 13px;
    min-height: 36px;
  }

  .pagination-page {
    width: 36px;
    height: 36px;
    font-size: 13px;
  }

  .modal-header,
  .modal-body {
    padding: 22px 24px;
  }

  .modal-actions {
    padding: 18px 24px;
  }

  .modal-title {
    font-size: 18px;
  }

  .modal-icon {
    width: 22px;
    height: 22px;
  }

  .form-group label {
    font-size: 12px;
    margin-bottom: 7px;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    padding: 9px 12px;
    font-size: 13px;
    min-height: 40px;
  }

  .btn {
    padding: 9px 18px;
    font-size: 13px;
    min-height: 40px;
  }

  .form-section-title {
    font-size: 16px;
  }

  .note-header-card {
    padding: 18px;
  }

  .note-section {
    padding: 18px;
  }

  .section-title {
    font-size: 15px;
  }
}

/* Smaller Laptops (1200px - 1366px) */
@media (max-width: 1366px) {
  .progress-notes-page {
    padding: 20px;
  }

  .page-header-content h1 {
    font-size: 26px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .stat-card {
    padding: 18px;
  }

  .stat-icon {
    width: 48px;
    height: 48px;
  }

  .stat-icon svg {
    width: 24px;
    height: 24px;
  }

  .stat-value {
    font-size: 26px;
  }

  .modern-table th {
    padding: 12px 16px;
  }

  .modern-table td {
    padding: 12px 16px;
  }
}

/* Tablets and Small Laptops (1024px and below) */
@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .filters-group {
    flex-wrap: wrap;
  }

  .filter-select {
    min-width: 120px;
  }
}

/* Mobile (768px and below) */
@media (max-width: 768px) {
  .progress-notes-page {
    padding: 16px;
    max-width: 100vw;
    overflow-x: hidden;
  }
  
  .page-header {
    flex-direction: column;
    align-items: stretch;
    max-width: 100%;
    overflow: hidden;
  }

  .page-header-content h1 {
    font-size: 22px;
  }

  .page-header-content p {
    font-size: 13px;
  }

  .page-header-actions {
    width: 100%;
    max-width: 100%;
    flex-direction: column;
  }

  .btn-modern {
    flex: 1;
    width: 100%;
    justify-content: center;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 14px;
    padding: 0;
  }

  .stat-card {
    padding: 18px;
  }

  .stat-value {
    font-size: 24px;
  }

  .stat-icon {
    width: 44px;
    height: 44px;
  }

  .stat-icon svg {
    width: 22px;
    height: 22px;
  }
  
  .filters-section {
    flex-direction: column;
    padding: 16px;
  }
  
  .search-wrapper {
    min-width: 100%;
  }
  
  .filters-group {
    flex-direction: column;
    width: 100%;
    gap: 10px;
  }
  
  .filter-select {
    width: 100%;
  }

  .table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .modern-table {
    min-width: 900px;
  }

  .pagination-container {
    flex-direction: column;
    align-items: stretch;
    padding: 16px;
  }

  .pagination-info {
    text-align: center;
    width: 100%;
    font-size: 12px;
  }

  .pagination-controls {
    justify-content: center;
    width: 100%;
    flex-wrap: wrap;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .modal-actions {
    flex-direction: column-reverse;
  }

  .modal-actions .btn {
    width: 100%;
  }

  .modal-header,
  .modal-body {
    padding: 20px;
  }

  .modal-actions {
    padding: 16px 20px;
  }

  .patient-nurse-info {
    grid-template-columns: 1fr;
  }

  .vitals-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .observations-grid {
    grid-template-columns: 1fr;
  }

  .signature-section {
    grid-template-columns: 1fr;
  }
}

/* Small Mobile (480px and below) */
@media (max-width: 480px) {
  .progress-notes-page {
    padding: 12px;
  }

  .page-header-content h1 {
    font-size: 20px;
  }

  .page-header-content p {
    font-size: 12px;
  }

  .stat-card {
    padding: 16px;
    max-width: 100%;
  }

  .stat-icon {
    width: 40px;
    height: 40px;
  }

  .stat-icon svg {
    width: 20px;
    height: 20px;
  }

  .stat-value {
    font-size: 22px;
  }

  .stat-label {
    font-size: 11px;
  }

  .filters-section {
    padding: 14px;
  }

  .search-input,
  .filter-select {
    font-size: 12px;
    padding: 8px 10px 8px 36px;
    min-height: 38px;
  }

  .filter-select {
    padding: 8px 10px;
  }

  .modal {
    border-radius: 16px;
  }

  .modal-header,
  .modal-body,
  .modal-actions {
    padding: 18px;
  }

  .modal-title {
    font-size: 17px;
  }

  .modal-icon {
    width: 20px;
    height: 20px;
  }

  .modern-table {
    min-width: 800px;
  }

  .user-avatar-table {
    width: 36px;
    height: 36px;
  }

  .user-name-table {
    font-size: 12px;
  }

  .modern-badge {
    font-size: 10px;
    padding: 4px 8px;
  }

  .pagination-page {
    width: 34px;
    height: 34px;
    font-size: 12px;
  }

  .pagination-btn {
    padding: 7px 10px;
    min-height: 34px;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    font-size: 12px;
    padding: 8px 10px;
    min-height: 38px;
  }

  .btn {
    font-size: 12px;
    padding: 8px 16px;
    min-height: 38px;
  }

  .form-section-title {
    font-size: 15px;
  }

  .checkbox-text {
    font-size: 13px;
  }

  .intervention-input {
    font-size: 12px;
    padding: 8px 10px;
  }

  .note-header-card,
  .note-section {
    padding: 16px;
  }

  .section-title {
    font-size: 14px;
  }

  .vitals-grid {
    grid-template-columns: 1fr;
  }

  .intervention-item {
    padding: 10px;
  }

  .intervention-item strong {
    font-size: 12px;
  }

  .intervention-item span {
    font-size: 13px;
  }
}

/* Extra Small (360px and below) */
@media (max-width: 360px) {
  .progress-notes-page {
    padding: 10px;
  }

  .page-header-content h1 {
    font-size: 18px;
  }

  .stat-value {
    font-size: 20px;
  }

  .stat-card {
    padding: 14px;
  }

  .modern-table {
    min-width: 700px;
  }

  .modern-badge {
    font-size: 9px;
    padding: 3px 7px;
  }

  .pagination-page {
    width: 32px;
    height: 32px;
    font-size: 11px;
  }
}

/* Tablet Landscape */
@media (max-width: 1024px) and (orientation: landscape) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>