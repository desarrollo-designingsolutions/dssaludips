<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import IImageSelected from "@/interfaces/FileUpload/IImageSelected";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from "vuetify/components/VForm";

const { toast } = useToast()
const authenticationStore = useAuthenticationStore();
const { company } = storeToRefs(authenticationStore)

const errorsBack = ref<IErrorsBack>({});
const refForm = ref<VForm>();
const emit = defineEmits(["execute"])

const titleModal = ref<string>("Glosa")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const isLoading = ref<boolean>(false)
const codeGlosa_arrayInfo1 = ref([])
const codeGlosa_arrayInfo2 = ref([])
const typeCodeGlosa_arrayInfo = ref<Array<{
  title: string;
  value: string;
}>>([])

const form = ref({
  id: null as string | null,
  company_id: null as string | null,
  user_id: null as string | null,
  service_id: null as string | null,
  code_glosa_id: null as string | null,
  glosa_value: null as number | string | null,
  observation: null as string | null,
  file: null as string | File | null,
  date: null as string | null,
})

const totalValueService = ref<string>('')
const totalValueGlosa = ref<number | string | null>(0)

const dataCalculate = reactive({
  real_glosa_value: 0 as number,
})

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  if (!isDialogVisible.value) {
    handleClearForm();
  }
};

const invoiceId = ref()
const radication_date = ref()

const openModal = async ({ id, service_id, total_value, invoice_id }: any, disabled: boolean = false) => {
  handleClearForm();
  handleDialogVisible();

  disabledFiledsView.value = disabled;
  form.value.id = id;
  form.value.service_id = service_id;
  totalValueService.value = total_value;
  invoiceId.value = invoice_id;

  await fetchDataForm();
};

const fetchDataForm = async () => {
  try {
    isLoading.value = true;
    const url = form.value.id ? `/glosa/${form.value.id}/edit` : `/glosa/create`;
    const { data, response } = await useAxios(url).get({
      params: {
        invoice_id: invoiceId.value,
      }
    });

    if (response.status === 200 && data) {
      codeGlosa_arrayInfo1.value = data.codeGlosa1.codeGlosa_arrayInfo
      codeGlosa_arrayInfo2.value = data.codeGlosa2.codeGlosa_arrayInfo

      typeCodeGlosa_arrayInfo.value = data.typeCodeGlosa_arrayInfo

      type_code_glosa_id.value = TYPE_CODE_GLOSA_001.id;

      radication_date.value = data.radication_date;

      if (data.form) {
        form.value = cloneObject(data.form);
        type_code_glosa_id.value = data.type_code_glosa_id;

        totalValueGlosa.value = form.value.glosa_value;
        form.value.glosa_value = currencyFormat(formatToCurrencyFormat(totalValueGlosa.value));
        dataCalculate.real_glosa_value = cloneObject(totalValueGlosa.value);
      }
    }
  } catch (error) {
    toast('Error al cargar los datos', '', 'warning');
  } finally {
    isLoading.value = false;
  }
};

const submitForm = async () => {
  try {
    const validation = await refForm.value?.validate();
    if (!validation?.valid) {
      toast('Faltan campos por diligenciar', '', 'warning');
      return;
    }
    if (!radication_date.value) {
      toast('Debe existir una fecha de radicacion para crear la glosa', '', 'warning');
      return;
    }

    isLoading.value = true;
    const url = form.value.id ? `/glosa/update/${form.value.id}` : `/glosa/store`;

    const formData = new FormData();
    formData.append("id", String(form.value.id));
    formData.append("company_id", String(authenticationStore.company.id));
    formData.append("user_id", String(authenticationStore.user.id));
    formData.append("service_id", String(form.value.service_id));
    formData.append("code_glosa_id", String(form.value.code_glosa_id.value));
    formData.append("glosa_value", String(dataCalculate.real_glosa_value));
    formData.append("observation", String(form.value.observation));
    formData.append("file", inputFile.value.imageFile || form.value.file);
    formData.append("date", String(form.value.date));

    const { data, response } = await useAxios(url).post(formData);

    if (response.status === 200 && data) {
      handleDialogVisible();
      emit('execute');
    }

    if (data.code === 422) {
      errorsBack.value = data.errors ?? {};
    }
  } catch (error) {
    toast('Error al guardar la glosa', '', 'warning');
  } finally {
    isLoading.value = false;
  }
};

// Validators
const positiveValidator = (value: number | string) => {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  return isNaN(num) || num <= 0 ? 'El valor debe ser mayor que cero' : true;
};

const mayorTotalValueServiceValidator = () => {

  return dataCalculate.real_glosa_value > parseFloat(totalValueService.value)
    ? 'El valor debe ser menor o igual al valor total del servicio'
    : true;
};

// Utility functions
const parseEuropeanNumber = (value: string): number => {
  if (!value) return 0;
  return parseFloat(value.replace(/\./g, '').replace(',', '.'));
};

const dataReal = (data: any, field: string) => {
  dataCalculate[field] = data;
};

// File handling
const inputFile = ref(useFileUpload());
inputFile.value.allowedExtensions = ["jpg", "jpeg", "png", "doc", "docx", "xls", "xlsx", "pdf"];

watch(inputFile.value, (newVal) => {
  if (newVal.imageFile) form.value.file = newVal.imageFile;
});

const changeFile = (event: Event) => {
  const response: IImageSelected = inputFile.value.handleImageSelected(event);
  if (response.success === false && response.icon) {
    openModalQuestion(response);
  }
};

// Modal Question handling
const refModalQuestion = ref();

const openModalQuestion = (response: IImageSelected) => {
  refModalQuestion.value.componentData.isDialogVisible = true;
  refModalQuestion.value.componentData.dialogMaxWidth = '20rem';
  refModalQuestion.value.componentData.showBtnCancel = false;
  refModalQuestion.value.componentData.btnSuccessText = 'Ok';
  refModalQuestion.value.componentData.icon = response.icon;
  refModalQuestion.value.componentData.title = response.title;
  refModalQuestion.value.componentData.subTitle = response.text;
};

defineExpose({
  openModal
});

const paramsCodeGlosa = computed(() => {
  return {
    type_code_glosa_id: type_code_glosa_id.value,
  };
});
const type_code_glosa_id = ref(null as string | null);


const codeGlosa_arrayInfo = computed(() => {
  if (type_code_glosa_id.value == TYPE_CODE_GLOSA_001.id) {
    return codeGlosa_arrayInfo1.value;
  } else if (type_code_glosa_id.value == TYPE_CODE_GLOSA_002.id) {

    return codeGlosa_arrayInfo2.value;
  }
  return [];

});

</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="65rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading" class="class-modal">

        <VToolbar color="primary" class="rounded-t">
          <VToolbarTitle>
            {{ titleModal }}
          </VToolbarTitle>
        </VToolbar>

        <VCardText class="pt-6">
          <VForm ref="refForm" @submit.prevent>
            <div class="glosa-form pa-4">
              <VRow>
                <VCol cols="12">
                  <VRadioGroup v-model="type_code_glosa_id" inline>
                    <VRadio v-for="(item, index) in typeCodeGlosa_arrayInfo" :key="index" :label="item.title"
                      :value="item.value" />
                  </VRadioGroup>
                </VCol>

                <VCol cols="12" md="6">
                  <AppSelectRemote label="Código Glosa" :requiredField="true" :rules="[requiredValidator]"
                    :disabled="disabledFiledsView" v-model="form.code_glosa_id" url="/selectInfiniteCodeGlosa"
                    array-info="codeGlosa" :error-messages="errorsBack.code_glosa_id"
                    @input="errorsBack.code_glosa_id = ''" clearable :itemsData="codeGlosa_arrayInfo"
                    :firstFetch="false" :params="paramsCodeGlosa" />
                </VCol>

                <VCol cols="12" md="6">
                  <FormatCurrency label="Valor glosa" :requiredField="true" :disabled="disabledFiledsView"
                    :rules="[requiredValidator, positiveValidator, mayorTotalValueServiceValidator]"
                    v-model="form.glosa_value" @realValue="dataReal($event, 'real_glosa_value')"
                    :error-messages="errorsBack.glosa_value" @input="errorsBack.glosa_value = ''" clearable />
                </VCol>

                <VCol cols="12">
                  <AppTextarea label="Observación" :requiredField="true" :rules="[requiredValidator]"
                    :disabled="disabledFiledsView" v-model="form.observation" :error-messages="errorsBack.observation"
                    @input="errorsBack.observation = ''" clearable rows="3" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppFileInput :disabled="disabledFiledsView" label="Archivo adjunto"
                    :label2="form.file ? '1 archivo agregado' : ''" :loading="inputFile.loading"
                    @change="changeFile($event)" clearable />
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField :disabled="disabledFiledsView" clearable type="datetime-local" :requiredField="true"
                    :error-messages="errorsBack.date" :rules="[requiredValidator]" v-model="form.date"
                    label="Fecha De Notificacion De Glosa" @input="errorsBack.date = ''"
                    :min="formatToDateTimeLocal(radication_date)" />
                </VCol>
              </VRow>
            </div>
          </VForm>
        </VCardText>

        <VDivider />

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VSpacer />
          <VBtn color="secondary" :disabled="isLoading" @click="handleDialogVisible" class="me-3">
            Cancelar
          </VBtn>
          <VBtn v-if="!disabledFiledsView" color="primary" :loading="isLoading" :disabled="isLoading"
            @click="submitForm">
            Guardar
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>

<style scoped>
.class-modal {
  overflow: hidden;
  border-radius: 8px;
}

.glosa-form {
  border-radius: 8px;
  background-color: rgba(var(--v-theme-surface-variant), 0.05);
}
</style>
