<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const router = useRouter()
const store = useAppStore()
const error = ref('')
const isLoading = ref(false)

const form = reactive({
  email: '',
  password: '',
})

async function submit() {
  error.value = ''
  
  if (!form.email || !form.password) {
    error.value = 'Please enter both email and password.'
    return
  }

  isLoading.value = true

  try {
    const success = await store.login({ ...form })
    
    if (!success.ok) {
      error.value = success.message || 'Invalid credentials. Please try again.'
      return
    }
    
    form.password = ''
    router.push('/profile')
    
  } catch (err) {
    error.value = 'A network error occurred. Please try again.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <PageHeader title="Login" text="Access your account to submit games, write reviews, and view your profile." />

  <section class="section-band">
    <div class="container">
      <form class="content-card p-4 col-lg-6 mx-auto" @submit.prevent="submit">
        <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
        
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

        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <input 
            id="password" 
            v-model="form.password" 
            class="form-control" 
            type="password" 
            autocomplete="current-password"
            required 
          />
        </div>

        <button 
          class="btn btn-accent w-100" 
          type="submit" 
          :disabled="isLoading"
        >
          {{ isLoading ? 'Logging in...' : 'Login' }}
        </button>
        
        <div class="text-center mt-3">
          <p class="small text-secondary mb-0">
            Dont have an account? <RouterLink to="/register">Register here</RouterLink>.
          </p>
        </div>
      </form>
    </div>
  </section>
</template> 