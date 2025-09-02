<script setup lang="ts">
import ModalErrorsXml from "@/pages/Invoice/Components/ModalErrorsXml.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

const emits = defineEmits(["execute"]);

const titleModal = ref<string>("Cargar archivo XML");
const isDialogVisible = ref<boolean>(false);
const disabledFiledsView = ref<boolean>(false);
const isLoading = ref<boolean>(false);
const progress = ref(0);

const form = ref({
  id: '' as string,
  invoice_number: '' as string,
});

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null;
  }
};

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openModal = async (invoice: any) => {
  handleClearForm();
  handleDialogVisible();

  resetValues();
  progress.value = 0;

  titleModal.value = `Cargar XML a la factura ${invoice.invoice_number}`;
  form.value = cloneObject(invoice);
};

const submitForm = async () => {
  if (fileData.value.length > 0) {
    let formData = new FormData();

    if (fileData.value[0]) {
      formData.append("archiveXml", fileData.value[0].file);
    }
    formData.append("company_id", String(authenticationStore.company.id));
    formData.append("user_id", String(authenticationStore.user.id));
    formData.append("invoice_id", String(form.value.id));

    isLoading.value = true;
    const { response, data } = await useAxios(`/invoice/uploadXml`).post(formData);
    isLoading.value = false;

    if (response.status == 200 && data) {
      progress.value = 0;
      emits("execute");
      handleDialogVisible();

      if (data.invoice.status_xml == 'INVOICE_STATUS_XML_002') {
        openModalErrorsXml(data.invoice.id);
      }
    }
  } else {
    refModalQuestion.value.componentData.isDialogVisible = true;
    refModalQuestion.value.componentData.showBtnCancel = false;
    refModalQuestion.value.componentData.btnSuccessText = 'Ok';
    refModalQuestion.value.componentData.title = 'Por favor, seleccione un archivo antes de continuar.';
  }
};

defineExpose({
  openModal,
  disabledFiledsView,
});

// ModalQuestion
const refModalQuestion = ref();

// dropZoneRef
const { dropZoneRef, fileData, open, error, resetValues } = useFileDrop(1, ['xml']);

// Manejar errores
watch(error, (newError) => {
  if (newError) {
    refModalQuestion.value.componentData.isDialogVisible = true;
    refModalQuestion.value.componentData.showBtnCancel = false;
    refModalQuestion.value.componentData.btnSuccessText = 'Ok';
    refModalQuestion.value.componentData.title = newError;
  }
});

// ModalErrorsXml
const refModalErrorsXml = ref();
const openModalErrorsXml = (invoice_id: string) => {
  refModalErrorsXml.value.openModal(invoice_id);
};

// Modificar el click handler del drop zone
const openFileDialog = () => {
  error.value = null; // Limpiar errores antes de abrir
  open();
};
</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="30rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading" class="w-100">
        <!-- Toolbar -->
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText>
          <div class="flex">
            <div class="w-full h-auto relative">
              <div ref="dropZoneRef" class="cursor-pointer" @click="openFileDialog">
                <div v-if="fileData.length === 0"
                  class="d-flex flex-column justify-center align-center gap-y-3 px-6 py-10 border-dashed drop-zone">
                  <IconBtn variant="tonal" class="rounded-sm">
                    <VIcon icon="tabler-upload" />
                  </IconBtn>
                  <div class="text-base text-high-emphasis font-weight-medium">
                    Arrastra y suelta tu archivo aquí.
                  </div>
                  <span class="text-disabled">o</span>
                  <VBtn variant="tonal">Explorar archivos</VBtn>
                </div>

                <div v-else class="d-flex justify-center align-center gap-3 pa-8 border-dashed drop-zone flex-wrap">
                  <VRow class="match-height w-100">
                    <template v-for="(item, index) in fileData" :key="index">
                      <VCol cols="12">
                        <VCard :ripple="false" border class="d-flex flex-column">
                          <VCardText @click.stop>
                            <VImg :src="item.url" />
                            <div class="mt-2">
                              <span class="clamp-text text-wrap">
                                {{ item.file.name }}
                              </span>
                              <span>
                                {{ item.file.size / 1000 }} KB
                              </span>
                            </div>

                            <div v-if="item.status === 'uploading'" class="mt-2">
                              <VProgressCircular :size="24" :value="item.progress" color="primary" />
                              <span>Cargando...</span>
                            </div>
                            <div v-if="item.status === 'completed'" class="mt-2 text-success">
                              <span>¡Archivo cargado exitosamente!</span>
                            </div>
                            <div v-if="item.status === 'failed'" class="mt-2 text-danger">
                              <span>Error al cargar el archivo</span>
                            </div>
                          </VCardText>
                          <VSpacer />
                          <VCardActions>
                            <VBtn variant="outlined" @click.stop="fileData.splice(index, 1)">
                              <VIcon icon="tabler-trash" />
                            </VBtn>
                          </VCardActions>
                        </VCard>
                      </VCol>
                    </template>
                  </VRow>
                </div>
              </div>
            </div>
          </div>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn :loading="isLoading" color="secondary" variant="tonal" @click="handleDialogVisible()">
            Cancelar
          </VBtn>
          <VBtn :disabled="isLoading" :loading="isLoading" @click="submitForm()" color="primary">
            Continuar
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <ModalQuestion ref="refModalQuestion" />
    <ModalErrorsXml ref="refModalErrorsXml" />
  </div>
</template>
