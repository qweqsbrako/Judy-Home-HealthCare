<template>
  <MainLayout>
    <div class="users-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-content">
          <h1>Nurse Management</h1>
          <p>Manage all nurses in the healthcare system</p>
        </div>
        <div class="page-header-actions">
          <button @click="exportNurses" class="btn-modern btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
          </button>
          <button @click="openCreateModal" class="btn-modern btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Nurse
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Nurses</div>
            <div class="stat-value">{{ stats.total_nurses }}</div>
            <div class="stat-change positive">All registered nurses</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Verified Nurses</div>
            <div class="stat-value">{{ verifiedCount }}</div>
            <div class="stat-change positive">{{ verifiedPercentage }}% of total</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Pending</div>
            <div class="stat-value">{{ pendingCount }}</div>
            <div class="stat-change neutral">Awaiting verification</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Active Today</div>
            <div class="stat-value">{{ activeTodayCount }}</div>
            <div class="stat-change positive">Logged in recently</div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="filters-section">
        <div class="search-wrapper">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Search nurses by name, email, license, specialization..."
            v-model="searchQuery"
            class="search-input"
          />
        </div>
        <div class="filters-group">
          <select v-model="statusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="verified">Verified</option>
            <option value="pending">Pending</option>
            <option value="suspended">Suspended</option>
            <option value="rejected">Rejected</option>
          </select>
          <select v-model="specializationFilter" class="filter-select">
            <option value="all">All Specializations</option>
            <option value="pediatric_care">Pediatric Care</option>
            <option value="general_care">General Care</option>
            <option value="emergency_care">Emergency Care</option>
            <option value="oncology">Oncology</option>
            <option value="cardiology">Cardiology</option>
            <option value="psychiatric_care">Psychiatric Care</option>
            <option value="geriatric_care">Geriatric Care</option>
            <option value="surgical_care">Surgical Care</option>
          </select>
          <select v-model="experienceFilter" class="filter-select">
            <option value="all">All Experience Levels</option>
            <option value="junior">Junior (0-2 years)</option>
            <option value="mid">Mid-level (3-7 years)</option>
            <option value="senior">Senior (8+ years)</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading nurses...</p>
      </div>

      <!-- Nurses Table -->
      <div v-else class="users-table-container">
        <div class="table-wrapper">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Nurse</th>
                <th>Contact</th>
                <th>Professional Info</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="nurse in filteredNurses" :key="nurse.id">
                <td>
                  <div class="user-cell">
                    <img :src="nurse.avatar_url" :alt="nurse.full_name" class="user-avatar-table" />
                    <div class="user-details-table">
                      <div class="user-name-table">{{ nurse.first_name }} {{ nurse.last_name }}</div>
                      <div class="user-id-table">{{ nurse.license_number || 'No License' }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ nurse.email }}</div>
                    <div class="contact-secondary">{{ nurse.phone }}</div>
                  </div>
                </td>
                <td>
                  <div class="contact-cell">
                    <div class="contact-primary">{{ formatSpecialization(nurse.specialization) }}</div>
                    <div class="contact-secondary">{{ nurse.years_experience || 0 }} years experience</div>
                  </div>
                </td>
                <td>
                  <span :class="'modern-badge ' + getStatusBadgeColor(nurse.verification_status)">
                    {{ capitalizeFirst(nurse.verification_status) }}
                  </span>
                </td>
                <td class="text-secondary">
                  {{ formatTimeAgo(nurse.last_login_at) }}
                </td>
                <td>
                  <div class="action-cell">
                    <button @click="toggleDropdown(nurse.id)" class="action-btn">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                      </svg>
                    </button>
                    <div v-show="activeDropdown === nurse.id" class="modern-dropdown">
                      <button @click="openViewModal(nurse)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>
                      
                      <button @click="openEditModal(nurse)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Nurse
                      </button>
                      
                      <button @click="openPasswordModal(nurse)" class="dropdown-item-modern">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        Change Password
                      </button>
                      
                      <button v-if="nurse.verification_status === 'pending'" @click="verifyNurse(nurse)" class="dropdown-item-modern success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Verify Nurse
                      </button>
                      
                      <button v-if="!nurse.is_active && nurse.verification_status !== 'pending'" @click="openActivateModal(nurse)" class="dropdown-item-modern success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Activate Nurse
                      </button>
                      
                      <button v-if="nurse.is_active" @click="openSuspendModal(nurse)" class="dropdown-item-modern warning">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                        </svg>
                        Suspend Nurse
                      </button>
                      
                      <div class="dropdown-divider"></div>
                      
                      <button @click="openDeleteModal(nurse)" class="dropdown-item-modern danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Nurse
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-if="filteredNurses.length === 0" class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
          </svg>
          <h3>No nurses found</h3>
          <p>
            {{ (searchQuery || statusFilter !== 'all' || specializationFilter !== 'all') 
              ? 'Try adjusting your search or filters.' 
              : 'Get started by adding a new nurse.' }}
          </p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="pagination-container">
        <div class="pagination-info">
          Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} nurses
        </div>
        <div class="pagination-controls">
          <button 
            @click="prevPage" 
            :disabled="currentPage === 1"
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
              :class="['pagination-page', { active: page === currentPage }]"
            >
              {{ page }}
            </button>
          </div>
          
          <button 
            @click="nextPage" 
            :disabled="currentPage === lastPage"
            class="pagination-btn"
          >
            Next
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Create/Edit Nurse Modal -->
      <div v-if="showNurseModal" class="modal-overlay" @click.self="closeNurseModal">
        <div class="modal modal-lg">
          <div class="modal-header">
            <h2 class="modal-title">
              {{ isEditing ? 'Edit Nurse' : 'Add New Nurse' }}
            </h2>
            <button @click="closeNurseModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveNurse">
            <div class="modal-body">
              <div class="form-grid">
                
                <!-- Profile Photo Section -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Profile Photo</h3>
                </div>
                
                <div class="form-group form-grid-full">
                  <label>Profile Photo</label>
                  <div class="photo-upload-container">
                    <!-- Photo Preview -->
                    <div class="photo-preview">
                      <img 
                        v-if="photoPreview || nurseForm.current_photo_url" 
                        :src="photoPreview || nurseForm.current_photo_url" 
                        alt="Profile photo"
                        class="preview-image"
                      />
                      <div v-else class="preview-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <p>No photo</p>
                      </div>
                    </div>
                    
                    <!-- Upload Controls -->
                    <div class="photo-controls">
                      <input
                        type="file"
                        ref="photoInput"
                        @change="handlePhotoChange"
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                        style="display: none"
                      />
                      
                      <button 
                        type="button" 
                        @click="$refs.photoInput.click()" 
                        class="btn btn-secondary btn-sm"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ photoPreview || nurseForm.current_photo_url ? 'Change Photo' : 'Upload Photo' }}
                      </button>
                      
                      <button 
                        v-if="photoPreview || nurseForm.current_photo_url"
                        type="button" 
                        @click="removePhoto" 
                        class="btn btn-danger btn-sm"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Remove Photo
                      </button>
                      
                      <p class="form-help" style="margin-top: 8px;">
                        Accepted formats: JPG, PNG, WEBP. Max size: 2MB
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Basic Information -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Basic Information</h3>
                </div>
                
                <div class="form-group">
                  <label>First Name <span class="required">*</span></label>
                  <input type="text" v-model="nurseForm.first_name" required />
                </div>

                <div class="form-group">
                  <label>Last Name <span class="required">*</span></label>
                  <input type="text" v-model="nurseForm.last_name" required />
                </div>

                <div class="form-group">
                  <label>Email <span class="required">*</span></label>
                  <input type="email" v-model="nurseForm.email" required />
                </div>

                <div class="form-group">
                  <label>Phone <span class="required">*</span></label>
                  <input type="tel" v-model="nurseForm.phone" required />
                </div>

                <div class="form-group">
                  <label>Gender <span class="required">*</span></label>
                  <SearchableSelect
                    v-model="nurseForm.gender"
                    :options="genderOptions"
                    placeholder="Select gender..."
                  />
                </div>

                <div class="form-group">
                  <label>Date of Birth</label>
                  <input type="date" v-model="nurseForm.date_of_birth" />
                </div>

                <div class="form-group">
                  <label>Ghana Card Number</label>
                  <input
                    type="text"
                    v-model="nurseForm.ghana_card_number"
                    placeholder="GHA-123456789-1"
                  />
                </div>

                <!-- Professional Information -->
                <div class="form-section-header">
                  <h3 class="form-section-title">Professional Information</h3>
                </div>
                
                <div class="form-group">
                  <label>License Number</label>
                  <input type="text" v-model="nurseForm.license_number" />
                </div>

                <div class="form-group">
                  <label>Specialization</label>
                  <SearchableSelect
                    v-model="nurseForm.specialization"
                    :options="specializationOptions"
                    placeholder="Search specializations..."
                  />
                </div>

                <div class="form-group form-grid-full">
                  <label>Years of Experience</label>
                  <input type="number" v-model="nurseForm.years_experience" min="0" />
                </div>

                <!-- Authentication -->
                <template v-if="!isEditing">
                  <div class="form-section-header">
                    <h3 class="form-section-title">Authentication Setup</h3>
                  </div>
                  
                  <div class="form-grid-full">
                    <label class="checkbox-label">
                      <input type="checkbox" v-model="nurseForm.send_invite" />
                      <span class="checkbox-text">Send email invitation to nurse</span>
                    </label>
                    <p class="form-help">
                      {{ nurseForm.send_invite 
                        ? 'Nurse will receive an email with instructions to set their password'
                        : 'You can set a temporary password for the nurse' }}
                    </p>
                  </div>

                  <div v-if="!nurseForm.send_invite" class="form-group form-grid-full">
                    <label>Temporary Password</label>
                    <input
                      type="password"
                      v-model="nurseForm.password"
                      placeholder="Enter temporary password"
                      :required="!nurseForm.send_invite"
                    />
                  </div>
                </template>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeNurseModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="isSaving" class="btn btn-primary">
                <div v-if="isSaving" class="spinner spinner-sm"></div>
                {{ isEditing ? 'Update Nurse' : 'Create Nurse' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- View Nurse Details Modal -->
      <div v-if="showViewModal && currentNurse" class="modal-overlay" @click.self="closeViewModal">
        <div class="modal modal-xl">
          <div class="modal-header">
            <h2 class="modal-title">Nurse Details</h2>
            <button @click="closeViewModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="user-view-grid">
              <!-- Nurse Profile Section -->
              <div class="user-profile-section">
                <img
                  :src="currentNurse.avatar_url"
                  :alt="currentNurse.full_name"
                  class="profile-avatar-large"
                />
                <h3 class="profile-name-view">
                  {{ currentNurse.first_name }} {{ currentNurse.last_name }}
                </h3>
                <span class="modern-badge badge-success">
                  Nurse
                </span>
                <div class="profile-contact-view">
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>{{ currentNurse.email }}</span>
                  </div>
                  <div class="contact-item-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span>{{ currentNurse.phone }}</span>
                  </div>
                </div>
              </div>

              <!-- Details Section -->
              <div class="details-section-view">
                <!-- Basic Information -->
                <div class="details-group">
                  <h4 class="details-header">Basic Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Full Name</label>
                      <p>{{ currentNurse.first_name }} {{ currentNurse.last_name }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Gender</label>
                      <p style="text-transform: capitalize;">{{ currentNurse.gender }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Ghana Card</label>
                      <p>{{ currentNurse.ghana_card_number || 'Not provided' }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Date of Birth</label>
                      <p>{{ formatDate(currentNurse.date_of_birth) }}</p>
                    </div>
                  </div>
                </div>

                <!-- Professional Information -->
                <div class="details-group">
                  <h4 class="details-header">Professional Information</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>License Number</label>
                      <p>{{ currentNurse.license_number || 'Not provided' }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Specialization</label>
                      <p>{{ formatSpecialization(currentNurse.specialization) || 'Not provided' }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Years of Experience</label>
                      <p>{{ currentNurse.years_experience || 0 }} years</p>
                    </div>
                  </div>
                </div>

                <!-- Account Status -->
                <div class="details-group">
                  <h4 class="details-header">Account Status</h4>
                  <div class="details-grid-view">
                    <div class="detail-item-view">
                      <label>Verification Status</label>
                      <span :class="'modern-badge ' + getStatusBadgeColor(currentNurse.verification_status)">
                        {{ capitalizeFirst(currentNurse.verification_status) }}
                      </span>
                    </div>
                    <div class="detail-item-view">
                      <label>Account Status</label>
                      <span :class="'modern-badge ' + (currentNurse.is_active ? 'badge-success' : 'badge-danger')">
                        {{ currentNurse.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </div>
                    <div class="detail-item-view">
                      <label>Last Login</label>
                      <p>{{ formatTimeAgo(currentNurse.last_login_at) }}</p>
                    </div>
                    <div class="detail-item-view">
                      <label>Member Since</label>
                      <p>{{ formatDate(currentNurse.created_at) }}</p>
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
            <button @click="editFromView" class="btn btn-primary">
              Edit Nurse
            </button>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal && currentNurse" class="modal-overlay" @click.self="closeDeleteModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Delete Nurse</h3>
            <button @click="closeDeleteModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to delete <strong>{{ currentNurse.first_name }} {{ currentNurse.last_name }}</strong>? 
              This action cannot be undone.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeDeleteModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="deleteNurse" :disabled="isSaving" class="btn btn-danger">
              <div v-if="isSaving" class="spinner spinner-sm"></div>
              Delete Nurse
            </button>
          </div>
        </div>
      </div>

      <!-- Suspend Confirmation Modal -->
      <div v-if="showSuspendModal && currentNurse" class="modal-overlay" @click.self="closeSuspendModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Suspend Nurse</h3>
            <button @click="closeSuspendModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to suspend <strong>{{ currentNurse.first_name }} {{ currentNurse.last_name }}</strong>? 
            </p>
            <p style="font-size: 13px; color: #64748b; margin-top: 12px;">
              This nurse will no longer be able to access the system until their account is reactivated.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeSuspendModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="suspendNurse" :disabled="isSuspending" class="btn btn-danger">
              <div v-if="isSuspending" class="spinner spinner-sm"></div>
              Suspend Nurse
            </button>
          </div>
        </div>
      </div>

      <!-- Activate Confirmation Modal -->
      <div v-if="showActivateModal && currentNurse" class="modal-overlay" @click.self="closeActivateModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Activate Nurse</h3>
            <button @click="closeActivateModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p>
              Are you sure you want to activate <strong>{{ currentNurse.first_name }} {{ currentNurse.last_name }}</strong>? 
            </p>
            <p style="font-size: 13px; color: #64748b; margin-top: 12px;">
              This nurse will regain access to the system and be able to log in again.
            </p>
          </div>

          <div class="modal-actions">
            <button @click="closeActivateModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="activateNurse" :disabled="isActivating" class="btn btn-primary">
              <div v-if="isActivating" class="spinner spinner-sm"></div>
              Activate Nurse
            </button>
          </div>
        </div>
      </div>

      <!-- Change Password Modal -->
      <div v-if="showPasswordModal && currentNurse" class="modal-overlay" @click.self="closePasswordModal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h3 class="modal-title">Change Password</h3>
            <button @click="closePasswordModal" class="modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="changePassword">
            <div class="modal-body">
              <p style="margin-bottom: 20px; font-size: 14px; color: #334155;">
                Change password for <strong>{{ currentNurse.first_name }} {{ currentNurse.last_name }}</strong>
              </p>

              <div class="password-option-card">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="passwordForm.send_via_email" />
                  <span class="checkbox-text">Send new password via email</span>
                </label>
                <p class="form-help">
                  {{ passwordForm.send_via_email 
                    ? 'A new temporary password will be generated and emailed to the nurse'
                    : 'You can set a new password manually below' }}
                </p>
              </div>

              <template v-if="!passwordForm.send_via_email">
                <div class="form-group" style="margin-bottom: 16px;">
                  <label>New Password <span class="required">*</span></label>
                  <input
                    type="password"
                    v-model="passwordForm.new_password"
                    placeholder="Enter new password (min. 8 characters)"
                    :required="!passwordForm.send_via_email"
                    minlength="8"
                    style="width: 100%;"
                  />
                </div>

                <div class="form-group">
                  <label>Confirm Password <span class="required">*</span></label>
                  <input
                    type="password"
                    v-model="passwordForm.confirm_password"
                    placeholder="Confirm new password"
                    :required="!passwordForm.send_via_email"
                    style="width: 100%;"
                  />
                </div>
              </template>
            </div>

            <div class="modal-actions">
              <button type="button" @click="closePasswordModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" :disabled="isSaving || isSendingPassword" class="btn btn-primary">
                <div v-if="isSaving || isSendingPassword" class="spinner spinner-sm"></div>
                {{ passwordForm.send_via_email ? 'Send Email' : 'Change Password' }}
              </button>
            </div>
          </form>
        </div>
      </div>
      
      <Toast />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'
import SearchableSelect from '../../common/components/SearchableSelect.vue'
import * as nursesService from '../../services/nursesService'

const router = useRouter()
const toast = inject('toast')

// Reactive data
const nurses = ref([])
const loading = ref(true)
const searchQuery = ref('')
const statusFilter = ref('all')
const specializationFilter = ref('all')
const experienceFilter = ref('all')

// Pagination state
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(15)
const total = ref(0)

// Stats from backend
const stats = ref({
  total_nurses: 0,
  verified_count: 0,
  pending_count: 0,
  active_today_count: 0,
  verified_percentage: 0
})

// Modal states
const showNurseModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const currentNurse = ref(null)
const isSaving = ref(false)
const showSuspendModal = ref(false)
const isSuspending = ref(false) 
const showActivateModal = ref(false)
const isActivating = ref(false)
const showPasswordModal = ref(false)
const isSendingPassword = ref(false)

// Dropdown state
const activeDropdown = ref(null)

// Photo upload refs
const photoInput = ref(null)
const photoPreview = ref(null)
const photoFile = ref(null)
const photoToRemove = ref(false)

// Stats computed properties
const verifiedCount = computed(() => stats.value.verified_count)
const verifiedPercentage = computed(() => stats.value.verified_percentage)
const pendingCount = computed(() => stats.value.pending_count)
const activeTodayCount = computed(() => stats.value.active_today_count)

// Define options
const genderOptions = [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' }
]

const specializationOptions = [
  { value: 'cardiology', label: 'Cardiology' },
  { value: 'pediatric_care', label: 'Pediatric Care' },
  { value: 'general_care', label: 'General Care' },
  { value: 'emergency_medicine', label: 'Emergency Medicine' },
  { value: 'oncology', label: 'Oncology' },
  { value: 'neurology', label: 'Neurology' },
  { value: 'orthopedics', label: 'Orthopedics' },
  { value: 'psychiatry', label: 'Psychiatry' },
  { value: 'geriatric_care', label: 'Geriatric Care' },
  { value: 'surgical_care', label: 'Surgical Care' }
]

// Form data
const nurseForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  role: 'nurse',
  date_of_birth: '',
  gender: 'male',
  ghana_card_number: '',
  license_number: '',
  specialization: '',
  years_experience: '',
  send_invite: true,
  password: '',
  current_photo_url: null
})

// Password change form
const passwordForm = ref({
  new_password: '',
  confirm_password: '',
  send_via_email: false
})

// Computed properties
const filteredNurses = computed(() => nurses.value)

// Photo handler functions
const handlePhotoChange = (event) => {
  const file = event.target.files[0]
  if (!file) return
  
  const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
  if (!validTypes.includes(file.type)) {
    toast.showError('Please upload a valid image file (JPG, PNG, or WEBP)')
    return
  }
  
  const maxSize = 2 * 1024 * 1024
  if (file.size > maxSize) {
    toast.showError('Image size must be less than 2MB')
    return
  }
  
  photoFile.value = file
  photoToRemove.value = false
  
  const reader = new FileReader()
  reader.onload = (e) => {
    photoPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

const removePhoto = () => {
  photoPreview.value = null
  photoFile.value = null
  photoToRemove.value = true
  nurseForm.value.current_photo_url = null
  
  if (photoInput.value) {
    photoInput.value.value = ''
  }
}

// Methods
const loadNurses = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page: page,
      per_page: perPage.value,
      search: searchQuery.value || '',
      specialization: specializationFilter.value,
      status: statusFilter.value,
      experience: experienceFilter.value
    }
    
    const response = await nursesService.getNurses(params)
    
    nurses.value = response.data || []
    
    if (response.meta) {
      currentPage.value = response.meta.current_page
      lastPage.value = response.meta.last_page
      perPage.value = response.meta.per_page
      total.value = response.meta.total
    }
    
    if (response.stats) {
      stats.value = response.stats
    }
    
  } catch (error) {
    console.error('Error loading nurses:', error)
    toast.showError('Failed to load nurses. Please try again.')
  }
  loading.value = false
}

const getStatusBadgeColor = (status) => {
  const colorMap = {
    'verified': 'badge-success',
    'pending': 'badge-warning',
    'rejected': 'badge-danger',
    'suspended': 'badge-danger'
  }
  return colorMap[status] || 'badge-secondary'
}

const capitalizeFirst = (str) => {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const formatSpecialization = (spec) => {
  if (!spec) return 'Not specified'
  return spec.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatTimeAgo = (dateString) => {
  if (!dateString) return 'Never'
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return '1 day ago'
  if (diffDays < 7) return `${diffDays} days ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
  return `${Math.floor(diffDays / 30)} months ago`
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const toggleDropdown = (nurseId) => {
  activeDropdown.value = activeDropdown.value === nurseId ? null : nurseId
}

const openCreateModal = () => {
  isEditing.value = false
  currentNurse.value = null
  photoPreview.value = null
  photoFile.value = null
  photoToRemove.value = false
  
  nurseForm.value = {
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    role: 'nurse',
    date_of_birth: '',
    gender: 'male',
    ghana_card_number: '',
    license_number: '',
    specialization: '',
    years_experience: '',
    send_invite: true,
    password: '',
    current_photo_url: null
  }
  showNurseModal.value = true
}

const openEditModal = (nurse) => {
  isEditing.value = true
  currentNurse.value = nurse
  photoPreview.value = null
  photoFile.value = null
  photoToRemove.value = false
  
  nurseForm.value = {
    first_name: nurse.first_name,
    last_name: nurse.last_name,
    email: nurse.email,
    phone: nurse.phone,
    role: 'nurse',
    date_of_birth: nurse.date_of_birth,
    gender: nurse.gender,
    ghana_card_number: nurse.ghana_card_number,
    license_number: nurse.license_number || '',
    specialization: nurse.specialization || '',
    years_experience: nurse.years_experience || '',
    send_invite: false,
    password: '',
    current_photo_url: nurse.avatar_url
  }
  showNurseModal.value = true
  activeDropdown.value = null
}

const openViewModal = (nurse) => {
  currentNurse.value = nurse
  showViewModal.value = true
  activeDropdown.value = null
}

const openDeleteModal = (nurse) => {
  currentNurse.value = nurse
  showDeleteModal.value = true
  activeDropdown.value = null
}

const openSuspendModal = (nurse) => {
  currentNurse.value = nurse
  showSuspendModal.value = true
  activeDropdown.value = null
}

const openActivateModal = (nurse) => {
  currentNurse.value = nurse
  showActivateModal.value = true
  activeDropdown.value = null
}

const openPasswordModal = (nurse) => {
  currentNurse.value = nurse
  passwordForm.value = {
    new_password: '',
    confirm_password: '',
    send_via_email: false
  }
  showPasswordModal.value = true
  activeDropdown.value = null
}

const closeNurseModal = () => {
  showNurseModal.value = false
  photoPreview.value = null
  photoFile.value = null
  photoToRemove.value = false
}

const closeViewModal = () => {
  showViewModal.value = false
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
}

const closeSuspendModal = () => {
  showSuspendModal.value = false
  currentNurse.value = null
}

const closeActivateModal = () => {
  showActivateModal.value = false
  currentNurse.value = null
}

const closePasswordModal = () => {
  showPasswordModal.value = false
  currentNurse.value = null
  passwordForm.value = {
    new_password: '',
    confirm_password: '',
    send_via_email: false
  }
}

const editFromView = () => {
  closeViewModal()
  openEditModal(currentNurse.value)
}

const saveNurse = async () => {
  isSaving.value = true
  
  try {
    const hasPhotoChange = photoFile.value || photoToRemove.value
    
    if (hasPhotoChange) {
      const formData = new FormData()
      
      Object.keys(nurseForm.value).forEach(key => {
        if (nurseForm.value[key] !== null && nurseForm.value[key] !== undefined && key !== 'current_photo_url') {
          formData.append(key, nurseForm.value[key])
        }
      })
      
      if (photoFile.value) {
        formData.append('photo', photoFile.value)
      }
      
      if (photoToRemove.value) {
        formData.append('remove_photo', '1')
      }
      
      if (isEditing.value) {
        formData.append('_method', 'PUT')
        await nursesService.updateNurseWithPhoto(currentNurse.value.id, formData)
      } else {
        await nursesService.createNurseWithPhoto(formData)
      }
      
    } else {
      if (isEditing.value) {
        await nursesService.updateNurse(currentNurse.value.id, nurseForm.value)
      } else {
        await nursesService.createNurse(nurseForm.value)
      }
    }
    
    photoFile.value = null
    photoPreview.value = null
    photoToRemove.value = false
    
    toast.showSuccess(isEditing.value ? 'Nurse updated successfully!' : 'Nurse created successfully!')
    await loadNurses(currentPage.value)
    closeNurseModal()
    
  } catch (error) {
    console.error('Error saving nurse:', error)
    toast.showError(error.message || 'Failed to save nurse. Please try again.')
  }
  
  isSaving.value = false
}

const deleteNurse = async () => {
  isSaving.value = true
  
  try {
    await nursesService.deleteNurse(currentNurse.value.id)
    await loadNurses(currentPage.value)
    closeDeleteModal()
    toast.showSuccess('Nurse deleted successfully!')
  } catch (error) {
    console.error('Error deleting nurse:', error)
    toast.showError(error.message || 'Failed to delete nurse. Please try again.')
  }
  
  isSaving.value = false
}

const verifyNurse = async (nurse) => {
  try {
    await nursesService.verifyNurse(nurse.id)
    await loadNurses(currentPage.value)
    toast.showSuccess('Nurse verified successfully!')
  } catch (error) {
    console.error('Error verifying nurse:', error)
    toast.showError(error.message || 'Failed to verify nurse.')
  }
  activeDropdown.value = null
}

const suspendNurse = async () => {
  isSuspending.value = true
  
  try {
    await nursesService.suspendNurse(currentNurse.value.id)
    await loadNurses(currentPage.value)
    closeSuspendModal()
    toast.showSuccess('Nurse suspended successfully!')
  } catch (error) {
    console.error('Error suspending nurse:', error)
    toast.showError(error.message || 'Failed to suspend nurse. Please try again.')
  }
  
  isSuspending.value = false
}

const activateNurse = async () => {
  isActivating.value = true
  
  try {
    const result = await nursesService.activateNurse(currentNurse.value.id)
    await loadNurses(currentPage.value)
    closeActivateModal()
    toast.showSuccess(result.message || 'Nurse activated successfully!')
  } catch (error) {
    console.error('Error activating nurse:', error)
    toast.showError(error.message || 'Failed to activate nurse. Please try again.')
  }
  
  isActivating.value = false
}

const changePassword = async () => {
  if (!passwordForm.value.send_via_email) {
    if (passwordForm.value.new_password !== passwordForm.value.confirm_password) {
      toast.showError('Passwords do not match!')
      return
    }
    
    if (passwordForm.value.new_password.length < 8) {
      toast.showError('Password must be at least 8 characters long!')
      return
    }
  }
  
  if (passwordForm.value.send_via_email) {
    isSendingPassword.value = true
  } else {
    isSaving.value = true
  }
  
  try {
    if (passwordForm.value.send_via_email) {
      const result = await nursesService.sendPasswordResetEmail(currentNurse.value.id)
      toast.showSuccess(result.message || 'Password reset email sent successfully!')
    } else {
      const result = await nursesService.changeNursePassword(currentNurse.value.id, {
        new_password: passwordForm.value.new_password
      })
      toast.showSuccess(result.message || 'Password changed successfully!')
    }
    
    closePasswordModal()
  } catch (error) {
    console.error('Error changing password:', error)
    toast.showError(error.message || 'Failed to change password. Please try again.')
  }
  
  isSaving.value = false
  isSendingPassword.value = false
}

const exportNurses = async () => {
  try {
    const filters = {
      specialization: specializationFilter.value,
      status: statusFilter.value,
      experience: experienceFilter.value,
      search: searchQuery.value
    }
    
    const { blob, filename } = await nursesService.exportNurses(filters)
    
    const downloadUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(downloadUrl)
    
    toast.showSuccess('Nurses exported successfully!')
  } catch (error) {
    console.error('Error exporting nurses:', error)
    toast.showError(error.message || 'Failed to export nurses. Please try again.')
  }
}

// Pagination methods
const goToPage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    loadNurses(page)
  }
}

const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    goToPage(currentPage.value + 1)
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1)
  }
}

const getPaginationPages = () => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(lastPage.value, start + maxVisible - 1)
  
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

// Debounce timer for search
let searchDebounceTimer = null

// Watch for search query changes with debounce
watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    currentPage.value = 1
    loadNurses(1)
  }, 500)
})

// Watch for filter changes (instant reload)
watch([statusFilter, specializationFilter, experienceFilter], () => {
  currentPage.value = 1
  loadNurses(1)
})

// Lifecycle
onMounted(() => {
  loadNurses()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  clearTimeout(searchDebounceTimer)
})
</script>

<style scoped>
/* Use the EXACT same styles from Users.vue */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.users-page {
  padding: 32px;
  background: #f8fafc;
  min-height: 100vh;
}

/* Photo Upload Styles */
.photo-upload-container {
  display: flex;
  gap: 24px;
  align-items: flex-start;
}

.photo-preview {
  width: 120px;
  height: 120px;
  border-radius: 16px;
  overflow: hidden;
  border: 2px solid #e2e8f0;
  flex-shrink: 0;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  gap: 8px;
}

.preview-placeholder svg {
  width: 40px;
  height: 40px;
}

.preview-placeholder p {
  font-size: 12px;
  font-weight: 500;
  margin: 0;
}

.photo-controls {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-sm {
  padding: 8px 16px;
  font-size: 13px;
}

/* All other styles from Users.vue - copying the entire style section */
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
  border-color: #e2e8f0;
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
  border: 1px solid #f1f5f9;
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

/* Modern Table */
.users-table-container {
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
  font-size: 14px;
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

.text-secondary {
  color: #94a3b8;
  font-weight: 500;
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

.modern-badge.badge-primary {
  background: #dbeafe;
  color: #1e40af;
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
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.05);
  min-width: 200px;
  z-index: 1000;
  padding: 8px;
  animation: slideInFadeMenu 0.2s ease-out;
}

@keyframes slideInFadeMenu {
  from {
    opacity: 0;
    transform: translateY(-8px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.form-group label .required {
  color: #ef4444;
  font-weight: 700;
  margin-left: 2px;
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

.dropdown-item-modern.success {
  color: #059669;
}

.dropdown-item-modern.success:hover {
  background: #f0fdf4;
}

.dropdown-item-modern.warning {
  color: #d97706;
}

.dropdown-item-modern.warning:hover {
  background: #fffbeb;
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
  margin: 0;
}

/* Pagination */
.pagination-container {
  padding: 20px 24px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.pagination-info {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pagination-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-btn svg {
  width: 16px;
  height: 16px;
}

.pagination-pages {
  display: flex;
  gap: 4px;
}

.pagination-page {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s;
}

.pagination-page:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.pagination-page.active {
  background: #667eea;
  border-color: #667eea;
  color: white;
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
    transform: translateY(20px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-lg {
  max-width: 800px;
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

.form-group input,
.form-group select {
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-section-header {
  grid-column: 1 / -1;
  margin-top: 12px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.form-section-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.3px;
}

.form-grid-full {
  grid-column: 1 / -1;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.checkbox-text {
  font-size: 14px;
  font-weight: 500;
  color: #334155;
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

/* View Modal Styles */
.user-view-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 32px;
}

.user-profile-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.profile-avatar-large {
  width: 120px;
  height: 120px;
  border-radius: 20px;
  object-fit: cover;
  border: 4px solid #e2e8f0;
  margin-bottom: 16px;
}

.profile-name-view {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 12px 0;
  letter-spacing: -0.4px;
}

.profile-contact-view {
  margin-top: 20px;
  width: 100%;
}

.contact-item-view {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: #f8fafc;
  border-radius: 10px;
  margin-bottom: 8px;
}

.contact-item-view svg {
  width: 18px;
  height: 18px;
  color: #64748b;
  flex-shrink: 0;
}

.contact-item-view span {
  font-size: 13px;
  color: #334155;
  font-weight: 500;
}

.details-section-view {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.details-group {
  background: #f8fafc;
  padding: 20px;
  border-radius: 12px;
}

.details-header {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 16px 0;
  letter-spacing: -0.3px;
}

.details-grid-view {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.detail-item-view {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-item-view label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-item-view p {
  font-size: 14px;
  color: #0f172a;
  font-weight: 500;
  margin: 0;
}

.form-help {
  font-size: 13px;
  color: #64748b;
  margin: 8px 0 0 0;
  line-height: 1.5;
}

/* Password Option Card */
.password-option-card {
  background: #f8fafc;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 20px;
}

.password-option-card .checkbox-label {
  margin-bottom: 8px;
}

.password-option-card .form-help {
  margin-left: 28px;
  margin-top: 8px;
}

/* Responsive */
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .users-page {
    padding: 16px;
  }
  
  .page-header {
    flex-direction: column;
    gap: 16px;
  }
  
  .page-header-content h1 {
    font-size: 24px;
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

  .user-view-grid {
    grid-template-columns: 1fr;
  }
  
  .details-grid-view {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .photo-upload-container {
    flex-direction: column;
  }
}
</style>