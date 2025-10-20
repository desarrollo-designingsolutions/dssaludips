<script setup lang="ts">
const { dataList } = defineProps({
  dataList: {
    type: Array<object>,
    default: [],
  }
})
//table 
const inputsTableFilter = ref([
  { key: "numAutorizacion", title: 'No Autorización', sortable: false, width: "200" },
  { key: "idMIPRES", title: 'id Mipres', sortable: false, width: "200" },
  { key: "fechaDispensAdmon", title: 'Fecha Dispens Admon', sortable: false, width: "200" },
  { key: "codDiagnosticoPrincipal", title: 'Diagnostico Principal', sortable: false, width: "350" },
  { key: "codDiagnosticoRelacionado", title: 'Diagnostico Relacionado', sortable: false, width: "350" },
  { key: "tipoMedicamento", title: 'Tipo Medicamento', sortable: false, width: "350" },
  { key: "codTecnologiaSalud", title: 'Cod Tecnologia Salud', sortable: false, width: "200" },
  { key: "nomTecnologiaSalud", title: 'Nombre Tecnología Salud', sortable: false, width: "200" },
  { key: "concentracionMedicamento", title: 'Concentracion Medicamento', sortable: false, width: "200" },
  { key: "unidadMedida", title: 'Unidad de Medida', sortable: false, width: "350" },
  { key: "formaFarmaceutica", title: 'Forma Farmaceutica', sortable: false, width: "200" },
  { key: "unidadMinDispensa", title: 'Unidad Min Dispensa', sortable: false, width: "200" },
  { key: "cantidadMedicamento", title: 'Cantidad Medicamento', sortable: false, width: "200" },
  { key: "diasTratamiento", title: 'DÍas Tratamiento', sortable: false, width: "200" },
  { key: "vrUnitMedicamento", title: 'Valor Unit. Medicamento', sortable: false, width: "200" },
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
