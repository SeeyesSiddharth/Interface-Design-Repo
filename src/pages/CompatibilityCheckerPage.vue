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
  cpuTier: 2,
})

// Computed properties for game selection
const game = computed(() => store.state.games.find((entry) => entry.id === Number(selectedId.value)))

// GPU requirement calculation based on game specs
const gpuNeed = computed(() => {
  if (!game.value) return 1
  if (game.value.rec.ram >= 32 || game.value.rec.storage > 55) return 3
  if (game.value.rec.ram >= 16) return 2
  return 1
})

// CPU requirement calculation
const cpuNeed = computed(() => {
  if (!game.value) return 1
  const cpuStr = game.value.rec.cpu.toLowerCase()
  if (cpuStr.includes('ryzen 7') || cpuStr.includes('i7') || cpuStr.includes('i9')) return 3
  if (cpuStr.includes('ryzen 5') || cpuStr.includes('i5')) return 2
  return 1
})

// Detailed scoring system
const score = computed(() => {
  if (!game.value) return 0
  let points = 0

  // RAM scoring (30 points max)
  if (specs.ram >= game.value.min.ram) points += 15
  if (specs.ram >= game.value.rec.ram) points += 15

  // Storage scoring (25 points max)
  if (specs.storage >= game.value.min.storage) points += 10
  if (specs.storage >= game.value.rec.storage) points += 15

  // GPU scoring (30 points max)
  if (specs.gpuTier >= cpuNeed.value) points += 30

  // CPU scoring (15 points max)
  if (specs.cpuTier >= cpuNeed.value) points += 15

  return Math.min(points, 100)
})

// Detailed verdict based on score
const verdict = computed(() => {
  if (score.value >= 90) return 'Excellent - Ultra settings at 60+ FPS'
  if (score.value >= 80) return 'Recommended experience - High settings'
  if (score.value >= 70) return 'Good - Medium to high settings'
  if (score.value >= 55) return 'Playable with adjusted settings'
  if (score.value >= 40) return 'Minimal requirements met - Low settings only'
  return 'Below minimum - Performance issues likely'
})

// Performance warning system
const performanceWarnings = computed(() => {
  const warnings = []
  if (!game.value) return warnings

  if (specs.ram < game.value.min.ram) {
    warnings.push(`RAM below minimum: You have ${specs.ram}GB, minimum is ${game.value.min.ram}GB`)
  }
  if (specs.storage < game.value.min.storage) {
    warnings.push(`Storage below minimum: You have ${specs.storage}GB, minimum is ${game.value.min.storage}GB`)
  }
  if (specs.gpuTier < 1) {
    warnings.push('GPU tier below minimum requirement')
  }
  if (specs.cpuTier < cpuNeed.value) {
    warnings.push('CPU may struggle with this game at recommended settings')
  }

  return warnings
})

// Score color based on compatibility
const scoreColor = computed(() => {
  if (score.value >= 80) return 'success'
  if (score.value >= 55) return 'warning'
  return 'danger'
})

// Show requirements detail function
const showRequirements = ref(false)
</script>

<template>
  <PageHeader
    title="PC Compatibility Checker"
    text="A reactive form that compares your PC specs against the selected game's requirements. Get detailed compatibility analysis and performance predictions."
  />

  <section class="section-band">
    <div class="container">
      <div class="row g-4">
        <!-- Input Controls -->
        <div class="col-lg-5">
          <form class="content-card p-4">
            <!-- Game Selection -->
            <div class="mb-4">
              <label class="form-label fw-bold" for="checkerGame">Select a Game</label>
              <select id="checkerGame" v-model="selectedId" class="form-select">
                <option v-for="entry in store.state.games" :key="entry.id" :value="entry.id">
                  {{ entry.title }}
                </option>
              </select>
            </div>

            <!-- RAM Input -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold" for="ram">Your RAM</label>
                <span class="badge text-bg-primary">{{ specs.ram }} GB</span>
              </div>
              <input
                id="ram"
                v-model.number="specs.ram"
                class="form-range"
                type="range"
                min="4"
                max="64"
                step="4"
              />
              <small class="text-secondary d-block mt-1">
                Min: {{ game?.min.ram }}GB | Recommended: {{ game?.rec.ram }}GB
              </small>
            </div>

            <!-- Storage Input -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold" for="storage">Free Storage</label>
                <span class="badge text-bg-primary">{{ specs.storage }} GB</span>
              </div>
              <input
                id="storage"
                v-model.number="specs.storage"
                class="form-range"
                type="range"
                min="10"
                max="200"
                step="5"
              />
              <small class="text-secondary d-block mt-1">
                Min: {{ game?.min.storage }}GB | Recommended: {{ game?.rec.storage }}GB
              </small>
            </div>

            <!-- GPU Tier -->
            <div class="mb-4">
              <label class="form-label fw-bold" for="gpuTier">GPU Tier</label>
              <select id="gpuTier" v-model.number="specs.gpuTier" class="form-select">
                <option :value="1">Entry Level (GTX 1050/RX 6500)</option>
                <option :value="2">Mid Range (RTX 2060/RX 5700)</option>
                <option :value="3">High End (RTX 3080/RX 6800)</option>
              </select>
            </div>

            <!-- CPU Tier -->
            <div class="mb-4">
              <label class="form-label fw-bold" for="cpuTier">CPU Tier</label>
              <select id="cpuTier" v-model.number="specs.cpuTier" class="form-select">
                <option :value="1">Entry Level (Ryzen 3/i3)</option>
                <option :value="2">Mid Range (Ryzen 5/i5)</option>
                <option :value="3">High End (Ryzen 7/i7)</option>
              </select>
            </div>

            <button
              type="button"
              class="btn btn-outline-secondary btn-sm w-100"
              @click="showRequirements = !showRequirements"
            >
              {{ showRequirements ? 'Hide Requirements' : 'Show Full Requirements' }}
            </button>
          </form>
        </div>

        <!-- Results Display -->
        <div class="col-lg-7">
          <div class="content-card p-4 h-100">
            <!-- Verdict Header -->
            <p class="eyebrow mb-2 text-uppercase">Compatibility Verdict</p>
            <h2 class="mb-3">{{ verdict }}</h2>

            <!-- Compatibility Score Meter -->
            <div class="requirement-meter my-4" aria-label="Compatibility score">
              <span :style="{ width: `${score}%` }" :class="`bg-${scoreColor}`"></span>
            </div>

            <!-- Score Display -->
            <div class="d-flex align-items-baseline gap-2 mb-4">
              <span class="fs-3 fw-bold">{{ score }}%</span>
              <span class="text-secondary">Compatibility Score</span>
            </div>

            <!-- Warnings Section -->
            <div v-if="performanceWarnings.length > 0" class="alert alert-warning mb-4" role="alert">
              <strong>⚠️ Performance Warnings:</strong>
              <ul class="mb-0 mt-2">
                <li v-for="(warning, idx) in performanceWarnings" :key="idx" class="small">
                  {{ warning }}
                </li>
              </ul>
            </div>

            <!-- Spec Summary -->
            <div class="bg-light p-3 rounded mb-4">
              <h5 class="mb-3">Recommended Specs</h5>
              <ul class="list-unstyled small">
                <li class="mb-2">
                  <strong>CPU:</strong>
                  {{ game?.rec.cpu }}
                </li>
                <li class="mb-2">
                  <strong>GPU:</strong>
                  {{ game?.rec.gpu }}
                </li>
                <li class="mb-2">
                  <strong>RAM:</strong>
                  {{ game?.rec.ram }} GB
                </li>
                <li>
                  <strong>Storage:</strong>
                  {{ game?.rec.storage }} GB
                </li>
              </ul>
            </div>

            <!-- Full Requirements Toggle -->
            <div v-if="showRequirements" class="mb-4">
              <h5 class="mb-3">Full Requirements Comparison</h5>
              <div class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead class="table-light">
                    <tr>
                      <th>Spec</th>
                      <th>Minimum</th>
                      <th>Recommended</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>CPU</td>
                      <td>{{ game?.min.cpu }}</td>
                      <td>{{ game?.rec.cpu }}</td>
                    </tr>
                    <tr>
                      <td>GPU</td>
                      <td>{{ game?.min.gpu }}</td>
                      <td>{{ game?.rec.gpu }}</td>
                    </tr>
                    <tr>
                      <td>RAM</td>
                      <td>{{ game?.min.ram }}GB</td>
                      <td>{{ game?.rec.ram }}GB</td>
                    </tr>
                    <tr>
                      <td>Storage</td>
                      <td>{{ game?.min.storage }}GB</td>
                      <td>{{ game?.rec.storage }}GB</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Action Button -->
            <RouterLink
              :to="`/games/${game?.slug}`"
              class="btn btn-dark"
              :disabled="!game"
            >
              View Full Game Details
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Tips Section -->
  <section class="section-band bg-light">
    <div class="container">
      <h3 class="mb-4">💡 Compatibility Checker Tips</h3>
      <div class="row g-3">
        <div class="col-md-4">
          <div class="content-card p-3">
            <h5>Understand GPU Requirements</h5>
            <p class="small text-secondary mb-0">
              GPU tier determines your gaming experience quality. Higher tiers enable better graphics settings and frame rates.
            </p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="content-card p-3">
            <h5>RAM Management</h5>
            <p class="small text-secondary mb-0">
              Insufficient RAM causes stuttering and crashes. Recommended RAM is for smooth gameplay at high settings.
            </p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="content-card p-3">
            <h5>Storage Planning</h5>
            <p class="small text-secondary mb-0">
              Modern games are large. Leave extra space for updates and system files to avoid performance issues.
            </p>
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
  background-color: #6c757d;
  transition: width 0.3s ease;
}

.requirement-meter span.bg-success {
  background-color: #198754;
}

.requirement-meter span.bg-warning {
  background-color: #ffc107;
}

.requirement-meter span.bg-danger {
  background-color: #dc3545;
}

.eyebrow {
  font-size: 0.75rem;
  letter-spacing: 0.1em;
  color: #6c757d;
}

.content-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.3s ease;
}

.content-card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.form-range {
  height: 6px;
}

.form-range::-webkit-slider-thumb {
  width: 20px;
  height: 20px;
  background: #0d6efd;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>