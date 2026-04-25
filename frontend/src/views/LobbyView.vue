<template>
  <div class="page">
    <div class="card" v-if="house">
      <div style="text-align:center;margin-bottom:24px;">
        <div style="font-size:3rem;">{{ house.icon }}</div>
        <h1 style="margin-top:8px;">{{ house.name }}</h1>
        <p class="subtitle" style="margin-top:4px;">
          {{ house.users.length < 2 ? 'Aguardando o segundo jogador...' : 'Os dois jogadores estão aqui!' }}
        </p>
      </div>

      <!-- Invite link -->
      <div v-if="isCreator" style="margin-bottom:20px;">
        <p class="section-title">Link de convite</p>
        <div style="display:flex;gap:8px;align-items:center;">
          <input :value="inviteLink" readonly style="background:rgba(255,255,255,0.06);border:1px solid var(--color-border);border-radius:10px;padding:10px 12px;color:var(--color-muted);font-size:0.875rem;flex:1;outline:none;" />
          <button class="btn btn-secondary btn-sm" @click="copyLink">
            {{ copied ? '✅' : '📋' }}
          </button>
        </div>
      </div>

      <!-- Players -->
      <p class="section-title">Jogadores</p>
      <div class="gap-stack" style="margin-bottom:24px;">
        <div v-for="(u, i) in house.users" :key="u.id" class="task-item">
          <div class="avatar" :class="i === 1 ? 'avatar-alt' : ''">{{ u.name[0].toUpperCase() }}</div>
          <div style="flex:1;">
            <div style="font-weight:600;">{{ u.name }}</div>
            <div style="font-size:0.8rem;color:var(--color-muted);">{{ u.id === house.created_by ? 'Criador' : 'Convidado' }}</div>
          </div>
          <div :style="{ width:'10px', height:'10px', borderRadius:'50%', background: u.id === house.created_by ? '#4ade80' : '#4ade80' }"></div>
        </div>
        <div v-if="house.users.length < 2" class="task-item" style="opacity:0.4;">
          <div class="avatar" style="background:rgba(255,255,255,0.1);">?</div>
          <div>
            <div style="font-weight:600;">Aguardando...</div>
            <div style="font-size:0.8rem;color:var(--color-muted);" class="animate-pulse">Compartilhe o link</div>
          </div>
        </div>
      </div>

      <button
        v-if="house.users.length === 2"
        class="btn btn-primary"
        @click="goToSetup"
      >
        🚀 Começar
      </button>
    </div>

    <div v-else style="color:var(--color-muted);">Carregando...</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useHouseStore } from '@/stores/house'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const houseStore = useHouseStore()
const house = computed(() => houseStore.house)
const copied = ref(false)
let pollInterval = null

const isCreator = computed(() => house.value?.created_by === auth.user?.id)
const inviteLink = computed(() => `${window.location.origin}/join/${house.value?.invite_code}`)

onMounted(async () => {
  await houseStore.fetchHouse(route.params.id)
  pollInterval = setInterval(async () => {
    const h = await houseStore.poll(route.params.id)
    if (h.status === 'setup') router.push({ name: 'setup', params: { id: h.id } })
    if (h.status === 'active') router.push({ name: 'game', params: { id: h.id } })
  }, 3000)
})

onUnmounted(() => clearInterval(pollInterval))

function copyLink() {
  navigator.clipboard.writeText(inviteLink.value)
  copied.value = true
  setTimeout(() => copied.value = false, 2000)
}

function goToSetup() {
  router.push({ name: 'setup', params: { id: house.value.id } })
}
</script>
