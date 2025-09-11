<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from "vuetify/components/VForm";

const { toast } = useToast()
const authenticationStore = useAuthenticationStore();
const { company } = storeToRefs(authenticationStore)

const errorsBack = ref<IErrorsBack>({});
const refForm = ref<VForm>();
const emit = defineEmits(["execute"])

const titleModal = ref<string>("Glosas Masivas")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const isLoading = ref<boolean>(false)

const form = ref({
  glosas: [] as Array<{
    id: string | null,
    codeGlosa: string | null,
    partialValue: number | string | null,
    observation: string | null,
    user_id: string | number | null,
    service_id: string | number | null,
    file: string | File | null,
    date: string | null,
  }>,
  servicesIds: null as Array<string> | null,
  company_id: null as string | null
})

const handleClearForm = () => {
  form.value = {
    glosas: [],
    servicesIds: null,
    company_id: null
  }
}

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openModal = async (servicesIds: Array<string> | null = null) => {
  handleClearForm()
  handleDialogVisible();
  addDataArray();
  titleModal.value = `Glosas Masivas: ${servicesIds?.length || 0} Servicio/s Seleccionado/s`;
  form.value.servicesIds = servicesIds;
};

const submitForm = async (isCreateAndNew: boolean = false) => {
  const validation = await refForm.value?.validate()
  if (!validation?.valid) {
    toast('Faltan Campos Por Diligenciar', '', 'danger')
    return
  }

  try {
    isLoading.value = true;

    const formData = new FormData();
    const processedGlosas = form.value.glosas.map(element => ({
      ...element,
      code_glosa_id: element.codeGlosa.value,
      partialValue: parseEuropeanNumber(element.partialValue),
      user_id: authenticationStore.user.id
    }));

    formData.append('company_id', String(authenticationStore.company.id))
    formData.append('glosas', JSON.stringify(processedGlosas))
    formData.append('servicesIds', JSON.stringify(form.value.servicesIds))
    formData.append('cantfiles', String(processedGlosas.length))

    processedGlosas.forEach((glosa, i) => {
      formData.append(`file_id${i}`, glosa.id)
      if (glosa.file) formData.append(`file_file${i}`, glosa.file)
    });

    const { data, response } = await useAxios('/glosa/storeMasive').post(formData);

    if (response.status === 200 && data) {
      handleDialogVisible();
    }

    if (data.code === 422) {
      errorsBack.value = data.errors ?? {};
    }
  } catch (error) {
    toast('Error al guardar las glosas', '', 'danger')
  } finally {
    isLoading.value = false;
  }
}

const parseEuropeanNumber = (value: string): number => {
  if (!value) return 0;
  return parseFloat(value.replace(/\./g, '').replace(',', '.'));
}

const addDataArray = () => {
  const nextId = form.value.glosas.length.toString()
  form.value.glosas.push({
    id: nextId,
    codeGlosa: null,
    partialValue: 0,
    observation: null,
    user_id: null,
    service_id: null,
    file: null,
    date: null,
  })
}

const deleteDataArray = (index: number) => {
  form.value.glosas.splice(index, 1);
}

const shouldShowDeleteButton = () => form.value.glosas.length > 1;

const positiveValidator = (value: number | string) => {
  const num = typeof value === 'string' ? parseFloat(value) : value
  return isNaN(num) || num <= 0 ? 'El valor debe ser mayor que cero' : true
}

const dataPercentage = reactive({
  partial_value_real: 0 as number,
})

const dataReal = (data: any, field: string) => {
  dataPercentage[field] = data
}

const changeFile = (e: any, item: any) => {
  item.file = e
}

defineExpose({
  openModal
})
</script>


<template>
  <VDialog v-model="isDialogVisible" :overlay="false" max-width="65rem" transition="dialog-transition" persistent>
    <DialogCloseBtn @click="handleDialogVisible" />
    <VCard :loading="isLoading" :disabled="isLoading" class="glosas-modal">

      <VToolbar color="primary" class="rounded-t">
        <VToolbarTitle>
          {{ titleModal }}
        </VToolbarTitle>
      </VToolbar>

      <VCardText class="pt-6">
        <VForm ref="refForm" @submit.prevent>
          <div class="d-flex justify-space-between align-center mb-4">
            <div class="text-subtitle-1 font-weight-medium">
              Registros de Glosas
            </div>
            <VBtn prepend-icon="tabler-plus" color="success" variant="tonal" @click="addDataArray"
              :disabled="isLoading">
              Agregar Glosa
            </VBtn>
          </div>

          <VDivider class="mb-6" />

          <div v-for="(item, index) in form.glosas" :key="index" class="glosa-item">
            <div class="d-flex align-center mb-4">
              <div class="text-subtitle-2 font-weight-medium">
                Glosa #{{ index + 1 }}
              </div>
              <VSpacer />
              <VBtn v-if="shouldShowDeleteButton() && !disabledFiledsView" icon="tabler-trash" color="error"
                variant="tonal" size="small" @click="deleteDataArray(index)" />
            </div>

            <VRow>
              <VCol cols="12" md="8">
                <AppSelectRemote label="Código de Glosa" :requiredField="true" :rules="[requiredValidator]"
                  v-model="item.codeGlosa" url="/selectInfiniteCodeGlosa" array-info="codeGlosa"
                  :error-messages="errorsBack[`${item.id}.codeGlosa`]" @input="errorsBack[`${item.id}.codeGlosa`] = ''"
                  clearable />
              </VCol>

              <VCol cols="12" md="4">
                <PercentageInput :requiredField="true" label="Valor de la Glosa" clearable maxDecimal="2"
                  v-model="item.partialValue" @realValue="dataReal($event, 'partial_value_real')"
                  :rules="[requiredValidator, positiveValidator]" />
              </VCol>

              <VCol cols="12">
                <AppTextarea :requiredField="true" label="Observación" :rules="[requiredValidator]" clearable
                  v-model="item.observation" outlined :error-messages="errorsBack[`${item.id}.observation`]"
                  @input="errorsBack[`${item.id}.observation`] = ''" rows="3" />
              </VCol>

              <VCol cols="12" md="6">
                <AppFileInput label="Archivo Adjunto" :label2="item.file ? '1 archivo agregado' : ''" clearable
                  :key="index" @update:model-value="changeFile($event, item)" />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField clearable type="date" :disabled="disabledFiledsView" :requiredField="true"
                  :error-messages="errorsBack.date" :rules="[requiredValidator]" v-model="item.date" label="Fecha"
                  @input="errorsBack[`${item.id}.date`] = ''" />
              </VCol>
            </VRow>

            <VDivider v-if="index < form.glosas.length - 1" class="my-6" />
          </div>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <VSpacer />
        <VBtn color="secondary" :disabled="isLoading" @click="handleDialogVisible" class="me-3">
          Cancelar
        </VBtn>
        <VBtn color="primary" :loading="isLoading" :disabled="isLoading" @click="submitForm">
          Guardar Glosas
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.glosas-modal {
  border-radius: 8px;
  overflow: hidden;
}

.glosa-item {
  background-color: rgba(var(--v-theme-surface-variant), 0.05);
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1rem;
}

.glosa-item:last-child {
  margin-bottom: 0;
}
</style>
