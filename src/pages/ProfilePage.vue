<script setup>
import { computed, ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()

// --- Edit Profile ---
const showEditProfile = ref(false)
const editName = ref('')
const editEmail = ref('')

function openEditProfile() {
  editName.value = store.state.currentUser.name
  editEmail.value = store.state.currentUser.email
  showEditProfile.value = true
}

function saveProfile() {
  store.updateCurrentUser({ name: editName.value, email: editEmail.value })
  showEditProfile.value = false
}

// --- Edit Review ---
const editingReviewId = ref(null)
const editComment = ref('')
const editScore = ref(5)

function startEditReview(review) {
  editingReviewId.value = review.id
  editComment.value = review.comment
  editScore.value = review.score
}

function saveReview(reviewId) {
  store.updateReview(reviewId, { comment: editComment.value, score: editScore.value })
  editingReviewId.value = null
}

function cancelEditReview() {
  editingReviewId.value = null
}

// --- Sort ---
const sortKey = ref('title-asc')

const sortOptions = [
  { value: 'title-asc', label: 'Game title A–Z' },
  { value: 'title-desc', label: 'Game title Z–A' },
  { value: 'score-asc', label: 'Score: low to high' },
  { value: 'score-desc', label: 'Score: high to low' },
]

// --- Pagination ---
const currentPage = ref(1)
const perPage = ref(3)

function setPage(page) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  editingReviewId.value = null
}

function onSortChange() {
  currentPage.value = 1
  editingReviewId.value = null
}

// --- Reviews ---
const gameTitle = (gameId) => {
  const game = store.state.games.find((game) => game.id === gameId)
  return game ? game.title : 'Unknown game'
}

const myReviews = computed(() => {
  const reviews = store.state.reviews.filter(
    (review) => review.author === store.state.currentUser?.name
  )
  return [...reviews].sort((a, b) => {
    switch (sortKey.value) {
      case 'title-asc':  return gameTitle(a.gameId).localeCompare(gameTitle(b.gameId))
      case 'title-desc': return gameTitle(b.gameId).localeCompare(gameTitle(a.gameId))
      case 'score-asc':  return a.score - b.score
      case 'score-desc': return b.score - a.score
      default: return 0
    }
  })
})

const totalPages = computed(() => Math.max(1, Math.ceil(myReviews.value.length / perPage.value)))

const pagedReviews = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return myReviews.value.slice(start, start + perPage.value)
})
</script>

<template>
  <PageHeader title="Member Profile" />

  <section class="section-band">
    <div class="container">
      <div v-if="!store.isAuthenticated.value" class="content-card p-4">
        <h2>Please login to view your member dashboard.</h2>
        <RouterLink class="btn btn-accent" to="/login">Login</RouterLink>
      </div>

      <template v-else>
        <!-- Top row: account + actions -->
        <div class="row g-4 mb-4">
          <!-- Account Card -->
          <div class="col-lg-4">
            <div class="content-card p-3 h-100">
              <p class="eyebrow mb-2">Account</p>
              <h2>{{ store.state.currentUser.name }}</h2>
              <p class="text-secondary mb-1">{{ store.state.currentUser.email }}</p>
              <p class="mb-3">Role: <strong>{{ store.state.currentUser.role }}</strong></p>
              <p class="mb-3">Member since: <strong>{{ new Date(store.state.currentUser.created.replace(' ', 'T')).toLocaleDateString() }}</strong></p>
              <button class="btn btn-outline-dark btn-sm" @click="openEditProfile">
                Edit Profile
              </button>
            </div>
          </div>

          <!-- Actions Card -->
          <div class="col-lg-8">
            <div class="content-card p-3 h-100">
              <h2 class="h4">Member actions</h2>
              <div class="d-flex flex-wrap gap-2 mb-3">
                <RouterLink class="btn btn-dark" to="/submit-game">Submit a game</RouterLink>
                <RouterLink class="btn btn-dark" to="/submit-review">Submit a review</RouterLink>
                <RouterLink class="btn btn-outline-dark" to="/reviews">View reviews</RouterLink>
              </div>
              <div v-if="store.isAdmin.value">
                <h2 class="h4">Admin actions</h2>
                <div class="d-flex flex-wrap gap-2">
                  <RouterLink class="btn btn-outline-dark" to="/admin">Manage catalogue</RouterLink>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reviews Section -->
        <div class="content-card p-3">
          <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <h3 class="h5 mb-0">
              Your reviews
              <span class="text-secondary fw-normal fs-6">({{ myReviews.length }})</span>
            </h3>
            <select v-model="sortKey" class="form-select form-select-sm w-auto" @change="onSortChange">
              <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </div>

          <p v-if="myReviews.length === 0" class="text-secondary mb-3">No reviews yet.</p>

          <div v-else class="row g-3 mb-3">
            <div v-for="review in pagedReviews" :key="review.id" class="col-md-6 col-lg-4">
              <div class="review-card p-3 h-100">
                <!-- View mode -->
                <template v-if="editingReviewId !== review.id">
                  <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                    <span class="fw-bold small">{{ gameTitle(review.gameId) }}</span>
                    <span class="badge text-bg-warning flex-shrink-0">{{ review.score }}/5</span>
                  </div>
                  <p class="small text-secondary mb-2 review-comment">{{ review.comment }}</p>
                  <div class="d-flex gap-2 mt-auto">
                    <button class="btn btn-sm btn-outline-secondary" @click="startEditReview(review)">Edit</button>
                    <button class="btn btn-sm btn-outline-danger" @click="store.deleteReview(review.id)">Delete</button>
                  </div>
                </template>

                <!-- Edit mode -->
                <template v-else>
                  <div class="fw-bold small mb-2">{{ gameTitle(review.gameId) }}</div>
                  <textarea v-model="editComment" class="form-control form-control-sm mb-2" rows="3" />
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <label class="small mb-0">Score:</label>
                    <select v-model="editScore" class="form-select form-select-sm w-auto">
                      <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                    </select>
                  </div>
                  <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success" @click="saveReview(review.id)">Save</button>
                    <button class="btn btn-sm btn-outline-secondary" @click="cancelEditReview">Cancel</button>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- Pagination — always visible -->
          <nav class="d-flex justify-content-center gap-2 mt-2" aria-label="Reviews pagination">
            <button class="btn btn-outline-dark" type="button"
              :disabled="currentPage === 1" @click="setPage(currentPage - 1)">Previous</button>
            <span class="btn btn-light disabled">Page {{ currentPage }} of {{ totalPages }}</span>
            <button class="btn btn-outline-dark" type="button"
              :disabled="currentPage === totalPages" @click="setPage(currentPage + 1)">Next</button>
            <select v-model="perPage" class="btn btn-outline-dark" @change="setPage(1)">
              <option :value="3">3</option>
              <option :value="6">6</option>
              <option :value="9">9</option>
            </select>
          </nav>
        </div>
      </template>
    </div>
  </section>

  <!-- Edit Profile Modal -->
  <div v-if="showEditProfile" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Profile</h5>
          <button type="button" class="btn-close" @click="showEditProfile = false" />
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Display Name</label>
            <input v-model="editName" class="form-control" type="text" />
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input v-model="editEmail" class="form-control" type="email" />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="showEditProfile = false">Cancel</button>
          <button class="btn btn-dark" @click="saveProfile">Save changes</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.review-card {
  background: var(--color-surface, #fff);
  border: 1px solid #e9ecef;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
}

.review-comment {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>