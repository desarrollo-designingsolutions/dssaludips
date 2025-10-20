<script setup lang="ts">
const { dataList } = defineProps({
  dataList: {
    type: Array<object>,
    default: [],
  }
})
//table 
const inputsTableFilter = ref([
  { key: "fechaInicioAtencion", title: 'Fecha Inicio Atención', sortable: false, width: "200" },
  { key: "idMIPRES", title: 'id Mipres', sortable: false, width: "200" },
  { key: "numAutorizacion", title: 'No Autorización', sortable: false, width: "200" },
  { key: "codProcedimiento", title: 'Codigo de Procedimiento', sortable: false, width: "350" },
  { key: "viaIngresoServicioSalud", title: 'Via Ingreso Servicio Salud', sortable: false, width: "350" },
  { key: "modalidadGrupoServicioTecSal", title: 'Modalidad Grupo Servicio TecSal', sortable: false, width: "350" },
  { key: "grupoServicios", title: 'Grupo Servicio', sortable: false, width: "350" },
  { key: "codServicio", title: 'Codigo Servicio', sortable: false, width: "200" },
  { key: "finalidadTecnologiaSalud", title: 'Finalidad Tecnologia Salud', sortable: false, width: "350" },
  { key: "codDiagnosticoPrincipal", title: 'Diagnostico Principal', sortable: false, width: "350" },
  { key: "codDiagnosticoRelacionado", title: 'Diagnostico Relacionado', sortable: false, width: "350" },
  { key: "codComplicacion", title: 'Código Complicación', sortable: false, width: "350" },
  { key: "valorPagoModerador", title: 'Valor Pago Moderador', sortable: false, width: "200" },
  { key: "vrServicio", title: 'Valor Servicio', sortable: false, width: "200" },
  { key: "conceptoRecaudo", title: 'Concepto Recaudo', sortable: false, width: "350" },
  { key: 'actions', title: 'Acciones', type: 'actions', sortable: false, width: "200" },
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
