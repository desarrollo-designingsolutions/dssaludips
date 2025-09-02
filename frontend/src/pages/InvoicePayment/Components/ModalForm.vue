<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import IImageSelected from "@/interfaces/FileUpload/IImageSelected";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from "vuetify/components/VForm";

const { toast } = useToast()
const authenticationStore = useAuthenticationStore();

const errorsBack = ref<IErrorsBack>({});
const refForm = ref<VForm>();
const emit = defineEmits(["execute"])

const titleModal = ref<string>("Pago")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const isLoading = ref<boolean>(false)
const invoiceData = ref<{
  id: string;
  remaining_balance: string;
} | null>(null)
const paymentData = ref<{
  id: string;
  value_paid: string;
} | null>(null)

const form = ref({
  id: null as string | null,
  invoice_id: null as string | null,
  value_paid: null as string | null,
  date_payment: null as string | null,
  observations: null as string | null,
  file: null as string | File | null,
})

const dataCalculate = reactive({
  real_value_paid: 0 as number,
})

const handleClearForm = () => {
  form.value = {
    id: null,
    invoice_id: null,
    value_paid: null,
    date_payment: null,
    observations: null,
    file: null,
  }
  paymentData.value = null;
  invoiceData.value = null;
}

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  if (!isDialogVisible.value) {
    handleClearForm();
  }
};

const openModal = async ({ id = null, invoice_id }: any, disabled: boolean = false) => {
  handleClearForm();
  handleDialogVisible();

  disabledFiledsView.value = disabled;
  form.value.id = id;
  form.value.invoice_id = invoice_id;
  await fetchDataForm();

};

const fetchDataForm = async () => {
  try {
    isLoading.value = true;
    const url = form.value.id ? `/invoicePayment/${form.value.id}/edit` : `/invoicePayment/create/${form.value.invoice_id}`;
    const { data, response } = await useAxios(url).get();

    if (response.status === 200 && data.code == 200) {

      invoiceData.value = cloneObject(data.invoice);

      if (data.form) {

        form.value = cloneObject(data.form);
        paymentData.value = cloneObject(data.form);
        form.value.value_paid = currencyFormat(formatToCurrencyFormat(data.form.value_paid));
        dataCalculate.real_value_paid = cloneObject(data.form.value_paid);
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
    const url = form.value.id ? `/invoicePayment/update/${form.value.id}` : `/invoicePayment/store`;

    const formData = new FormData();
    formData.append("id", String(form.value.id));
    formData.append("invoice_id", String(form.value.invoice_id));
    formData.append("value_paid", String(dataCalculate.real_value_paid));
    formData.append("date_payment", String(form.value.date_payment));
    formData.append("observations", String(form.value.observations));
    formData.append("file", inputFile.value.imageFile || form.value.file);

    const { data, response } = await useAxios(url).post(formData);

    if (response.status === 200 && data) {
      handleDialogVisible();
      emit('execute');
    }

    if (data.code === 422) {
      errorsBack.value = data.errors ?? {};
    }
  } catch (error) {
    toast('Error al guardar', '', 'warning');
  } finally {
    isLoading.value = false;
  }
};

// Validators
const positiveValidator = (value: number | string) => {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  return isNaN(num) || num <= 0 ? 'El valor debe ser mayor que cero' : true;
};

const mayorRemainingBalanceInvoiceValidator = () => {

  const unitValue = Number(invoiceData.value?.remaining_balance) || 0;
  const value_paid = Number(paymentData.value?.value_paid) || 0;
  const total = unitValue + value_paid;

  return parseFloat(dataCalculate.real_value_paid) > parseFloat(total)
    ? 'El valor debe ser menor o igual al valor restante de la factura'
    : true;
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


const remainingBalance = computed(() => {
  // Asegúrate de que remaining_balance sea un número, con 0 como valor por defecto
  const unitValue = Number(invoiceData.value?.remaining_balance) || 0;
  const value_paid = Number(paymentData.value?.value_paid) || 0;
  const total = unitValue + value_paid;

  // Formatear como moneda colombiana
  return "$ " + currencyFormat(formatToCurrencyFormat(total));
});
</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="800px" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />

      <VCard :loading="isLoading" :disabled="isLoading" class="payment-dialog">
        <!-- Header -->
        <VToolbar color="primary" class="payment-dialog__header">
          <VToolbarTitle>
            {{ titleModal }}
          </VToolbarTitle>
          <VSpacer />
        </VToolbar>

        <!-- Content -->
        <VCardText class="pa-6">
          <!-- Invoice Summary -->
          <div class="payment-dialog__summary mb-6">
            <VCard variant="outlined" class="pa-4">
              <div class="d-flex align-center justify-space-between">
                <div>
                  <div class="text-subtitle-2 text-medium-emphasis">Total a Pagar</div>
                  <div class="text-h5 font-weight-bold primary--text">
                    {{ remainingBalance }}
                  </div>
                </div>
                <VIcon size="32" color="primary" icon="tabler-file"></VIcon>
              </div>
            </VCard>
          </div>

          <VForm ref="refForm" @submit.prevent>
            <div class="payment-dialog__form pa-4 rounded-lg">
              <VRow>
                <VCol cols="12" md="6">
                  <FormatCurrency label="Valor del Pago" :requiredField="true" :disabled="disabledFiledsView"
                    :rules="[requiredValidator, positiveValidator, mayorRemainingBalanceInvoiceValidator]"
                    v-model="form.value_paid" @realValue="dataReal($event, 'real_value_paid')"
                    :error-messages="errorsBack.value_paid" @input="errorsBack.value_paid = ''" clearable />
                </VCol>

                <VCol cols="12" md="6">
                  <AppTextField clearable type="date" :disabled="disabledFiledsView" :requiredField="true"
                    :error-messages="errorsBack.date_payment" :rules="[requiredValidator]" v-model="form.date_payment"
                    label="Fecha de Pago" />
                </VCol>

                <VCol cols="12">
                  <AppTextarea label="Observaciones" :requiredField="true" :rules="[requiredValidator]"
                    :disabled="disabledFiledsView" v-model="form.observations" :error-messages="errorsBack.observations"
                    @input="errorsBack.observations = ''" clearable rows="3" />
                </VCol>

                <VCol cols="12">
                  <VCard variant="outlined" class="pa-4">
                    <div class="d-flex align-center mb-2">
                      <VIcon color="info" class="mr-2" icon="tabler-files"></VIcon>

                      <span class="text-subtitle-1">Documentación de Soporte</span>
                    </div>
                    <AppFileInput :disabled="disabledFiledsView" label="Adjuntar comprobante de pago"
                      :label2="form.file ? '1 archivo agregado' : ''" :loading="inputFile.loading"
                      @change="changeFile($event)" clearable />
                  </VCard>
                </VCol>
              </VRow>
            </div>
          </VForm>
        </VCardText>

        <!-- Actions -->
        <VDivider />
        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VSpacer />
          <VBtn color="secondary" :disabled="isLoading" @click="handleDialogVisible">
            Cancelar
          </VBtn>
          <VBtn v-if="!disabledFiledsView" color="primary" :loading="isLoading" :disabled="isLoading"
            @click="submitForm">
            Confirmar Pago
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>

<style lang="scss" scoped>
.payment-dialog {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);

  &__header {
    border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
  }

  &__summary {
    background-color: rgb(var(--v-theme-background));
  }

  &__form {
    background-color: rgb(var(--v-theme-surface-variant), 0.05);
    border: 1px solid rgba(var(--v-border-color), 0.12);
  }

  &__input {
    :deep(.v-field) {
      border-radius: 8px;
      background-color: rgb(var(--v-theme-surface));
    }
  }
}

// Animation
.dialog-transition-enter-active,
.dialog-transition-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.dialog-transition-enter-from,
.dialog-transition-leave-to {
  transform: translateY(20px);
  opacity: 0;
}
</style>
