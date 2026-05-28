<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()
const router = useRouter()

// Check authentication
const isAuthenticated = computed(() => store.isAuthenticated.value)

// Form state
const form = reactive({
  gameId: store.state.games[0]?.id || 1,
  score: 5,
  comment: '',
  author: store.state.currentUser?.name || '',
})

// Errors
const errors = reactive({
  gameId: '',
  score: '',
  comment: '',
})

// Form submission state
const isSubmitting = ref(false)
const submitSuccess = ref(false)

// Computed properties
const selectedGame = computed(() =>
  store.state.games.find((game) => game.id === Number(form.gameId))
)

// Update author when user logs in
const updateAuthorFromUser = () => {
  if (store.state.currentUser?.name) {
    form.author = store.state.currentUser.name
  }
}

// Validate form
const validateForm = () => {
  let isValid = true

  errors.gameId = ''
  errors.score = ''
  errors.comment = ''

  if (!form.gameId) {
    errors.gameId = 'Please select a game'
    isValid = false
  }

  if (form.score < 1 || form.score > 5) {
    errors.score = 'Rating must be between 1 and 5'
    isValid = false
  }

  if (!form.comment.trim()) {
    errors.comment = 'Please write a review comment'
    isValid = false
  }

  if (form.comment.trim().length < 10) {
    errors.comment = 'Review must be at least 10 characters long'
    isValid = false
  }

  if (form.comment.trim().length > 1000) {
    errors.comment = 'Review cannot exceed 1000 characters'
    isValid = false
  }

  return isValid
}

// Submit review
const submitReview = async () => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true

  try {
    // Simulate API call delay
    await new Promise((resolve) => setTimeout(resolve, 500))

    store.addReview({
      gameId: Number(form.gameId),
      score: Number(form.score),
      comment: form.comment.trim(),
      author: form.author || store.state.currentUser?.name || 'Anonymous',
    })

    submitSuccess.value = true

    // Reset form
    form.gameId = store.state.games[0]?.id || 1
    form.score = 5
    form.comment = ''

    // Redirect after success
    setTimeout(() => {
      router.push('/reviews')
    }, 1500)
  } catch (error) {
    console.error('Error submitting review:', error)
    errors.comment =
      'An error occurred while submitting your review. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}

// Get star display
const getStarDisplay = (count) => {
  return '⭐'.repeat(count) + '☆'.repeat(5 - count)
}
</script>

<template>
  <!-- Not Authenticated State -->
  <div v-if="!isAuthenticated" class="alert alert-warning">
    <div class="container">
      <h4>Login Required</h4>
      <p class="mb-2">You must be logged in to submit a review.</p>

      <RouterLink to="/login" class="btn btn-primary btn-sm">
        Log In
      </RouterLink>

      <RouterLink to="/register" class="btn btn-outline-primary btn-sm ms-2">
        Sign Up
      </RouterLink>
    </div>
  </div>

  <!-- Main Form -->
  <template v-else>
    <PageHeader
      title="Write a Game Review"
      text="Share your honest opinion about a game. Your review helps other players make informed decisions."
    />

    <section class="section-band">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <!-- Success Message -->
            <div
              v-if="submitSuccess"
              class="alert alert-success alert-dismissible fade show"
              role="alert"
            >
              <h4 class="alert-heading">✓ Review Submitted!</h4>

              <p>
                Thank you for sharing your thoughts. You'll be redirected to
                reviews shortly...
              </p>
            </div>

            <!-- Review Form -->
            <form
              v-else
              class="content-card p-5"
              @submit.prevent="submitReview"
            >
              <!-- Game Selection -->
              <div class="mb-4">
                <label class="form-label fw-bold" for="gameId">
                  Select a Game <span class="text-danger">*</span>
                </label>

                <select
                  id="gameId"
                  v-model="form.gameId"
                  class="form-select"
                  :class="{ 'is-invalid': errors.gameId }"
                  :disabled="isSubmitting"
                >
                  <option disabled value="">-- Choose a game --</option>

                  <option
                    v-for="game in store.state.games"
                    :key="game.id"
                    :value="game.id"
                  >
                    {{ game.title }}
                  </option>
                </select>

                <div v-if="errors.gameId" class="invalid-feedback d-block">
                  {{ errors.gameId }}
                </div>

                <small
                  v-if="selectedGame"
                  class="text-secondary d-block mt-2"
                >
                  Reviewing:
                  <strong>{{ selectedGame.title }}</strong>
                  ({{ selectedGame.releaseYear }})
                </small>
              </div>

              <!-- Rating Input -->
              <div class="mb-4">
                <label class="form-label fw-bold">
                  Rating <span class="text-danger">*</span>
                </label>

                <div class="rating-selector">
                  <div class="d-flex gap-2 align-items-center">
                    <div class="rating-stars">
                      <button
                        v-for="star in 5"
                        :key="star"
                        type="button"
                        class="star-button"
                        :class="{ active: form.score >= star }"
                        @click="form.score = star"
                        @keydown.enter="form.score = star"
                        @keydown.space="form.score = star"
                        :title="`Rate ${star} out of 5`"
                        :disabled="isSubmitting"
                      >
                        ⭐
                      </button>
                    </div>

                    <span class="badge text-bg-primary fs-6">
                      {{ form.score }}/5
                    </span>
                  </div>
                </div>

                <div v-if="errors.score" class="invalid-feedback d-block mt-2">
                  {{ errors.score }}
                </div>

                <small class="text-secondary d-block mt-2">
                  {{ getStarDisplay(form.score) }}
                </small>
              </div>

              <!-- Review Comment -->
              <div class="mb-4">
                <label class="form-label fw-bold" for="comment">
                  Your Review <span class="text-danger">*</span>

                  <span class="float-end text-secondary small">
                    {{ form.comment.length }}/1000
                  </span>
                </label>

                <textarea
                  id="comment"
                  v-model="form.comment"
                  class="form-control"
                  :class="{ 'is-invalid': errors.comment }"
                  rows="6"
                  placeholder="Share your thoughts about the game. What did you like? What could be improved? Be specific and constructive..."
                  :disabled="isSubmitting"
                  maxlength="1000"
                ></textarea>

                <div v-if="errors.comment" class="invalid-feedback d-block">
                  {{ errors.comment }}
                </div>

                <small class="text-secondary d-block mt-2">
                  Minimum 10 characters, maximum 1000 characters.
                </small>
              </div>

              <!-- Review Guidelines -->
              <div class="alert alert-light mb-4">
                <h6 class="alert-heading mb-2">📋 Review Guidelines</h6>

                <ul class="mb-0 small">
                  <li>Be honest and fair in your assessment</li>
                  <li>
                    Mention specific aspects (gameplay, graphics, story,
                    performance)
                  </li>
                  <li>Avoid spoilers in your review</li>
                  <li>Use respectful language</li>
                  <li>No spam or promotional content</li>
                </ul>
              </div>

              <!-- Action Buttons -->
              <div class="d-flex gap-2">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="isSubmitting"
                >
                  <span
                    v-if="isSubmitting"
                    class="spinner-border spinner-border-sm me-2"
                  ></span>

                  {{
                    isSubmitting ? 'Submitting...' : 'Submit Review'
                  }}
                </button>

                <RouterLink
                  to="/reviews"
                  class="btn btn-outline-secondary"
                >
                  Cancel
                </RouterLink>
              </div>
            </form>
          </div>
        </div>

        <!-- Review Tips -->
        <div class="row mt-5">
          <div class="col-md-6">
            <div class="content-card p-3">
              <h5>✅ Do's</h5>

              <ul class="small mb-0">
                <li>Include details about gameplay</li>
                <li>Mention technical performance</li>
                <li>Comment on value for money</li>
                <li>Share specific experiences</li>
              </ul>
            </div>
          </div>

          <div class="col-md-6">
            <div class="content-card p-3">
              <h5>❌ Don'ts</h5>

              <ul class="small mb-0">
                <li>Major story spoilers</li>
                <li>Offensive language</li>
                <li>Spam or promotional links</li>
                <li>Unrelated criticism</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
  </template>
</template>

<style scoped>
.content-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e9ecef;
}

.form-label {
  color: #212529;
  margin-bottom: 0.5rem;
}

.form-control,
.form-select {
  border-color: #dee2e6;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.form-control:focus,
.form-select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.rating-stars {
  display: flex;
  gap: 0.5rem;
}

.star-button {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  padding: 0.25rem;
  opacity: 0.3;
  transition: all 0.2s ease;
}

.star-button:hover,
.star-button.active {
  opacity: 1;
  transform: scale(1.1);
}

.star-button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.form-control.is-invalid,
.form-select.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  color: #dc3545;
  font-size: 0.875rem;
}

.alert {
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.spinner-border {
  border-width: 0.25em;
}

.text-danger {
  color: #dc3545;
}

.badge {
  font-weight: 600;
  padding: 0.5rem 0.75rem;
}
</style>