<script setup lang="ts">
const route = useRoute();
const refTableFull = ref()

const optionsTable = {
  showSelect: true,
  url: "/ripInvoiceService/paginateOtherServices",
  paramsGlobal: {
    rip_invoice_user_id: route.params?.ripInvoiceUser_id,
  },
  headers: [
    // { key: "consecutivo", title: 'Consecutivo', sortable: true },
    { key: "numAutorizacion", title: 'No Autorización', sortable: false, minWidth: "200px" },
    { key: "idMIPRES", title: 'id Mipres', sortable: false, minWidth: "200px" },
    { key: "fechaSuministroTecnologia", title: 'Fecha Suministro Tecnología', sortable: false, minWidth: "200px" },
    { key: "tipoOS", title: 'Tipo Otros Servicios', sortable: false, minWidth: "350px" },
    { key: "codTecnologiaSalud", title: 'Cod Tecnologia Salud', sortable: false, minWidth: "200px" },
    { key: "nomTecnologiaSalud", title: 'Nombre Tecnología Salud', sortable: false, minWidth: "200px" },
    { key: "cantidadOS", title: 'Cantidad Otro Servicio', sortable: false, minWidth: "200px" },
    { key: "vrUnitOS", title: 'Valor Unit. Servicio', sortable: false, minWidth: "200px" },
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
              No hay otros servicios disponibles
            </v-alert>
          </template>

        </TableFull>
      </VCardText>
    </VCard>
  </div>
</template>
