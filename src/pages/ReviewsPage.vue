<script setup>
import { computed, ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()

// Review data with game association
const reviewsWithGames = computed(() =>
  store.state.reviews.map((review) => ({
    ...review,
    game: store.state.games.find((game) => game.id === review.gameId),
  }))
)

// Sorting options
const sortBy = ref('recent')

// Filtered and sorted reviews
const sortedReviews = computed(() => {
  const reviews = [...reviewsWithGames.value]

  switch (sortBy.value) {
    case 'recent':
      return reviews.reverse()
    case 'rating-high':
      return reviews.sort((a, b) => b.score - a.score)
    case 'rating-low':
      return reviews.sort((a, b) => a.score - b.score)
    case 'author':
      return reviews.sort((a, b) => a.author.localeCompare(b.author))
    default:
      return reviews
  }
})

// Statistics
const reviewStats = computed(() => {
  if (reviewsWithGames.value.length === 0) {
    return { totalReviews: 0, averageRating: 0, ratingDistribution: [] }
  }

  const total = reviewsWithGames.value.length
  const sum = reviewsWithGames.value.reduce((acc, review) => acc + review.score, 0)
  const average = (sum / total).toFixed(1)

  // Calculate rating distribution
  const distribution = {
    5: 0,
    4: 0,
    3: 0,
    2: 0,
    1: 0,
  }

  reviewsWithGames.value.forEach((review) => {
    distribution[review.score]++
  })

  return {
    totalReviews: total,
    averageRating: parseFloat(average),
    ratingDistribution: distribution,
  }
})

// Filter by game
const selectedGameFilter = ref('all')
const filteredByGame = computed(() => {
  if (selectedGameFilter.value === 'all') {
    return sortedReviews.value
  }
  return sortedReviews.value.filter((review) => review.gameId === Number(selectedGameFilter.value))
})

// Delete review function
const deleteReview = (id) => {
  if (confirm('Are you sure you want to delete this review?')) {
    store.deleteReview(id)
  }
}

// Check if user is authenticated
const isAuthenticated = computed(() => store.isAuthenticated.value)

// Search functionality
const searchTerm = ref('')
const finalReviews = computed(() => {
  if (!searchTerm.value) {
    return filteredByGame.value
  }
  const term = searchTerm.value.toLowerCase()
  return filteredByGame.value.filter(
    (review) =>
      review.game?.title.toLowerCase().includes(term) ||
      review.author.toLowerCase().includes(term) ||
      review.comment.toLowerCase().includes(term)
  )
})

// Get star rating display
const getStarDisplay = (score) => {
  return '⭐'.repeat(score) + '☆'.repeat(5 - score)
}

// Get rating badge color
const getRatingBadgeClass = (score) => {
  if (score >= 4) return 'bg-success'
  if (score >= 3) return 'bg-info'
  if (score >= 2) return 'bg-warning'
  return 'bg-danger'
}
</script>

<template>
  <PageHeader
    title="Community Reviews"
    text="Share your gaming experiences and discover what other players think about the latest games. Read detailed reviews, ratings, and honest feedback from the gaming community."
  />

  <!-- Statistics Section -->
  <section class="section-band bg-light">
    <div class="container">
      <div class="row g-4 mb-4">
        <div class="col-md-3">
          <div class="text-center">
            <p class="eyebrow text-uppercase mb-1">Total Reviews</p>
            <h2 class="display-4 fw-bold">{{ reviewStats.totalReviews }}</h2>
          </div>
        </div>
        <div class="col-md-3">
          <div class="text-center">
            <p class="eyebrow text-uppercase mb-1">Average Rating</p>
            <h2 class="display-4 fw-bold">{{ reviewStats.averageRating }}</h2>
            <p class="text-warning">
              {{ getStarDisplay(Math.round(reviewStats.averageRating)) }}
            </p>
          </div>
        </div>
        <div class="col-md-6">
          <p class="eyebrow text-uppercase mb-3">Rating Distribution</p>
          <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="d-flex align-items-center gap-2 mb-2">
            <span class="small fw-bold" style="width: 30px">{{ star }}⭐</span>
            <div class="progress flex-grow-1" style="height: 20px">
              <div
                class="progress-bar"
                :style="{
                  width:
                    reviewStats.totalReviews > 0
                      ? `${(reviewStats.ratingDistribution[star] / reviewStats.totalReviews) * 100}%`
                      : '0%',
                }"
              ></div>
            </div>
            <span class="small text-secondary" style="width: 30px">{{ reviewStats.ratingDistribution[star] }}</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="text-center">
        <RouterLink v-if="isAuthenticated" to="/submit-review" class="btn btn-primary">
          + Write a Review
        </RouterLink>
        <RouterLink v-else to="/login" class="btn btn-primary">
          Log In to Write Reviews
        </RouterLink>
      </div>
    </div>
  </section>

  <!-- Main Reviews Section -->
  <section class="section-band">
    <div class="container">
      <!-- Filters and Search -->
      <div class="row mb-4 g-3">
        <div class="col-md-4">
          <input
            v-model="searchTerm"
            type="text"
            class="form-control"
            placeholder="Search by game, author, or content..."
          />
        </div>

        <div class="col-md-4">
          <select v-model="selectedGameFilter" class="form-select">
            <option value="all">All Games</option>
            <option v-for="game in store.state.games" :key="game.id" :value="game.id">
              {{ game.title }}
            </option>
          </select>
        </div>

        <div class="col-md-4">
          <select v-model="sortBy" class="form-select">
            <option value="recent">Most Recent</option>
            <option value="rating-high">Highest Rated</option>
            <option value="rating-low">Lowest Rated</option>
            <option value="author">By Author</option>
          </select>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="finalReviews.length === 0" class="alert alert-info text-center py-5">
        <h4>No reviews found</h4>
        <p class="text-secondary mb-0">
          {{
            searchTerm
              ? 'Try adjusting your search criteria.'
              : selectedGameFilter !== 'all'
              ? 'No reviews for the selected game yet.'
              : 'Be the first to write a review!'
          }}
        </p>
      </div>

      <!-- Reviews Grid -->
      <div v-else class="row g-4">
        <div v-for="review in finalReviews" :key="review.id" class="col-md-6 col-lg-4">
          <article class="content-card h-100 d-flex flex-column">
            <!-- Review Header -->
            <div class="p-4 border-bottom">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                <div class="flex-grow-1">
                  <h3 class="h5 mb-1">
                    <RouterLink
                      :to="`/games/${review.game?.slug}`"
                      class="text-dark text-decoration-none"
                      :title="review.game?.title || 'Removed game'"
                    >
                      {{ review.game?.title || 'Removed game' }}
                    </RouterLink>
                  </h3>
                  <p class="text-secondary mb-0 small">By <strong>{{ review.author }}</strong></p>
                </div>
                <span :class="`badge text-white ${getRatingBadgeClass(review.score)} align-self-start flex-shrink-0`">
                  {{ review.score }}/5
                </span>
              </div>
              <p class="text-warning small mb-0">{{ getStarDisplay(review.score) }}</p>
            </div>

            <!-- Review Body -->
            <div class="p-4 flex-grow-1">
              <p class="mb-0">{{ review.comment }}</p>
            </div>

            <!-- Review Footer -->
            <div class="p-4 border-top bg-light">
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary flex-grow-1" disabled>
                  👍 Helpful
                </button>
                <button
                  v-if="review.author === store.state.currentUser?.name"
                  class="btn btn-sm btn-outline-danger"
                  @click="deleteReview(review.id)"
                >
                  Delete
                </button>
              </div>
            </div>
          </article>
        </div>
      </div>

      <!-- Result Count -->
      <div v-if="finalReviews.length > 0" class="text-center mt-4 text-secondary">
        <small>Showing {{ finalReviews.length }} of {{ reviewsWithGames.length }} reviews</small>
      </div>
    </div>
  </section>

  <!-- Review Tips Section -->
  <section class="section-band bg-light">
    <div class="container">
      <h3 class="mb-4">📝 Writing Great Reviews</h3>
      <div class="row g-3">
        <div class="col-md-4">
          <div class="content-card p-3">
            <h5>🎯 Be Specific</h5>
            <p class="small text-secondary mb-0">
              Mention specific aspects like graphics, gameplay, story, and performance to help other players.
            </p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="content-card p-3">
            <h5>⚖️ Be Fair</h5>
            <p class="small text-secondary mb-0">
              Rate games fairly based on their genre and target audience. Compare similar games.
            </p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="content-card p-3">
            <h5>✨ Be Constructive</h5>
            <p class="small text-secondary mb-0">
              Provide constructive criticism that helps developers and fellow gamers understand strengths and weaknesses.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.content-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border: 1px solid #e9ecef;
}

.content-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.eyebrow {
  font-size: 0.75rem;
  letter-spacing: 0.1em;
  color: #6c757d;
}

.text-warning {
  color: #ffc107;
}

.progress {
  background-color: #e9ecef;
  border-radius: 4px;
}

.progress-bar {
  background: linear-gradient(90deg, #0d6efd, #0d6efd);
  transition: width 0.6s ease;
}

.badge {
  padding: 0.5rem 0.75rem;
  font-size: 0.85rem;
  font-weight: 600;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
}

a {
  transition: color 0.2s ease;
}

a:hover {
  color: #0d6efd;
}
</style>