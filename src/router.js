import { createRouter, createWebHistory } from 'vue-router'
import HomePage from './pages/HomePage.vue'
import CataloguePage from './pages/CataloguePage.vue'
import GameDetailPage from './pages/GameDetailPage.vue'
import ComparePage from './pages/ComparePage.vue'
import RequirementsGuidePage from './pages/RequirementsGuidePage.vue'
import CompatibilityCheckerPage from './pages/CompatibilityCheckerPage.vue'
import ReviewsPage from './pages/ReviewsPage.vue'
import LoginPage from './pages/LoginPage.vue'
import RegisterPage from './pages/RegisterPage.vue'
import ProfilePage from './pages/ProfilePage.vue'
import SubmitGamePage from './pages/SubmitGamePage.vue'
import SubmitReviewPage from './pages/SubmitReviewPage.vue'
import AdminPage from './pages/AdminPage.vue'
import TeamPlanPage from './pages/TeamPlanPage.vue'
import NotFoundPage from './pages/NotFoundPage.vue'
import AnalyticsDashboardPage from './pages/AnalyticsDashboardPage.vue'

const routes = [
  { path: '/', name: 'home', component: HomePage },
  { path: '/catalogue', name: 'catalogue', component: CataloguePage },
  { path: '/games/:slug', name: 'game-detail', component: GameDetailPage, props: true },
  { path: '/compare', name: 'compare', component: ComparePage },
  { path: '/requirements-guide', name: 'requirements-guide', component: RequirementsGuidePage },
  { path: '/compatibility-checker', name: 'compatibility-checker', component: CompatibilityCheckerPage },
  { path: '/reviews', name: 'reviews', component: ReviewsPage },
  { path: '/login', name: 'login', component: LoginPage },
  { path: '/register', name: 'register', component: RegisterPage },
  { path: '/profile', name: 'profile', component: ProfilePage },
  { path: '/submit-game', name: 'submit-game', component: SubmitGamePage },
  { path: '/admin', name: 'admin', component: AdminPage },
  { path: '/team-plan', name: 'team-plan', component: TeamPlanPage },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundPage },
  { path: '/submit-review', name: 'submit-review', component: SubmitReviewPage },
  { path: '/analytics', name: 'analytics', component: AnalyticsDashboardPage },
]

export default createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})
