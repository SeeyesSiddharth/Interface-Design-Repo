<script setup>
import { computed } from 'vue'
import GameCard from '../components/GameCard.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()
const featuredGames = computed(() => [...store.state.games].sort((a, b) => b.rating - a.rating).slice(0, 3))
const totalStorage = computed(() => Math.round(store.state.games.reduce((sum, game) => sum + game.rec.storage, 0) / store.state.games.length))
</script>

<template>
  <section class="hero-band">
    <div class="container">
      <div class="hero-copy">
        <p class="eyebrow text-warning mb-3">Game catalogue and requirements hub</p>
        <h1 class="display-title">GameBench</h1>
        <p class="lead mt-4 mb-4">
          Compare game requirements, check whether a setup can run a title, read player reviews, and manage a shared
          catalogue with Vue, Bootstrap, routing, forms, CRUD, likes, and persistent data.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <RouterLink class="btn btn-accent btn-lg" to="/catalogue">Browse Catalogue</RouterLink>
          <RouterLink class="btn btn-outline-light btn-lg" to="/team-plan">Team Page Plan</RouterLink>
        </div>
      </div>
    </div>
  </section>

  <section class="section-band">
    <div class="container">
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="content-card p-3 h-100">
            <span class="metric-label">Seeded games</span>
            <strong class="fs-2">{{ store.state.games.length }}</strong>
          </div>
        </div>
        <div class="col-md-4">
          <div class="content-card p-3 h-100">
            <span class="metric-label">Average recommended storage</span>
            <strong class="fs-2">{{ totalStorage }} GB</strong>
          </div>
        </div>
        <div class="col-md-4">
          <div class="content-card p-3 h-100">
            <span class="metric-label">Working routes</span>
            <strong class="fs-2">13+</strong>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-end gap-3 mb-3">
        <div>
          <p class="eyebrow mb-1">Featured</p>
          <h2 class="h1">Highest rated games</h2>
        </div>
        <RouterLink class="btn btn-outline-dark" to="/compare">Compare Specs</RouterLink>
      </div>
      <div class="row g-4">
        <div v-for="game in featuredGames" :key="game.id" class="col-md-6 col-xl-4">
          <GameCard :game="game" />
        </div>
      </div>
    </div>
  </section>
</template>
