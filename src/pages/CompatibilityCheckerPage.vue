<script setup>
import { computed, reactive, ref, watch } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const BUILD_STORAGE_KEY = 'gamebench-pc-build-v1'
const store = useAppStore()
const selectedId = ref(store.state.games[0]?.id)
const showRequirements = ref(false)
const savedMessage = ref('')

function loadSavedBuild() {
  try {
    const saved = localStorage.getItem(BUILD_STORAGE_KEY)
    return saved
      ? JSON.parse(saved)
      : { name: 'My PC Build', ram: 16, storage: 100, gpuTier: 2, cpuTier: 2 }
  } catch {
    return { name: 'My PC Build', ram: 16, storage: 100, gpuTier: 2, cpuTier: 2 }
  }
}

const specs = reactive(loadSavedBuild())

const game = computed(() => store.state.games.find((entry) => entry.id === Number(selectedId.value)))

watch(
  () => store.state.games,
  (games) => {
    if (!selectedId.value && games.length) selectedId.value = games[0].id
  },
  { immediate: true }
)

function tierLabel(tier, type) {
  const labels = {
    cpu: ['Entry level CPU', 'Mid range CPU', 'High end CPU'],
    gpu: ['Entry level GPU', 'Mid range GPU', 'High end GPU'],
  }
  return labels[type][Math.max(0, Math.min(2, Number(tier) - 1))]
}

function estimateCpuNeed(entry) {
  const cpu = String(entry?.rec?.cpu || '').toLowerCase()
  if (cpu.includes('ryzen 7') || cpu.includes('ryzen 9') || cpu.includes('i7') || cpu.includes('i9')) return 3
  if (cpu.includes('ryzen 5') || cpu.includes('i5')) return 2
  return 1
}

function estimateGpuNeed(entry) {
  const gpu = String(entry?.rec?.gpu || '').toLowerCase()
  if (gpu.includes('4080') || gpu.includes('4070') || gpu.includes('7900') || gpu.includes('7800')) return 3
  if (gpu.includes('3070') || gpu.includes('3060') || gpu.includes('2060') || gpu.includes('6800') || gpu.includes('6700') || gpu.includes('6600')) return 2
  if (entry?.rec?.ram >= 32 || entry?.rec?.storage > 60) return 3
  if (entry?.rec?.ram >= 16) return 2
  return 1
}

function analyseGame(entry) {
  if (!entry) return { score: 0, verdict: 'Select a game', suggestions: [], cpuNeed: 1, gpuNeed: 1 }

  const cpuNeed = estimateCpuNeed(entry)
  const gpuNeed = estimateGpuNeed(entry)
  let points = 0
  const suggestions = []

  if (specs.ram >= entry.min.ram) points += 12
  else suggestions.push(`Upgrade RAM to at least ${entry.min.ram}GB to meet the minimum requirement.`)

  if (specs.ram >= entry.rec.ram) points += 18
  else suggestions.push(`Upgrade RAM to ${entry.rec.ram}GB for the recommended experience.`)

  if (specs.storage >= entry.min.storage) points += 10
  else suggestions.push(`Free at least ${entry.min.storage}GB of storage to install the game.`)

  if (specs.storage >= entry.rec.storage) points += 15
  else suggestions.push(`Keep ${entry.rec.storage}GB free so updates and loading performance are safer.`)

  if (specs.gpuTier >= gpuNeed) points += 30
  else suggestions.push(`Move from ${tierLabel(specs.gpuTier, 'gpu')} to ${tierLabel(gpuNeed, 'gpu')} for this game's graphics target.`)

  if (specs.cpuTier >= cpuNeed) points += 25
  else suggestions.push(`Move from ${tierLabel(specs.cpuTier, 'cpu')} to ${tierLabel(cpuNeed, 'cpu')} to reduce CPU bottlenecks.`)

  const score = Math.min(points, 100)
  let verdict = 'Below minimum - upgrade recommended'
  if (score >= 90) verdict = 'Excellent match - high/ultra settings likely'
  else if (score >= 78) verdict = 'Recommended experience - high settings likely'
  else if (score >= 62) verdict = 'Playable - medium settings recommended'
  else if (score >= 45) verdict = 'Limited - low settings likely'

  return { score, verdict, suggestions, cpuNeed, gpuNeed }
}

const analysis = computed(() => analyseGame(game.value))
const score = computed(() => analysis.value.score)
const verdict = computed(() => analysis.value.verdict)
const upgradeSuggestions = computed(() => analysis.value.suggestions.slice(0, 4))

const rankedGames = computed(() =>
  store.state.games
    .map((entry) => ({ ...entry, compatibility: analyseGame(entry) }))
    .sort((a, b) => b.compatibility.score - a.compatibility.score)
)

const bestMatches = computed(() => rankedGames.value.slice(0, 3))
const weakMatches = computed(() => rankedGames.value.slice(-3).reverse())

const scoreColor = computed(() => {
  if (score.value >= 78) return 'success'
  if (score.value >= 45) return 'warning'
  return 'danger'
})

function saveBuild() {
  localStorage.setItem(BUILD_STORAGE_KEY, JSON.stringify({ ...specs }))
  savedMessage.value = 'Build saved. Your recommendations will reload next time.'
}

function resetBuild() {
  specs.name = 'My PC Build'
  specs.ram = 16
  specs.storage = 100
  specs.gpuTier = 2
  specs.cpuTier = 2
  saveBuild()
}
</script>

<template>
  <PageHeader
    title="PC Compatibility Recommendation System"
    text="Save a PC build, score it against every game, rank the best matches, and receive upgrade suggestions based on weighted compatibility logic."
  />

  <section class="section-band">
    <div class="container">
      <div v-if="store.state.apiError" class="alert alert-info mb-4">
        {{ store.state.apiError }}
      </div>

      <div class="row g-4">
        <div class="col-lg-5">
          <form class="content-card p-4" @submit.prevent="saveBuild">
            <p class="eyebrow mb-2">Saved build profile</p>
            <label class="form-label fw-bold" for="buildName">Build name</label>
            <input id="buildName" v-model="specs.name" class="form-control mb-3" />

            <label class="form-label fw-bold" for="checkerGame">Focus game</label>
            <select id="checkerGame" v-model="selectedId" class="form-select mb-4">
              <option v-for="entry in store.state.games" :key="entry.id" :value="entry.id">
                {{ entry.title }}
              </option>
            </select>

            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold" for="ram">RAM</label>
                <span class="badge text-bg-primary">{{ specs.ram }} GB</span>
              </div>
              <input id="ram" v-model.number="specs.ram" class="form-range" type="range" min="4" max="64" step="4" />
            </div>

            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold" for="storage">Free storage</label>
                <span class="badge text-bg-primary">{{ specs.storage }} GB</span>
              </div>
              <input id="storage" v-model.number="specs.storage" class="form-range" type="range" min="10" max="250" step="5" />
            </div>

            <label class="form-label fw-bold" for="gpuTier">GPU tier</label>
            <select id="gpuTier" v-model.number="specs.gpuTier" class="form-select mb-4">
              <option :value="1">Entry level (GTX 1050 / RX 6500)</option>
              <option :value="2">Mid range (RTX 2060 / RX 6600)</option>
              <option :value="3">High end (RTX 4070+ / RX 7800+)</option>
            </select>

            <label class="form-label fw-bold" for="cpuTier">CPU tier</label>
            <select id="cpuTier" v-model.number="specs.cpuTier" class="form-select mb-4">
              <option :value="1">Entry level (Ryzen 3 / i3)</option>
              <option :value="2">Mid range (Ryzen 5 / i5)</option>
              <option :value="3">High end (Ryzen 7+ / i7+)</option>
            </select>

            <div class="d-flex flex-wrap gap-2">
              <button class="btn btn-accent" type="submit">Save build</button>
              <button class="btn btn-outline-dark" type="button" @click="resetBuild">Reset</button>
              <button class="btn btn-outline-secondary" type="button" @click="showRequirements = !showRequirements">
                {{ showRequirements ? 'Hide specs' : 'Show specs' }}
              </button>
            </div>
            <p v-if="savedMessage" class="text-success small mt-3 mb-0">{{ savedMessage }}</p>
          </form>
        </div>

        <div class="col-lg-7">
          <div class="content-card p-4 h-100">
            <p class="eyebrow mb-2">Focused game result</p>
            <h2 class="mb-3">{{ game?.title || 'No game selected' }}</h2>
            <p class="h4 mb-3">{{ verdict }}</p>

            <div class="requirement-meter my-4" aria-label="Compatibility score">
              <span :style="{ width: `${score}%` }" :class="`bg-${scoreColor}`"></span>
            </div>

            <div class="d-flex align-items-baseline gap-2 mb-4">
              <span class="fs-3 fw-bold">{{ score }}%</span>
              <span class="text-secondary">weighted compatibility score</span>
            </div>

            <div v-if="upgradeSuggestions.length" class="alert alert-warning">
              <strong>Upgrade suggestions</strong>
              <ul class="mb-0 mt-2">
                <li v-for="suggestion in upgradeSuggestions" :key="suggestion">{{ suggestion }}</li>
              </ul>
            </div>
            <div v-else class="alert alert-success">
              Your saved build meets or exceeds the recommended target for this game.
            </div>

            <div v-if="showRequirements" class="table-responsive mt-4">
              <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                  <tr><th>Spec</th><th>Your build</th><th>Recommended</th></tr>
                </thead>
                <tbody>
                  <tr><td>CPU</td><td>{{ tierLabel(specs.cpuTier, 'cpu') }}</td><td>{{ game?.rec.cpu }}</td></tr>
                  <tr><td>GPU</td><td>{{ tierLabel(specs.gpuTier, 'gpu') }}</td><td>{{ game?.rec.gpu }}</td></tr>
                  <tr><td>RAM</td><td>{{ specs.ram }}GB</td><td>{{ game?.rec.ram }}GB</td></tr>
                  <tr><td>Storage</td><td>{{ specs.storage }}GB</td><td>{{ game?.rec.storage }}GB</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4 mt-1">
        <div class="col-lg-6">
          <div class="content-card p-4 h-100">
            <p class="eyebrow mb-2">Best recommendations</p>
            <h3 class="h4 mb-3">Games most suited to {{ specs.name }}</h3>
            <div v-for="entry in bestMatches" :key="entry.id" class="recommendation-row">
              <div>
                <strong>{{ entry.title }}</strong>
                <p class="text-secondary small mb-0">{{ entry.compatibility.verdict }}</p>
              </div>
              <span class="badge text-bg-success">{{ entry.compatibility.score }}%</span>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="content-card p-4 h-100">
            <p class="eyebrow mb-2">Needs upgrades</p>
            <h3 class="h4 mb-3">Games that may struggle</h3>
            <div v-for="entry in weakMatches" :key="entry.id" class="recommendation-row">
              <div>
                <strong>{{ entry.title }}</strong>
                <p class="text-secondary small mb-0">{{ entry.compatibility.suggestions[0] || 'Minor tuning may be needed.' }}</p>
              </div>
              <span class="badge text-bg-danger">{{ entry.compatibility.score }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.requirement-meter {
  height: 24px;
  background-color: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
}

.requirement-meter span {
  display: block;
  height: 100%;
  transition: width 0.3s ease;
}

.requirement-meter span.bg-success { background-color: #198754; }
.requirement-meter span.bg-warning { background-color: #ffc107; }
.requirement-meter span.bg-danger { background-color: #dc3545; }

.recommendation-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
  padding: 0.85rem 0;
  border-top: 1px solid #e8e3da;
}

.recommendation-row:first-of-type {
  border-top: 0;
}
</style>
