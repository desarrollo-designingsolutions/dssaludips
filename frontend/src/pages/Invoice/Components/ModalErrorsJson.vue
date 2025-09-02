<script setup lang="ts">

import { router } from "@/plugins/1.router";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();

// Interfaz para tipar los datos
interface ValidationError {
  level: string;
  key: string;
  data: string;
  message: string;
}

interface ModalData {
  isValid: boolean;
  message: string | null;
  errors: ValidationError[];
  validatedData: object;
}

const emit = defineEmits(["continue", "cancel"]);

const titleModal = ref<string>("Errores en la validación");
const isDialogVisible = ref<boolean>(false);
const loading = reactive({
  excel: false,
  getData: false,
  submit: false,
});

// Inicializar objData con un valor por defecto
const objData = ref<ModalData>({ isValid: false, message: "", errors: [], validatedData: {} });

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  if (!isDialogVisible.value) {
    objData.value = { isValid: false, message: "", errors: [], validatedData: {} }; // Limpiar datos al cerrar
  }
};

const openModal = async (data: any) => {
  handleDialogVisible();
  objData.value = data;
};

defineExpose({ openModal });


// Headers para la tabla
const inputsTableFilter = [
  { title: "Tipo de validación", key: "type" },
  { title: "Nivel", key: "level" },
  { title: "Elemento/Campo", key: "key" },
  { title: "Dato", key: "data" },
  { title: "Descripción error", key: "message" },
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
  exportArrayToExcel(objData.value.errors, "Lista de errores")
};

const cancel = () => {
  emit("cancel");
  handleDialogVisible();
};

const fetchForm = async () => {
  loading.submit = true;
  const { response, data } = await useAxios(`/invoice/jsonToForm`).post({
    json: objData.value.validatedData,
    company_id: authenticationStore.company.id,
  });

  if (response.status == 200 && data) {
    router.push({ name: "Invoice-Form", params: { action: "edit", type: data.invoice.type, id: data.invoice.id } })
  }
  loading.submit = false;
}
const submitForm = async () => {
  if (objData.value.errors.length == 0) {
    fetchForm();
  } else {
    refModalQuestion.value.componentData.title = 'Esta seguro que desea continuar.';
    refModalQuestion.value.componentData.isDialogVisible = true;
  }
};

// Computed que verifica si al menos uno de los valores es true
const isLoading = computed(() => {
  return Object.values(loading).some(value => value);
});

// ModalQuestion
const refModalQuestion = ref();

</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading" class="w-100">
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
            :items="objData.errors" :items-per-page="options.itemsPerPage" :page="options.page" />
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn :loading="isLoading" color="secondary" variant="tonal" @click="cancel">
            Cerrar
          </VBtn>
          <VBtn :disabled="isLoading" :loading="isLoading" @click="submitForm()" color="primary">
            Continuar
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <ModalQuestion ref="refModalQuestion" @success="fetchForm" />

  </div>
</template>
