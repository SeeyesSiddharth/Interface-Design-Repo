<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()
const router = useRouter()
const error = ref('')

const form = reactive({
  gameId: 1,
  score: 0,
  comment: '',
  author: '',
})

function submit() {
  error.value = ''
  if (!store.isAuthenticated.value) {
    error.value = 'Login before submitting a review.'
    return
  }
  else {
    form.author = store.state.currentUser.name
  }
  if (form.comment.trim().length < 10 ) {
    error.value = 'Add a comment of at least 10 characters.'
    return
  }
  if (form.score < 0 || form.score > 5) {
    error.value = 'Score must be between 0 and 5.'
    return
  }
  store.addReview({
    ...form
  })
  router.push('/Reviews')
}
</script>

<template>
  <PageHeader title="Submit Game"/>

  <section class="section-band">
    <div class="container">
      <form class="content-card p-4" @submit.prevent="submit">
        <div v-if="error" class="alert alert-warning">{{ error }}</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="gameSelect">Select game</label>
            <select id="gameSelect" v-model="form.gameId" class="form-select mb-3">
              <option v-for="game in store.state.games" :key="game.id" :value="game.id">{{ game.title }}</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="score">Score</label>
            <input id="studio" v-model="form.score" class="form-control" required />
          </div>
          <div class="col-md-12">
            <label class="form-label" for="comment">Comment</label>
            <input id="comment" v-model="form.comment" class="form-control" required />
          </div>
        </div>
        <button class="btn btn-accent mt-4" type="submit">Submit Review</button>
      </form>
    </div>
  </section>
</template>
