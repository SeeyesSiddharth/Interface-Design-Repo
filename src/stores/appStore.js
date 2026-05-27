import { computed, reactive } from 'vue'
import { seedGames, seedReviews } from '../data/games'

const STORAGE_KEY = 'gamebench-state-v1'

function loadState() {
  const freshState = () => ({
    games: seedGames,
    reviews: seedReviews,
    currentUser: null,
    users: [{ name: 'Demo Member', email: 'demo@gamebench.test', password: 'password123', role: 'member' }],
    votes: {},
  })

  const saved = localStorage.getItem(STORAGE_KEY)
  if (!saved) {
    return freshState()
  }

  try {
    const parsed = JSON.parse(saved)
    return {
      games: parsed.games?.length ? parsed.games : seedGames,
      reviews: parsed.reviews?.length ? parsed.reviews : seedReviews,
      currentUser: parsed.currentUser || null,
      users: parsed.users?.length ? parsed.users : [],
      votes: parsed.votes || {},
    }
  } catch {
    return freshState()
  }
}

const state = reactive(loadState())

function persist() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state))
}

function nextId(collection) {
  return collection.length ? Math.max(...collection.map((item) => item.id)) + 1 : 1
}

function makeSlug(title) {
  return title
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '')
}

export function useAppStore() {
  const isAuthenticated = computed(() => Boolean(state.currentUser))
  const isAdmin = computed(() => state.currentUser?.role === 'admin')

  function login(email, password) {
    const user = state.users.find((entry) => entry.email === email && entry.password === password)
    if (!user) return false
    state.currentUser = { name: user.name, email: user.email, role: user.role }
    persist()
    return true
  }

  function register(payload) {
    if (state.users.some((user) => user.email === payload.email)) {
      return { ok: false, message: 'An account with this email already exists.' }
    }

    const role = state.users.length === 0 ? 'admin' : 'member'
    state.users.push({ ...payload, role })
    state.currentUser = { name: payload.name, email: payload.email, role }
    persist()
    return { ok: true }
  }

  function logout() {
    state.currentUser = null
    persist()
  }

  function addGame(payload) {
    const id = nextId(state.games)
    state.games.push({
      ...payload,
      id,
      slug: makeSlug(payload.title),
      likes: 0,
      rating: Number(payload.rating || 0),
      releaseYear: Number(payload.releaseYear),
      coverTheme: payload.coverTheme || 'cover-space',
      platform: Array.isArray(payload.platform) ? payload.platform : String(payload.platform).split(',').map((item) => item.trim()),
    })
    persist()
  }

  function updateGame(id, payload) {
    const index = state.games.findIndex((game) => game.id === id)
    if (index === -1) return
    state.games[index] = {
      ...state.games[index],
      ...payload,
      slug: makeSlug(payload.title || state.games[index].title),
      releaseYear: Number(payload.releaseYear || state.games[index].releaseYear),
      rating: Number(payload.rating || state.games[index].rating),
    }
    persist()
  }

  function deleteGame(id) {
    state.games = state.games.filter((game) => game.id !== id)
    state.reviews = state.reviews.filter((review) => review.gameId !== id)
    persist()
  }

  function toggleLike(gameId) {
    const key = `${state.currentUser?.email || 'guest'}-${gameId}`
    const game = state.games.find((entry) => entry.id === gameId)
    if (!game) return

    if (state.votes[key]) {
      game.likes = Math.max(0, game.likes - 1)
      delete state.votes[key]
    } else {
      game.likes += 1
      state.votes[key] = true
    }
    persist()
  }

  function addReview(payload) {
    state.reviews.unshift({ id: nextId(state.reviews), ...payload, score: Number(payload.score) })
    persist()
  }

  function deleteReview(id) {
    state.reviews = state.reviews.filter((review) => review.id !== id)
    persist()
  }

  function resetDemoData() {
    state.games = seedGames.map((game) => ({ ...game, min: { ...game.min }, rec: { ...game.rec } }))
    state.reviews = seedReviews.map((review) => ({ ...review }))
    state.votes = {}
    persist()
  }

  return {
    state,
    isAuthenticated,
    isAdmin,
    login,
    register,
    logout,
    addGame,
    updateGame,
    deleteGame,
    toggleLike,
    addReview,
    deleteReview,
    resetDemoData,
  }
}
