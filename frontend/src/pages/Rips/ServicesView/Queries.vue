<script setup lang="ts">
const { dataList } = defineProps({
  dataList: {
    type: Array<object>,
    default: [],
  }
})
//table 
const inputsTableFilter = ref([
  { key: "consecutivo", title: 'Consecutivo', sortable: true },
  { key: "fechaInicioAtencion", title: 'Fecha Inicio Atención', sortable: false, width: "200" },
  { key: "numAutoriacion", title: 'No Autorización', sortable: false, width: "200" },
  { key: "codConsulta", title: 'Codigo de Consulta', sortable: false, width: "350" },
  { key: "modalidadGrupoServicioTecSal", title: 'Modalidad Grupo Servicio TecSal', sortable: false, width: "350" },
  { key: "grupoServicios", title: 'Grupo Servicios', sortable: false, width: "350" },
  { key: "codServicio", title: 'Codigo Servicio', sortable: false, width: "350" },
  { key: "finalidadTecnologiaSalud", title: 'Finalidad Tecnologia Salud', sortable: false, width: "350" },
  { key: "causaMotivoAtencion", title: 'Causa Motivo Atención', sortable: false, width: "350" },
  { key: "codDiagnosticoPrincipal", title: 'Diagnostico Principal', sortable: false, width: "350" },
  { key: "codDiagnosticoRelacionado1", title: 'Diagnostico Relacionado 1', sortable: false, width: "200" },
  { key: "codDiagnosticoRelacionado2", title: 'Diagnostico Relacionado 2', sortable: false, width: "200" },
  { key: "codDiagnosticoRelacionado3", title: 'Diagnostico Relacionado 3', sortable: false, width: "200" },
  { key: "tipoDiagnosticoPrincipal", title: 'Tipo Diagnostico Principal', sortable: false, width: "200" },
  { key: "valorPagoModerador", title: 'Valor Pago Moderador', sortable: false, width: "200" },
  { key: "vrServicio", title: 'Valor Servicio', sortable: false, width: "200" },
  { key: "conceptoRecaudo", title: 'Concepto Recaudo', sortable: false, width: "350" },
])



const options = ref({ page: 1, itemsPerPage: 10, sortBy: [''], sortDesc: [false] })
const search = ref('')

</script>
<template>
  <div>
    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" offset-md="8" md="4">
            <AppTextField v-model="search" density="compact" placeholder="Search ..." append-inner-icon="tabler-search"
              single-line hide-details dense outlined clearable />
          </VCol>
        </VRow>
      </VCardText>

      <VCardText>
        <VDataTable :search="search" :headers="inputsTableFilter" :items="dataList"
          :items-per-page="options.itemsPerPage" :page="options.page" :options="options">

        </VDataTable>

      </VCardText>
    </VCard>
  </div>
</template>
