<script setup>
import { computed, ref } from 'vue'
import GameCard from '../components/GameCard.vue'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'
import Paginate from 'vuejs-paginate-next'


const store = useAppStore()
const search = ref('')
const genre = ref('All')
const sortBy = ref('rating')
const page = ref(1)
const perPage = ref(6)

const genres = computed(() => ['All', ...new Set(store.state.games.map((game) => game.genre))])

const filteredGames = computed(() => {
  const term = search.value.trim().toLowerCase()
  const games = store.state.games.filter((game) => {
    const matchesTerm = !term || [game.title, game.genre, game.studio, game.summary, ...game.tags].join(' ').toLowerCase().includes(term)
    const matchesGenre = genre.value === 'All' || game.genre === genre.value
    return matchesTerm && matchesGenre
  })

  return games.sort((a, b) => {
    if (sortBy.value === 'title') return a.title.localeCompare(b.title)
    if (sortBy.value === 'ram') return a.rec.ram - b.rec.ram
    if (sortBy.value === 'storage') return a.rec.storage - b.rec.storage
    if (sortBy.value === 'likes') return b.likes - a.likes
    return b.rating - a.rating
  })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredGames.value.length / perPage.value)))
const pagedGames = computed(() => {
  const current = page.value * perPage.value
  const start = current - perPage.value

  return filteredGames.value.slice(start, current)
})

function setPage(nextPage) {
  page.value = Math.min(Math.max(nextPage, 1), totalPages.value)
}
</script>

<template>
  <PageHeader
    title="Game Catalogue"
    text="Search, filter, sort, paginate, like games, and open detail pages. This page covers several core functional requirements from the project brief."
  />

  <section class="section-band">
    <div class="container">
      <div class="content-card p-3 mb-4">
        <div class="row g-3 align-items-end">
          <div class="col-md-5">
            <label class="form-label" for="catalogueSearch">Search games</label>
            <input id="catalogueSearch" v-model="search" class="form-control" type="search" placeholder="Try RPG, low spec, co-op..." @input="setPage(1)" />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="genreFilter">Genre</label>
            <select id="genreFilter" v-model="genre" class="form-select" @change="setPage(1)">
              <option v-for="item in genres" :key="item">{{ item }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="sortBy">Sort by</label>
            <select id="sortBy" v-model="sortBy" class="form-select">
              <option value="rating">Highest rating</option>
              <option value="likes">Most liked</option>
              <option value="ram">Lowest RAM</option>
              <option value="storage">Lowest storage</option>
              <option value="title">Title A-Z</option>
            </select>
          </div>
          <div class="col-md-1">
            <span class="badge text-bg-dark w-100 py-2">{{ filteredGames.length }}</span>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <div v-for="game in pagedGames" :key="game.id" class="col-md-6 col-xl-4">
          <GameCard :game="game" />
        </div>
      </div>
      

      <nav class="d-flex justify-content-center gap-2 mt-4" aria-label="Catalogue pagination">
        <button class="btn btn-outline-dark" type="button" :disabled="page === 1" @click="setPage(page - 1)">Previous</button>
        <span class="btn btn-light disabled">Page {{ page }} of {{ totalPages }}</span>
        <button class="btn btn-outline-dark" type="button" :disabled="page === totalPages" @click="setPage(page + 1)">Next</button>
        <select v-model="perPage"  class="btn btn-outline-dark" @change="setPage(1)">
            <option :value="6">6</option>
            <option :value="9">9</option>
            <option :value="12">12</option>
        </select>
      </nav>



    </div>
  </section>
</template>
