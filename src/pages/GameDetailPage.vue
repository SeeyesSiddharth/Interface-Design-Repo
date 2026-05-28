<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAppStore } from '../stores/appStore'

const route = useRoute()
const router = useRouter()
const store = useAppStore()
const error = ref('')

const game = computed(() => store.state.games.find((entry) => entry.slug === route.params.slug))
const gameReviews = computed(() => store.state.reviews.filter((review) => review.gameId === game.value?.id))

const reviewForm = reactive({
  score: 5,
  comment: '',
})

function submitReview() {
  error.value = ''
  if (!store.isAuthenticated.value) {
    error.value = 'Please login before adding a review.'
    return
  }
  if (reviewForm.comment.trim().length < 12) {
    error.value = 'Review comments must be at least 12 characters.'
    return
  }
  store.addReview({
    gameId: game.value.id,
    author: store.state.currentUser.name,
    score: reviewForm.score,
    comment: reviewForm.comment,
  })
  reviewForm.score = 5
  reviewForm.comment = ''
}

function deleteCurrentGame() {
  store.deleteGame(game.value.id)
  router.push('/catalogue')
}
</script>

<template>
  <section v-if="game" class="section-band">
    <div class="container">
      <div class="row g-4 align-items-start">
        <div class="col-lg-5">
          <div class="cover-panel rounded-3" :class="game.coverTheme">
            <span>{{ game.genre }}</span>
          </div>
        </div>
        <div class="col-lg-7">
          <p class="eyebrow mb-2">{{ game.studio }}</p>
          <h1 class="display-title">{{ game.title }}</h1>
          <p class="lead text-secondary mt-3">{{ game.summary }}</p>
          <div class="d-flex flex-wrap gap-2 mb-4">
            <span v-for="tag in game.tags" :key="tag" class="tag-chip">{{ tag }}</span>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-danger" type="button" @click="store.toggleLike(game.id)">Like {{ game.likes }}</button>
            <RouterLink class="btn btn-dark" to="/compare">Compare with another game</RouterLink>
            <button v-if="store.isAuthenticated.value" class="btn btn-outline-dark" type="button" @click="deleteCurrentGame">Delete entry</button>
          </div>
        </div>
      </div>

      <div class="row g-4 mt-4">
        <div class="col-lg-6">
          <div class="content-card p-3 h-100">
            <h2 class="h4">Minimum requirements</h2>
            <dl class="row mb-0">
              <dt class="col-sm-3">CPU</dt><dd class="col-sm-9">{{ game.min.cpu }}</dd>
              <dt class="col-sm-3">GPU</dt><dd class="col-sm-9">{{ game.min.gpu }}</dd>
              <dt class="col-sm-3">RAM</dt><dd class="col-sm-9">{{ game.min.ram }} GB</dd>
              <dt class="col-sm-3">Storage</dt><dd class="col-sm-9">{{ game.min.storage }} GB</dd>
              <dt class="col-sm-3">OS</dt><dd class="col-sm-9">{{ game.min.os }}</dd>
            </dl>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="content-card p-3 h-100">
            <h2 class="h4">Recommended requirements</h2>
            <dl class="row mb-0">
              <dt class="col-sm-3">CPU</dt><dd class="col-sm-9">{{ game.rec.cpu }}</dd>
              <dt class="col-sm-3">GPU</dt><dd class="col-sm-9">{{ game.rec.gpu }}</dd>
              <dt class="col-sm-3">RAM</dt><dd class="col-sm-9">{{ game.rec.ram }} GB</dd>
              <dt class="col-sm-3">Storage</dt><dd class="col-sm-9">{{ game.rec.storage }} GB</dd>
              <dt class="col-sm-3">OS</dt><dd class="col-sm-9">{{ game.rec.os }}</dd>
            </dl>
          </div>
        </div>
      </div>

      <div class="content-card p-3 mt-4">
        <h2 class="h4">Review this game</h2>
        <p class="text-secondary">{{ game.notes }}</p>
        <div v-if="error" class="alert alert-warning">{{ error }}</div>
        <form class="row g-3" @submit.prevent="submitReview">
          <div class="col-md-2">
            <label class="form-label" for="score">Score</label>
            <select id="score" v-model="reviewForm.score" class="form-select">
              <option v-for="score in [5, 4, 3, 2, 1]" :key="score" :value="score">{{ score }}</option>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label" for="comment">Comment</label>
            <input id="comment" v-model="reviewForm.comment" class="form-control" placeholder="Share a useful compatibility note..." />
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-accent w-100" type="submit">Post</button>
          </div>
        </form>
        <div class="row g-3 mt-2">
          <div v-for="review in gameReviews" :key="review.id" class="col-md-6">
            <div class="border rounded p-3 h-100 bg-light">
              <strong>{{ review.author }} rated {{ review.score }}/5</strong>
              <p class="mb-0 text-secondary">{{ review.comment }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section v-else class="section-band">
    <div class="container">
      <div class="content-card p-4">
        <h1>Game not found</h1>
        <RouterLink class="btn btn-dark" to="/catalogue">Back to catalogue</RouterLink>
      </div>
    </div>
  </section>
</template>
