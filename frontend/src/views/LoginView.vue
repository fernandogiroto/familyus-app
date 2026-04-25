<template>
  <div class="page">
    <div class="card">
      <div class="logo">FamilyUs 🏡</div>
      <p class="subtitle">Entre na sua conta</p>

      <form @submit.prevent="submit" class="gap-stack">
        <div class="input-group">
          <label>Email</label>
          <input v-model="form.email" type="email" placeholder="seu@email.com" required />
        </div>
        <div class="input-group">
          <label>Senha</label>
          <input v-model="form.password" type="password" placeholder="••••••" required />
        </div>

        <p v-if="error" style="color:#f87171;font-size:0.875rem;text-align:center;">{{ error }}</p>

        <button class="btn btn-primary" :disabled="loading">
          <span v-if="loading">Entrando...</span>
          <span v-else>Entrar</span>
        </button>
      </form>

      <hr class="divider" />
      <p style="text-align:center;color:var(--color-muted);font-size:0.9rem;">
        Não tem conta?
        <RouterLink to="/register" class="link"> Criar conta</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const form = ref({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.value.email, form.value.password)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Erro ao entrar'
  } finally {
    loading.value = false
  }
}
</script>
