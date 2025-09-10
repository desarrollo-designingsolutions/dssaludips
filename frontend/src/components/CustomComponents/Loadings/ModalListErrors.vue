<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();

const emit = defineEmits(["execute"])

const titleModal = ref<string>("Listado de errores")
const isDialogVisible = ref<boolean>(false)
const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;

};

const loading = reactive({ csv: false })

//TABLE
const refTableFull = ref()

const optionsTable = ref({
  url: "/processBatch/errorsPaginate",
  headers: [
    { key: 'error_type', title: 'Tipo de error' },
    { key: 'column_name', title: 'Columna' },
    { key: 'row_number', title: 'Fila' },
    { key: 'error_value', title: 'Valor' },
    { key: 'error_message', title: 'Mensaje de error' },
  ],
  paramsGlobal: {
    batch_id: ""
  },
})

//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

const tableLoading = ref(false); // Estado de carga de la tabla

// Nueva prop para controlar si se actualiza la URL
const disableUrlUpdate = ref(true);

// Nuevo método para manejar la búsqueda forzada desde el filtro
const handleForceSearch = (params: any) => {
  if (refTableFull.value) {
    // Si disableUrlUpdate está activo, pasamos los parámetros manualmente
    if (disableUrlUpdate.value && params) {
      refTableFull.value.fetchTableData(null, false, true, params);
    } else {
      refTableFull.value.fetchTableData(null, false, true);
    }
  }
};

const openModal = async (batchId: string) => {
  handleDialogVisible();
  console.log("batchId", batchId);

  optionsTable.value.paramsGlobal.batch_id = batchId
};

defineExpose({
  openModal
})


const downloadErrorsCsv = async () => {
  loading.csv = true;

  try {
    const { data, response } = await useAxios(`/processBatch/generateCsvReportErrors`).post({
        user_id: authenticationStore.user.id,
        batch_id: optionsTable.value.paramsGlobal.batch_id,
    });
  } catch (error) {
    console.error("Error downloading CSV:", error);
  } finally {
    loading.csv = false;
  }


}

</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" max-width="90rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard class="w-100">
        <!-- Toolbar -->

        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText class="d-flex justify-space-between">

          <div class="d-flex justify-end gap-3 flex-wrap ">
            <VBtn :loading="loading.csv" :disabled="loading.csv" size="38" color="primary" icon
              @click="downloadErrorsCsv">
              <VIcon icon="tabler-file-spreadsheet"></VIcon>
              <VTooltip location="top" transition="scale-transition" activator="parent"
                text="Descargar errores en formato CSV">
              </VTooltip>
            </VBtn>
          </div>
        </VCardText>

        <VCardText>
          <FilterDialog :options-filter="optionsFilter" @force-search="handleForceSearch" :table-loading="tableLoading"
            :disable-url-update="disableUrlUpdate">
          </FilterDialog>
        </VCardText>

        <VCardText>
          <TableFull ref="refTableFull" :options="optionsTable" @update:loading="tableLoading = $event"
            :disable-url-update="disableUrlUpdate">
          </TableFull>
        </VCardText>
      </VCard>
    </VDialog>

  </div>
</template>
