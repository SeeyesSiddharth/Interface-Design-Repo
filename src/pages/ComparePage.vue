<script setup>
import { computed, ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()
const firstId = ref(store.state.games[0]?.id)
const secondId = ref(store.state.games[1]?.id)

const first = computed(() => store.state.games.find((game) => game.id === Number(firstId.value)))
const second = computed(() => store.state.games.find((game) => game.id === Number(secondId.value)))
</script>

<template>
  <PageHeader
    title="Game Comparison"
    text="Choose two catalogue entries and compare recommended requirements, storage needs, platforms, and key notes."
  />

  <section class="section-band">
    <div class="container">
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label" for="firstGame">First game</label>
          <select id="firstGame" v-model="firstId" class="form-select">
            <option v-for="game in store.state.games" :key="game.id" :value="game.id">{{ game.title }}</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="secondGame">Second game</label>
          <select id="secondGame" v-model="secondId" class="form-select">
            <option v-for="game in store.state.games" :key="game.id" :value="game.id">{{ game.title }}</option>
          </select>
        </div>
      </div>

      <div class="table-responsive content-card">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Requirement</th>
              <th>{{ first?.title }}</th>
              <th>{{ second?.title }}</th>
            </tr>
          </thead>
          <tbody>
            <tr><th>Recommended CPU</th><td>{{ first?.rec.cpu }}</td><td>{{ second?.rec.cpu }}</td></tr>
            <tr><th>Recommended GPU</th><td>{{ first?.rec.gpu }}</td><td>{{ second?.rec.gpu }}</td></tr>
            <tr><th>RAM</th><td>{{ first?.rec.ram }} GB</td><td>{{ second?.rec.ram }} GB</td></tr>
            <tr><th>Storage</th><td>{{ first?.rec.storage }} GB</td><td>{{ second?.rec.storage }} GB</td></tr>
            <tr><th>Platforms</th><td>{{ first?.platform.join(', ') }}</td><td>{{ second?.platform.join(', ') }}</td></tr>
            <tr><th>Notes</th><td>{{ first?.notes }}</td><td>{{ second?.notes }}</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
