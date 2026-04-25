import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)

  async function login(email, password) {
    const { data } = await api.post('/login', { email, password })
    token.value = data.token
    user.value = data.user
    localStorage.setItem('token', data.token)
  }

  async function register(name, email, password, password_confirmation) {
    const { data } = await api.post('/register', { name, email, password, password_confirmation })
    token.value = data.token
    user.value = data.user
    localStorage.setItem('token', data.token)
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      const { data } = await api.get('/me')
      user.value = data
    } catch {
      logout()
    }
  }

  function logout() {
    api.post('/logout').catch(() => {})
    token.value = null
    user.value = null
    localStorage.removeItem('token')
  }

  async function updateProfile(payload) {
    const { data } = await api.put('/me', payload)
    user.value = data
    return data
  }

  async function uploadAvatar(file) {
    const form = new FormData()
    form.append('avatar', file)
    const { data } = await api.post('/me/avatar', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    user.value = data
    return data
  }

  const isLoggedIn = () => !!token.value

  return { user, token, login, register, fetchUser, logout, isLoggedIn, updateProfile, uploadAvatar }
})
