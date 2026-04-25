<template>
  <div class="page-top">
    <!-- Header -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
      <div style="font-size:2rem;">{{ house?.icon }}</div>
      <div style="flex:1;">
        <h1 style="margin-bottom:0;font-size:1.3rem;">{{ house?.name }}</h1>
        <p style="color:var(--color-muted);font-size:0.8rem;">Semana de {{ weekLabel }}</p>
      </div>
      <button class="btn btn-secondary btn-sm" style="width:auto;" @click="showPrizesModal = true">🏆</button>
      <button class="btn btn-secondary btn-sm" style="width:auto;" @click="showProfileModal = true">👤</button>
    </div>

    <!-- Progress bars -->
    <div class="card-wide" style="margin-bottom:16px;">
      <p class="section-title">Placar da Semana</p>
      <div v-for="(u, i) in house?.users" :key="u.id" style="margin-bottom:14px;">
        <div class="gap-row" style="margin-bottom:6px;">
          <UserAvatar :name="u.name" :avatar-url="u.avatar_url" :alt="i===1" size="sm" style="width:36px;height:36px;" />
          <span style="font-weight:600;flex:1;">{{ u.name }}</span>
          <span style="font-size:0.9rem;font-weight:700;color:var(--color-accent);">{{ u.weekly_score }} pts</span>
          <span style="font-size:1.2rem;">🏁</span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar-fill" :style="{
            width: u.percentage + '%',
            background: i===0 ? 'linear-gradient(90deg,#6c63ff,#8b5cf6)' : 'linear-gradient(90deg,#f093fb,#f5576c)'
          }"></div>
        </div>
        <div style="text-align:right;font-size:0.75rem;color:var(--color-muted);margin-top:2px;">{{ u.percentage }}%</div>
      </div>
    </div>

    <!-- Tie banner -->
    <div v-if="weeklyWinner?.is_tie" class="card-wide" style="margin-bottom:16px;border-color:rgba(99,102,241,0.4);background:rgba(99,102,241,0.06);">
      <div style="text-align:center;padding:8px 0;">
        <div style="font-size:2rem;">🤝</div>
        <h2 style="color:#a5b4fc;">Empate!</h2>
        <p style="color:var(--color-muted);font-size:0.9rem;margin-top:8px;">Os dois jogadores fizeram a mesma pontuação na semana passada. Nenhum prêmio é concedido.</p>
      </div>
    </div>

    <!-- Winner banner -->
    <div v-else-if="weeklyWinner && !weeklyWinner.prize_name" class="card-wide" style="margin-bottom:16px;border-color:rgba(250,204,21,0.4);background:rgba(250,204,21,0.06);">
      <div style="text-align:center;padding:8px 0;">
        <div style="font-size:2rem;">🎉</div>
        <h2 style="color:var(--color-warning);">{{ weeklyWinner.user?.name }} venceu!</h2>
        <p v-if="weeklyWinner.user?.id === auth.user?.id" style="color:var(--color-muted);font-size:0.9rem;margin-top:4px;">Escolha seu prêmio:</p>
        <div v-if="weeklyWinner.user?.id === auth.user?.id" class="gap-stack" style="margin-top:12px;">
          <button v-for="prize in house?.prizes" :key="prize.id"
            class="btn btn-secondary btn-sm" style="width:100%;background:rgba(250,204,21,0.1);border-color:rgba(250,204,21,0.3);"
            @click="claimPrize(prize)">
            🏆 {{ prize.name }}
          </button>
        </div>
        <p v-else style="color:var(--color-muted);font-size:0.9rem;margin-top:8px;">Aguardando {{ weeklyWinner.user?.name }} escolher o prêmio...</p>
      </div>
    </div>

    <!-- Tasks list -->
    <div class="card-wide">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <p class="section-title" style="margin-bottom:0;">Tarefas da Semana</p>
        <button class="btn btn-secondary btn-sm" @click="refresh">🔄</button>
      </div>

      <div v-if="activeTasks.length === 0" class="empty-state">Nenhuma tarefa configurada.</div>

      <div class="gap-stack">
        <div v-for="task in activeTasks" :key="task.id" class="task-item" style="flex-direction:column;align-items:stretch;gap:10px;">
          <!-- Task header -->
          <div class="gap-row">
            <div style="flex:1;">
              <div style="font-weight:600;">{{ task.name }}</div>
              <div style="display:flex;gap:6px;margin-top:4px;flex-wrap:wrap;">
                <span class="badge badge-purple">{{ task.score }} pts</span>
                <span class="badge badge-yellow">{{ task.weekly_frequency }}x/semana</span>
                <span class="badge badge-green">Eu: {{ myDoneCount(task) }}/{{ task.weekly_frequency }}</span>
                <span v-if="task.category" class="badge" style="background:rgba(255,255,255,0.07);color:var(--color-muted);">{{ task.category }}</span>
              </div>
            </div>
            <!-- Status badge -->
            <span v-if="taskStatus(task) === 'doing-me'" class="badge badge-yellow animate-pulse">⚡ Fazendo</span>
            <span v-else-if="taskStatus(task) === 'doing-other'" class="badge badge-red">🔒 Em uso</span>
            <span v-else-if="taskStatus(task) === 'done-today' && doneByMe(task)" class="badge badge-green">✅ Feita hoje</span>
            <span v-else-if="taskStatus(task) === 'done-today' && !doneByMe(task)" class="badge" style="background:rgba(99,102,241,0.2);color:#a5b4fc;">🔒 Feita hoje</span>
            <span v-else-if="taskStatus(task) === 'done-no-photo'" class="badge badge-yellow">📷 Tirar foto</span>
            <span v-else-if="taskStatus(task) === 'maxed'" class="badge badge-green">✅ Semana completa</span>
          </div>

          <!-- Done completions list -->
          <div v-if="task.done_completions?.length" style="display:flex;flex-direction:column;gap:6px;">
            <div v-for="c in task.done_completions" :key="c.id"
              style="display:flex;align-items:center;gap:8px;font-size:0.8rem;color:var(--color-success);background:rgba(74,222,128,0.06);border-radius:8px;padding:6px 10px;">
              <UserAvatar :name="c.user?.name || '?'" :avatar-url="userAvatarUrl(c.user_id)" size="sm" style="width:24px;height:24px;font-size:0.7rem;" />
              <span>{{ c.user?.name }} · {{ formatTime(c.completed_at) }}</span>
              <span style="flex:1;"></span>
              <button v-if="c.image_path" @click="openPhoto(c)" style="background:none;border:none;cursor:pointer;font-size:1rem;">📷</button>
              <span v-else style="color:var(--color-muted);">sem foto</span>
            </div>
          </div>

          <!-- Action buttons -->
          <div v-if="taskStatus(task) === 'idle'" class="gap-row">
            <button class="btn btn-primary btn-sm" style="flex:1;" @click="startTask(task)">Fazer agora</button>
          </div>

          <div v-if="taskStatus(task) === 'doing-me'" class="gap-row">
            <button class="btn btn-danger btn-sm" style="flex:1;" @click="cancelTask(task)">✖ Cancelar</button>
            <button class="btn btn-success btn-sm" style="flex:1;" @click="openCompleteConfirm(task)">✅ Tarefa Realizada</button>
          </div>

          <!-- Photo upload (after completing, before photo) -->
          <div v-if="taskStatus(task) === 'done-no-photo'" class="gap-row">
            <button class="btn btn-secondary btn-sm" style="flex:1;" @click="triggerPhoto(task)">📷 Registrar com Foto</button>
            <input :ref="el => photoInputs[task.id] = el" type="file" accept="image/*" capture="environment" style="display:none;" @change="e => uploadPhoto(e, task)" />
          </div>
        </div>
      </div>
    </div>

    <!-- Weekly winners history -->
    <div v-if="house?.weekly_winners?.length" class="card-wide" style="margin-top:16px;">
      <p class="section-title">Histórico de Vencedores 🏆</p>
      <div class="gap-stack">
        <div v-for="winner in house.weekly_winners" :key="winner.id" class="task-item">
          <template v-if="winner.is_tie">
            <div class="avatar" style="width:36px;height:36px;font-size:0.9rem;background:linear-gradient(135deg,#6366f1,#8b5cf6);">🤝</div>
          </template>
          <UserAvatar v-else :name="winner.user?.name || '?'" :avatar-url="userAvatarUrl(winner.user_id)" size="sm" style="width:36px;height:36px;" />
          <div style="flex:1;">
            <div style="font-weight:600;">{{ winner.is_tie ? 'Empate' : winner.user?.name }}</div>
            <div style="font-size:0.8rem;color:var(--color-muted);">Semana de {{ formatDate(winner.week_start) }}</div>
          </div>
          <div style="text-align:right;">
            <div v-if="winner.prize_name" style="font-size:0.85rem;color:var(--color-warning);">🏆 {{ winner.prize_name }}</div>
            <div v-else-if="winner.is_tie" style="font-size:0.75rem;color:#a5b4fc;">sem prêmio</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Prizes list -->
  <div v-if="showPrizesModal" class="modal-overlay" @click.self="showPrizesModal = false">
    <div class="card" style="max-width:360px;">
      <h2>🏆 Prêmios</h2><br/>
      <div class="gap-stack">
        <div v-for="prize in house?.prizes" :key="prize.id" class="task-item">
          <span style="flex:1;">{{ prize.name }}</span>
          <span v-if="prizeWinner(prize.id)" style="font-size:0.75rem;color:var(--color-warning);">
            {{ prizeWinner(prize.id)?.user?.name }}
          </span>
        </div>
        <div v-if="!house?.prizes?.length" class="empty-state">Nenhum prêmio definido.</div>
      </div>
      <br/>
      <button class="btn btn-secondary" @click="showPrizesModal = false">Fechar</button>
    </div>
  </div>

  <!-- Modal: Profile edit -->
  <div v-if="showProfileModal" class="modal-overlay scrollable" @click.self="showProfileModal = false">
    <div class="card" style="max-width:380px;width:100%;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <h2 style="margin-bottom:0;">👤 Meu Perfil</h2>
        <button @click="showProfileModal = false" style="background:none;border:none;cursor:pointer;color:var(--color-muted);font-size:1.4rem;line-height:1;padding:4px;">✕</button>
      </div>

      <!-- Avatar upload -->
      <div style="display:flex;flex-direction:column;align-items:center;margin-bottom:20px;gap:10px;">
        <div style="position:relative;cursor:pointer;" @click="avatarInputRef?.click()">
          <UserAvatar :name="auth.user?.name || '?'" :avatar-url="myAvatarUrl()" size="lg" style="width:80px;height:80px;font-size:1.8rem;" />
          <div style="position:absolute;bottom:0;right:0;width:24px;height:24px;border-radius:50%;background:var(--color-accent);display:flex;align-items:center;justify-content:center;font-size:0.8rem;">
            📷
          </div>
        </div>
        <span style="font-size:0.8rem;color:var(--color-muted);">{{ avatarUploading ? 'Enviando...' : 'Clique para alterar foto' }}</span>
        <input ref="avatarInputRef" type="file" accept="image/*" style="display:none;" @change="handleAvatarChange" />
      </div>

      <div class="input-group">
        <label>Nome</label>
        <input v-model="profileForm.name" />
      </div>
      <div class="input-group">
        <label>Email</label>
        <input v-model="profileForm.email" type="email" />
      </div>
      <hr class="divider" />
      <p style="color:var(--color-muted);font-size:0.8rem;margin-bottom:12px;">Para alterar a senha, preencha abaixo:</p>
      <div class="input-group">
        <label>Senha atual</label>
        <input v-model="profileForm.current_password" type="password" placeholder="••••••" />
      </div>
      <div class="input-group">
        <label>Nova senha</label>
        <input v-model="profileForm.password" type="password" placeholder="••••••" />
      </div>
      <div class="input-group">
        <label>Confirmar nova senha</label>
        <input v-model="profileForm.password_confirmation" type="password" placeholder="••••••" />
      </div>
      <p v-if="profileError" style="color:#f87171;font-size:0.875rem;margin-bottom:12px;">{{ profileError }}</p>
      <p v-if="profileSuccess" style="color:var(--color-success);font-size:0.875rem;margin-bottom:12px;">{{ profileSuccess }}</p>
      <div class="gap-row">
        <button class="btn btn-secondary btn-sm" @click="showProfileModal = false">Cancelar</button>
        <button class="btn btn-primary btn-sm" @click="saveProfile" :disabled="profileLoading">
          {{ profileLoading ? 'Salvando...' : 'Salvar' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Modal: Confirm task complete -->
  <div v-if="confirmTask" class="modal-overlay" @click.self="confirmTask = null">
    <div class="card" style="max-width:340px;text-align:center;">
      <div style="font-size:2.5rem;margin-bottom:12px;">✅</div>
      <h2>Tarefa Realizada?</h2>
      <p style="color:var(--color-muted);font-size:0.9rem;margin-top:8px;margin-bottom:24px;">
        Confirme que você concluiu <strong>{{ confirmTask?.name }}</strong>.
      </p>
      <div class="gap-row">
        <button class="btn btn-secondary btn-sm" style="flex:1;" @click="confirmTask = null">Não, ainda não</button>
        <button class="btn btn-success btn-sm" style="flex:1;" @click="doneTask(confirmTask)">Sim, concluí!</button>
      </div>
    </div>
  </div>

  <!-- Modal: Photo viewer -->
  <div v-if="photoUrl" class="modal-overlay" @click.self="photoUrl = null">
    <div style="max-width:90vw;max-height:90vh;text-align:center;">
      <img :src="photoUrl" style="max-width:100%;max-height:80vh;border-radius:12px;object-fit:contain;" />
      <br/><br/>
      <button class="btn btn-secondary btn-sm" @click="photoUrl = null">Fechar</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useHouseStore } from '@/stores/house'
import UserAvatar from '@/components/UserAvatar.vue'

const route = useRoute()
const auth = useAuthStore()
const houseStore = useHouseStore()
const house = computed(() => houseStore.house)
const activeTasks = computed(() => house.value?.tasks?.filter(t => t.is_active) || [])
const weeklyWinner = ref(null)
const showPrizesModal = ref(false)
const showProfileModal = ref(false)
const photoInputs = ref({})
const photoUrl = ref(null)
const confirmTask = ref(null)
let pollInterval = null

// Profile form
const profileForm = ref({ name: auth.user?.name || '', email: auth.user?.email || '', current_password: '', password: '', password_confirmation: '' })
const profileError = ref('')
const profileSuccess = ref('')
const profileLoading = ref(false)
const avatarInputRef = ref(null)
const avatarUploading = ref(false)

function userAvatarUrl(userId) {
  return house.value?.users?.find(u => u.id === userId)?.avatar_url || null
}

function myAvatarUrl() {
  return userAvatarUrl(auth.user?.id) || auth.user?.avatar_url || null
}

async function handleAvatarChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  avatarUploading.value = true
  try {
    await auth.uploadAvatar(file)
    await houseStore.fetchHouse(route.params.id)
    profileSuccess.value = 'Foto atualizada!'
  } catch {
    profileError.value = 'Erro ao enviar foto'
  } finally {
    avatarUploading.value = false
  }
}

const weekLabel = computed(() => {
  const d = new Date()
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  const monday = new Date(d.setDate(diff))
  return monday.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
})

onMounted(async () => {
  await houseStore.fetchHouse(route.params.id)
  await checkReset()
  pollInterval = setInterval(async () => {
    await houseStore.fetchHouse(route.params.id)
  }, 5000)
})

onUnmounted(() => clearInterval(pollInterval))

async function refresh() {
  await houseStore.fetchHouse(route.params.id)
}

async function checkReset() {
  try {
    const data = await houseStore.checkWeeklyReset(route.params.id)
    if (data.winner) weeklyWinner.value = data.winner
  } catch {}
}

// Returns: 'idle' | 'doing-me' | 'doing-other' | 'done-today' | 'done-no-photo' | 'maxed'
function taskStatus(task) {
  const doing = task.doing_completion
  if (doing) {
    return doing.user_id === auth.user?.id ? 'doing-me' : 'doing-other'
  }

  // Shared daily lock: if anyone completed it today, button is gone for everyone
  if (task.completed_today) {
    // Check if it was me who did it and I still need to add a photo
    const myDone = (task.done_completions || []).filter(c => c.user_id === auth.user?.id)
    const doneToday = myDone.filter(c => (task.done_today_by_user || []).includes(auth.user?.id))
    if (doneToday.length > 0 && !doneToday[doneToday.length - 1].image_path) {
      return 'done-no-photo'
    }
    return 'done-today'
  }

  // Weekly cap
  if (myDoneCount(task) >= task.weekly_frequency) return 'maxed'
  return 'idle'
}

function myDoneCount(task) {
  return (task.done_by_user || {})[auth.user?.id] || 0
}

function doneByMe(task) {
  return (task.done_today_by_user || []).includes(auth.user?.id)
}

async function startTask(task) {
  try {
    await houseStore.startDoing(house.value.id, task.id)
    await houseStore.fetchHouse(route.params.id)
  } catch(e) {
    alert(e.response?.data?.message || 'Erro ao iniciar tarefa')
  }
}

async function cancelTask(task) {
  await houseStore.cancelDoing(house.value.id, task.id)
  await houseStore.fetchHouse(route.params.id)
}

function openCompleteConfirm(task) {
  confirmTask.value = task
}

async function doneTask(task) {
  confirmTask.value = null
  await houseStore.completeDoing(house.value.id, task.id)
  await houseStore.fetchHouse(route.params.id)
}

function triggerPhoto(task) {
  photoInputs.value[task.id]?.click()
}

async function uploadPhoto(event, task) {
  const file = event.target.files?.[0]
  if (!file) return
  await houseStore.uploadPhoto(house.value.id, task.id, file)
  await houseStore.fetchHouse(route.params.id)
}

function openPhoto(completion) {
  photoUrl.value = `http://localhost:8000/storage/${completion.image_path}`
}

async function claimPrize(prize) {
  const d = new Date()
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  const monday = new Date(new Date().setDate(diff))
  const weekStart = monday.toISOString().split('T')[0]
  await houseStore.selectPrize(house.value.id, prize.id, weekStart)
  weeklyWinner.value = null
  await houseStore.fetchHouse(route.params.id)
  await checkReset()
}

function prizeWinner(prizeId) {
  return house.value?.weekly_winners?.find(w => w.prize_id === prizeId)
}

async function saveProfile() {
  profileError.value = ''
  profileSuccess.value = ''
  profileLoading.value = true
  try {
    const payload = {}
    if (profileForm.value.name) payload.name = profileForm.value.name
    if (profileForm.value.email) payload.email = profileForm.value.email
    if (profileForm.value.password) {
      payload.password = profileForm.value.password
      payload.password_confirmation = profileForm.value.password_confirmation
      payload.current_password = profileForm.value.current_password
    }
    await auth.updateProfile(payload)
    profileSuccess.value = 'Perfil atualizado com sucesso!'
    profileForm.value.current_password = ''
    profileForm.value.password = ''
    profileForm.value.password_confirmation = ''
  } catch(e) {
    const errs = e.response?.data?.errors
    profileError.value = errs ? Object.values(errs).flat().join(' ') : (e.response?.data?.message || 'Erro ao salvar')
  } finally {
    profileLoading.value = false
  }
}

function formatTime(ts) {
  if (!ts) return ''
  return new Date(ts).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}

function formatDate(ts) {
  if (!ts) return ''
  return new Date(ts).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  z-index: 100;
  overflow-y: auto;
}
.modal-overlay.scrollable {
  align-items: flex-start;
  padding-top: 32px;
  padding-bottom: 32px;
}
</style>
