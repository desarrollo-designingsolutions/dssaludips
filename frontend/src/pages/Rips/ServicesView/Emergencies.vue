<script setup lang="ts">
const route = useRoute();
const refTableFull = ref()

const optionsTable = {
  showSelect: true,
  url: "/ripInvoiceService/paginateUrgencies",
  paramsGlobal: {
    rip_invoice_user_id: route.params?.ripInvoiceUser_id,
  },
  headers: [
    { key: "fechaInicioAtencion", title: 'Fecha Inicio Atención', sortable: false, minWidth: "200px" },
    { key: "causaMotivoAtencion", title: 'Causa Motivo Atención', sortable: false, minWidth: "350px" },
    { key: "codDiagnosticoPrincipal", title: 'Diagnostico Principal', sortable: false, minWidth: "350px" },
    { key: "codDiagnosticoPrincipalE", title: 'Diagnostico Principal E', sortable: false, minWidth: "350px" },
    { key: "codDiagnosticoRelacionadoE1", title: 'Diagnostico Principal E1', sortable: false, minWidth: "350px" },
    { key: "codDiagnosticoRelacionadoE2", title: 'Diagnostico Principal E2', sortable: false, minWidth: "350px" },
    { key: "codDiagnosticoRelacionadoE3", title: 'Diagnostico Principal E3', sortable: false, minWidth: "350px" },
    { key: "condicionDestinoUsuarioEgreso", title: 'Condicion Destino Usuario Egreso', sortable: false, minWidth: "350px" },
    { key: "codDiagnosticoCausaMuerte", title: 'Diagnostico Causa de Muerte', sortable: false, minWidth: "350px" },
    { key: "fechaEgreso", title: 'Fecha De Egreso', sortable: false, minWidth: "200px" },
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
              No hay urgencias disponibles
            </v-alert>
          </template>

        </TableFull>
      </VCardText>
    </VCard>
  </div>
</template>
