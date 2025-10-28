<script setup lang="ts">
import { useRipInvoiceUserStore } from "@/pages/Rips/Components/useRipInvoiceUserStore";
import { router } from "@/plugins/1.router";
import { useRoute } from 'vue-router';

definePage({
  path: "Invoice/ListUsers/:invoice_id",
  name: "Invoice-ListUsers",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "menu.invoice",
  },
});

const { dataUser, servicesCount, dataInvoice } = storeToRefs(useRipInvoiceUserStore());

const route = useRoute();
const invoiceId = ref(route.params.invoice_id);

const invoice = ref({
  id: "" as string,
  invoice_number: "" as string,
});
const loading = ref(false);
const fetchUsers = async (opts = {}) => {
  loading.value = true;

  try {
    const { data, response } = await useAxios(`/ripInvoiceUser/getInfoInvoice/${invoiceId.value}`).get({
      params: {
        invoice_id: invoiceId,
      }
    })

    if (response.status == 200 && data) {
      invoice.value = data.invoice;
    }
  } catch (error) {
    console.error('Error al obtener informacion:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchUsers();
});


const goViewServices = (obj: any) => {

  dataUser.value = obj;

  const id = obj.id;
  router.push({
    name: "Invoice-ListUserServicesView",
    params: {
      id: invoice.value.id,
      numFactura: invoice.value.invoice_number,
      ripInvoiceUser_id: id,
    },
  });
};

const goBack = () => {
  router.push({
    name: "Rips-ListInvoices",
    params: { id: invoice.value.rip_id },
  });
}

//FILTER
const filterTable = ref()
const optionsFilter = ref({
  dialog: {
    width: 500,
    cols: '6',
    inputs: [],
  },
  filterLabels: { inputGeneral: 'Buscar en todo' }

})

const refTableFull = ref()

const optionsTable = {
  showSelect: true,
  url: "/ripInvoiceUser/paginate",
  paramsGlobal: {
    rip_invoice_id: invoiceId,
  },
  headers: [
    { key: "actions", title: "Acciones", type: "actions", sortable: false, minWidth: "100px", fixed: true },
    { key: "consecutivo", title: "Consecutivo", sortable: true, minWidth: "10px" },
    { key: "tipoDocumentoIdentificacion", title: "Tipo de Documento", sortable: true, minWidth: "350px" },
    { key: "numDocumentoIdentificacion", title: "No Documento", sortable: true, minWidth: "200px" },
    { key: "tipoUsuario", title: "Tipo de Usuario", sortable: true, minWidth: "350px" },
    { key: "fechaNacimiento", title: "Fecha de Nacimiento", sortable: true, minWidth: "200px" },
    { key: "codSexo", title: "Sexo", sortable: true, minWidth: "200px" },
    { key: "codPaisResidencia", title: "Pais Residencia", sortable: true, minWidth: "350px" },
    { key: "codMunicipioResidencia", title: "Municipio Residencia", sortable: true, minWidth: "350px" },
    { key: "codZonaTerritorialResidencia", title: "Zona Territorial Residencia", sortable: true, minWidth: "350px" },
    { key: "incapacidad", title: "Incapacidad", sortable: true, minWidth: "200px" },
    { key: "codPaisOrigen", title: "Pais Origen", sortable: true, minWidth: "350px" },
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
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Lista de usuarios
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn icon @click="goBack">
            <VIcon icon="tabler-arrow-narrow-left" />
            <VTooltip location="top" transition="scale-transition" activator="parent" text="Regresar">
            </VTooltip>
          </VBtn>

          <!-- Cuadro estilizado -->
          <div class="info-box">
            <div class="info-row">
              <span class="info-title">Número de factura:</span>
              <span class="info-value">{{ invoice.invoice_number }}</span>
            </div>
            <div class="info-row">
              <span class="info-title">Cant. usuarios:</span>
              <span class="info-value">{{ invoice.count_users }}</span>
            </div>
          </div>
        </div>

      </VCardTitle>

      <VCardText>
        <FilterDialog :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialog>
      </VCardText>

      <VCardText class=" mt-2">
        <TableFull v-model:selected="invoicesIds" ref="refTableFull" :options="optionsTable"
          @update:loading="tableLoading = $event" @dataFetched="echoChannel">

          <template #item.tipoDocumentoIdentificacion="{ item }">
            <div>
              {{ item.tipoDocumentoIdentificacion?.title }}
            </div>
          </template>

          <template #item.tipoUsuario="{ item }">
            <div>
              {{ item.tipoUsuario?.title }}
            </div>
          </template>

          <template #item.codSexo="{ item }">
            <div>
              {{ item.codSexo?.title }}
            </div>
          </template>

          <template #item.codPaisResidencia="{ item }">
            <div>
              {{ item.codPaisResidencia?.title }}
            </div>
          </template>

          <template #item.codMunicipioResidencia="{ item }">
            <div>
              {{ item.codMunicipioResidencia?.title }}
            </div>
          </template>

          <template #item.codZonaTerritorialResidencia="{ item }">
            <div>
              {{ item.codZonaTerritorialResidencia?.title }}
            </div>
          </template>

          <template #item.codPaisOrigen="{ item }">
            <div>
              {{ item.codPaisOrigen?.title }}
            </div>
          </template>

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
              No hay usuarios disponibles
            </v-alert>
          </template>

        </TableFull>
      </VCardText>
    </VCard>
  </div>
</template>

<style scoped>
.info-box {
  /* Bordes redondeados */
  padding: 10px;

  /* Fondo gris claro */
  border: 1px solid #e0e0e0;

  /* Borde sutil */
  border-radius: 8px;
  background-color: #f5f5f5;

  /* Espacio interno */
  min-inline-size: 200px;

  /* Ancho mínimo */
}

.info-row {
  display: flex;

  /* Título a la izquierda, valor a la derecha */
  align-items: center;
  justify-content: space-between;

  /* Espacio vertical entre filas */
  gap: 20px;
  padding-block: 5px;
  padding-inline: 0;

  /* Separación adicional entre título y valor */
}

.info-title {
  /* Color del texto */
  flex-shrink: 0;

  /* Título en negrita */
  color: #333;
  font-weight: bold;

  /* Evita que el título se encoja demasiado */
}

.info-value {
  color: #007bff;

  /* Color del valor */
  text-align: end;

  /* Alinea el valor a la derecha */
}
</style>
