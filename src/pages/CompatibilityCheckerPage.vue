<script setup>
import { computed, reactive, ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()
const selectedId = ref(store.state.games[0]?.id)
const specs = reactive({
  ram: 16,
  storage: 100,
  gpuTier: 2,
})

const game = computed(() => store.state.games.find((entry) => entry.id === Number(selectedId.value)))
const score = computed(() => {
  if (!game.value) return 0
  let points = 0
  if (specs.ram >= game.value.min.ram) points += 30
  if (specs.ram >= game.value.rec.ram) points += 15
  if (specs.storage >= game.value.rec.storage) points += 25
  if (specs.gpuTier >= gpuNeed.value) points += 30
  return Math.min(points, 100)
})
const gpuNeed = computed(() => {
  if (!game.value) return 1
  if (game.value.rec.ram >= 32 || game.value.rec.storage > 55) return 3
  if (game.value.rec.ram >= 16) return 2
  return 1
})
const verdict = computed(() => {
  if (score.value >= 80) return 'Recommended experience'
  if (score.value >= 55) return 'Playable with adjusted settings'
  return 'Below minimum or needs careful tuning'
})
</script>

<template>
  <PageHeader
    title="Compatibility Checker"
    text="A reactive form that compares a user's basic PC specs against the selected game's requirements."
  />

  <section class="section-band">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-5">
          <form class="content-card p-3">
            <label class="form-label" for="checkerGame">Game</label>
            <select id="checkerGame" v-model="selectedId" class="form-select mb-3">
              <option v-for="entry in store.state.games" :key="entry.id" :value="entry.id">{{ entry.title }}</option>
            </select>

            <label class="form-label" for="ram">Your RAM: {{ specs.ram }} GB</label>
            <input id="ram" v-model.number="specs.ram" class="form-range" type="range" min="4" max="64" step="4" />

            <label class="form-label" for="storage">Free storage: {{ specs.storage }} GB</label>
            <input id="storage" v-model.number="specs.storage" class="form-range" type="range" min="10" max="200" step="5" />

            <label class="form-label" for="gpuTier">GPU tier</label>
            <select id="gpuTier" v-model.number="specs.gpuTier" class="form-select">
              <option :value="1">Entry level</option>
              <option :value="2">Mid range</option>
              <option :value="3">High end</option>
            </select>
          </form>
        </div>
        <div class="col-lg-7">
          <div class="content-card p-4 h-100">
            <p class="eyebrow mb-2">Result</p>
            <h2>{{ verdict }}</h2>
            <div class="requirement-meter my-3" aria-label="Compatibility score">
              <span :style="{ width: `${score}%` }"></span>
            </div>
            <p class="fs-4 fw-bold">{{ score }}%</p>
            <p class="text-secondary mb-4">
              Recommended: {{ game?.rec.cpu }}, {{ game?.rec.gpu }}, {{ game?.rec.ram }} GB RAM,
              {{ game?.rec.storage }} GB storage.
            </p>
            <RouterLink class="btn btn-dark" :to="`/games/${game?.slug}`">Open full requirements</RouterLink>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
