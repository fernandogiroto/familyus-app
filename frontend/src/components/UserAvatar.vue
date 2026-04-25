<template>
  <div
    class="avatar"
    :class="[altClass, sizeClass]"
    :style="avatarUrl ? { background: 'none', padding: 0 } : {}"
  >
    <img
      v-if="avatarUrl"
      :src="avatarUrl"
      :alt="name"
      style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
    />
    <span v-else>{{ initial }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  name: { type: String, default: '?' },
  avatarUrl: { type: String, default: null },
  alt: { type: Boolean, default: false },
  size: { type: String, default: 'md' }, // sm | md | lg
})

const initial = computed(() => props.name?.[0]?.toUpperCase() || '?')
const altClass = computed(() => props.alt ? 'avatar-alt' : '')
const sizeClass = computed(() => ({
  sm: 'avatar-sm',
  md: '',
  lg: 'avatar-lg',
}[props.size] || ''))
</script>

<style scoped>
.avatar-sm { width: 32px; height: 32px; font-size: 0.85rem; }
</style>
