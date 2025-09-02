<script setup lang="ts">
const emits = defineEmits(["execute"]);

const titleModal = ref<string>("Visualizar soportes.");
const isDialogVisible = ref<boolean>(false);
const isLoading = ref<boolean>(false);

const form = ref({
  fileable_id: null as string | null,
  fileable_type: null as string | null,
})

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
  showBtnDelete.value = true;
}

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openModal = async (fileable_id: string, fileable_type: string, dataExtra: any = {}) => {
  handleClearForm();
  handleDialogVisible();

  form.value.fileable_id = fileable_id
  form.value.fileable_type = fileable_type

  if (dataExtra.status == 'FILINGINVOICE_EST_002') {
    showBtnDelete.value = false;
  }

  // Update optionsTable after form is filled
  updateTableParams();
};

defineExpose({
  openModal,
});


//TABLE
const refTableFull = ref()
const showBtnDelete = ref(true);

const optionsTable = ref({
  url: "file/paginate",
  headers: [
    { key: 'filename', title: 'Nombre' },
    { key: "created_at", title: 'Fecha' },
    { key: 'actions', title: 'Acciones', width: 50 },
  ],
  actions: {
    view: {
      show: false
    },
    edit: {
      show: false
    },
    delete: {
      url: "/file/delete",
      show: true
    },
  }
})

const updateTableParams = () => {
  optionsTable.value.paramsGlobal = {
    fileable_type: form.value.fileable_type,
    fileable_id: form.value.fileable_id,
  };

  optionsTable.value.actions.delete.show = showBtnDelete.value;
};

//FILTER
const refFilterDialogNew = ref()
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

const viewFile = (pathname: any) => {
  window.open(
    `${import.meta.env.VITE_API_BASE_BACK}/storage/${pathname}`,
    "_blank"
  );
};

// Nueva prop para controlar si se actualiza la URL
const disableUrlUpdate = ref(true);

const tableLoading = ref(false); // Estado de carga de la tabla

// Nuevo método para manejar la búsqueda forzada desde el filtro
const handleForceSearch = (params) => {
  if (refTableFull.value) {
    // Si disableUrlUpdate está activo, pasamos los parámetros manualmente
    if (disableUrlUpdate.value && params) {
      refTableFull.value.fetchTableData(null, false, true, params);
    } else {
      refTableFull.value.fetchTableData(1, false, true);
    }
  }
};

</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="90rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" class="w-100">
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText>
          <FilterDialog ref="refFilterDialogNew" :options-filter="optionsFilter" @force-search="handleForceSearch"
            :table-loading="tableLoading" :disable-url-update="disableUrlUpdate">
          </FilterDialog>
        </VCardText>

        <VCardText class="mt-2">

          <TableFull ref="refTableFull" :options="optionsTable" @update:loading="tableLoading = $event"
            :disable-url-update="disableUrlUpdate">
            <template #item.actions2="{ item }">
              <VListItem @click="viewFile(item.pathname)">
                <template #prepend>
                  <VIcon icon="tabler-eye" />
                </template>
                <span>Ver soporte</span>
              </VListItem>
            </template>
          </TableFull>


        </VCardText>

      </VCard>
    </VDialog>
  </div>
</template>
