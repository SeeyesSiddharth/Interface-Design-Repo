<script setup>
import { computed, onMounted, ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import GameCard from '../components/GameCard.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()

const firstId = ref('')
const secondId = ref('')
const requirementMode = ref('rec')

onMounted(async () => {
  if (!store.state.games.length) {
    await store.loadGames()
  }

  if (store.state.games.length >= 2) {
    firstId.value = String(store.state.games[0].id)
    secondId.value = String(store.state.games[1].id)
  }
})

const games = computed(() => store.state.games)

const first = computed(() => {
  return games.value.find(game => String(game.id) === String(firstId.value)) || null
})

const second = computed(() => {
  return games.value.find(game => String(game.id) === String(secondId.value)) || null
})

const requirementLabel = computed(() => {
  return requirementMode.value === 'min' ? 'Minimum' : 'Recommended'
})

const firstSpecs = computed(() => {
  if (!first.value) return null
  return requirementMode.value === 'min' ? first.value.min : first.value.rec
})

const secondSpecs = computed(() => {
  if (!second.value) return null
  return requirementMode.value === 'min' ? second.value.min : second.value.rec
})
</script>

<template>
  <section>
    <PageHeader
      eyebrow="Compare"
      title="Compare Game Requirements"
      text="Choose two games from the catalogue and compare their hardware requirements side by side."
    />

    <div class="container section-band">
      <div v-if="store.state.apiError" class="alert alert-warning mb-4">
        {{ store.state.apiError }}
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold d-block">Requirement level</label>
        <div class="btn-group" role="group" aria-label="Requirement mode toggle">
          <button
            type="button"
            class="btn"
            :class="requirementMode === 'rec' ? 'btn-accent' : 'btn-outline-secondary'"
            @click="requirementMode = 'rec'"
          >
            Recommended
          </button>
          <button
            type="button"
            class="btn"
            :class="requirementMode === 'min' ? 'btn-accent' : 'btn-outline-secondary'"
            @click="requirementMode = 'min'"
          >
            Minimum
          </button>
        </div>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-md-6">
          <label for="first-game" class="form-label fw-semibold">First game</label>
          <select id="first-game" v-model="firstId" class="form-select">
            <option disabled value="">Select a game</option>
            <option
              v-for="game in games"
              :key="game.id"
              :value="String(game.id)"
            >
              {{ game.title }}
            </option>
          </select>
        </div>

        <div class="col-md-6">
          <label for="second-game" class="form-label fw-semibold">Second game</label>
          <select id="second-game" v-model="secondId" class="form-select">
            <option disabled value="">Select a game</option>
            <option
              v-for="game in games"
              :key="game.id"
              :value="String(game.id)"
            >
              {{ game.title }}
            </option>
          </select>
        </div>
      </div>

      <div v-if="first && second && firstSpecs && secondSpecs" class="content-card p-3 p-md-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <h3 class="h5 mb-0">{{ requirementLabel }} Requirements Comparison</h3>
          <span class="tag-chip">{{ requirementLabel }} mode</span>
        </div>

        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">Requirement</th>
                <th scope="col">{{ first.title }}</th>
                <th scope="col">{{ second.title }}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th scope="row">CPU</th>
                <td>{{ firstSpecs.cpu }}</td>
                <td>{{ secondSpecs.cpu }}</td>
              </tr>
              <tr>
                <th scope="row">GPU</th>
                <td>{{ firstSpecs.gpu }}</td>
                <td>{{ secondSpecs.gpu }}</td>
              </tr>
              <tr>
                <th scope="row">RAM</th>
                <td>{{ firstSpecs.ram }} GB</td>
                <td>{{ secondSpecs.ram }} GB</td>
              </tr>
              <tr>
                <th scope="row">Storage</th>
                <td>{{ firstSpecs.storage }} GB</td>
                <td>{{ secondSpecs.storage }} GB</td>
              </tr>
              <tr>
                <th scope="row">Operating system</th>
                <td>{{ firstSpecs.os }}</td>
                <td>{{ secondSpecs.os }}</td>
              </tr>
              <tr>
                <th scope="row">Platforms</th>
                <td>{{ first.platform.join(', ') }}</td>
                <td>{{ second.platform.join(', ') }}</td>
              </tr>
              <tr>
                <th scope="row">Notes</th>
                <td>{{ first.notes }}</td>
                <td>{{ second.notes }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="content-card p-4 text-center mb-4">
        <p class="mb-0 text-muted">
          Select two games to compare their hardware requirements.
        </p>
      </div>

      <div v-if="first || second" class="row g-4">
        <div class="col-md-6">
          <GameCard
            v-if="first"
            :game="first"
            :requirement-label="requirementLabel"
          />
        </div>

        <div class="col-md-6">
          <GameCard
            v-if="second"
            :game="second"
            :requirement-label="requirementLabel"
          />
        </div>
      </div>
    </div>
  </section>
</template>