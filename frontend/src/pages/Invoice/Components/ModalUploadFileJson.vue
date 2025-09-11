<script setup lang="ts">
import ModalErrorsJson from "@/pages/Invoice/Components/ModalErrorsJson.vue";
import ValidJSONSchema from "@/pages/Invoice/Components/ValidJSONSchema.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();
const emits = defineEmits(["execute"]);

const titleModal = ref<string>("Cargar archivo JSON");
const isDialogVisible = ref<boolean>(false);
const disabledFiledsView = ref<boolean>(false);
const isLoading = ref<boolean>(false);
const progress = ref(0);

const form = ref({
  fileJson: null as File | null,
});

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null;
  }
};

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openModal = async () => {
  handleClearForm();
  handleDialogVisible();

  progress.value = 0;

  titleModal.value = `Cargar JSON`;
};

const submitForm = async () => {
  if (form.value.fileJson) {
    let formData = new FormData();

    formData.append("archiveJson", form.value.fileJson);
    formData.append("company_id", String(authenticationStore.company.id));
    formData.append("user_id", String(authenticationStore.user.id));

    isLoading.value = true;
    const { response, data } = await useAxios(`/invoice/uploadJson`).post(formData);
    isLoading.value = false;

    if (response.status == 200 && data) {
      progress.value = 0;
      emits("execute");
      // handleDialogVisible();

      if (!data.isValid) {
        openModalErrorsJson(data);
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

// ModalErrorsJson
const refModalErrorsJson = ref();
const openModalErrorsJson = (data: any) => {
  refModalErrorsJson.value.openModal(data);
};

const changeFile = (file: File) => {
  form.value.fileJson = file
}
const clearFile = () => {
  form.value.fileJson = null
}
</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="80rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading" class="w-100">
        <!-- Toolbar -->
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText>
          <ValidJSONSchema @loadFile="changeFile" @clearFile="clearFile" />

        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn :loading="isLoading" color="secondary" variant="tonal" @click="handleDialogVisible()">
            Cancelar
          </VBtn>
          <VBtn v-if="form.fileJson" :disabled="isLoading" :loading="isLoading" @click="submitForm()" color="primary">
            Continuar
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <ModalQuestion ref="refModalQuestion" />
    <ModalErrorsJson ref="refModalErrorsJson" />
  </div>
</template>
