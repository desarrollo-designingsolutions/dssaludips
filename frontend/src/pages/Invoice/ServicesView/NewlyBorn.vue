<script setup lang="ts">
const { dataList } = defineProps({
  dataList: {
    type: Array<object>,
    default: [],
  }
})
//table 
const inputsTableFilter = ref([
  { key: 'fechaNacimiento', title: 'Fecha de Nacimiento', sortable: false, width: "200" },
  { key: 'edadGestacional', title: 'Edad Gestacional', sortable: false, width: "200" },
  { key: 'numConsultasCPrenatal', title: 'No Consultas C. Prenatal', sortable: false, width: "200" },
  { key: 'codSexoBiologico', title: 'Cod. Sexo Biologico', sortable: false, width: "350" },
  { key: 'peso', title: 'Peso', sortable: false, width: "200" },
  { key: 'codDiagnosticoPrincipal', title: 'Diagnostico Principal', sortable: false, width: "350" },
  { key: 'condicionDestino', title: 'Condicion Destino Usuario Egreso', sortable: false, width: "200" },
  { key: 'condicionDestinoUsuarioEgreso', title: 'Diagnostico Causa de Muerte', sortable: false, width: "350" },
  { key: 'codDiagnosticoCausaMuerte', title: 'codDiagnosticoCausaMuerte', sortable: false, width: "350" },
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
