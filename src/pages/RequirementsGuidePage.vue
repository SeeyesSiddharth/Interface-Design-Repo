<script setup>
import { computed, onMounted, ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()

onMounted(async () => {
  if (!store.state.games.length) {
    await store.loadGames()
  }
})

const requirementRows = computed(() => [
  {
    level: 'Entry',
    ram: '8 GB',
    gpu: 'GTX 950 / RX 460 class',
    use: 'Low-spec systems, lighter releases, indie games, and reduced graphics settings.',
  },
  {
    level: 'Recommended',
    ram: '16 GB',
    gpu: 'RTX 2060 / RX 6600 class',
    use: 'Balanced performance for most modern games at solid settings and resolution.',
  },
  {
    level: 'High-End',
    ram: '32 GB',
    gpu: 'RTX 4070+ / RX 7800 XT+ class',
    use: 'Demanding games, advanced visual features, high settings, and heavier effects.',
  },
])

const examples = computed(() => {
  const games = store.state.games

  return {
    entry: games.filter(game => game.rec.ram <= 8).slice(0, 3),
    recommended: games
      .filter(game => game.rec.ram > 8 && game.rec.ram <= 16)
      .slice(0, 3),
    highEnd: games.filter(game => game.rec.ram > 16).slice(0, 3),
  }
})

const selectedTopic = ref('resolution')

const performanceTopics = computed(() => {
  const games = store.state.games

  const findTitles = filterFn =>
    games.filter(filterFn).slice(0, 3).map(game => game.title)

  return {
    resolution: {
      label: 'Resolution',
      impact: ['GPU', 'VRAM'],
      explanation:
        'Higher resolutions increase the number of pixels that must be rendered, which places more pressure on the graphics card and video memory. This is why higher-end GPUs are better suited to 1440p or 4K play.',
      tip: 'If performance is low, reducing resolution can provide a large FPS improvement.',
      examples: findTitles(
        game =>
          game.tags?.includes('High VRAM') ||
          game.tags?.includes('Ray tracing') ||
          game.rec.ram >= 16,
      ),
    },
    textures: {
      label: 'Texture quality',
      impact: ['GPU', 'VRAM', 'RAM'],
      explanation:
        'Texture quality affects how detailed surfaces and objects appear. Higher texture settings need more graphics memory and can also increase memory use when large worlds or many assets are loaded at once.',
      tip: 'Lower texture quality first if you notice stutter, texture pop-in, or VRAM pressure.',
      examples: findTitles(
        game =>
          game.tags?.includes('High VRAM') ||
          game.tags?.includes('Large install') ||
          game.rec.storage >= 40,
      ),
    },
    shadows: {
      label: 'Shadows & lighting',
      impact: ['GPU', 'CPU'],
      explanation:
        'Shadows, lighting, reflections, and post-processing effects are usually GPU-heavy, but can also increase CPU load when scenes are large or contain many dynamic objects.',
      tip: 'Shadow quality is often one of the best settings to reduce for a quick performance boost.',
      examples: findTitles(
        game =>
          game.tags?.includes('Ray tracing') ||
          game.genre?.includes('Horror') ||
          game.notes?.toLowerCase().includes('shadows'),
      ),
    },
    simulation: {
      label: 'Physics, AI & simulation',
      impact: ['CPU', 'RAM'],
      explanation:
        'Simulation-heavy games rely more on the processor for AI behaviour, large maps, background systems, and physics calculations. Extra memory can also help when many systems are active at once.',
      tip: 'If a game slows down during large battles or busy scenes, CPU-heavy simulation may be the cause.',
      examples: findTitles(
        game =>
          game.tags?.includes('CPU heavy') ||
          game.tags?.includes('Physics') ||
          game.genre?.includes('Strategy') ||
          game.genre?.includes('Management'),
      ),
    },
    storage: {
      label: 'Storage speed',
      impact: ['Storage', 'RAM'],
      explanation:
        'Storage speed mainly affects loading times and asset streaming rather than raw frame rate. Faster SSDs help games load more quickly and reduce interruptions when new areas or assets are streamed in.',
      tip: 'An SSD is especially useful for large or open-world games with frequent loading.',
      examples: findTitles(
        game =>
          game.tags?.includes('Large install') ||
          game.tags?.includes('Fast load') ||
          game.notes?.toLowerCase().includes('ssd'),
      ),
    },
  }
})

const currentTopic = computed(() => performanceTopics.value[selectedTopic.value])
</script>

<template>
  <section>
    <PageHeader
      eyebrow="Guide"
      title="Requirements Guide"
      text="Explaining what minimum and recommended specs mean and how hardware requirements affect the player experience."
    />

    <div class="container section-band">
      <div v-if="store.state.apiError" class="alert alert-warning mb-4">
        {{ store.state.apiError }}
      </div>

      <div class="content-card p-3 p-md-4 mb-4">
        <h3 class="h5 mb-3">Requirement Tiers</h3>

        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">Tier</th>
                <th scope="col">RAM</th>
                <th scope="col">GPU range</th>
                <th scope="col">Best for</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in requirementRows" :key="row.level">
                <th scope="row">{{ row.level }}</th>
                <td>{{ row.ram }}</td>
                <td>{{ row.gpu }}</td>
                <td>{{ row.use }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="content-card p-3 p-md-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <h3 class="h5 mb-0">What Affects Performance?</h3>
          <span class="tag-chip">Interactive guide</span>
        </div>

        <p class="text-muted mb-3">
          Select a topic to see which part of a system is affected most and how it relates to games in the catalogue.
        </p>

        <div class="topic-toggle-group mb-4">
          <button
            v-for="(topic, key) in performanceTopics"
            :key="key"
            type="button"
            class="btn btn-sm"
            :class="selectedTopic === key ? 'btn-accent' : 'btn-outline-secondary'"
            @click="selectedTopic = key"
          >
            {{ topic.label }}
          </button>
        </div>

        <div class="row g-4">
          <div class="col-lg-7">
            <div class="border rounded p-3 h-100">
              <div class="small text-muted fw-semibold mb-2">Main hardware impact</div>
              <div class="d-flex flex-wrap gap-2 mb-3">
                <span
                  v-for="item in currentTopic.impact"
                  :key="item"
                  class="tag-chip"
                >
                  {{ item }}
                </span>
              </div>

              <div class="small text-muted fw-semibold mb-2">Explanation</div>
              <p class="mb-3">{{ currentTopic.explanation }}</p>

              <div class="small text-muted fw-semibold mb-2">Performance tip</div>
              <p class="mb-0">{{ currentTopic.tip }}</p>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="border rounded p-3 h-100">
              <div class="small text-muted fw-semibold mb-2">Related catalogue examples</div>

              <ul v-if="currentTopic.examples.length" class="mb-0">
                <li v-for="title in currentTopic.examples" :key="title">
                  {{ title }}
                </li>
              </ul>

              <p v-else class="text-muted mb-0">
                No matching examples are currently available.
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-lg-7">
          <div class="content-card p-3 p-md-4 h-100">
            <h3 class="h5 mb-3">Understanding System Requirements</h3>
            <ul class="mb-0">
              <li>
                <strong>Minimum vs recommended requirements:</strong>
                Minimum specifications describe the absolute lowest hardware needed to launch and run a game,
                but performance may be unstable, with low frame rates or reduced visual quality.
                Recommended specifications indicate the hardware needed for a smooth and consistent experience
                with higher graphics settings, better resolution, and improved stability.
              </li>

              <li>
                <strong>How settings affect performance:</strong>
                Graphics settings such as texture quality, shadows, reflections, and draw distance primarily
                impact the GPU, while simulation-heavy features (large maps, AI, physics) affect the CPU.
                Increasing RAM helps with multitasking and large game worlds, while storage (especially SSDs)
                improves loading times and reduces stuttering during gameplay.
              </li>

              <li>
                <strong>In regards to our games catalogue:</strong>
                Some games in the catalogue demonstrate different hardware demands. For example, lighter titles
                can run well on entry-level systems, while visually demanding or simulation-heavy games require
                more RAM, stronger GPUs, and faster CPUs. Use the compare page to see these differences clearly.
              </li>

              <li>
                <strong>Balancing quality vs performance:</strong>
                Players can often optimise their experience by adjusting settings. Lowering shadows, reflections,
                and post-processing effects can significantly improve performance without greatly impacting visual
                clarity, making games more playable on mid-range or older hardware.
              </li>
            </ul>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="content-card p-3 p-md-4 h-100">
            <h3 class="h5 mb-3">Catalogue Examples</h3>

            <div class="mb-3">
              <div class="small text-muted fw-semibold mb-2">Entry examples</div>
              <ul v-if="examples.entry.length" class="mb-0">
                <li v-for="game in examples.entry" :key="`entry-${game.id}`">
                  {{ game.title }} — {{ game.rec.ram }} GB RAM
                </li>
              </ul>
              <p v-else class="text-muted mb-0">No entry-tier examples loaded.</p>
            </div>

            <div class="mb-3">
              <div class="small text-muted fw-semibold mb-2">Recommended examples</div>
              <ul v-if="examples.recommended.length" class="mb-0">
                <li
                  v-for="game in examples.recommended"
                  :key="`recommended-${game.id}`"
                >
                  {{ game.title }} — {{ game.rec.ram }} GB RAM
                </li>
              </ul>
              <p v-else class="text-muted mb-0">No mid-tier examples loaded.</p>
            </div>

            <div>
              <div class="small text-muted fw-semibold mb-2">High-end examples</div>
              <ul v-if="examples.highEnd.length" class="mb-0">
                <li v-for="game in examples.highEnd" :key="`high-${game.id}`">
                  {{ game.title }} — {{ game.rec.ram }} GB RAM
                </li>
              </ul>
              <p v-else class="text-muted mb-0">No high-end examples loaded.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.topic-toggle-group {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
</style>