import { defineStore } from 'pinia';

export const useRipInvoiceUserStore = defineStore('useRipInvoiceUserStore', {
  state: () => ({
    dataUser: {
      arrayData: [] as Array<object>
    } as object,
    dataInvoice: { 
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
