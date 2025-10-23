import { defineStore } from 'pinia';

export const useRipStore = defineStore('useRipStore', {
  state: () => ({
    dataRip: {
      arrayData: [] as Array<object>
    } as object,
    servicesCount: {
      consultas: 0,
      procedimientos: 0,
      urgencias: 0,
      hospitalizacion: 0,
      recienNacidos: 0,
      medicamentos: 0,
      otrosServicios: 0,
    }
  }),
  persist: true,
  getters: {
  },
  actions: {
  },
})
