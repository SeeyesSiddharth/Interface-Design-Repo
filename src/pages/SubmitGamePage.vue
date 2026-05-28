<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import { useAppStore } from '../stores/appStore'

const store = useAppStore()
const router = useRouter()
const error = ref('')

const form = reactive({
  title: '',
  studio: '',
  genre: 'Action',
  releaseYear: 2026,
  platform: 'Windows',
  rating: 4,
  summary: '',
  tags: 'New entry, Student added',
  coverTheme: 'cover-rift',
  min: { cpu: '', gpu: '', ram: 8, storage: 20, os: 'Windows 10 64-bit' },
  rec: { cpu: '', gpu: '', ram: 16, storage: 40, os: 'Windows 11 64-bit' },
  notes: '',
})

function submit() {
  error.value = ''
  if (!store.isAuthenticated.value) {
    error.value = 'Login before submitting catalogue content.'
    return
  }
  if (form.title.trim().length < 3 || form.summary.trim().length < 20) {
    error.value = 'Add a title and a useful summary of at least 20 characters.'
    return
  }
  if (!form.min.cpu || !form.min.gpu || !form.rec.cpu || !form.rec.gpu) {
    error.value = 'CPU and GPU fields are required for both minimum and recommended specs.'
    return
  }
  store.addGame({
    ...form,
    tags: form.tags.split(',').map((tag) => tag.trim()).filter(Boolean),
    min: { ...form.min },
    rec: { ...form.rec },
  })
  router.push('/catalogue')
}
</script>

<template>
  <PageHeader title="Submit Game" text="A validated content creation form for authorised users." />

  <section class="section-band">
    <div class="container">
      <form class="content-card p-4" @submit.prevent="submit">
        <div v-if="error" class="alert alert-warning">{{ error }}</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="title">Game title</label>
            <input id="title" v-model="form.title" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="studio">Studio</label>
            <input id="studio" v-model="form.studio" class="form-control" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="genre">Genre</label>
            <input id="genre" v-model="form.genre" class="form-control" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="releaseYear">Release year</label>
            <input id="releaseYear" v-model.number="form.releaseYear" class="form-control" type="number" min="1980" max="2035" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="platform">Platforms</label>
            <input id="platform" v-model="form.platform" class="form-control" placeholder="Windows, macOS" required />
          </div>
          <div class="col-12">
            <label class="form-label" for="summary">Summary</label>
            <textarea id="summary" v-model="form.summary" class="form-control" rows="3" required></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="minCpu">Minimum CPU</label>
            <input id="minCpu" v-model="form.min.cpu" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="minGpu">Minimum GPU</label>
            <input id="minGpu" v-model="form.min.gpu" class="form-control" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="minRam">Minimum RAM</label>
            <input id="minRam" v-model.number="form.min.ram" class="form-control" type="number" min="2" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="minStorage">Minimum storage</label>
            <input id="minStorage" v-model.number="form.min.storage" class="form-control" type="number" min="1" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="minOs">Minimum OS</label>
            <input id="minOs" v-model="form.min.os" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="recCpu">Recommended CPU</label>
            <input id="recCpu" v-model="form.rec.cpu" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="recGpu">Recommended GPU</label>
            <input id="recGpu" v-model="form.rec.gpu" class="form-control" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="recRam">Recommended RAM</label>
            <input id="recRam" v-model.number="form.rec.ram" class="form-control" type="number" min="2" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="recStorage">Recommended storage</label>
            <input id="recStorage" v-model.number="form.rec.storage" class="form-control" type="number" min="1" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="recOs">Recommended OS</label>
            <input id="recOs" v-model="form.rec.os" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="tags">Tags</label>
            <input id="tags" v-model="form.tags" class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="notes">Notes</label>
            <input id="notes" v-model="form.notes" class="form-control" />
          </div>
        </div>
        <button class="btn btn-accent mt-4" type="submit">Create catalogue entry</button>
      </form>
    </div>
  </section>
</template>
