import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { guest: true } },
    { path: '/register', name: 'register', component: () => import('@/views/RegisterView.vue'), meta: { guest: true } },
    { path: '/', name: 'home', component: () => import('@/views/HomeView.vue'), meta: { auth: true } },
    { path: '/join/:code', name: 'join', component: () => import('@/views/JoinView.vue'), meta: { auth: true } },
    { path: '/house/:id/lobby', name: 'lobby', component: () => import('@/views/LobbyView.vue'), meta: { auth: true } },
    { path: '/house/:id/setup', name: 'setup', component: () => import('@/views/SetupView.vue'), meta: { auth: true } },
    { path: '/house/:id/game', name: 'game', component: () => import('@/views/GameView.vue'), meta: { auth: true } },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.user && auth.token) {
    await auth.fetchUser()
  }

  if (to.meta.auth && !auth.isLoggedIn()) return { name: 'login' }
  if (to.meta.guest && auth.isLoggedIn()) return { name: 'home' }
})

export default router
