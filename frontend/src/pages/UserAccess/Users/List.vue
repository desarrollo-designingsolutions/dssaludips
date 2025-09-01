<script setup lang="ts">
definePage({
  name: "User-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "menu.user",
  },
});

import ModalForm from '@/pages/UserAccess/Users/Components/ModalForm.vue';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();
const { company, user } = storeToRefs(authenticationStore);

const route = useRoute()

const loading = reactive({ excel: false })


//TABLE
const refTableFull = ref()

const optionsTable = {
  url: "/user/paginate",
  paramsGlobal: {
    company_id: company.value.id,
  },
  headers: [
    { key: 'full_name', title: 'Nombre Completo' },
    { key: 'email', title: 'Email' },
    { key: 'role_description', title: 'Rol' },
    { key: "is_active", title: 'Estado', },
    { key: 'actions', title: 'Acciones', sortable: false },
  ],
  actions: {
    changeStatus: {
      url: "/user/changeStatus"
    },
    view: {
      show: false,
    },
    delete: {
      show: false,
    },
  }
}

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


//ModalForm
const refModalForm = ref()

const openModalForm = () => {
  refModalForm.value.openModal()
}


const goViewEdit = (data: any) => {
  refModalForm.value.openModal(data.id)
}

const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(1, false, true); // Forzamos la búsqueda
  }
};

const downloadExcel = async () => {
  loading.excel = true;

  const { data, response } = await useAxios("/user/excelExport").get({
    params: {
      ...route.query,
      company_id: authenticationStore.company.id
    }
  })


  loading.excel = false;

  if (response.status == 200 && data) {
    downloadExcelBase64(data.excel, "Lista de usuarios")
  }
}
</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Usuarios
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn :loading="loading.excel" :disabled="loading.excel" size="38" color="primary" icon
            @click="downloadExcel()">
            <VIcon icon="tabler-file-spreadsheet"></VIcon>
            <VTooltip location="top" transition="scale-transition" activator="parent" text="Descargar Excel">
            </VTooltip>
          </VBtn>

          <VBtn @click="openModalForm">
            Agregar usuario
          </VBtn>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialog :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialog>
      </VCardText>

      <VCardText class="mt-2">
        <TableFull ref="refTableFull" :options="optionsTable" @edit="goViewEdit"
          @update:loading="tableLoading = $event">
        </TableFull>
      </VCardText>
    </VCard>

    <ModalForm ref="refModalForm" @execute="refreshTable" />
  </div>
</template>
