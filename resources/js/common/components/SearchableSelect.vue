<template>
  <div class="searchable-select" ref="selectContainer">
    <div class="select-input-wrapper">
      <input
        ref="searchInput"
        type="text"
        :value="displayValue"
        @input="handleInput"
        @focus="openDropdown"
        @keydown="handleKeydown"
        :placeholder="placeholder"
        class="form-control"
        :class="{ 'has-clear': modelValue }"
        autocomplete="off"
      />
      
      <!-- Clear button - only show when there's a value -->
      <button
        v-if="modelValue"
        @click.stop="clearSelection"
        type="button"
        class="select-clear-btn"
        title="Clear selection"
      >
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      
      <svg 
        class="select-arrow"
        :class="{ 'rotated': isOpen }"
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </div>
    
    <div v-if="isOpen" class="select-dropdown">
      <div 
        v-for="(option, index) in filteredOptions" 
        :key="option.value"
        @click="selectOption(option)"
        class="select-option"
        :class="{ 'highlighted': index === highlightedIndex }"
      >
        {{ option.label }}
      </div>
      <div v-if="filteredOptions.length === 0" class="select-no-results">
        No results found
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  options: {
    type: Array,
    required: true
  },
  placeholder: {
    type: String,
    default: 'Select an option...'
  },
  searchable: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue'])

const selectContainer = ref(null)
const searchInput = ref(null)
const isOpen = ref(false)
const searchQuery = ref('')
const highlightedIndex = ref(-1)

// Computed properties
const displayValue = computed(() => {
  if (isOpen.value) return searchQuery.value
  const selectedOption = props.options.find(opt => opt.value === props.modelValue)
  return selectedOption ? selectedOption.label : ''
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options
  return props.options.filter(option => 
    option.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

// Methods
const openDropdown = () => {
  isOpen.value = true
  searchQuery.value = ''
  highlightedIndex.value = -1
}

const closeDropdown = () => {
  isOpen.value = false
  searchQuery.value = ''
  highlightedIndex.value = -1
}

const handleInput = (event) => {
  searchQuery.value = event.target.value
  highlightedIndex.value = 0
}

const selectOption = (option) => {
  emit('update:modelValue', option.value)
  closeDropdown()
  searchInput.value.blur()
}

// NEW: Clear selection method
const clearSelection = () => {
  emit('update:modelValue', null)
  closeDropdown()
  searchInput.value.blur()
}

const handleKeydown = (event) => {
  if (!isOpen.value) {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault()
      openDropdown()
    }
    return
  }

  switch (event.key) {
    case 'Escape':
      closeDropdown()
      break
    case 'ArrowDown':
      event.preventDefault()
      highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0)
      break
    case 'Enter':
      event.preventDefault()
      if (highlightedIndex.value >= 0 && filteredOptions.value[highlightedIndex.value]) {
        selectOption(filteredOptions.value[highlightedIndex.value])
      }
      break
  }
}

const handleClickOutside = (event) => {
  if (selectContainer.value && !selectContainer.value.contains(event.target)) {
    closeDropdown()
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.searchable-select {
  position: relative;
  width: 100%;
}

.select-input-wrapper {
  position: relative;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  padding-right: 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: white;
  cursor: pointer;
}

/* Add extra padding when clear button is visible */
.form-control.has-clear {
  padding-right: 4.5rem;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  cursor: text;
}

/* NEW: Clear button styles */
.select-clear-btn {
  position: absolute;
  right: 2.25rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  border-radius: 0.25rem;
  transition: all 0.2s;
  z-index: 1;
}

.select-clear-btn:hover {
  color: #dc2626;
  background: #fee2e2;
}

.select-clear-btn svg {
  width: 0.875rem;
  height: 0.875rem;
}

.select-arrow {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 1rem;
  height: 1rem;
  color: #6b7280;
  pointer-events: none;
  transition: transform 0.2s;
}

.select-arrow.rotated {
  transform: translateY(-50%) rotate(180deg);
}

.select-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  margin-top: 0.25rem;
  max-height: 200px;
  overflow-y: auto;
}

.select-option {
  padding: 0.75rem;
  cursor: pointer;
  transition: background-color 0.15s;
  border-bottom: 1px solid #f3f4f6;
}

.select-option:last-child {
  border-bottom: none;
}

.select-option:hover,
.select-option.highlighted {
  background: #f9fafb;
}

.select-no-results {
  padding: 0.75rem;
  color: #6b7280;
  font-style: italic;
  text-align: center;
}
</style>