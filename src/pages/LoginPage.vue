<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()
const router = useRouter()
const error = ref('')
const form = reactive({
  email: 'demo@gamebench.test',
  password: 'password123',
})

async function submit() {
  error.value = ''
  if (!form.email.includes('@') || form.password.length < 8) {
    error.value = 'Enter a valid email and a password with at least 8 characters.'
    return
  }
  const success = await store.login(form.email, form.password)
  if (!success) {
    error.value = 'No matching account was found.'
    return
  }
  router.push('/profile')
}
</script>

<template>
  <PageHeader title="Login" text="Demo account: demo@gamebench.test with password password123." />

  <section class="section-band">
    <div class="container">
      <form class="content-card p-4 col-lg-6 mx-auto" @submit.prevent="submit">
        <div v-if="error" class="alert alert-warning">{{ error }}</div>
        <label class="form-label" for="email">Email</label>
        <input id="email" v-model="form.email" class="form-control mb-3" type="email" required />
        <label class="form-label" for="password">Password</label>
        <input id="password" v-model="form.password" class="form-control mb-3" type="password" minlength="8" required />
        <button class="btn btn-accent w-100" type="submit">Login</button>
      </form>
    </div>
  </section>
</template>
