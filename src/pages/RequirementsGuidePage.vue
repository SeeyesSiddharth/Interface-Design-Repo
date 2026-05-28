<script setup>
import PageHeader from '../components/PageHeader.vue'

const guideRows = [
  { level: 'Low spec', ram: '4-8 GB', gpu: 'Integrated to entry level', use: 'Puzzle games, 2D games, cozy sims, browser demos' },
  { level: 'Mid range', ram: '12-16 GB', gpu: 'GTX 1660 to RTX 3060 class', use: 'Most racing, RPG, strategy, and action games at 1080p' },
  { level: 'Demanding', ram: '32 GB', gpu: 'RTX 4070+ class', use: 'Ray tracing, high-resolution textures, heavy physics, large online worlds' },
]
</script>

<template>
  <PageHeader
    title="Requirements Guide"
    text="A plain-language explainer page the team can expand into a stronger educational section for the report and video."
  />

  <section class="section-band">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="content-card p-3 h-100">
            <h2 class="h4">Requirement tiers</h2>
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr><th>Tier</th><th>RAM</th><th>GPU range</th><th>Best for</th></tr>
                </thead>
                <tbody>
                  <tr v-for="row in guideRows" :key="row.level">
                    <th>{{ row.level }}</th>
                    <td>{{ row.ram }}</td>
                    <td>{{ row.gpu }}</td>
                    <td>{{ row.use }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="content-card p-3 h-100">
            <h2 class="h4">Content ideas to add</h2>
            <ul class="mb-0">
              <li>Explain minimum versus recommended requirements.</li>
              <li>Add examples of settings that affect CPU, GPU, RAM, VRAM, and storage.</li>
              <li>Include screenshots for the group report.</li>
              <li>Add accessibility notes, such as scalable text and color contrast.</li>
              <li>Link each explanation to catalogue examples.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
