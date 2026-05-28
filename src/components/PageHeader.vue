<script setup>
defineProps({
  eyebrow: {
    type: String,
    default: 'GameBench',
  },
  title: {
    type: String,
    required: true,
  },
  text: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <header class="page-header py-5">
    <div class="container">
      <p class="eyebrow mb-2">{{ eyebrow }}</p>
      <h1 class="display-title">{{ title }}</h1>
      <p v-if="text" class="lead text-secondary col-lg-8 mb-0">{{ text }}</p>
    </div>
  </header>
</template>
