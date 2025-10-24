import { defineStore } from "pinia";

export const useRipManualStore = defineStore("useRipManualStore", {
  state: () => ({
    cupsRips_arrayInfo: [] as Array<object>,
    modalidadAtencion_arrayInfo: [] as Array<object>,
    grupoServicio_arrayInfo: [] as Array<object>,
    servicio_arrayInfo: [] as Array<object>,
    ripsFinalidadConsultaVersion2_arrayInfo: [] as Array<object>,
    ripsCausaExternaVersion2_arrayInfo: [] as Array<object>,
    cie10_arrayInfo: [] as Array<object>,
    ripsTipoDiagnosticoPrincipalVersion2_arrayInfo: [] as Array<object>,
    conceptoRecaudo_arrayInfo: [] as Array<object>,
  }),
  persist: true,
  actions: {
    async ripInvoiceServicesSelectsInfinite() {
      const { data, response } = await useAxios("/rip/ripInvoiceServicesSelectsInfinite").get();

      if (response.status == 200 && data) {
        this.cupsRips_arrayInfo = data.cupsRips_arrayInfo;
        this.modalidadAtencion_arrayInfo = data.modalidadAtencion_arrayInfo;
        this.grupoServicio_arrayInfo = data.grupoServicio_arrayInfo;
        this.servicio_arrayInfo = data.servicio_arrayInfo;
        this.ripsFinalidadConsultaVersion2_arrayInfo = data.ripsFinalidadConsultaVersion2_arrayInfo;
        this.ripsCausaExternaVersion2_arrayInfo = data.ripsCausaExternaVersion2_arrayInfo;
        this.cie10_arrayInfo = data.cie10_arrayInfo;
        this.ripsTipoDiagnosticoPrincipalVersion2_arrayInfo = data.ripsTipoDiagnosticoPrincipalVersion2_arrayInfo;
        this.conceptoRecaudo_arrayInfo = data.conceptoRecaudo_arrayInfo;
      }

      return data;
    },
  },
});
