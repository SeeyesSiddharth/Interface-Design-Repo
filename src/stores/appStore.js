import { computed, reactive } from 'vue'

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
})

// ── Generic REST helpers ──────────────────────────────────────
// Responses are converted to camelCase, request bodies to snake_case

async function apiGet(table, field = '', value = '') {
  const snakeField = field ? toSnake(field) : ''
  const path = snakeField ? `${API_BASE}/${table}/${snakeField}/${encodeURIComponent(value)}` : `${API_BASE}/${table}`
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
    const data = await apiGet('games')
    state.games = data.map(g => ({ ...g, rating: Number(g.rating), likes: Number(g.likes) }))
  }

  async function loadReviews() {
    const data = await apiGet('reviews')
    state.reviews = data.map(r => ({ ...r, score: Number(r.score), gameId: Number(r.gameId) }))
  }

  // ── Auth ───────────────────────────────────────────────────

  async function login(email, password) {
    const rows = await apiGet('users', 'email', email)
    const user = rows[0]
    if (!user || user.password !== password) return false
    const profile = { id: Number(user.id), name: user.name, email: user.email, role: user.role }
    state.currentUser = profile
    persistUser(profile)
    return true
  }

  async function register(payload) {
    const existing = await apiGet('users', 'email', payload.email)
    if (existing.length > 0) {
      return { ok: false, message: 'An account with this email already exists.' }
    }
    const allUsers = await apiGet('users')
    const role = allUsers.length === 0 ? 'admin' : 'member'
    const result = await apiPost('users', { ...payload, role })
    const profile = { id: result.id, name: payload.name, email: payload.email, role }
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