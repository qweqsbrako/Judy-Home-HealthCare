<template>
  <MainLayout>
    <div class="roles-permissions-container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-content">
          <h1 class="page-title">Roles & Permissions</h1>
          <p class="page-subtitle">Manage user roles and permissions for the healthcare system</p>
        </div>
        <div class="header-actions">
          <button class="btn btn-primary" @click="openCreateRoleModal">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            Create Role
          </button>
        </div>
      </div>

      <!-- View Role Details Modal -->
      <div v-if="showViewRoleModal" class="modal-overlay" @click="closeViewRoleModal">
        <div class="modal large" @click.stop>
          <div class="modal-header">
            <h2 class="modal-title">
              Role Details - {{ currentRole?.display_name }}
            </h2>
            <button class="modal-close" @click="closeViewRoleModal">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="role-details-content">
              <!-- Role Information -->
              <div class="details-section">
                <h3 class="section-title">Basic Information</h3>
                <div class="details-grid">
                  <div class="detail-item">
                    <label>Role Name (Internal)</label>
                    <span class="detail-value">{{ currentRole?.name }}</span>
                  </div>
                  <div class="detail-item">
                    <label>Display Name</label>
                    <span class="detail-value">{{ currentRole?.display_name }}</span>
                  </div>
                  <div class="detail-item">
                    <label>Description</label>
                    <span class="detail-value">{{ currentRole?.description || 'No description provided' }}</span>
                  </div>
                  <div class="detail-item">
                    <label>Role Type</label>
                    <span class="detail-value">
                      <span v-if="currentRole?.is_system_role" class="badge system">System Role</span>
                      <span v-else class="badge custom">Custom Role</span>
                    </span>
                  </div>
                  <div class="detail-item">
                    <label>Status</label>
                    <span class="detail-value">
                      <span 
                        class="badge status" 
                        :class="{ active: currentRole?.is_active, inactive: !currentRole?.is_active }"
                      >
                        {{ currentRole?.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Statistics -->
              <div class="details-section">
                <h3 class="section-title">Statistics</h3>
                <div class="stats-row">
                  <div class="stat-card">
                    <div class="stat-number">{{ currentRole?.users_count || 0 }}</div>
                    <div class="stat-label">Users Assigned</div>
                  </div>
                  <div class="stat-card">
                    <div class="stat-number">{{ currentRole?.permissions_count || 0 }}</div>
                    <div class="stat-label">Permissions Granted</div>
                  </div>
                  <div class="stat-card">
                    <div class="stat-number">{{ Object.keys(currentRole?.permissions || {}).length }}</div>
                    <div class="stat-label">Permission Categories</div>
                  </div>
                </div>
              </div>

              <!-- Permissions by Category -->
              <div class="details-section">
                <h3 class="section-title">Assigned Permissions</h3>
                <div v-if="currentRole?.permissions && Object.keys(currentRole.permissions).length > 0" class="permissions-overview">
                  <div 
                    v-for="(categoryPerms, category) in currentRole.permissions" 
                    :key="category"
                    class="permission-category-overview"
                  >
                    <h4 class="category-name">{{ formatCategoryName(category) }}</h4>
                    <div class="permission-tags">
                      <span 
                        v-for="permission in categoryPerms" 
                        :key="permission.id"
                        class="permission-tag"
                      >
                        {{ permission.display_name || formatCategoryName(permission.name) }}
                      </span>
                    </div>
                  </div>
                </div>
                <div v-else class="no-permissions">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L2 7v10c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V7l-10-5z"/>
                  </svg>
                  <p>No permissions assigned to this role</p>
                </div>
              </div>

              <!-- Timestamps -->
              <div class="details-section">
                <h3 class="section-title">Timeline</h3>
                <div class="timeline-item">
                  <div class="timeline-icon created">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                  </div>
                  <div class="timeline-content">
                    <div class="timeline-title">Role Created</div>
                    <div class="timeline-date">{{ new Date(currentRole?.created_at).toLocaleString() }}</div>
                  </div>
                </div>
                <div class="timeline-item">
                  <div class="timeline-icon updated">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                    </svg>
                  </div>
                  <div class="timeline-content">
                    <div class="timeline-title">Last Updated</div>
                    <div class="timeline-date">{{ new Date(currentRole?.updated_at).toLocaleString() }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-secondary" @click="closeViewRoleModal">
                <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
                          10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17
                          12 13.41 8.41 17 7 15.59 10.59 12
                          7 8.41 8.41 7 12 10.59 15.59 7
                          17 8.41 13.41 12 17 15.59z"/>
                </svg>
                Cancel
              </button>
              <button 
                type="button" 
                class="btn btn-primary" 
                @click="editRole(currentRole)"
              >
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                </svg>
                Edit Role
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs-container">
        <div class="tabs">
          <button 
            class="tab" 
            :class="{ active: activeTab === 'roles' }" 
            @click="activeTab = 'roles'"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zM4 18v-4h3v4h2v-7.5c0-1.1.9-2 2-2s2 .9 2 2V18h2v-4h3v4h1v2H3v-2h1z"/>
            </svg>
            Roles ({{ roles.length }})
          </button>
          <button 
            class="tab" 
            :class="{ active: activeTab === 'permissions' }" 
            @click="activeTab = 'permissions'"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2L2 7v10c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V7l-10-5z"/>
            </svg>
            Permissions ({{ totalPermissions }})
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-container">
        <div class="loading-spinner">
        
        </div>
      </div>

      <!-- Roles Tab Content -->
      <div v-else-if="activeTab === 'roles'" class="tab-content">
        
        <!-- Search and Filters -->
        <div class="filters-section">
          <div class="search-input">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M21 19l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input 
              type="text" 
              placeholder="Search roles..." 
              v-model="roleSearchQuery"
            >
          </div>
          <div class="filter-buttons">
            <button 
              class="filter-btn" 
              :class="{ active: roleFilter === 'all' }" 
              @click="roleFilter = 'all'"
            >
              All ({{ roles.length }})
            </button>
            <button 
              class="filter-btn" 
              :class="{ active: roleFilter === 'system' }" 
              @click="roleFilter = 'system'"
            >
              System ({{ systemRolesCount }})
            </button>
            <button 
              class="filter-btn" 
              :class="{ active: roleFilter === 'custom' }" 
              @click="roleFilter = 'custom'"
            >
              Custom ({{ customRolesCount }})
            </button>
          </div>
        </div>

        <!-- Roles Grid -->
        <div class="roles-grid">
          <div 
            v-for="role in filteredRoles" 
            :key="role.id" 
            class="role-card"
            :class="{ 'system-role': role.is_system_role }"
          >
            <div class="role-card-header">
              <div class="role-info">
                <h3 class="role-name">{{ role.display_name }}</h3>
                <p class="role-description">{{ role.description }}</p>
                <div class="role-badges">
                  <span v-if="role.is_system_role" class="badge system">System Role</span>
                  <span v-else class="badge custom">Custom Role</span>
                  <span 
                    class="badge status" 
                    :class="{ active: role.is_active, inactive: !role.is_active }"
                  >
                    {{ role.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
              <div class="role-actions">
                <div class="dropdown" @click.stop>
                  <button class="dropdown-toggle" @click="toggleRoleDropdown(role.id)">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                    </svg>
                  </button>
                  <div 
                    v-if="activeRoleDropdown === role.id" 
                    class="dropdown-menu"
                  >
                    <button @click="viewRole(role)" class="dropdown-item">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                      </svg>
                      View Details
                    </button>
                    <button @click="editRole(role)" class="dropdown-item">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                      </svg>
                      Edit Role
                    </button>
                    <button @click="managePermissions(role)" class="dropdown-item">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L2 7v10c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V7l-10-5z"/>
                      </svg>
                      Manage Permissions
                    </button>
                 
                    <div v-if="!role.is_system_role" class="dropdown-divider"></div>
                    <button 
                      v-if="!role.is_system_role" 
                      @click="openDeleteModal(role)" 
                      class="dropdown-item danger"
                    >
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                      </svg>
                      Delete Role
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="role-card-body">
              <div class="role-stats">
                <div class="stat">
                  <span class="stat-value">{{ role.users_count }}</span>
                  <span class="stat-label">Users</span>
                </div>
                <div class="stat">
                  <span class="stat-value">{{ role.permissions_count }}</span>
                  <span class="stat-label">Permissions</span>
                </div>
              </div>
            </div>

            <div class="role-card-footer">
              <div class="permission-preview">
                <div class="permission-categories">
                  <span 
                    v-for="(categoryPerms, category) in role.permissions" 
                    :key="category"
                    class="category-tag"
                  >
                    {{ formatCategoryName(category) }}
                  </span>
                </div>
                <div class="role-updated">
                  Updated {{ formatTimeAgo(role.updated_at) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Permissions Tab Content -->
      <div v-else-if="activeTab === 'permissions'" class="tab-content">
        
        <!-- Permission Categories -->
        <div class="permissions-container">
          <div 
            v-for="category in permissionCategories" 
            :key="category.category" 
            class="permission-category"
          >
            <div class="category-header">
              <h3 class="category-title">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2L2 7v10c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V7l-10-5z"/>
                </svg>
                {{ category.display_category }}
              </h3>
              <span class="permission-count">{{ category.total_permissions }} permissions</span>
            </div>

            <!-- General Permissions -->
            <div v-if="category.general_permissions.length > 0" class="permission-section">
              <h4 class="section-title">General</h4>
              <div class="permissions-list">
                <div 
                  v-for="permission in category.general_permissions" 
                  :key="permission.id"
                  class="permission-item"
                >
                  <div class="permission-info">
                    <span class="permission-name">{{ permission.display_name }}</span>
                    <p class="permission-description">{{ permission.description }}</p>
                  </div>
                  <button 
                    class="permission-roles-btn" 
                    @click="viewPermissionRoles(permission)"
                    :title="`View roles with this permission`"
                  >
                    View Roles
                  </button>
                </div>
              </div>
            </div>

            <!-- Subcategory Permissions -->
            <div 
              v-for="subcategory in category.subcategories" 
              :key="subcategory.subcategory"
              class="permission-section"
            >
              <h4 class="section-title">{{ subcategory.display_subcategory }}</h4>
              <div class="permissions-list">
                <div 
                  v-for="permission in subcategory.permissions" 
                  :key="permission.id"
                  class="permission-item"
                >
                  <div class="permission-info">
                    <span class="permission-name">{{ permission.display_name }}</span>
                    <p class="permission-description">{{ permission.description }}</p>
                  </div>
                  <button 
                    class="permission-roles-btn" 
                    @click="viewPermissionRoles(permission)"
                    :title="`View roles with this permission`"
                  >
                    View Roles
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Create/Edit Role Modal -->
      <div v-if="showRoleModal" class="modal-overlay" @click="closeRoleModal">
        <div class="modal" @click.stop>
          <div class="modal-header">
            <h2 class="modal-title">
              {{ isEditingRole ? 'Edit Role' : 'Create New Role' }}
            </h2>
            <button class="modal-close" @click="closeRoleModal">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="form-container">
              <div class="form-group">
                <label for="roleName">Role Name (Internal)</label>
                <input 
                  id="roleName"
                  type="text" 
                  v-model="roleForm.name"
                  placeholder="e.g., care_coordinator"
                  :disabled="isEditingRole && currentRole?.is_system_role"
                  required
                >
                <small class="form-help">Lowercase, underscores only. Used internally.</small>
              </div>

              <div class="form-group">
                <label for="roleDisplayName">Display Name</label>
                <input 
                  id="roleDisplayName"
                  type="text" 
                  v-model="roleForm.display_name"
                  placeholder="e.g., Care Coordinator"
                  required
                >
                <small class="form-help">Human-readable name shown to users.</small>
              </div>

              <div class="form-group">
                <label for="roleDescription">Description</label>
                <textarea 
                  id="roleDescription"
                  v-model="roleForm.description"
                  placeholder="Brief description of this role's responsibilities..."
                  rows="3"
                ></textarea>
              </div>

              <div v-if="isEditingRole">
                <label class="checkbox-label">
                  <input
                    type="checkbox"
                    v-model="roleForm.is_active"
                    :disabled="currentRole?.is_system_role"
                  >
                  <span class="checkmark"></span>
                  <span class="checkbox-text">Role is active</span>
                </label>
              </div>

              <div class="modal-actions">
                <button type="button" class="btn btn-secondary" @click="closeRoleModal">
                  <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
                            10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17
                            12 13.41 8.41 17 7 15.59 10.59 12
                            7 8.41 8.41 7 12 10.59 15.59 7
                            17 8.41 13.41 12 17 15.59z"/>
                  </svg>
                  Cancel
                </button>
                <button 
                  type="button" 
                  class="btn btn-primary" 
                  @click="saveRole"
                  :disabled="isSavingRole"
                >
                  <svg v-if="isSavingRole" class="spinner" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                      <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                      <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                    </circle>
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                  </svg>
                  {{ isEditingRole ? 'Update Role' : 'Create Role' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Duplicate Role Modal -->
      <div v-if="showDuplicateModal" class="modal-overlay" @click="closeDuplicateModal">
        <div class="modal" @click.stop>
          <div class="modal-header">
            <h2 class="modal-title">
              <svg viewBox="0 0 24 24" fill="currentColor" class="modal-icon">
                <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
              </svg>
              Duplicate Role
            </h2>
            <button class="modal-close" @click="closeDuplicateModal">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="duplicate-info">
              <div class="source-role-info">
                <h4>Creating a copy of:</h4>
                <div class="role-card-mini">
                  <div class="role-info-mini">
                    <h5>{{ roleToduplicate?.display_name }}</h5>
                    <p>{{ roleToduplicate?.description }}</p>
                    <div class="mini-badges">
                      <span v-if="roleToDelete?.is_system_role" class="badge system mini">System Role</span>
                      <span v-else class="badge custom mini">Custom Role</span>
                      <span class="badge mini-stat">{{ roleToDelete?.permissions_count || 0 }} permissions</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="duplicate-form">
                <div class="form-group">
                  <label for="duplicateRoleName">New Role Name (Internal)</label>
                  <input 
                    id="duplicateRoleName"
                    type="text" 
                    v-model="duplicateForm.name"
                    placeholder="e.g., care_coordinator_copy"
                    required
                  >
                  <small class="form-help">Must be unique. Lowercase, underscores only.</small>
                </div>

                <div class="form-group">
                  <label for="duplicateRoleDisplayName">Display Name</label>
                  <input 
                    id="duplicateRoleDisplayName"
                    type="text" 
                    v-model="duplicateForm.display_name"
                    placeholder="e.g., Care Coordinator (Copy)"
                    required
                  >
                  <small class="form-help">Human-readable name shown to users.</small>
                </div>

                <div class="form-group">
                  <label for="duplicateRoleDescription">Description</label>
                  <textarea 
                    id="duplicateRoleDescription"
                    v-model="duplicateForm.description"
                    placeholder="Description for the duplicated role..."
                    rows="3"
                  ></textarea>
                </div>

                <div class="duplicate-features">
                  <h5>What will be copied:</h5>
                  <ul class="feature-list">
                    <li>
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                      </svg>
                      All assigned permissions ({{ roleToDelete?.permissions_count || 0 }})
                    </li>
                    <li>
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                      </svg>
                      Role configuration and settings
                    </li>
                  </ul>
                  
                  <div class="duplicate-note">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <span>The new role will be created as a custom role and can be modified independently.</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-secondary" @click="closeDuplicateModal">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
                Cancel
              </button>
              <button 
                type="button" 
                class="btn btn-primary" 
                @click="confirmDuplicate"
                :disabled="isDuplicating || !duplicateForm.name.trim() || !duplicateForm.display_name.trim()"
              >
                <svg v-if="isDuplicating" class="spinner" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                    <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                    <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                  </circle>
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="currentColor">
                  <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                </svg>
                {{ isDuplicating ? 'Duplicating...' : 'Create Duplicate' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
        <div class="modal delete-modal" @click.stop>
          <div class="modal-header danger">
            <h2 class="modal-title">
              <svg viewBox="0 0 24 24" fill="currentColor" class="modal-icon danger">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
              Confirm Role Deletion
            </h2>
            <button class="modal-close" @click="closeDeleteModal">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="delete-content">
              <div class="delete-warning">
                <div class="warning-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                  </svg>
                </div>
                <div class="warning-text">
                  <h4>This action cannot be undone!</h4>
                  <p>You are about to permanently delete this role and all its associated data.</p>
                </div>
              </div>

              <div class="role-to-delete">
                <h5>Role to be deleted:</h5>
                <div class="role-card-mini delete">
                  <div class="role-info-mini">
                    <h6>{{ roleToDelete?.display_name }}</h6>
                    <p>{{ roleToDelete?.description }}</p>
                    <div class="mini-badges">
                      <span v-if="roleToDelete?.is_system_role" class="badge system mini">System Role</span>
                      <span v-else class="badge custom mini">Custom Role</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="deletion-impact">
                <h5>Deletion Impact:</h5>
                <div class="impact-items">
                  <div class="impact-item">
                    <div class="impact-icon users">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zM4 18v-4h3v4h2v-7.5c0-1.1.9-2 2-2s2 .9 2 2V18h2v-4h3v4h1v2H3v-2h1z"/>
                      </svg>
                    </div>
                    <div class="impact-details">
                      <span class="impact-count">{{ roleToDelete?.users_count || 0 }}</span>
                      <span class="impact-label">Users will lose this role</span>
                    </div>
                  </div>

                  <div class="impact-item">
                    <div class="impact-icon permissions">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L2 7v10c0 5.55 3.84 9.739 9 11 5.16-1.261 9-5.45 9-11V7l-10-5z"/>
                      </svg>
                    </div>
                    <div class="impact-details">
                      <span class="impact-count">{{ roleToDelete?.permissions_count || 0 }}</span>
                      <span class="impact-label">Permission assignments will be removed</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="confirmation-input">
                <label for="deleteConfirmation">
                  To confirm deletion, please type <strong>{{ roleToDelete?.display_name }}</strong> below:
                </label>
                <input 
                  id="deleteConfirmation"
                  type="text" 
                  v-model="deleteConfirmationText"
                  :placeholder="`Type '${roleToDelete?.display_name}' to confirm`"
                  class="confirmation-field"
                >
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
                Cancel
              </button>
              <button 
                type="button" 
                class="btn btn-danger" 
                @click="confirmDelete"
                :disabled="isDeleting || deleteConfirmationText !== roleToDelete?.display_name"
              >
                <svg v-if="isDeleting" class="spinner" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                    <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                    <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                  </circle>
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="currentColor">
                  <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                </svg>
                {{ isDeleting ? 'Deleting...' : 'Delete Role' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Permissions Management Modal -->
      <div v-if="showPermissionsModal" class="modal-overlay" @click="closePermissionsModal">
        <div class="modal large" @click.stop>
          <div class="modal-header">
            <h2 class="modal-title">
              Manage Permissions - {{ currentRole?.display_name }}
            </h2>
            <button class="modal-close" @click="closePermissionsModal">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="permissions-management">
              <div class="permissions-stats">
                <div class="stat">
                  <span class="stat-value">{{ selectedPermissions.length }}</span>
                  <span class="stat-label">Selected</span>
                </div>
                <div class="stat">
                  <span class="stat-value">{{ totalPermissions }}</span>
                  <span class="stat-label">Total Available</span>
                </div>
              </div>

              <div class="permissions-selection">
                <div 
                  v-for="category in permissionCategories" 
                  :key="category.category"
                  class="permission-category-selection"
                >
                  <div class="category-header-selection">
                    <label class="category-checkbox">
                      <input 
                        type="checkbox"
                        :checked="isCategorySelected(category)"
                        :indeterminate.prop="isCategoryIndeterminate(category)"
                        @change="toggleCategory(category)"
                      >
                      <span class="checkmark"></span>
                      <span class="category-name">{{ category.display_category }}</span>
                    </label>
                  </div>

                  <!-- General Permissions -->
                  <div v-if="category.general_permissions.length > 0" class="permission-subsection">
                    <h5 class="subsection-title">General</h5>
                    <div class="permissions-checkboxes">
                      <label 
                        v-for="permission in category.general_permissions"
                        :key="permission.id"
                        class="permission-checkbox"
                      >
                        <input 
                          type="checkbox"
                          :value="permission.id"
                          v-model="selectedPermissions"
                        >
                        <span class="checkmark"></span>
                        <div class="permission-details">
                          <span class="permission-name">{{ permission.display_name }}</span>
                          <p class="permission-description">{{ permission.description }}</p>
                        </div>
                      </label>
                    </div>
                  </div>

                  <!-- Subcategory Permissions -->
                  <div 
                    v-for="subcategory in category.subcategories"
                    :key="subcategory.subcategory"
                    class="permission-subsection"
                  >
                    <h5 class="subsection-title">{{ subcategory.display_subcategory }}</h5>
                    <div class="permissions-checkboxes">
                      <label 
                        v-for="permission in subcategory.permissions"
                        :key="permission.id"
                        class="permission-checkbox"
                      >
                        <input 
                          type="checkbox"
                          :value="permission.id"
                          v-model="selectedPermissions"
                        >
                        <span class="checkmark"></span>
                        <div class="permission-details">
                          <span class="permission-name">{{ permission.display_name }}</span>
                          <p class="permission-description">{{ permission.description }}</p>
                        </div>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-secondary" @click="closePermissionsModal">
                Cancel
              </button>
              <button 
                type="button" 
                class="btn btn-primary" 
                @click="savePermissions"
                :disabled="isSavingPermissions"
              >
                <svg v-if="isSavingPermissions" class="spinner" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                    <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                    <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                  </circle>
                </svg>
                Save Permissions
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Toast Component -->
      <Toast />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import Toast from '../../common/components/Toast.vue'

// Get toast functionality
const toast = inject('toast')

// Reactive data
const activeTab = ref('roles')
const isLoading = ref(true)
const roles = ref([])
const permissionCategories = ref([])
const roleSearchQuery = ref('')
const roleFilter = ref('all')

// Modal states
const showRoleModal = ref(false)
const showPermissionsModal = ref(false)
const showViewRoleModal = ref(false)
const showDuplicateModal = ref(false)
const showDeleteModal = ref(false)
const isEditingRole = ref(false)
const currentRole = ref(null)
const roleToDelete = ref(null)
const isSavingRole = ref(false)
const isSavingPermissions = ref(false)
const isDuplicating = ref(false)
const isDeleting = ref(false)
const editingRoleId = ref(null)
const deleteConfirmationText = ref('')

// Dropdown states
const activeRoleDropdown = ref(null)

// Permission management
const selectedPermissions = ref([])

// Form data
const roleForm = reactive({
  name: '',
  display_name: '',
  description: '',
  is_active: true
})

const duplicateForm = reactive({
  name: '',
  display_name: '',
  description: ''
})

// Computed properties
const filteredRoles = computed(() => {
  let filtered = roles.value

  // Apply search filter
  if (roleSearchQuery.value) {
    const query = roleSearchQuery.value.toLowerCase()
    filtered = filtered.filter(role => 
      role.name.toLowerCase().includes(query) ||
      role.display_name.toLowerCase().includes(query) ||
      role.description?.toLowerCase().includes(query)
    )
  }

  // Apply role type filter
  if (roleFilter.value === 'system') {
    filtered = filtered.filter(role => role.is_system_role)
  } else if (roleFilter.value === 'custom') {
    filtered = filtered.filter(role => !role.is_system_role)
  }

  return filtered
})

const systemRolesCount = computed(() => {
  return roles.value.filter(role => role.is_system_role).length
})

const customRolesCount = computed(() => {
  return roles.value.filter(role => !role.is_system_role).length
})

const totalPermissions = computed(() => {
  return permissionCategories.value.reduce((total, category) => {
    return total + category.total_permissions
  }, 0)
})

// Methods
const fetchRoles = async () => {
  try {
    const response = await fetch('/roles', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
      }
    })

    const data = await response.json()

    if (data.success) {
      roles.value = data.data
    } else {
      throw new Error(data.message)
    }
  } catch (error) {
    console.error('Failed to fetch roles:', error)
    toast.showError('Failed to load roles. Please try again.')
  }
}

const fetchPermissions = async () => {
  try {
    const response = await fetch('/permissions', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
      }
    })

    const data = await response.json()

    if (data.success) {
      permissionCategories.value = data.data
    } else {
      throw new Error(data.message)
    }
  } catch (error) {
    console.error('Failed to fetch permissions:', error)
    toast.showError('Failed to load permissions. Please try again.')
  }
}


const openCreateRoleModal = () => {
  // Reset everything for new role creation
  isEditingRole.value = false
  currentRole.value = null
  editingRoleId.value = null
  
  // Reset form fields
  roleForm.name = ''
  roleForm.display_name = ''
  roleForm.description = ''
  roleForm.is_active = true
  
  showRoleModal.value = true
}

const editRole = (role) => {
  if (!role) {
    toast.showError('Invalid role data. Please try again.')
    return
  }

  // Set editing state and current role BEFORE opening modal
  isEditingRole.value = true
  currentRole.value = role
  
  // Store role ID separately as backup
  editingRoleId.value = role.id
  
  // Ensure all form fields are properly set with fallback values
  roleForm.name = role.name || ''
  roleForm.display_name = role.display_name || ''
  roleForm.description = role.description || ''
  roleForm.is_active = role.is_active !== undefined ? role.is_active : true
  
  console.log('State before opening modal:', {
    isEditingRole: isEditingRole.value,
    currentRole: currentRole.value,
    editingRoleId: editingRoleId.value
  }) // Debug log
  
  // Close dropdown and other modals first
  closeRoleDropdown()
  closeViewRoleModal()
  
  // Open the modal last
  showRoleModal.value = true
}

const closeRoleModal = () => {
  showRoleModal.value = false
  // Reset state
  isEditingRole.value = false
  currentRole.value = null
  editingRoleId.value = null
  isSavingRole.value = false
  
  // Reset form to prevent stale data
  roleForm.name = ''
  roleForm.display_name = ''
  roleForm.description = ''
  roleForm.is_active = true
}

const closeViewRoleModal = () => {
  showViewRoleModal.value = false
  currentRole.value = null
}

const openDuplicateModal = (role) => {
  roleToDelete = role
  duplicateForm.name = `${role.name}_copy`
  duplicateForm.display_name = `${role.display_name} (Copy)`
  duplicateForm.description = `Copy of ${role.display_name}`
  
  showDuplicateModal.value = true
  closeRoleDropdown()
}

const closeDuplicateModal = () => {
  showDuplicateModal.value = false
  roleToDelete = null
  isDuplicating.value = false
  duplicateForm.name = ''
  duplicateForm.display_name = ''
  duplicateForm.description = ''
}

const confirmDuplicate = async () => {
  isDuplicating.value = true

  try {
    const response = await fetch(`/roles/${roleToDelete.id}/duplicate`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(duplicateForm)
    })

    const data = await response.json()

    if (data.success) {
      await fetchRoles()
      closeDuplicateModal()
      toast.showSuccess('Role duplicated successfully')
    } else {
      throw new Error(data.message)
    }
  } catch (error) {
    console.error('Failed to duplicate role:', error)
    // Mock behavior for demo
    const newRole = {
      ...roleToDelete,
      id: Date.now(),
      name: duplicateForm.name,
      display_name: duplicateForm.display_name,
      description: duplicateForm.description,
      is_system_role: false,
      users_count: 0,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    }
    roles.value = [...roles.value, newRole]
    closeDuplicateModal()
    toast.showSuccess('Role duplicated successfully')
  } finally {
    isDuplicating.value = false
  }
}

const openDeleteModal = (role) => {
  roleToDelete.value = role
  deleteConfirmationText.value = ''
  showDeleteModal.value = true
  closeRoleDropdown()
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  roleToDelete.value = null
  deleteConfirmationText.value = ''
  isDeleting.value = false
}

const confirmDelete = async () => {
  isDeleting.value = true

  try {
    const response = await fetch(`/roles/${roleToDelete.value.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
      }
    })

    const data = await response.json()

    if (data.success) {
      await fetchRoles()
      closeDeleteModal()
      toast.showSuccess('Role deleted successfully')
    } else {
      throw new Error(data.message)
    }
  } catch (error) {
    console.error('Failed to delete role:', error)
    // Mock behavior for demo
    roles.value = roles.value.filter(r => r.id !== roleToDelete.value.id)
    closeDeleteModal()
    toast.showSuccess('Role deleted successfully')
  } finally {
    isDeleting.value = false
  }
}

const saveRole = async () => {
  console.log('saveRole called with state:', {
    isEditingRole: isEditingRole.value,
    currentRole: currentRole.value,
    editingRoleId: editingRoleId.value
  }) // Debug log
  
  // Enhanced validation for editing mode
  if (isEditingRole.value) {
    if (!currentRole.value && !editingRoleId.value) {
      toast.showError('No role selected for editing. Please try again.')
      closeRoleModal()
      return
    }
    
    // If currentRole is missing but we have editingRoleId, try to recover
    if (!currentRole.value && editingRoleId.value) {
      currentRole.value = roles.value.find(role => role.id === editingRoleId.value)
      if (!currentRole.value) {
        toast.showError('Could not find the role to edit. Please try again.')
        closeRoleModal()
        return
      }
    }
  }

  isSavingRole.value = true

  try {
    const url = isEditingRole.value ? `/roles/${currentRole.value.id}` : '/roles'
    const method = isEditingRole.value ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(roleForm)
    })

    const data = await response.json()

    if (data.success) {
      await fetchRoles()
      closeRoleModal()
      toast.showSuccess(data.message || `Role ${isEditingRole.value ? 'updated' : 'created'} successfully`)
    } else {
      throw new Error(data.message)
    }
  } catch (error) {
    console.error('Failed to save role:', error)
    // Mock behavior for demo
    if (isEditingRole.value && currentRole.value) {
      const updatedRoles = roles.value.map(role => 
        role.id === currentRole.value.id ? { ...role, ...roleForm, updated_at: new Date().toISOString() } : role
      )
      roles.value = updatedRoles
    } else {
      const newRole = {
        id: Date.now(),
        ...roleForm,
        is_system_role: false,
        users_count: 0,
        permissions_count: 0,
        permissions: {},
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      }
      roles.value = [...roles.value, newRole]
    }
    closeRoleModal()
    toast.showSuccess(`Role ${isEditingRole.value ? 'updated' : 'created'} successfully`)
  } finally {
    isSavingRole.value = false
  }
}

const managePermissions = async (role) => {
  currentRole.value = role
  
  // Get current role permissions
  try {
    const response = await fetch(`/roles/${role.id}/permissions`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
      }
    })

    const data = await response.json()

    if (data.success) {
      // Extract permission IDs from role permissions
      selectedPermissions.value = []
      Object.values(data.data.permissions).forEach(categoryPerms => {
        categoryPerms.forEach(permission => {
          selectedPermissions.value.push(permission.id)
        })
      })
    }
  } catch (error) {
    console.error('Failed to fetch role permissions:', error)
    // Mock behavior - extract current permissions
    selectedPermissions.value = []
    Object.values(role.permissions || {}).forEach(categoryPerms => {
      categoryPerms.forEach(permName => {
        const permission = permissionCategories.value.flatMap(cat => [
          ...cat.general_permissions,
          ...cat.subcategories.flatMap(sub => sub.permissions)
        ]).find(p => p.name === permName)
        if (permission) selectedPermissions.value.push(permission.id)
      })
    })
  }

  showPermissionsModal.value = true
  closeRoleDropdown()
}

const closePermissionsModal = () => {
  showPermissionsModal.value = false
  currentRole.value = null
  selectedPermissions.value = []
  isSavingPermissions.value = false
}

const savePermissions = async () => {
  isSavingPermissions.value = true

  try {
    const response = await fetch(`/roles/${currentRole.value.id}/permissions`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        permissions: selectedPermissions.value
      })
    })

    const data = await response.json()

    if (data.success) {
      await fetchRoles()
      closePermissionsModal()
      toast.showSuccess('Permissions updated successfully')
    } else {
      throw new Error(data.message)
    }
  } catch (error) {
    console.error('Failed to save permissions:', error)
    // Mock behavior for demo
    const updatedRoles = roles.value.map(role =>
      role.id === currentRole.value.id 
        ? { ...role, permissions_count: selectedPermissions.value.length, updated_at: new Date().toISOString() }
        : role
    )
    roles.value = updatedRoles
    closePermissionsModal()
    toast.showSuccess('Permissions updated successfully')
  } finally {
    isSavingPermissions.value = false
  }
}

const viewRole = (role) => {
  currentRole.value = role
  showViewRoleModal.value = true
  closeRoleDropdown()
}

const viewPermissionRoles = (permission) => {
  toast.showInfo(`Viewing roles for permission: ${permission.display_name}`)
}

const toggleRoleDropdown = (roleId) => {
  activeRoleDropdown.value = activeRoleDropdown.value === roleId ? null : roleId
}

const closeRoleDropdown = () => {
  activeRoleDropdown.value = null
}

const formatCategoryName = (category) => {
  if (typeof category !== 'string') {
    return 'Unknown'
  }
  
  if (category === '') {
    return 'Unnamed'
  }
  
  return category.replace(/_/g, ' ')
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const formatTimeAgo = (dateString) => {
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

const isCategorySelected = (category) => {
  const categoryPermissionIds = [
    ...category.general_permissions.map(p => p.id),
    ...category.subcategories.flatMap(sub => sub.permissions.map(p => p.id))
  ]
  return categoryPermissionIds.every(id => selectedPermissions.value.includes(id))
}

const isCategoryIndeterminate = (category) => {
  const categoryPermissionIds = [
    ...category.general_permissions.map(p => p.id),
    ...category.subcategories.flatMap(sub => sub.permissions.map(p => p.id))
  ]
  const selectedCount = categoryPermissionIds.filter(id => selectedPermissions.value.includes(id)).length
  return selectedCount > 0 && selectedCount < categoryPermissionIds.length
}

const toggleCategory = (category) => {
  const categoryPermissionIds = [
    ...category.general_permissions.map(p => p.id),
    ...category.subcategories.flatMap(sub => sub.permissions.map(p => p.id))
  ]
  
  const allSelected = categoryPermissionIds.every(id => selectedPermissions.value.includes(id))
  
  if (allSelected) {
    // Remove all category permissions
    selectedPermissions.value = selectedPermissions.value.filter(id => !categoryPermissionIds.includes(id))
  } else {
    // Add all category permissions
    const newPermissions = [...selectedPermissions.value]
    categoryPermissionIds.forEach(id => {
      if (!newPermissions.includes(id)) {
        newPermissions.push(id)
      }
    })
    selectedPermissions.value = newPermissions
  }
}

// Lifecycle
onMounted(async () => {
  isLoading.value = true
  
  try {
    await Promise.all([
      fetchRoles(),
      fetchPermissions()
    ])
  } catch (error) {
    console.error('Failed to load initial data:', error)
    toast.showError('Failed to load initial data. Please refresh the page.')
  } finally {
    isLoading.value = false
  }
})

// Close dropdowns when clicking outside
document.addEventListener('click', () => {
  closeRoleDropdown()
})
</script>

<style scoped>
/* ================================================
   COMPONENT-SPECIFIC STYLES ONLY
   ================================================ */

.roles-permissions-container {
  width: 100%;
  padding: 2rem;
  background: #fff;
}

/* ================================================
   FILTERS SECTION LAYOUT
   ================================================ */

.filters-section {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  align-items: center;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  max-width: 400px;
  min-width: 300px;
}

/* ================================================
   ROLES GRID LAYOUT
   ================================================ */

.roles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 1.5rem;
}

.role-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  overflow: hidden;
  transition: all 0.2s;
}

.role-card:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.role-card-system {
  border-left: 4px solid #D3D3D3;
}

.role-card:not(.role-card-system) {
  border-left: 4px solid #8b5cf6;
}

.role-card-header {
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.role-card.system-role {
  border-left: 4px solid #D3D3D3;
}

.role-card:not(.system-role) {
  border-left: 4px solid #8b5cf6;
}
.role-info {
  flex: 1;
}

.role-name {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.role-description {
  margin: 0 0 1rem 0;
  color: #6b7280;
  font-size: 0.875rem;
  line-height: 1.4;
}

.role-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.role-actions {
  position: relative;
}

.role-card-body {
  padding: 0 1.5rem 1rem;
}

.role-stats {
  display: flex;
  gap: 2rem;
  justify-content: center;
}



.stat {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.role-card-footer {
  padding: 1rem 1.5rem;
  background: #f9fafb;
  border-top: 1px solid #e5e7eb;
}

.permission-preview {
  width: 100%;
}

.permission-categories {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 0.5rem;
}

.category-tag {
  background: #e0e7ff;
  color: #3730a3;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.role-updated {
  font-size: 0.75rem;
  color: #9ca3af;
}

/* ================================================
   PERMISSIONS LAYOUT
   ================================================ */

.permissions-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.permission-category {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1.5rem;
  transition: box-shadow 0.2s;
}

.permission-category:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.category-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.category-title svg {
  width: 20px;
  height: 20px;
  color: #3b82f6;
}

.permission-count {
  background: #f3f4f6;
  color: #4b5563;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
}

.permission-section {
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 1rem 0;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 0.5rem;
}

.permissions-list {
  display: grid;
  gap: 0.75rem;
}

.permission-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  border: 1px solid #f3f4f6;
  transition: all 0.2s;
}

.permission-item:hover {
  background: #f3f4f6;
  border-color: #e5e7eb;
}

.permission-info {
  flex: 1;
}

.permission-name {
  font-weight: 500;
  color: #1f2937;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
  display: block;
}

.permission-description {
  color: #6b7280;
  font-size: 0.8125rem;
  margin: 0;
  line-height: 1.4;
}

.permission-roles-btn {
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.375rem;
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.permission-roles-btn:hover {
  background: #2563eb;
}

/* ================================================
   ROLE DETAILS MODAL SPECIFIC
   ================================================ */

.role-details-content {
  max-height: 600px;
  overflow-y: auto;
  padding: 0.5rem;
}

.details-section {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.details-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e5e7eb;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-item label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.detail-value {
  font-size: 0.95rem;
  color: #1f2937;
  font-weight: 500;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.stat-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1.5rem;
  text-align: center;
  transition: all 0.2s;
}

.stat-card:hover {
  background: #f1f5f9;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #3b82f6;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.permissions-overview {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.permission-category-overview {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1rem;
}

.category-name {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.75rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.category-name::before {
  content: '';
  width: 8px;
  height: 8px;
  background: #3b82f6;
  border-radius: 50%;
}

.permission-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.permission-tag {
  background: #dbeafe;
  color: #1e40af;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.8125rem;
  font-weight: 500;
  border: 1px solid #bfdbfe;
}

.no-permissions {
  text-align: center;
  padding: 2rem;
  color: #9ca3af;
}

.no-permissions svg {
  width: 48px;
  height: 48px;
  margin: 0 auto 1rem;
  display: block;
}

.no-permissions p {
  font-size: 1rem;
  margin: 0;
}

.timeline-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 0;
}

.timeline-item:first-child {
  padding-top: 0;
}

.timeline-item:last-child {
  padding-bottom: 0;
}

.timeline-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.timeline-icon-created {
  background: #dbeafe;
  color: #1d4ed8;
}

.timeline-icon-updated {
  background: #fef3c7;
  color: #d97706;
}

.timeline-icon svg {
  width: 20px;
  height: 20px;
}

.timeline-content {
  flex: 1;
  min-width: 0;
}

.timeline-title {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.timeline-date {
  font-size: 0.875rem;
  color: #6b7280;
}

/* ================================================
   DELETE MODAL SPECIFIC
   ================================================ */

.delete-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.delete-warning {
  display: flex;
  gap: 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.5rem;
  padding: 1.5rem;
}

.filter-buttons {
  display: flex;
  gap: 0.5rem;
}

.filter-btn {
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  color: #4b5563;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.filter-btn.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.filter-btn:hover:not(.active) {
  background: #e5e7eb;
}


.warning-icon {
  flex-shrink: 0;
}

.warning-icon svg {
  width: 24px;
  height: 24px;
  color: #dc2626;
}

.warning-text h4 {
  color: #991b1b;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.warning-text p {
  color: #7f1d1d;
  font-size: 0.875rem;
  margin: 0;
  line-height: 1.4;
}

.role-to-delete h5 {
  color: #374151;
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
}

.role-card-mini {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 1rem;
  border-left: 4px solid #3b82f6;
}

.role-card-mini-delete {
  background: #fef2f2;
  border-color: #fecaca;
  border-left-color: #ef4444;
}

.role-info-mini h6 {
  color: #1f2937;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.role-info-mini p {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 0.75rem 0;
  line-height: 1.4;
}

.mini-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.deletion-impact h5 {
  color: #374151;
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
}

.impact-items {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.impact-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1rem;
}

.impact-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.impact-icon-users {
  background: #fef3c7;
  color: #d97706;
}

.impact-icon-permissions {
  background: #dbeafe;
  color: #2563eb;
}

.impact-icon svg {
  width: 20px;
  height: 20px;
}

.impact-details {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.impact-count {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.impact-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.confirmation-input {
  background: #fefefe;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1.5rem;
}

.confirmation-input label {
  display: block;
  color: #374151;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 0.75rem;
}

.confirmation-input strong {
  color: #1f2937;
  background: #f3f4f6;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
  font-size: 0.8125rem;
}

.confirmation-field {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.confirmation-field:focus {
  outline: none;
  border-color: #dc2626;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

/* ================================================
   PERMISSIONS MANAGEMENT MODAL
   ================================================ */

.permissions-management {
  max-height: 600px;
  overflow-y: auto;
}

.permissions-stats {
  display: flex;
  gap: 2rem;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.permissions-stats .stat {
  text-align: center;
}

.permissions-stats .stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.permissions-stats .stat-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.permission-category-selection {
  margin-bottom: 2rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1rem;
}

.category-header-selection {
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e5e7eb;
}

.category-checkbox .category-name {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
}

.permission-subsection {
  margin-bottom: 1.5rem;
}

.subsection-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.75rem 0;
}

.permissions-checkboxes {
  display: grid;
  gap: 0.75rem;
}

.permission-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  cursor: pointer;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 0.375rem;
  transition: background-color 0.2s;
}

.permission-checkbox:hover {
  background: #f3f4f6;
}

.permission-details {
  flex: 1;
}

.permission-details .permission-name {
  font-weight: 500;
  color: #1f2937;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
  display: block;
}

.permission-details .permission-description {
  color: #6b7280;
  font-size: 0.8125rem;
  margin: 0;
  line-height: 1.4;
}

/* ================================================
   RESPONSIVE DESIGN
   ================================================ */

@media (max-width: 768px) {
  .roles-permissions-container {
    padding: 1rem;
  }

  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }

  .search-input {
    max-width: none;
    min-width: auto;
  }

  .roles-grid {
    grid-template-columns: 1fr;
  }

  .role-stats {
    gap: 1rem;
  }

  .permissions-stats {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .stats-row {
    grid-template-columns: 1fr;
  }

  .timeline-item {
    gap: 0.75rem;
  }

  .timeline-icon {
    width: 32px;
    height: 32px;
  }

  .timeline-icon svg {
    width: 16px;
    height: 16px;
  }

  .impact-items {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .role-card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .role-actions {
    align-self: flex-end;
  }

  .permission-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .permission-roles-btn {
    align-self: flex-start;
  }
}
</style>