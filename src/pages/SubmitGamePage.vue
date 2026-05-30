<script setup>
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()
const router = useRouter()

const isAuthenticated = computed(() => store.isAuthenticated.value)
const isSubmitting = ref(false)
const submitSuccess = ref(false)

const form = reactive({
  title: '',
  studio: '',
  genre: 'Action',
  releaseYear: 2026,
  platform: 'Windows',
  summary: '',
  tags: 'New entry, Student added',
  coverTheme: 'cover-rift',
  min: { cpu: '', gpu: '', ram: 8, storage: 20, os: 'Windows 10 64-bit' },
  rec: { cpu: '', gpu: '', ram: 16, storage: 40, os: 'Windows 11 64-bit' },
  notes: '',
})

const errors = reactive({
  title: '',
  studio: '',
  genre: '',
  releaseYear: '',
  platform: '',
  summary: '',
  minCpu: '',
  minGpu: '',
  minRam: '',
  minStorage: '',
  minOs: '',
  recCpu: '',
  recGpu: '',
  recRam: '',
  recStorage: '',
  recOs: '',
})

function clearErrors() {
  Object.keys(errors).forEach(k => errors[k] = '')
}

function validateForm() {
  clearErrors()
  let valid = true

  if (form.title.trim().length < 3) {
    errors.title = 'Title must be at least 3 characters.'
    valid = false
  }
  if (form.studio.trim().length < 2) {
    errors.studio = 'Studio name must be at least 2 characters.'
    valid = false
  }
  if (!form.genre.trim()) {
    errors.genre = 'Genre is required.'
    valid = false
  }
  const year = Number(form.releaseYear)
  if (!year || year < 1980 || year > 2035) {
    errors.releaseYear = 'Release year must be between 1980 and 2035.'
    valid = false
  }
  if (!form.platform.trim()) {
    errors.platform = 'Enter at least one platform.'
    valid = false
  }
  if (form.summary.trim().length < 20) {
    errors.summary = 'Summary must be at least 20 characters.'
    valid = false
  }
  if (form.summary.trim().length > 500) {
    errors.summary = 'Summary cannot exceed 500 characters.'
    valid = false
  }

  if (!form.min.cpu.trim()) { errors.minCpu = 'Minimum CPU is required.'; valid = false }
  if (!form.min.gpu.trim()) { errors.minGpu = 'Minimum GPU is required.'; valid = false }
  if (!form.min.ram || form.min.ram < 1) { errors.minRam = 'Enter a valid RAM amount.'; valid = false }
  if (!form.min.storage || form.min.storage < 1) { errors.minStorage = 'Enter a valid storage amount.'; valid = false }
  if (!form.min.os.trim()) { errors.minOs = 'Minimum OS is required.'; valid = false }

  if (!form.rec.cpu.trim()) { errors.recCpu = 'Recommended CPU is required.'; valid = false }
  if (!form.rec.gpu.trim()) { errors.recGpu = 'Recommended GPU is required.'; valid = false }
  if (!form.rec.ram || form.rec.ram < 1) { errors.recRam = 'Enter a valid RAM amount.'; valid = false }
  if (Number(form.rec.ram) < Number(form.min.ram)) { errors.recRam = 'Recommended RAM should be ≥ minimum RAM.'; valid = false }
  if (!form.rec.storage || form.rec.storage < 1) { errors.recStorage = 'Enter a valid storage amount.'; valid = false }
  if (!form.rec.os.trim()) { errors.recOs = 'Recommended OS is required.'; valid = false }

  return valid
}

async function submit() {
  if (!validateForm()) return

  isSubmitting.value = true
  try {
    await store.addGame({
      ...form,
      tags: form.tags.split(',').map(t => t.trim()).filter(Boolean),
      min: { ...form.min },
      rec: { ...form.rec },
    })
    submitSuccess.value = true
    setTimeout(() => router.push('/catalogue'), 1500)
  } catch (e) {
    console.error(e)
    errors.title = 'Something went wrong submitting the game. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <!-- Not authenticated -->
  <div v-if="!isAuthenticated" class="alert alert-warning m-4">
    <div class="container">
      <h4>Login Required</h4>
      <p class="mb-2">You must be logged in to submit a game.</p>
      <RouterLink to="/login" class="btn btn-primary btn-sm">Log In</RouterLink>
      <RouterLink to="/register" class="btn btn-outline-primary btn-sm ms-2">Sign Up</RouterLink>
    </div>
  </div>

  <template v-else>
    <PageHeader title="Submit Game" text="A validated content creation form for authorised users." />

    <section class="section-band">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-9">

            <!-- Success -->
            <div v-if="submitSuccess" class="alert alert-success" role="alert">
              <h4 class="alert-heading">✓ Game Submitted!</h4>
              <p>The game has been added to the catalogue. Redirecting you now...</p>
            </div>

            <!-- Form -->
            <form v-else class="content-card p-5" @submit.prevent="submit">

              <!-- Basic Info -->
              <h5 class="section-heading mb-3">Basic Information</h5>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label fw-bold" for="title">Game Title <span class="text-danger">*</span></label>
                  <input id="title" v-model="form.title" class="form-control"
                    :class="{ 'is-invalid': errors.title }" :disabled="isSubmitting" />
                  <div v-if="errors.title" class="invalid-feedback d-block">{{ errors.title }}</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold" for="studio">Studio <span class="text-danger">*</span></label>
                  <input id="studio" v-model="form.studio" class="form-control"
                    :class="{ 'is-invalid': errors.studio }" :disabled="isSubmitting" />
                  <div v-if="errors.studio" class="invalid-feedback d-block">{{ errors.studio }}</div>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-bold" for="genre">Genre <span class="text-danger">*</span></label>
                  <input id="genre" v-model="form.genre" class="form-control"
                    :class="{ 'is-invalid': errors.genre }" :disabled="isSubmitting" />
                  <div v-if="errors.genre" class="invalid-feedback d-block">{{ errors.genre }}</div>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-bold" for="releaseYear">Release Year <span class="text-danger">*</span></label>
                  <input id="releaseYear" v-model.number="form.releaseYear" type="number"
                    min="1980" max="2035" class="form-control"
                    :class="{ 'is-invalid': errors.releaseYear }" :disabled="isSubmitting" />
                  <div v-if="errors.releaseYear" class="invalid-feedback d-block">{{ errors.releaseYear }}</div>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-bold" for="platform">Platforms <span class="text-danger">*</span></label>
                  <input id="platform" v-model="form.platform" class="form-control"
                    placeholder="Windows, macOS" :class="{ 'is-invalid': errors.platform }" :disabled="isSubmitting" />
                  <div v-if="errors.platform" class="invalid-feedback d-block">{{ errors.platform }}</div>
                  <small class="text-secondary">Comma-separated</small>
                </div>

                <div class="col-12">
                  <label class="form-label fw-bold" for="summary">
                    Summary <span class="text-danger">*</span>
                    <span class="float-end text-secondary small fw-normal">{{ form.summary.length }}/500</span>
                  </label>
                  <textarea id="summary" v-model="form.summary" class="form-control"
                    :class="{ 'is-invalid': errors.summary }"
                    rows="3" maxlength="500"
                    placeholder="Describe the game in at least 20 characters..."
                    :disabled="isSubmitting"></textarea>
                  <div v-if="errors.summary" class="invalid-feedback d-block">{{ errors.summary }}</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold" for="tags">Tags</label>
                  <input id="tags" v-model="form.tags" class="form-control"
                    placeholder="Co-op, Low spec, HDR" :disabled="isSubmitting" />
                  <small class="text-secondary">Comma-separated</small>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold" for="notes">Notes</label>
                  <input id="notes" v-model="form.notes" class="form-control"
                    placeholder="Performance tips, caveats..." :disabled="isSubmitting" />
                </div>
              </div>

              <!-- Minimum Specs -->
              <h5 class="section-heading mb-3">Minimum Requirements</h5>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label fw-bold" for="minCpu">CPU <span class="text-danger">*</span></label>
                  <input id="minCpu" v-model="form.min.cpu" class="form-control"
                    :class="{ 'is-invalid': errors.minCpu }"
                    placeholder="Intel i5-8400 / Ryzen 5 2600" :disabled="isSubmitting" />
                  <div v-if="errors.minCpu" class="invalid-feedback d-block">{{ errors.minCpu }}</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold" for="minGpu">GPU <span class="text-danger">*</span></label>
                  <input id="minGpu" v-model="form.min.gpu" class="form-control"
                    :class="{ 'is-invalid': errors.minGpu }"
                    placeholder="GTX 1660 / RX 590" :disabled="isSubmitting" />
                  <div v-if="errors.minGpu" class="invalid-feedback d-block">{{ errors.minGpu }}</div>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-bold" for="minRam">RAM (GB) <span class="text-danger">*</span></label>
                  <input id="minRam" v-model.number="form.min.ram" type="number" min="1"
                    class="form-control" :class="{ 'is-invalid': errors.minRam }" :disabled="isSubmitting" />
                  <div v-if="errors.minRam" class="invalid-feedback d-block">{{ errors.minRam }}</div>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-bold" for="minStorage">Storage (GB) <span class="text-danger">*</span></label>
                  <input id="minStorage" v-model.number="form.min.storage" type="number" min="1"
                    class="form-control" :class="{ 'is-invalid': errors.minStorage }" :disabled="isSubmitting" />
                  <div v-if="errors.minStorage" class="invalid-feedback d-block">{{ errors.minStorage }}</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold" for="minOs">OS <span class="text-danger">*</span></label>
                  <input id="minOs" v-model="form.min.os" class="form-control"
                    :class="{ 'is-invalid': errors.minOs }"
                    placeholder="Windows 10 64-bit" :disabled="isSubmitting" />
                  <div v-if="errors.minOs" class="invalid-feedback d-block">{{ errors.minOs }}</div>
                </div>
              </div>

              <!-- Recommended Specs -->
              <h5 class="section-heading mb-3">Recommended Requirements</h5>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label fw-bold" for="recCpu">CPU <span class="text-danger">*</span></label>
                  <input id="recCpu" v-model="form.rec.cpu" class="form-control"
                    :class="{ 'is-invalid': errors.recCpu }"
                    placeholder="Intel i7-12700 / Ryzen 7 5800X" :disabled="isSubmitting" />
                  <div v-if="errors.recCpu" class="invalid-feedback d-block">{{ errors.recCpu }}</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold" for="recGpu">GPU <span class="text-danger">*</span></label>
                  <input id="recGpu" v-model="form.rec.gpu" class="form-control"
                    :class="{ 'is-invalid': errors.recGpu }"
                    placeholder="RTX 3070 / RX 6800" :disabled="isSubmitting" />
                  <div v-if="errors.recGpu" class="invalid-feedback d-block">{{ errors.recGpu }}</div>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-bold" for="recRam">RAM (GB) <span class="text-danger">*</span></label>
                  <input id="recRam" v-model.number="form.rec.ram" type="number" min="1"
                    class="form-control" :class="{ 'is-invalid': errors.recRam }" :disabled="isSubmitting" />
                  <div v-if="errors.recRam" class="invalid-feedback d-block">{{ errors.recRam }}</div>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-bold" for="recStorage">Storage (GB) <span class="text-danger">*</span></label>
                  <input id="recStorage" v-model.number="form.rec.storage" type="number" min="1"
                    class="form-control" :class="{ 'is-invalid': errors.recStorage }" :disabled="isSubmitting" />
                  <div v-if="errors.recStorage" class="invalid-feedback d-block">{{ errors.recStorage }}</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold" for="recOs">OS <span class="text-danger">*</span></label>
                  <input id="recOs" v-model="form.rec.os" class="form-control"
                    :class="{ 'is-invalid': errors.recOs }"
                    placeholder="Windows 11 64-bit" :disabled="isSubmitting" />
                  <div v-if="errors.recOs" class="invalid-feedback d-block">{{ errors.recOs }}</div>
                </div>
              </div>

              <!-- Guidelines -->
              <div class="alert alert-light mb-4">
                <h6 class="alert-heading mb-2">📋 Submission Guidelines</h6>
                <ul class="mb-0 small">
                  <li>Use accurate, sourced system requirements</li>
                  <li>Write a clear, spoiler-free summary</li>
                  <li>List all platforms the game officially supports</li>
                  <li>Recommended specs should always meet or exceed minimum specs</li>
                </ul>
              </div>

              <!-- Actions -->
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent" :disabled="isSubmitting">
                  <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                  {{ isSubmitting ? 'Submitting...' : 'Create Catalogue Entry' }}
                </button>
                <RouterLink to="/catalogue" class="btn btn-outline-secondary">Cancel</RouterLink>
              </div>

            </form>
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

.section-heading {
  font-size: 1rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6c757d;
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 0.5rem;
}

.form-label {
  color: #212529;
  margin-bottom: 0.5rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-control.is-invalid {
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
</style>