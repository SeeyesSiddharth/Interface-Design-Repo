import { computed, reactive } from 'vue'
import { seedGames, seedReviews } from '../data/games'

// ── API base URL — update to match your server path ──────────
const API_BASE = import.meta.env.BASE_URL + 'resources/apis.php'

// ── Auth is kept entirely in localStorage (no server session) ─
const AUTH_KEY = 'gamebench-user-v1'

function loadCurrentUser() {
  try {
    const saved = localStorage.getItem(AUTH_KEY)
    return saved ? JSON.parse(saved) : null
  } catch {
    return null
  }
}

function persistUser(user) {
  if (user) {
    localStorage.setItem(AUTH_KEY, JSON.stringify(user))
  } else {
    localStorage.removeItem(AUTH_KEY)
  }
}

// ── snake_case <-> camelCase conversion ───────────────────────

function toCamel(str) {
  return str.replace(/_([a-z])/g, (_, c) => c.toUpperCase())
}

function toSnake(str) {
  return str.replace(/[A-Z]/g, c => '_' + c.toLowerCase())
}

function keysToCamel(obj) {
  if (Array.isArray(obj)) return obj.map(keysToCamel)
  if (obj !== null && typeof obj === 'object') {
    return Object.fromEntries(
      Object.entries(obj).map(([k, v]) => [toCamel(k), keysToCamel(v)])
    )
  }
  return obj
}

function keysToSnake(obj) {
  if (Array.isArray(obj)) return obj.map(keysToSnake)
  if (obj !== null && typeof obj === 'object') {
    return Object.fromEntries(
      Object.entries(obj).map(([k, v]) => [toSnake(k), keysToSnake(v)])
    )
  }
  return obj
}

// ── Shared state (mirrors the original store shape) ───────────
const state = reactive({
  games: [],
  reviews: [],
  currentUser: loadCurrentUser(),
  votes: {},
  apiError: '',
})

function normaliseGame(game) {
  return {
    ...game,
    id: Number(game.id),
    rating: Number(game.rating || 0),
    likes: Number(game.likes || 0),
    releaseYear: Number(game.releaseYear || game.release_year || 0),
    platform: Array.isArray(game.platform)
      ? game.platform
      : String(game.platform || '').split(',').map(s => s.trim()).filter(Boolean),
    tags: Array.isArray(game.tags)
      ? game.tags
      : String(game.tags || '').split(',').map(s => s.trim()).filter(Boolean),
    min: game.min || {
      cpu: game.minCpu || game.min_cpu || '',
      gpu: game.minGpu || game.min_gpu || '',
      ram: Number(game.minRam || game.min_ram || 0),
      storage: Number(game.minStorage || game.min_storage || 0),
      os: game.minOs || game.min_os || '',
    },
    rec: game.rec || {
      cpu: game.recCpu || game.rec_cpu || '',
      gpu: game.recGpu || game.rec_gpu || '',
      ram: Number(game.recRam || game.rec_ram || 0),
      storage: Number(game.recStorage || game.rec_storage || 0),
      os: game.recOs || game.rec_os || '',
    },
  }
}

function normaliseReview(review) {
  return {
    ...review,
    id: Number(review.id),
    score: Number(review.score || 0),
    gameId: Number(review.gameId || review.game_id),
  }
}

// ── Generic REST helpers ──────────────────────────────────────
// Responses are converted to camelCase, request bodies to snake_case

async function apiGet(table, field = '', value = '') {
  const snakeField = field ? toSnake(field) : ''
  const path = snakeField ? `${API_BASE}/${table}/${snakeField}/${encodeURIComponent(value)}` : `${API_BASE}/${table}`
  console.log('apiGet path:', path)  // add this
  const res = await fetch(path)
  if (!res.ok) throw new Error(`GET ${path} failed: ${res.status}`)
  return res.json().then(keysToCamel)
}

async function apiPost(table, body) {
  const res = await fetch(`${API_BASE}/${table}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(keysToSnake(body)),
  })
  if (!res.ok) throw new Error(`POST ${table} failed: ${res.status}`)
  return res.json().then(keysToCamel)
}

async function apiPut(table, field, value, body) {
  const snakeField = toSnake(field)
  const path = `${API_BASE}/${table}/${snakeField}/${encodeURIComponent(value)}`
  const res = await fetch(path, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(keysToSnake(body)),
  })
  if (!res.ok) throw new Error(`PUT ${path} failed: ${res.status}`)
  return res.json().then(keysToCamel)
}

async function apiDelete(table, field, value) {
  const snakeField = toSnake(field)
  const path = `${API_BASE}/${table}/${snakeField}/${encodeURIComponent(value)}`
  const res = await fetch(path, { method: 'DELETE' })
  if (!res.ok) throw new Error(`DELETE ${path} failed: ${res.status}`)
  return res.json().then(keysToCamel)
}

// ── Slug helper ───────────────────────────────────────────────

function makeSlug(title) {
  return title
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '')
}

// ── Composable ───────────────────────────────────────────────

export function useAppStore() {
  const isAuthenticated = computed(() => Boolean(state.currentUser))
  const isAdmin = computed(() => state.currentUser?.role === 'admin')

  // ── Data loading (call once from App.vue) ──────────────────

  async function loadGames() {
    try {
      const data = await apiGet('games')
      state.games = data.length ? data.map(normaliseGame) : seedGames.map(normaliseGame)
      state.apiError = data.length ? '' : 'The backend returned no games, so demo games are being shown.'
    } catch (error) {
      console.warn('Using demo games because the backend API is unavailable.', error)
      state.games = seedGames.map(normaliseGame)
      state.apiError = 'The backend API is unavailable, so demo games are being shown.'
    }
  }

  async function loadReviews() {
    try {
      const data = await apiGet('reviews')
      state.reviews = data.length ? data.map(normaliseReview) : seedReviews.map(normaliseReview)
    } catch (error) {
      console.warn('Using demo reviews because the backend API is unavailable.', error)
      state.reviews = seedReviews.map(normaliseReview)
    }
  }

  // ── Auth ───────────────────────────────────────────────────

  async function login(emailOrPayload, password) {
    const email = typeof emailOrPayload === 'object' ? emailOrPayload.email : emailOrPayload
    const pass  = typeof emailOrPayload === 'object' ? emailOrPayload.password : password

    const rows = await apiGet('users', 'email', email)
    const user = rows[0]
    if (!user) return { ok: false, message: 'No account found with this email.' }
    if (user.password !== pass) return { ok: false, message: 'Incorrect password.' }
    const profile = { id: Number(user.id), name: user.name, email: user.email, role: user.role, created: user.createdAt}
    state.currentUser = profile
    persistUser(profile)
    return { ok: true }
  }

  async function register(payload) {
    const existing = await apiGet('users', 'email', payload.email)
    if (existing.length > 0) {
      return { ok: false, message: 'An account with this email already exists.' }
    }
    const allUsers = await apiGet('users')
    const role = allUsers.length === 0 ? 'admin' : 'member'
    const result = await apiPost('users', { ...payload, role })
    
    // Fetch the newly created user to get created_at
    const newUserRows = await apiGet('users', 'id', result.id)
    const newUser = newUserRows[0]
    
    const profile = { id: result.id, name: payload.name, email: payload.email, role, created: newUser?.createdAt || new Date().toISOString() }
    state.currentUser = profile
    persistUser(profile)
    return { ok: true }
  }

  function logout() {
    state.currentUser = null
    persistUser(null)
  }

  function updateCurrentUser(fields) {
    state.currentUser = { ...state.currentUser, ...fields }
    persistUser(state.currentUser)
  }

  // ── Games ──────────────────────────────────────────────────

  async function addGame(payload) {
    await apiPost('games', {
      ...payload,
      slug: makeSlug(payload.title),
      releaseYear: Number(payload.releaseYear),
      rating: Number(payload.rating || 0),
      likes: 0,
      coverTheme: payload.coverTheme || 'cover-space',
      platform: Array.isArray(payload.platform)
        ? payload.platform
        : String(payload.platform).split(',').map(s => s.trim()),
    })
    await loadGames()
  }

  async function updateGame(id, payload) {
    await apiPut('games', 'id', id, {
      ...payload,
      slug: makeSlug(payload.title),
      releaseYear: Number(payload.releaseYear),
      rating: Number(payload.rating),
    })
    await loadGames()
  }

  async function deleteGame(id) {
    await apiDelete('games', 'id', id)
    state.games = state.games.filter(g => g.id !== id)
    state.reviews = state.reviews.filter(r => r.gameId !== id)
  }

  async function toggleLike(gameId) {
    const key = `${state.currentUser?.email || 'guest'}-${gameId}`
    const game = state.games.find(g => g.id === gameId)
    if (!game) return

    const newLikes = state.votes[key]
      ? Math.max(0, game.likes - 1)
      : game.likes + 1

    await apiPut('games', 'id', gameId, { likes: newLikes })
    game.likes = newLikes
    if (state.votes[key]) {
      delete state.votes[key]
    } else {
      state.votes[key] = true
    }
  }

  // ── Reviews ────────────────────────────────────────────────

  async function addReview(payload) {
    await apiPost('reviews', { ...payload, score: Number(payload.score) })
    await loadReviews()
  }

  async function updateReview(id, fields) {
    await apiPut('reviews', 'id', id, fields)
    await loadReviews()
  }

  async function deleteReview(id) {
    await apiDelete('reviews', 'id', id)
    state.reviews = state.reviews.filter(r => r.id !== id)
  }

  return {
    state,
    isAuthenticated,
    isAdmin,

    // Data loading
    loadGames,
    loadReviews,

    // Auth
    login,
    register,
    logout,
    updateCurrentUser,

    // Games
    addGame,
    updateGame,
    deleteGame,
    toggleLike,

    // Reviews
    addReview,
    updateReview,
    deleteReview,
  }
}
