import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useHouseStore = defineStore('house', () => {
  const house = ref(null)
  const loading = ref(false)

  async function createHouse(name, icon) {
    const { data } = await api.post('/houses', { name, icon })
    house.value = data
    return data
  }

  async function joinByCode(code) {
    const { data } = await api.get(`/houses/join/${code}`)
    house.value = data
    return data
  }

  async function fetchHouse(id) {
    const { data } = await api.get(`/houses/${id}`)
    house.value = data
    return data
  }

  async function poll(id) {
    const { data } = await api.get(`/houses/${id}/poll`)
    house.value = data
    return data
  }

  async function setReady(id, ready) {
    const { data } = await api.post(`/houses/${id}/ready`, { ready })
    house.value = data
    return data
  }

  async function setStartDate(id, start_date) {
    const { data } = await api.post(`/houses/${id}/start-date`, { start_date })
    house.value = data
    return data
  }

  async function addTask(houseId, task) {
    const { data } = await api.post(`/houses/${houseId}/tasks`, task)
    return data
  }

  async function updateTask(houseId, taskId, task) {
    const { data } = await api.put(`/houses/${houseId}/tasks/${taskId}`, task)
    return data
  }

  async function deleteTask(houseId, taskId) {
    await api.delete(`/houses/${houseId}/tasks/${taskId}`)
  }

  async function startDoing(houseId, taskId) {
    const { data } = await api.post(`/houses/${houseId}/tasks/${taskId}/doing`)
    return data
  }

  async function cancelDoing(houseId, taskId) {
    await api.delete(`/houses/${houseId}/tasks/${taskId}/doing`)
  }

  async function completeDoing(houseId, taskId) {
    const { data } = await api.post(`/houses/${houseId}/tasks/${taskId}/done`)
    return data
  }

  async function uploadPhoto(houseId, taskId, file) {
    const form = new FormData()
    form.append('image', file)
    const { data } = await api.post(`/houses/${houseId}/tasks/${taskId}/photo`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data
  }

  async function addPrize(houseId, name) {
    const { data } = await api.post(`/houses/${houseId}/prizes`, { name })
    return data
  }

  async function deletePrize(houseId, prizeId) {
    await api.delete(`/houses/${houseId}/prizes/${prizeId}`)
  }

  async function selectPrize(houseId, prizeId, weekStart) {
    const { data } = await api.post(`/houses/${houseId}/prizes/select`, {
      prize_id: prizeId,
      week_start: weekStart,
    })
    return data
  }

  async function checkWeeklyReset(houseId) {
    const { data } = await api.get(`/houses/${houseId}/weekly-reset`)
    return data
  }

  return {
    house,
    loading,
    createHouse,
    joinByCode,
    fetchHouse,
    poll,
    setReady,
    setStartDate,
    addTask,
    updateTask,
    deleteTask,
    startDoing,
    cancelDoing,
    completeDoing,
    uploadPhoto,
    addPrize,
    deletePrize,
    selectPrize,
    checkWeeklyReset,
  }
})
