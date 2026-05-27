<script setup>
import AppNavbar from './components/AppNavbar.vue'
</script>

<template>
  <AppNavbar />
  <main>
    <RouterView />
  </main>
  <footer class="footer-band py-4 mt-5">
    <div class="container d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
      <span>GameBench Catalogue starter for COS30043 Interface Design and Development.</span>
      <RouterLink class="footer-link" to="/team-plan">Open team handover plan</RouterLink>
    </div>
  </footer>
</template>
