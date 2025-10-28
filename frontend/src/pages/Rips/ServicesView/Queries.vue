<script setup lang="ts">
const route = useRoute();
const refTableFull = ref()

const optionsTable = {
  showSelect: true,
  url: "/ripInvoiceService/paginateQueries",
  paramsGlobal: {
    rip_invoice_user_id: route.params?.ripInvoiceUser_id,
  },
  headers: [
    { key: "actions", title: "Acciones", type: "actions", sortable: false, minWidth: "100px", fixed: true },
    { key: "consecutivo", title: 'Consecutivo', sortable: true },
    { key: "fechaInicioAtencion", title: 'Fecha Inicio Atención', sortable: false, minWidth: "200px" },
    { key: "numAutorizacion", title: 'No Autorización', sortable: false, minWidth: "200px" },
    { key: "codConsulta", title: 'Codigo de Consulta', sortable: false, minWidth: "350px" },
    { key: "modalidadGrupoServicioTecSal", title: 'Modalidad Grupo Servicio TecSal', sortable: false, minWidth: "350px" },
    { key: "grupoServicios", title: 'Grupo Servicios', sortable: false, minWidth: "350px" },
    { key: "codServicio", title: 'Codigo Servicio', sortable: false, minWidth: "350px" },
    { key: "finalidadTecnologiaSalud", title: 'Finalidad Tecnologia Salud', sortable: false, minWidth: "350px" },
    { key: "causaMotivoAtencion", title: 'Causa Motivo Atención', sortable: false, minWidth: "350px" },
    { key: "codDiagnosticoPrincipal", title: 'Diagnostico Principal', sortable: false, minWidth: "350px" },
    { key: "codDiagnosticoRelacionado1", title: 'Diagnostico Relacionado 1', sortable: false, minWidth: "200px" },
    { key: "codDiagnosticoRelacionado2", title: 'Diagnostico Relacionado 2', sortable: false, minWidth: "200px" },
    { key: "codDiagnosticoRelacionado3", title: 'Diagnostico Relacionado 3', sortable: false, minWidth: "200px" },
    { key: "tipoDiagnosticoPrincipal", title: 'Tipo Diagnostico Principal', sortable: false, minWidth: "200px" },
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

          <template #item.actions="{ item }">
            <div>
              <VBtn icon color="primary">
                <VIcon icon="tabler-square-rounded-chevron-down"></VIcon>
                <VMenu activator="parent">
                  <VList>

                    <VListItem @click="goViewServices(item)">Ver Servicios</VListItem>

                  </VList>
                </VMenu>
              </VBtn>
            </div>
          </template>
          <template #no-data>
            <v-alert :value="true" color="warning" icon="mdi-alert">
              No hay consultas disponibles
            </v-alert>
          </template>

        </TableFull>
      </VCardText>
    </VCard>
  </div>
</template>
