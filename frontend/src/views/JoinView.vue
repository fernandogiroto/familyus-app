<template>
  <div class="page">
    <div class="card" style="text-align:center;">
      <div class="logo">FamilyUs 🏡</div>
      <p style="font-size:2rem;margin:16px 0;">{{ house?.icon || '🏠' }}</p>
      <h2 v-if="house">{{ house.name }}</h2>
      <p class="subtitle" style="margin-top:8px;">Você foi convidado para esta casa!</p>

      <div v-if="loading" style="color:var(--color-muted);">Carregando...</div>
      <div v-else-if="error" style="color:#f87171;">{{ error }}</div>
      <div v-else-if="house">
        <div style="margin-bottom:24px;">
          <p style="color:var(--color-muted);font-size:0.9rem;">Membros já na casa:</p>
          <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px;">
            <div v-for="u in house.users" :key="u.id" class="gap-row" style="justify-content:center;">
              <div class="avatar">{{ u.name[0].toUpperCase() }}</div>
              <span>{{ u.name }}</span>
            </div>
          </div>
        </div>
        <button class="btn btn-primary" @click="enter">Entrar na Casa</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHouseStore } from '@/stores/house'

const route = useRoute()
const router = useRouter()
const houseStore = useHouseStore()
const house = ref(null)
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const data = await houseStore.joinByCode(route.params.code)
    house.value = data
    if (data.status === 'active') {
      router.push({ name: 'game', params: { id: data.id } })
      return
    }
    if (data.status === 'setup') {
      router.push({ name: 'setup', params: { id: data.id } })
      return
    }
    router.push({ name: 'lobby', params: { id: data.id } })
  } catch (e) {
    error.value = e.response?.data?.message || 'Link inválido'
  } finally {
    loading.value = false
  }
})

async function enter() {
  router.push({ name: 'lobby', params: { id: house.value.id } })
}
</script>
