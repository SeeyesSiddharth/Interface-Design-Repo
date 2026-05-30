<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const router = useRouter()
const store = useAppStore()
const error = ref('')
const isLoading = ref( false)

const form = reactive({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
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
  
  const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&^_-]{8,}$/
  if (!passwordRegex.test(form.password)) {
    error.value = 'Password must be at least 8 characters and contain at least one letter and one number.'
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
          <input 
            id="name" 
            v-model="form.name" 
            class="form-control" 
            autocomplete="name"
            required 
          />
        </div>
        
        <div class="mb-3">
          <label class="form-label" for="email">Email address</label>
          <input 
            id="email" 
            v-model="form.email" 
            class="form-control" 
            type="email" 
            autocomplete="email"
            required 
          />
        </div>
        
        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <input 
            id="password" 
            v-model="form.password" 
            class="form-control" 
            type="password" 
            autocomplete="new-password"
            minlength="8" 
            required 
          />
          <div class="form-text text-secondary small">Must be at least 8 characters and include a number and letter.</div>
        </div>

        <div class="mb-4">
          <label class="form-label" for="confirmPassword">Confirm Password</label>
          <input 
            id="confirmPassword" 
            v-model="form.confirmPassword" 
            class="form-control" 
            type="password" 
            autocomplete="new-password"
            minlength="8" 
            required 
          />
        </div>
        
        <button 
          class="btn btn-accent w-100" 
          type="submit"
          :disabled="isLoading"
        >
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