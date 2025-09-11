<script setup lang="ts">
const emits = defineEmits(["success", "cancel"]);

const componentData = reactive({
  isDialogVisible: false,
  isLoading: false,
  principalIcon: "tabler-alert-circle",
  title: "¿Está seguro que desea proceder con esta acción?",
  subTitle: "",
  html: null,
  btnSuccessText: "Si",
  btnSuccessIcon: "tabler-check",
  btnCancelText: "Cancelar",
  btnCancelIcon: "tabler-x",
  showActions: true,
  showBtnCancel: true,
  showBtnSuccess: true,
  dialogMaxWidth: '40rem',
  id: null as number | string | null,
});

const handleIsDialogVisible = () => {
  componentData.isDialogVisible = !componentData.isDialogVisible;
};

const openModal = async (id: number | string | null = null) => {
  handleIsDialogVisible();
  componentData.id = id;
};

const handleSubmit = async () => {
  handleIsDialogVisible();
  emits("success", componentData.id);
};

const handleCancel = async () => {
  handleIsDialogVisible();
  emits("cancel");
};

defineExpose({
  openModal,
  componentData,
});
</script>

<template>
  <VDialog v-model="componentData.isDialogVisible" :max-width="componentData.dialogMaxWidth" persistent
    transition="dialog-bottom-transition" class="confirmation-dialog">
    <DialogCloseBtn @click="handleIsDialogVisible()" class="close-btn" />
    <VCard :loading="componentData.isLoading" class="rounded-lg">
      <VCardText class="text-center pt-8 pb-4">
        <VIcon :icon="componentData.principalIcon" size="4rem" color="primary" class="mb-4 icon-pulse" />
        <h2 class="text-h4 font-weight-bold mb-2">{{ componentData.title }}</h2>
        <span class="text-body-1 text-medium-emphasis">{{ componentData.subTitle }}</span>
      </VCardText>

      <VCardText v-if="componentData.html" class="text-center px-6">
        <div v-html="componentData.html"></div>
      </VCardText>

      <VCardText v-if="componentData.showActions" class="d-flex justify-center gap-4 pb-6 px-6">
        <VBtn v-if="componentData.showBtnCancel" color="secondary" @click="handleCancel()" min-width="120"
          class="text-button">
          <VIcon start :icon="componentData.btnCancelIcon" class="mr-2" />
          {{ componentData.btnCancelText }}
        </VBtn>
        <VBtn v-if="componentData.showBtnSuccess" variant="elevated" color="primary" @click="handleSubmit()"
          min-width="120" class="text-button">
          <VIcon start :icon="componentData.btnSuccessIcon" class="mr-2" />
          {{ componentData.btnSuccessText }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.confirmation-dialog {
  backdrop-filter: blur(4px);
}

.close-btn {
  position: absolute;
  top: -12px;
  right: -12px;
  z-index: 1;
}

.icon-pulse {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }

  50% {
    transform: scale(1.1);
    opacity: 0.8;
  }

  100% {
    transform: scale(1);
    opacity: 1;
  }
}

:deep(.v-card) {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.text-button {
  text-transform: none;
  letter-spacing: 0.25px;
}
</style>
