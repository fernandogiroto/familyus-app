<template>
  <div class="page">
    <div class="card">
      <div class="logo">FamilyUs 🏡</div>
      <p class="subtitle">Olá, {{ auth.user?.name }}! O que deseja fazer?</p>
      <div class="gap-stack">
        <button class="btn btn-primary" @click="showCreate = true">🏠 Criar uma Casa</button>
        <button class="btn btn-secondary" @click="showJoin = true">🔑 Entrar numa Casa</button>
      </div>
      <hr class="divider" />
      <button class="btn btn-secondary btn-sm" style="width:auto;margin:0 auto;" @click="doLogout">Sair</button>
    </div>

    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="card" style="max-width:380px;">
        <h2>🏠 Criar Casa</h2><br/>
        <div class="input-group">
          <label>Nome da Casa</label>
          <input v-model="createForm.name" placeholder="Ex: Casa da Família Silva" />
        </div>
        <div class="input-group">
          <label>Escolha um ícone</label>
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px;">
            <button v-for="emoji in houseEmojis" :key="emoji" type="button" class="emoji-btn" :class="{ selected: createForm.icon === emoji }" @click="createForm.icon = emoji">{{ emoji }}</button>
          </div>
        </div>
        <p v-if="createError" style="color:#f87171;font-size:0.875rem;margin-bottom:12px;">{{ createError }}</p>
        <div class="gap-row">
          <button class="btn btn-secondary btn-sm" @click="showCreate = false">Cancelar</button>
          <button class="btn btn-primary btn-sm" @click="handleCreate" :disabled="createLoading">{{ createLoading ? 'Criando...' : 'Criar' }}</button>
        </div>
      </div>
    </div>

    <div v-if="showJoin" class="modal-overlay" @click.self="showJoin = false">
      <div class="card" style="max-width:380px;">
        <h2>🔑 Entrar numa Casa</h2><br/>
        <div class="input-group">
          <label>Código de convite</label>
          <input v-model="joinCode" placeholder="Ex: ABCD1234" style="text-transform:uppercase;" />
        </div>
        <p v-if="joinError" style="color:#f87171;font-size:0.875rem;margin-bottom:12px;">{{ joinError }}</p>
        <div class="gap-row">
          <button class="btn btn-secondary btn-sm" @click="showJoin = false">Cancelar</button>
          <button class="btn btn-primary btn-sm" @click="handleJoin" :disabled="joinLoading">{{ joinLoading ? 'Entrando...' : 'Entrar' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useHouseStore } from '@/stores/house'

const auth = useAuthStore()
const houseStore = useHouseStore()
const router = useRouter()

const showCreate = ref(false)
const showJoin = ref(false)
const createForm = ref({ name: '', icon: '🏠' })
const createError = ref('')
const createLoading = ref(false)
const joinCode = ref('')
const joinError = ref('')
const joinLoading = ref(false)
const houseEmojis = ['🏠', '🏡', '🏰', '🏯', '🛖', '🏘', '⛺', '🌴', '🌻', '⭐', '🌈', '🎪']

async function doLogout() {
  auth.logout()
  router.push('/login')
}

async function handleCreate() {
  if (!createForm.value.name) return
  createLoading.value = true; createError.value = ''
  try {
    const house = await houseStore.createHouse(createForm.value.name, createForm.value.icon)
    router.push({ name: 'lobby', params: { id: house.id } })
  } catch { createError.value = 'Erro ao criar casa' }
  finally { createLoading.value = false }
}

async function handleJoin() {
  if (!joinCode.value) return
  joinLoading.value = true; joinError.value = ''
  try {
    const house = await houseStore.joinByCode(joinCode.value.trim().toUpperCase())
    router.push({ name: 'lobby', params: { id: house.id } })
  } catch (e) { joinError.value = e.response?.data?.message || 'Código inválido' }
  finally { joinLoading.value = false }
}
</script>

<style scoped>
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; padding: 24px; z-index: 100; }
</style>
