<script setup lang="ts">
const route = useRoute();
const refTableFull = ref()

const optionsTable = {
  showSelect: true,
  url: "/ripInvoiceService/paginateProcedures",
  paramsGlobal: {
    rip_invoice_user_id: route.params?.ripInvoiceUser_id,
  },
  headers: [
    // { key: "consecutivo", title: 'Consecutivo', sortable: true },
    { key: "fechaInicioAtencion", title: 'Fecha Inicio Atención', sortable: false, minWidth: "200px" },
    { key: "idMIPRES", title: 'id Mipres', sortable: false, minWidth: "200px" },
    { key: "numAutorizacion", title: 'No Autorización', sortable: false, minWidth: "200px" },
    { key: "codProcedimiento", title: 'Codigo de Procedimiento', sortable: false, minWidth: "350px" },
    { key: "viaIngresoServicioSalud", title: 'Via Ingreso Servicio Salud', sortable: false, minWidth: "350px" },
    { key: "modalidadGrupoServicioTecSal", title: 'Modalidad Grupo Servicio TecSal', sortable: false, minWidth: "350px" },
    { key: "grupoServicios", title: 'Grupo Servicio', sortable: false, minWidth: "350px" },
    { key: "codServicio", title: 'Codigo Servicio', sortable: false, minWidth: "200px" },
    { key: "finalidadTecnologiaSalud", title: 'Finalidad Tecnologia Salud', sortable: false, minWidth: "350px" },
    { key: "codDiagnosticoPrincipal", title: 'Diagnostico Principal', sortable: false, minWidth: "350px" },
    { key: "codDiagnosticoRelacionado", title: 'Diagnostico Relacionado', sortable: false, minWidth: "350px" },
    { key: "codComplicacion", title: 'Código Complicación', sortable: false, minWidth: "350px" },
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
              No hay procedimientos disponibles
            </v-alert>
          </template>

        </TableFull>
      </VCardText>
    </VCard>
  </div>
</template>
