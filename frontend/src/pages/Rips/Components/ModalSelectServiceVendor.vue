<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";

const errorsBack = ref<IErrorsBack>({});
const authenticationStore = useAuthenticationStore();
const emits = defineEmits(["success", "cancel"]);

const componentData = reactive({
  isDialogVisible: false,
  isLoading: false,
});

const disabledFiledsView = ref<boolean>(false)

const serviceVendors_arrayInfo = ref([])

const form = ref({
  service_vendor_id: null as null | string,
  company_id: authenticationStore.company.id,
  user_id: authenticationStore.user.id
})

const handleIsDialogVisible = () => {
  componentData.isDialogVisible = !componentData.isDialogVisible;
};

const openModal = async (id: number | string | null = null) => {
  form.value = {};
  handleIsDialogVisible();
};

const handleSubmit = async () => {
  const url = `/rip/createRipManual`

  componentData.isLoading = true
  const { data, response } = await useAxios(`${url}`).post(form.value);

  if (response.status == 200 && data && data.code === 200) {
    handleIsDialogVisible();
    emits("success");
  }

  if (data.code == 422) {
    errorsBack.value = data.errors ?? {}
  }

  componentData.isLoading = false
};

defineExpose({
  openModal,
  componentData,
});

const paramsSelectInfinite = {
  company_id: authenticationStore.company.id,
}
</script>

<template>
  <VDialog v-model="componentData.isDialogVisible" max-width="30rem" persistent transition="dialog-bottom-transition"
    class="confirmation-dialog">
    <DialogCloseBtn @click="handleIsDialogVisible()" class="close-btn" />
    <VCard :loading="componentData.isLoading" class="rounded-lg">
      <div>
        <VToolbar color="primary">
          <VToolbarTitle>
            Crear Rip
          </VToolbarTitle>
        </VToolbar>
      </div>
      <VCardText class="d-flex justify-center gap-4 pb-6 px-6">
        <VRow>
          <VCol cols="12">
            <AppSelectRemote :disabled="disabledFiledsView" v-model="form.service_vendor_id"
              url="/selectInfiniteServiceVendor" arrayInfo="serviceVendors" clearable :params="paramsSelectInfinite"
              :itemsData="serviceVendors_arrayInfo" :rules="[requiredValidator]"
              :error-messages="errorsBack.service_vendor_id" @input="errorsBack.service_vendor_id = ''">
            </AppSelectRemote>
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <v-btn @click="handleIsDialogVisible()">
          Cancelar
        </v-btn>

        <v-btn color="primary" @click="handleSubmit()">
          Crear
        </v-btn>
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
