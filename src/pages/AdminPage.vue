<script setup>
import { computed, reactive, ref, watch } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()
const selectedId = ref(store.state.games[0]?.id)
const saved = ref('')
const draft = reactive({
  title: '',
  rating: 0,
  releaseYear: 2026,
  summary: '',
})

const selectedGame = computed(() => store.state.games.find((game) => game.id === Number(selectedId.value)))

watch(
  selectedGame,
  (game) => {
    if (!game) return
    draft.title = game.title
    draft.rating = game.rating
    draft.releaseYear = game.releaseYear
    draft.summary = game.summary
    saved.value = ''
  },
  { immediate: true }
)

function saveChanges() {
  if (!selectedGame.value) return
  store.updateGame(selectedGame.value.id, { ...draft })
  saved.value = 'Changes saved locally.'
}

function removeGame(id) {
  store.deleteGame(id)
  selectedId.value = store.state.games[0]?.id
}
</script>

<template>
  <PageHeader title="Admin Management" text="A simple edit/delete page that demonstrates authorised CRUD operations." />

  <section class="section-band">
    <div class="container">
      <div v-if="!store.isAuthenticated.value" class="content-card p-4">
        <h2>Admin tools are hidden until login.</h2>
        <RouterLink class="btn btn-accent" to="/login">Login</RouterLink>
      </div>

      <div v-else class="row g-4">
        <div class="col-lg-5">
          <div class="content-card p-3">
            <label class="form-label" for="gameSelect">Select entry</label>
            <select id="gameSelect" v-model="selectedId" class="form-select mb-3">
              <option v-for="game in store.state.games" :key="game.id" :value="game.id">{{ game.title }}</option>
            </select>
            <label class="form-label" for="editTitle">Title</label>
            <input id="editTitle" v-model="draft.title" class="form-control mb-3" />
            <label class="form-label" for="editYear">Release year</label>
            <input id="editYear" v-model.number="draft.releaseYear" class="form-control mb-3" type="number" />
            <label class="form-label" for="editRating">Rating</label>
            <input id="editRating" v-model.number="draft.rating" class="form-control mb-3" type="number" min="0" max="5" step="0.1" />
            <label class="form-label" for="editSummary">Summary</label>
            <textarea id="editSummary" v-model="draft.summary" class="form-control mb-3" rows="4"></textarea>
            <button class="btn btn-accent" type="button" @click="saveChanges">Save changes</button>
            <span class="ms-2 text-success">{{ saved }}</span>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="content-card p-3">
            <h2 class="h4">Delete entries</h2>
            <div class="table-responsive">
              <table class="table align-middle">
                <thead><tr><th>Title</th><th>Genre</th><th>Action</th></tr></thead>
                <tbody>
                  <tr v-for="game in store.state.games" :key="game.id">
                    <td>{{ game.title }}</td>
                    <td>{{ game.genre }}</td>
                    <td><button class="btn btn-sm btn-outline-danger" type="button" @click="removeGame(game.id)">Delete</button></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <button class="btn btn-outline-dark" type="button" @click="store.resetDemoData">Reset demo data</button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
