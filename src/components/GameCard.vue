<script setup>
import { computed } from 'vue'
import { useAppStore } from '../stores/appStore'

const props = defineProps({
  game: {
    type: Object,
    required: true,
  },
})

const store = useAppStore()

const requirementLabel = computed(() => {
  if (props.game.rec.ram >= 32 || props.game.rec.storage >= 70) return 'Demanding'
  if (props.game.rec.ram >= 16) return 'Mid range'
  return 'Low spec'
})
</script>

<template>
  <RouterLink class="text-decoration-none" :to="`/games/${game.slug}`">
  <article class="game-card h-100">
    <div class="cover-panel" :class="game.coverTheme">
      <span>{{ game.genre }}</span>
    </div>
    <div class="p-3 d-flex flex-column h-100">
      <div class="d-flex justify-content-between gap-3 align-items-start mb-2">
        <div>
          <h2 class="h5 mb-1">{{ game.title }}</h2>
          <p class="small text-secondary mb-0">{{ game.studio }} · {{ game.releaseYear }}</p>
        </div>
        <span class="badge rounded-pill text-bg-light">{{ requirementLabel }}</span>
      </div>
      <p class="text-secondary flex-grow-1">{{ game.summary }}</p>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span v-for="tag in game.tags" :key="tag" class="tag-chip">{{ tag }}</span>
      </div>
      <div class="row g-2 small mb-3">
        <div class="col-6">
          <span class="metric-label">RAM</span>
          <strong>{{ game.rec.ram }} GB</strong>
        </div>
        <div class="col-6">
          <span class="metric-label">Storage</span>
          <strong>{{ game.rec.storage }} GB</strong>
        </div>
      </div>
      <div class="d-flex gap-2">
        
        <button class="btn btn-outline-danger like-button" type="button" @click="store.toggleLike(game.id)">
          {{ game.likes }}
        </button>
      </div>
    </div>
  </article>
</RouterLink>
</template>
