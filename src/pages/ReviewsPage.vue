<script setup>
import { computed } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()
const reviewsWithGames = computed(() =>
  store.state.reviews.map((review) => ({
    ...review,
    game: store.state.games.find((game) => game.id === review.gameId),
  }))
)
</script>

<template>
  <PageHeader
    title="Community Reviews"
    text="A central page for social interaction. Users can add more reviews from each game's detail page."
  />

  <section class="section-band">
    <div class="container">
      <div class="row g-3">
        <div v-for="review in reviewsWithGames" :key="review.id" class="col-md-6">
          <article class="content-card p-3 h-100">
            <div class="d-flex justify-content-between gap-3">
              <div>
                <h2 class="h5 mb-1">{{ review.game?.title || 'Removed game' }}</h2>
                <p class="text-secondary mb-2">By {{ review.author }}</p>
              </div>
              <span class="badge text-bg-warning align-self-start">{{ review.score }}/5</span>
            </div>
            <p class="mb-0">{{ review.comment }}</p>
          </article>
        </div>
      </div>
    </div>
  </section>
</template>
