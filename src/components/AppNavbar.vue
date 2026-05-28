<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAppStore } from '../stores/appStore'

const router = useRouter()
const store = useAppStore()

const userLabel = computed(() => store.state.currentUser?.name || 'Guest')

function logout() {
  store.logout()
  router.push('/')
}
</script>

<template>
  <nav class="navbar navbar-expand-lg sticky-top app-nav">
    <div class="container">
      <RouterLink class="navbar-brand fw-bold" to="/">GameBench</RouterLink>
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#mainNavigation"
        aria-controls="mainNavigation"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
      <div id="mainNavigation" class="collapse navbar-collapse">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><RouterLink class="nav-link" to="/catalogue">Catalogue</RouterLink></li>
          <li class="nav-item"><RouterLink class="nav-link" to="/compare">Compare</RouterLink></li>
          <li class="nav-item"><RouterLink class="nav-link" to="/requirements-guide">Guide</RouterLink></li>
          <li class="nav-item"><RouterLink class="nav-link" to="/compatibility-checker">Checker</RouterLink></li>
          <li class="nav-item"><RouterLink class="nav-link" to="/reviews">Reviews</RouterLink></li>
          <li class="nav-item"><RouterLink class="nav-link" to="/team-plan">Team Plan</RouterLink></li>
        </ul>
        <div class="d-flex align-items-center gap-2">
          <span class="small text-secondary d-none d-md-inline">Signed in as {{ userLabel }}</span>
          <RouterLink v-if="!store.isAuthenticated.value" class="btn btn-outline-dark btn-sm" to="/login">Login</RouterLink>
          <RouterLink v-if="!store.isAuthenticated.value" class="btn btn-accent btn-sm" to="/register">Register</RouterLink>
          <RouterLink v-if="store.isAuthenticated.value" class="btn btn-outline-dark btn-sm" to="/profile">Profile</RouterLink>
          <button v-if="store.isAuthenticated.value" class="btn btn-accent btn-sm" type="button" @click="logout">
            Logout
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>
