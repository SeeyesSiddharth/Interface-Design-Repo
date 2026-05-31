<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const router = useRouter()
const store = useAppStore()
const error = ref('')
const isLoading = ref(false)
const showPassword = ref(false)

const form = reactive({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
})

// Dynamic Password Strength Logic
const passwordStrength = computed(() => {
  let score = 0
  const pw = form.password
  
  if (!pw) return { score: 0, label: 'None', color: 'bg-light' }
  if (pw.length >= 8) score += 25
  if (/[A-Z]/.test(pw)) score += 25
  if (/[0-9]/.test(pw)) score += 25
  if (/[^A-Za-z0-9]/.test(pw)) score += 25

  if (score === 0) return { score, label: 'Too short', color: 'bg-danger' }
  if (score <= 50) return { score, label: 'Weak', color: 'bg-warning' }
  if (score <= 75) return { score, label: 'Good', color: 'bg-info' }
  return { score, label: 'Strong', color: 'bg-success' }
})

async function submit() {
  error.value = ''
  
  const trimmedName = form.name.trim()
  if (trimmedName.length < 2) {
    error.value = 'Name must be at least 2 characters.'
    return
  }
  
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(form.email)) {
    error.value = 'Please enter a valid email address.'
    return
  }
  
  if (passwordStrength.value.score < 75) {
    error.value = 'Please choose a stronger password.'
    return
  }

  if (form.password !== form.confirmPassword) {
    error.value = 'Passwords do not match.'
    return
  }

  isLoading.value = true

  try {
    const success = await store.register({ 
      name: trimmedName, 
      email: form.email, 
      password: form.password 
    })
    
    if (!success.ok) {
      error.value = success.message
      return
    }
    
    form.password = ''
    form.confirmPassword = ''
    router.push('/profile')
    
  } catch (err) {
    error.value = 'Registration failed due to a network error.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <PageHeader title="Register" text="Create an account to unlock member-only pages and actions." />

  <section class="section-band">
    <div class="container">
      <form class="content-card p-4 col-lg-6 mx-auto" @submit.prevent="submit">
        <div v-if="error" class="alert alert-warning" role="alert">{{ error }}</div>
        
        <div class="mb-3">
          <label class="form-label" for="name">Full Name</label>
          <input id="name" v-model="form.name" class="form-control" autocomplete="name" required />
        </div>
        
        <div class="mb-3">
          <label class="form-label" for="email">Email address</label>
          <input id="email" v-model="form.email" class="form-control" type="email" autocomplete="email" required />
        </div>
        
        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <div class="input-group mb-2">
            <input 
              id="password" 
              v-model="form.password" 
              class="form-control" 
              :type="showPassword ? 'text' : 'password'" 
              autocomplete="new-password"
              required 
            />
            <button 
              class="btn btn-outline-secondary" 
              type="button" 
              @click="showPassword = !showPassword"
            >
              {{ showPassword ? 'Hide' : 'Show' }}
            </button>
          </div>
          
          <!-- Password Strength Meter -->
          <div class="progress" style="height: 6px;">
            <div 
              class="progress-bar" 
              :class="passwordStrength.color" 
              role="progressbar" 
              :style="{ width: passwordStrength.score + '%' }" 
              aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
            </div>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <span class="form-text text-secondary small">8+ chars, 1 uppercase, 1 number</span>
            <span class="small fw-bold" :class="passwordStrength.color.replace('bg-', 'text-')">
              {{ passwordStrength.label }}
            </span>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label" for="confirmPassword">Confirm Password</label>
          <input 
            id="confirmPassword" 
            v-model="form.confirmPassword" 
            class="form-control" 
            type="password" 
            autocomplete="new-password"
            required 
          />
        </div>
        
        <button class="btn btn-accent w-100" type="submit" :disabled="isLoading">
          <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
          {{ isLoading ? 'Creating account...' : 'Create Account' }}
        </button>

        <div class="text-center mt-3">
          <p class="small text-secondary mb-0">
            Already have an account? <RouterLink to="/login">Login here</RouterLink>.
          </p>
        </div>
      </form>
    </div>
  </section>
</template>