<script setup lang="ts">
const { dataList } = defineProps({
  dataList: {
    type: Array<object>,
    default: [],
  }
})
//table 
const inputsTableFilter = ref([
  { key: 'viaIngresoServicioSalud', title: 'Via Ingreso Servicio Salud', sortable: false, width: "350" },
  { key: 'fechaInicioAtencion', title: 'Fecha Inicio Atención', sortable: false, width: "200" },
  { key: 'numAutorizacion', title: 'No Autorización', sortable: false, width: "200" },
  { key: 'causaMotivoAtencion', title: 'Causa Motivo Atención', sortable: false, width: "350" },
  { key: 'codDiagnosticoPrincipal', title: 'Diagnostico Principal', sortable: false, width: "350" },
  { key: 'codDiagnosticoPrincipalE', title: 'Diagnostico Principal E', sortable: false, width: "350" },
  { key: 'codDiagnosticoRelacionadoE1', title: 'Diagnostico Principal E1', sortable: false, width: "350" },
  { key: 'codDiagnosticoRelacionadoE2', title: 'Diagnostico Principal E2', sortable: false, width: "350" },
  { key: 'codDiagnosticoRelacionadoE3', title: 'Diagnostico Principal E3', sortable: false, width: "350" },
  { key: 'codComplicacion', title: 'Código Complicación', sortable: false, width: "350" },
  { key: 'condicionDestinoUsuarioEgreso', title: 'Condicion Destino Usuario Egreso', sortable: false, width: "350" },
  { key: 'codDiagnosticoMuerte', title: 'Diagnostico Causa de Muerte', sortable: false, width: "350" },
  { key: 'fechaEgreso', title: 'Fecha De Egreso', sortable: false, width: "200" },
  { key: 'actions', title: 'Acciones', type: 'actions', sortable: false, width: "200" },

])


const options = ref({ page: 1, itemsPerPage: 10, sortBy: [''], sortDesc: [false] })
const search = ref('')

</script>
<template>
  <div>
    <VCard>
      <VCardText>
        <VCardText>
          <VRow>
            <VCol cols="12" offset-md="8" md="4">
              <AppTextField v-model="search" density="compact" placeholder="Search ..."
                append-inner-icon="tabler-search" single-line hide-details dense outlined clearable />
            </VCol>
          </VRow>
        </VCardText>
        <VDataTable :search="search" :headers="inputsTableFilter" :items="dataList"
          :items-per-page="options.itemsPerPage" :page="options.page" :options="options">

        </VDataTable>

      </VCardText>
    </VCard>
  </div>
</template>
