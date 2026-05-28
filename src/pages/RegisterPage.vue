<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const router = useRouter()
const store = useAppStore()
const error = ref('')
const form = reactive({
  name: '',
  email: '',
  password: '',
})

function submit() {
  error.value = ''
  if (form.name.trim().length < 2) {
    error.value = 'Name must be at least 2 characters.'
    return
  }
  if (!form.email.includes('@')) {
    error.value = 'Enter a valid email address.'
    return
  }
  if (form.password.length < 8) {
    error.value = 'Password must be at least 8 characters.'
    return
  }

  const result = store.register({ ...form })
  if (!result.ok) {
    error.value = result.message
    return
  }
  router.push('/profile')
}
</script>

<template>
  <PageHeader title="Register" text="Creates a local demo account and unlocks member-only pages and actions." />

  <section class="section-band">
    <div class="container">
      <form class="content-card p-4 col-lg-6 mx-auto" @submit.prevent="submit">
        <div v-if="error" class="alert alert-warning">{{ error }}</div>
        <label class="form-label" for="name">Name</label>
        <input id="name" v-model="form.name" class="form-control mb-3" required />
        <label class="form-label" for="email">Email</label>
        <input id="email" v-model="form.email" class="form-control mb-3" type="email" required />
        <label class="form-label" for="password">Password</label>
        <input id="password" v-model="form.password" class="form-control mb-3" type="password" minlength="8" required />
        <button class="btn btn-accent w-100" type="submit">Create Account</button>
      </form>
    </div>
  </section>
</template>
