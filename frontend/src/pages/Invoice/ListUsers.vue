<script setup lang="ts">
import { useInvoiceUserStore } from "@/pages/Invoice/Components/useInvoiceUserStore";
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

const { dataUser, servicesCount, dataInvoice } = storeToRefs(useInvoiceUserStore());


const route = useRoute();
const invoiceId = ref(route.params.invoice_id);

const invoice = ref({
  id: "" as string,
  invoice_number: "" as string,
});
const users = ref([]);
const loading = ref(false);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
});
const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [],
  sortDesc: [],
});

const inputsTableFilter = ref([
  { key: "actions", title: "Acciones", type: "actions", sortable: false, width: "100", fixed: true },
  { key: "consecutivo", title: "Consecutivo", sortable: true, width: "10" },
  { key: "tipoDocumentoIdentificacion", title: "Tipo de Documento", sortable: true, width: "350" },
  { key: "numDocumentoIdentificacion", title: "No Documento", sortable: true, width: "200" },
  { key: "tipoUsuario", title: "Tipo de Usuario", sortable: true, width: "350" },
  { key: "fechaNacimiento", title: "Fecha de Nacimiento", sortable: true, width: "200" },
  { key: "codSexo", title: "Sexo", sortable: true, width: "200" },
  { key: "codPaisResidencia", title: "Pais Residencia", sortable: true, width: "350" },
  { key: "codMunicipioResidencia", title: "Municipio Residencia", sortable: true, width: "350" },
  { key: "codZonaTerritorialResidencia", title: "Zona Territorial Residencia", sortable: true, width: "350" },
  { key: "incapacidad", title: "Incapacidad", sortable: true, width: "200" },
  { key: "codPaisOrigen", title: "Pais Origen", sortable: true, width: "350" },
]);

const fetchUsers = async (opts = {}) => {
  loading.value = true;
  const { page, itemsPerPage, sortBy, sortDesc } = opts;

  try {
    const { data, response } = await useAxios(`/invoice/${invoiceId.value}/paginatedUsers`).get({
      params: {
        page: page || options.value.page,
        per_page: itemsPerPage || options.value.itemsPerPage,
        sortBy: sortBy?.length ? sortBy[0] : '',
        sortDesc: sortDesc?.length ? sortDesc[0] : false,
      }
    })

    if (response.status == 200 && data) {
      invoice.value = data.invoice;
      dataInvoice.value = cloneObject(data.invoice);
      users.value = data.dataUsers;
      pagination.value = data.pagination;
      options.value.page = pagination.value.current_page;
      options.value.itemsPerPage = pagination.value.per_page;
    }
  } catch (error) {
    console.error('Error al obtener usuarios:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  dataUser.value = { arrayData: [] };
});


const goViewServices = (obj: any) => {

  dataUser.value = obj;

  const numDocumentoIdentificacion = obj.numDocumentoIdentificacion;
  router.push({
    name: "Invoice-ListUserServicesView",
    params: {
      id: invoice.value.id,
      numFactura: invoice.value.invoice_number,
      numDocumentoIdentificacion: numDocumentoIdentificacion,
    },
  });
};

const goBack = () => {
  router.go(-1);
}

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
              <span class="info-value">{{ pagination.total }}</span>
            </div>
          </div>
        </div>

      </VCardTitle>

      <VCardText class="mt-5">
        <VDataTable :headers="inputsTableFilter" :items="users" :server-items-length="pagination.total"
          :items-per-page="pagination.per_page" :page="pagination.current_page" :loading="loading"
          :options.sync="options" @update:options="fetchUsers">


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
        </VDataTable>
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
