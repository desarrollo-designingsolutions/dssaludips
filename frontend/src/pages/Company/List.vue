<script setup lang="ts">
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

definePage({
  name: "Company-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "company.list",
  },
});

const authenticationStore = useAuthenticationStore();

//FILTER
const optionsFilter = ref({
  dialog: {
    cols: "12",
    width: 500,
    inputs: [
      {
        name: "is_active",
        label: "Estado",
        type: "booleanActive",
        value: null,
        placeholder: "Ingrese valor"
      },
    ],
  },
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

//TABLE
const refTableFull = ref()
const optionsTable = {
  url: "/company/list",
  headers: [
    { key: 'logo', title: 'Logo', sortable: false },
    { key: 'name', title: 'Nombre compañia' },
    { key: 'nit', title: 'Nit' },
    { key: 'email', title: 'Correo' },
    { key: 'phone', title: 'Teléfono' },
    { key: "is_active", title: 'Estado', },
    { key: 'actions', title: 'Acciones', sortable: false },
  ],
  actions: {
    changeStatus: {
      url: "/company/changeStatus"
    },
    view: {
      show: false,
    },
    delete: {
      show: false,
    },
  }
}

const goViewEdit = (data: any) => {
  router.push({ name: "Company-Form", params: { action: "edit", id: data.id } })
}

const goViewCreate = () => {
  router.push({ name: "Company-Form", params: { action: "create" } })
}



const selectCompany = (company: { id: null }) => {
  authenticationStore.company = { id: company.id };
  router.push({ name: "Home" });
};


const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(1, false, true); // Forzamos la búsqueda
  }
};

</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Compañias
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn @click="goViewCreate()">
            Agregar compañia
          </VBtn>
        </div>
      </VCardTitle>

      <!-- Sección de filtros mejorada -->
      <VCardText>
        <FilterDialog :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialog>
      </VCardText>

      <VCardText class="mt-2">

        <TableFull ref="refTableFull" :options="optionsTable" @edit="goViewEdit"
          @update:loading="tableLoading = $event">


          <template #item.logo="{ item }">
            <div class="my-2">
              <VImg style="inline-size: 80px;" :src="storageBack(item.logo)"></VImg>
            </div>
          </template>

          <template #item.actions2="{ item }">
            <VListItem @click="selectCompany(item)">
              <template #prepend>
                <VIcon size="22" icon="tabler-square-rounded-arrow-right" />
              </template>
              <span>Ingresar</span>
            </VListItem>
          </template>

        </TableFull>


      </VCardText>
    </VCard>
  </div>
</template>
