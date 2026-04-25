<template>
  <div class="page">
    <div class="card">
      <div class="logo">FamilyUs 🏡</div>
      <p class="subtitle">Crie sua conta</p>

      <form @submit.prevent="submit" class="gap-stack">
        <div class="input-group">
          <label>Nome</label>
          <input v-model="form.name" type="text" placeholder="Seu nome" required />
        </div>
        <div class="input-group">
          <label>Email</label>
          <input v-model="form.email" type="email" placeholder="seu@email.com" required />
        </div>
        <div class="input-group">
          <label>Senha</label>
          <input v-model="form.password" type="password" placeholder="mínimo 6 caracteres" required />
        </div>
        <div class="input-group">
          <label>Confirmar Senha</label>
          <input v-model="form.password_confirmation" type="password" placeholder="repita a senha" required />
        </div>

        <p v-if="error" style="color:#f87171;font-size:0.875rem;text-align:center;">{{ error }}</p>

        <button class="btn btn-primary" :disabled="loading">
          <span v-if="loading">Criando...</span>
          <span v-else>Criar Conta</span>
        </button>
      </form>

      <hr class="divider" />
      <p style="text-align:center;color:var(--color-muted);font-size:0.9rem;">
        Já tem conta?
        <RouterLink to="/login" class="link"> Entrar</RouterLink>
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
const form = ref({ name: '', email: '', password: '', password_confirmation: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'As senhas não coincidem'
    return
  }
  loading.value = true
  try {
    await auth.register(form.value.name, form.value.email, form.value.password, form.value.password_confirmation)
    router.push('/')
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(' ') : 'Erro ao criar conta'
  } finally {
    loading.value = false
  }
}
</script>
