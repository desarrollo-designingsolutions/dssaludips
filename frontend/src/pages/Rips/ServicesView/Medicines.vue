<script setup lang="ts">
const route = useRoute();
const refTableFull = ref()

const optionsTable = {
  showSelect: true,
  url: "/ripInvoiceService/paginateMedicines",
  paramsGlobal: {
    rip_invoice_user_id: route.params?.ripInvoiceUser_id,
  },
  headers: [
    // { key: "consecutivo", title: 'Consecutivo', sortable: true },
    { key: "numAutorizacion", title: 'No Autorización', sortable: false, minWidth: "200px" },
    { key: "idMIPRES", title: 'id Mipres', sortable: false, minWidth: "200px" },
    { key: "fechaDispensAdmon", title: 'Fecha Dispens Admon', sortable: false, minWidth: "200px" },
    { key: "codDiagnosticoPrincipal", title: 'Diagnostico Principal', sortable: false, minWidth: "350px" },
    { key: "codDiagnosticoRelacionado", title: 'Diagnostico Relacionado', sortable: false, minWidth: "350px" },
    { key: "tipoMedicamento", title: 'Tipo Medicamento', sortable: false, minWidth: "350px" },
    { key: "codTecnologiaSalud", title: 'Cod Tecnologia Salud', sortable: false, minWidth: "200px" },
    { key: "nomTecnologiaSalud", title: 'Nombre Tecnología Salud', sortable: false, minWidth: "200px" },
    { key: "concentracionMedicamento", title: 'Concentracion Medicamento', sortable: false, minWidth: "200px" },
    { key: "unidadMedida", title: 'Unidad de Medida', sortable: false, minWidth: "350px" },
    { key: "formaFarmaceutica", title: 'Forma Farmaceutica', sortable: false, minWidth: "200px" },
    { key: "unidadMinDispensa", title: 'Unidad Min Dispensa', sortable: false, minWidth: "200px" },
    { key: "cantidadMedicamento", title: 'Cantidad Medicamento', sortable: false, minWidth: "200px" },
    { key: "diasTratamiento", title: 'DÍas Tratamiento', sortable: false, minWidth: "200px" },
    { key: "vrUnitMedicamento", title: 'Valor Unit. Medicamento', sortable: false, minWidth: "200px" },
    { key: "valorPagoModerador", title: 'Valor Pago Moderador', sortable: false, minWidth: "200px" },
    { key: "vrServicio", title: 'Valor Servicio', sortable: false, minWidth: "200px" },
    { key: "conceptoRecaudo", title: 'Concepto Recaudo', sortable: false, minWidth: "350px" },
  ],
  actions: {
  }
}

const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};

</script>
<template>
  <div>
    <VCard class="mt-5">
      <VCardText class=" mt-2">
        <TableFull v-model:selected="invoicesIds" ref="refTableFull" :options="optionsTable"
          @update:loading="tableLoading = $event" @dataFetched="echoChannel">
          <template #no-data>
            <v-alert :value="true" color="warning" icon="mdi-alert">
              No hay medicamentos disponibles
            </v-alert>
          </template>

        </TableFull>
      </VCardText>
    </VCard>
  </div>
</template>
