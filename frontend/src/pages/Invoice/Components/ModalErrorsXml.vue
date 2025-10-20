<script setup lang="ts">

// Interfaz para tipar los datos
interface ValidationError {
  type: string;
  validacion_type_Y: string;
  num_invoice: string;
  file: string;
  column: string;
  row: string;
  validacion: string;
  data: string;
  error: string;
}

interface ModalData {
  invoice_id: string;
  validationXml?: ValidationError[] | null;
}

const emit = defineEmits(["continue", "cancel"]);

const titleModal = ref<string>("Errores en la validación");
const isDialogVisible = ref<boolean>(false);
const loading = reactive({
  excel: false,
  getData: false,
});

// Inicializar objData con un valor por defecto
const objData = ref<ModalData>({ invoice_id: "" });

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  if (!isDialogVisible.value) {
    objData.value = { invoice_id: "" }; // Limpiar datos al cerrar
    errorMessages.value = [];
  }
};

const openModal = async (invoice_id: string) => {
  handleDialogVisible();
  objData.value = { invoice_id };
  await init();
};

defineExpose({ openModal });

const errorMessages = ref<ValidationError[]>([]);

const init = async () => {
  try {
    errorMessages.value = [];
    loading.getData = true;
    const { data, response } = await useAxios(`/invoice/showErrorsValidationXml/${objData.value.invoice_id}`).get();
    if (response.status === 200 && data?.errorMessages && Array.isArray(data.errorMessages)) {
      errorMessages.value = data.errorMessages;
    } else {
      console.error("No se encontraron mensajes de error en la respuesta");
    }
  } catch (error) {
    console.error("Error al cargar errores de validación:", error);
    errorMessages.value = [];
  } finally {
    loading.getData = false;
  }
};

// Headers para la tabla
const inputsTableFilter = [
  { title: "Tipo de Validación", key: "validacion_type_Y" },
  { title: "Num Factura", key: "num_invoice" },
  { title: "Archivo", key: "file" },
  { title: "Columna", key: "column" },
  { title: "Fila", key: "row" },
  { title: "Validación", key: "validacion" },
  { title: "Dato registrado", key: "data" },
  { title: "Descripción error", key: "error" },
];

// Opciones reactivas para la tabla
const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [],
  sortDesc: [],
});
const search = ref("");

const downloadExcel = async () => {
  try {
    loading.excel = true;
    const { data, response } = await useAxios(`/invoice/excelErrorsValidationXml/${objData.value.invoice_id}`).get();
    if (response.status === 200 && data?.excel) {
      downloadExcelBase64(data.excel, "Lista de errores de validación");
    } else {
      console.error("Error al descargar el Excel");
    }
  } catch (error) {
    console.error("Error al exportar a Excel:", error);
  } finally {
    loading.excel = false;
  }
};

const cancel = () => {
  emit("cancel");
  handleDialogVisible();
};
</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="loading.getData" :disabled="loading.getData" class="w-100">
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText>
          <VRow>
            <VCol cols="12" sm="6">
              <AppTextField v-model="search" density="compact" placeholder="Buscar..." append-inner-icon="tabler-search"
                single-line hide-details clearable />
            </VCol>
            <VCol cols="12" sm="6" class="d-flex justify-end">
              <VBtn :loading="loading.excel" :disabled="loading.excel" @click="downloadExcel">
                Exportar a Excel
              </VBtn>
            </VCol>
          </VRow>
        </VCardText>

        <VCardText>
          <VDataTable v-model:options="options" v-model:search="search" :headers="inputsTableFilter"
            :items="errorMessages" :items-per-page="options.itemsPerPage" :page="options.page" />
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn :loading="loading.getData" color="secondary" variant="tonal" @click="cancel">
            Cerrar
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
