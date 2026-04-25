<template>
  <div class="page-top">
    <!-- Header -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
      <div style="font-size:2rem;">{{ house?.icon }}</div>
      <div>
        <h1 style="margin-bottom:0;">{{ house?.name }}</h1>
        <p style="color:var(--color-muted);font-size:0.85rem;">Configuração do jogo</p>
      </div>
    </div>

    <!-- Tasks Section -->
    <div class="card-wide" style="margin-bottom:16px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <p class="section-title" style="margin-bottom:0;">Tarefas</p>
        <button class="btn btn-primary btn-sm" @click="showTaskForm = true">+ Adicionar</button>
      </div>

      <div v-if="activeTasks.length === 0" class="empty-state">Nenhuma tarefa ainda. Adicione a primeira!</div>
      <div class="gap-stack" v-else>
        <div v-for="task in activeTasks" :key="task.id" class="task-item">
          <div style="flex:1;">
            <div style="font-weight:600;">{{ task.name }}</div>
            <div style="display:flex;gap:8px;margin-top:4px;flex-wrap:wrap;">
              <span class="badge badge-purple">{{ task.score }} pts</span>
              <span class="badge badge-yellow">{{ task.weekly_frequency }}x/semana</span>
              <span v-if="task.category" class="badge" style="background:rgba(255,255,255,0.08);color:var(--color-muted);">{{ task.category }}</span>
            </div>
          </div>
          <button class="btn btn-secondary btn-sm" @click="editTask(task)" style="padding:6px 10px;">✏️</button>
          <button class="btn btn-danger btn-sm" @click="removeTask(task.id)" style="padding:6px 10px;">🗑</button>
        </div>
      </div>
    </div>

    <!-- Prizes Section -->
    <div class="card-wide" style="margin-bottom:16px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <p class="section-title" style="margin-bottom:0;">Prêmios Semanais 🏆</p>
        <button class="btn btn-primary btn-sm" @click="showPrizeForm = true">+ Prêmio</button>
      </div>
      <div v-if="!house?.prizes?.length" class="empty-state">Defina os prêmios semanais!</div>
      <div class="gap-stack" v-else>
        <div v-for="prize in house.prizes" :key="prize.id" class="task-item">
          <span style="font-size:1.2rem;">🏆</span>
          <span style="flex:1;font-weight:500;">{{ prize.name }}</span>
          <button class="btn btn-danger btn-sm" @click="removePrize(prize.id)" style="padding:6px 10px;">🗑</button>
        </div>
      </div>
    </div>

    <!-- Start Date + Ready -->
    <div class="card-wide" style="margin-bottom:16px;">
      <p class="section-title">Data de Início</p>
      <div class="input-group" style="margin-bottom:12px;">
        <label>Quando o jogo começa?</label>
        <input type="date" v-model="startDate" @change="saveStartDate" :min="today" />
      </div>

      <hr class="divider" />

      <p class="section-title">Jogadores</p>
      <div class="gap-stack" style="margin-bottom:16px;">
        <div v-for="(u, i) in house?.users" :key="u.id" class="task-item">
          <div class="avatar" :class="i===1?'avatar-alt':''">{{ u.name[0].toUpperCase() }}</div>
          <span style="flex:1;">{{ u.name }}</span>
          <div :style="{width:'10px',height:'10px',borderRadius:'50%',background:u.is_ready?'#4ade80':'#6b7280'}" :class="u.is_ready?'':'animate-pulse'"></div>
          <span style="font-size:0.8rem;color:var(--color-muted);">{{ u.is_ready ? 'Pronto!' : 'Aguardando' }}</span>
        </div>
      </div>

      <button
        v-if="!myReady"
        class="btn btn-success"
        @click="toggleReady(true)"
        :disabled="!startDate || readyLoading"
      >
        ✅ Estou Pronto!
      </button>
      <button v-else class="btn btn-secondary" @click="toggleReady(false)" :disabled="readyLoading">
        ⏳ Esperando o outro usuário... (cancelar)
      </button>
    </div>
  </div>

  <!-- Modal: Tarefa -->
  <div v-if="showTaskForm" class="modal-overlay" @click.self="closeTaskForm">
    <div class="card" style="max-width:400px;">
      <h2>{{ editingTask ? '✏️ Editar Tarefa' : '➕ Nova Tarefa' }}</h2><br/>
      <div class="input-group">
        <label>Nome da tarefa</label>
        <input v-model="taskForm.name" placeholder="Ex: Lavar a louça" />
      </div>
      <div class="gap-row" style="margin-bottom:16px;">
        <div class="input-group" style="margin-bottom:0;flex:1;">
          <label>Pontuação</label>
          <input v-model.number="taskForm.score" type="number" min="1" placeholder="10" />
        </div>
        <div class="input-group" style="margin-bottom:0;flex:1;">
          <label>Vezes/semana</label>
          <input v-model.number="taskForm.weekly_frequency" type="number" min="1" placeholder="1" />
        </div>
      </div>
      <div class="input-group">
        <label>Categoria (opcional)</label>
        <input v-model="taskForm.category" placeholder="Ex: Cozinha, Limpeza..." />
      </div>
      <p v-if="taskError" style="color:#f87171;font-size:0.875rem;margin-bottom:12px;">{{ taskError }}</p>
      <div class="gap-row">
        <button class="btn btn-secondary btn-sm" @click="closeTaskForm">Cancelar</button>
        <button class="btn btn-primary btn-sm" @click="saveTask" :disabled="taskLoading">
          {{ taskLoading ? 'Salvando...' : 'Salvar' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Modal: Prêmio -->
  <div v-if="showPrizeForm" class="modal-overlay" @click.self="showPrizeForm = false">
    <div class="card" style="max-width:360px;">
      <h2>🏆 Adicionar Prêmio</h2><br/>
      <div class="input-group">
        <label>Nome do prêmio</label>
        <input v-model="prizeForm.name" placeholder="Ex: Escolher o filme da noite" />
      </div>
      <div class="gap-row">
        <button class="btn btn-secondary btn-sm" @click="showPrizeForm = false">Cancelar</button>
        <button class="btn btn-primary btn-sm" @click="savePrize">Adicionar</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useHouseStore } from '@/stores/house'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const houseStore = useHouseStore()
const house = computed(() => houseStore.house)
const activeTasks = computed(() => house.value?.tasks?.filter(t => t.is_active) || [])
const today = new Date().toISOString().split('T')[0]

const startDate = ref('')
const readyLoading = ref(false)

// Sync startDate whenever house data updates (e.g. via polling when other user sets it)
watch(() => house.value?.start_date, (val) => {
  if (val && val !== startDate.value) startDate.value = val
})
const myReady = computed(() => house.value?.users?.find(u => u.id === auth.user?.id)?.is_ready || false)

const showTaskForm = ref(false)
const editingTask = ref(null)
const taskForm = ref({ name: '', score: 10, weekly_frequency: 1, category: '' })
const taskError = ref('')
const taskLoading = ref(false)

const showPrizeForm = ref(false)
const prizeForm = ref({ name: '' })

let pollInterval = null

onMounted(async () => {
  await houseStore.fetchHouse(route.params.id)
  if (house.value?.start_date) startDate.value = house.value.start_date
  pollInterval = setInterval(async () => {
    const h = await houseStore.poll(route.params.id)
    if (h.status === 'active') router.push({ name: 'game', params: { id: h.id } })
  }, 3000)
})

onUnmounted(() => clearInterval(pollInterval))

async function saveStartDate() {
  if (!startDate.value) return
  await houseStore.setStartDate(house.value.id, startDate.value)
}

async function toggleReady(ready) {
  readyLoading.value = true
  try {
    const h = await houseStore.setReady(house.value.id, ready)
    if (h.status === 'active') router.push({ name: 'game', params: { id: h.id } })
  } finally {
    readyLoading.value = false
  }
}

function editTask(task) {
  editingTask.value = task
  taskForm.value = { name: task.name, score: task.score, weekly_frequency: task.weekly_frequency, category: task.category || '' }
  showTaskForm.value = true
}

function closeTaskForm() {
  showTaskForm.value = false
  editingTask.value = null
  taskForm.value = { name: '', score: 10, weekly_frequency: 1, category: '' }
  taskError.value = ''
}

async function saveTask() {
  if (!taskForm.value.name) { taskError.value = 'Nome é obrigatório'; return }
  taskLoading.value = true; taskError.value = ''
  try {
    if (editingTask.value) {
      await houseStore.updateTask(house.value.id, editingTask.value.id, taskForm.value)
    } else {
      await houseStore.addTask(house.value.id, taskForm.value)
    }
    await houseStore.fetchHouse(route.params.id)
    closeTaskForm()
  } catch { taskError.value = 'Erro ao salvar tarefa' }
  finally { taskLoading.value = false }
}

async function removeTask(taskId) {
  await houseStore.deleteTask(house.value.id, taskId)
  await houseStore.fetchHouse(route.params.id)
}

async function savePrize() {
  if (!prizeForm.value.name) return
  await houseStore.addPrize(house.value.id, prizeForm.value.name)
  await houseStore.fetchHouse(route.params.id)
  prizeForm.value = { name: '' }
  showPrizeForm.value = false
}

async function removePrize(prizeId) {
  await houseStore.deletePrize(house.value.id, prizeId)
  await houseStore.fetchHouse(route.params.id)
}
</script>

<style scoped>
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; padding: 24px; z-index: 100; }
</style>
