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

const titleModal = ref<string>("Respuesta")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const isLoading = ref<boolean>(false)
const statusGlosaAnswerEnumValues = ref([])

const form = ref({
  id: null as string | null,
  company_id: null as string | null,
  user_id: null as string | null,
  glosa_id: null as string | null,
  observation: null as string | null,
  file: null as string | File | null,
  date_answer: null as string | null,
  value_approved: null as string | null,
  value_accepted: null as string | null,
  status_id: null as string | null,
  code_glosa_answer_id: null as string | null,
})

const totalValueApproved = ref<number | string | null>(0)
const totalValueAccepted = ref<number | string | null>(0)
const codeGlosaAnswer_arrayInfo = ref([])

const dataCalculate = reactive({
  real_value_approved: 0 as number,
  real_value_accepted: 0 as number,
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

const total_value_glosa = ref()
const glosa_date = ref()

const openModal = async ({ id, glosa_id, total_value }: any, disabled: boolean = false) => {
  handleClearForm();
  handleDialogVisible();

  disabledFiledsView.value = disabled;
  form.value.id = id;
  form.value.glosa_id = glosa_id;
  total_value_glosa.value = total_value;

  await fetchDataForm();
};

const fetchDataForm = async () => {
  try {
    isLoading.value = true;
    const url = form.value.id ? `/glosaAnswer/${form.value.id}/edit` : `/glosaAnswer/create`;
    const { data, response } = await useAxios(url).get({
      params: {
        glosa_id: form.value.glosa_id,
      }
    });

    if (response.status === 200 && data) {
      statusGlosaAnswerEnumValues.value = data.statusGlosaAnswerEnumValues

      form.value.value_approved = '0,00';
      form.value.value_accepted = '0,00';
      codeGlosaAnswer_arrayInfo.value = data.codeGlosaAnswer_arrayInfo

      glosa_date.value = data.glosa_date;

      if (data.form) {
        form.value = cloneObject(data.form);
        totalValueApproved.value = form.value.value_approved;
        totalValueAccepted.value = form.value.value_accepted;
        form.value.value_approved = currencyFormat(formatToCurrencyFormat(totalValueApproved.value));
        dataCalculate.real_value_approved = cloneObject(totalValueApproved.value);
        form.value.value_accepted = currencyFormat(formatToCurrencyFormat(totalValueAccepted.value));
        dataCalculate.real_value_accepted = cloneObject(totalValueAccepted.value);
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

    isLoading.value = true;
    const url = form.value.id ? `/glosaAnswer/update/${form.value.id}` : `/glosaAnswer/store`;

    const formData = new FormData();
    formData.append("id", String(form.value.id));
    formData.append("company_id", String(authenticationStore.company.id));
    formData.append("user_id", String(authenticationStore.user.id));
    formData.append("glosa_id", String(form.value.glosa_id));
    formData.append("observation", String(form.value.observation));
    formData.append("file", inputFile.value.imageFile || form.value.file);
    formData.append("date_answer", String(form.value.date_answer));
    formData.append("value_approved", String(dataCalculate.real_value_approved));
    formData.append("value_accepted", String(dataCalculate.real_value_accepted));
    formData.append("status", String(form.value.status_id));
    formData.append("code_glosa_answer_id", String(form.value.code_glosa_answer_id.value));

    const { data, response } = await useAxios(url).post(formData);

    if (response.status === 200 && data) {
      handleDialogVisible();
      emit('execute');
    }

    if (data.code === 422) {
      errorsBack.value = data.errors ?? {};
    }
  } catch (error) {
    toast('Error al guardar la answer', '', 'warning');
  } finally {
    isLoading.value = false;
  }
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

// Validators
const positiveValidator = (value: number | string) => {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  return isNaN(num) || num <= 0 ? 'El valor debe ser mayor que cero' : true;
};

const sumEqualsTotalValidator = () => {
  const approved = parseFloat(dataCalculate.real_value_approved || '0');
  const accepted = parseFloat(dataCalculate.real_value_accepted || '0');
  const total = parseCurrencyToFloat(total_value_glosa.value);
  const sum = approved + accepted;
  return sum === total
    ? true
    : `La suma del valor aprobado (${approved}) y aceptado (${accepted}) debe ser igual al total de la glosa (${total})`;
};

const rulesValueApprovedAccepted = [
  value => form.value.status_id == 'GLOSA_ANSWER_STATUS_002' ? requiredValidator(value) : true,
  value => form.value.status_id == 'GLOSA_ANSWER_STATUS_002' ? positiveValidator(value) : true,
  value => form.value.status_id == 'GLOSA_ANSWER_STATUS_002' ? sumEqualsTotalValidator() : true,
];
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
            <div class="answer-form pa-4">
              <VRow>

                <VCol cols="12">
                  <AppSelectRemote :disabled="disabledFiledsView" label="Código respuesta glosa"
                    v-model="form.code_glosa_answer_id" url="/selectCodeGlosaAnswer" arrayInfo="codeGlosaAnswer"
                    clearable :itemsData="codeGlosaAnswer_arrayInfo" :firstFetch="false"
                    :error-messages="errorsBack.code_glosa_answer_id" @input="errorsBack.code_glosa_answer_id = ''"
                    :rules="[requiredValidator]" :requiredField="true">
                  </AppSelectRemote>
                </VCol>

                <VCol cols="12">
                  <AppTextarea label="Observación" :requiredField="true" :rules="[requiredValidator]"
                    :disabled="disabledFiledsView" v-model="form.observation" :error-messages="errorsBack.observation"
                    @input="errorsBack.observation = ''" clearable rows="3" />
                </VCol>

                <VCol cols="12" md="6">
                  <FormatCurrency label="Valor Aprobado"
                    :requiredField="form.status_id == 'GLOSA_ANSWER_STATUS_002' ? true : false"
                    :disabled="disabledFiledsView || form.status_id != 'GLOSA_ANSWER_STATUS_002'"
                    :rules="rulesValueApprovedAccepted" v-model="form.value_approved"
                    @realValue="dataReal($event, 'real_value_approved')" :error-messages="errorsBack.value_approved"
                    @input="errorsBack.value_approved = ''" clearable />
                </VCol>

                <VCol cols="12" md="6">
                  <FormatCurrency label="Valor Aceptado"
                    :requiredField="form.status_id == 'GLOSA_ANSWER_STATUS_002' ? true : false"
                    :disabled="disabledFiledsView || form.status_id != 'GLOSA_ANSWER_STATUS_002'"
                    :rules="rulesValueApprovedAccepted" v-model="form.value_accepted"
                    @realValue="dataReal($event, 'real_value_accepted')" :error-messages="errorsBack.value_accepted"
                    @input="errorsBack.value_accepted = ''" clearable />
                </VCol>


                <VCol cols="12" md="6">
                  <AppFileInput :disabled="disabledFiledsView" label="Archivo adjunto"
                    :label2="form.file ? '1 archivo agregado' : ''" :loading="inputFile.loading"
                    @change="changeFile($event)" clearable />
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField :disabled="disabledFiledsView" clearable type="datetime-local" :requiredField="true"
                    :error-messages="errorsBack.date_answer" :rules="[requiredValidator]" v-model="form.date_answer"
                    label="Fecha De Respuesta" @input="errorsBack.date_answer = ''"
                    :min="formatToDateTimeLocal(glosa_date)" />
                </VCol>

                <VCol cols="12" md="6">
                  <AppSelect :requiredField="true" :items="statusGlosaAnswerEnumValues" label="Estado"
                    :rules="[requiredValidator]" v-model="form.status_id" :error-messages="errorsBack.status_id"
                    clearable :disabled="disabledFiledsView" />
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

.answer-form {
  border-radius: 8px;
  background-color: rgba(var(--v-theme-surface-variant), 0.05);
}
</style>
