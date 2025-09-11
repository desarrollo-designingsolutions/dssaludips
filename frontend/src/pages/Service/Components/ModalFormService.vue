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

const titleModal = ref<string>("Servicio")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const isLoading = ref<boolean>(false)
const disabledTotal = ref(false)

const form = ref({
  id: null as string | null,
  invoice_id: null as string | null,
  cups_rip_id: null as string | null,
  company_id: null as string | null,
  quantity: null as string | null,
  unit_value: null as number | string | null,
})

const dataCalculate = reactive({
  real_unit_value: 0 as number,
})

const handleClearForm = () => {
  form.value = {
    id: null,
    invoice_id: null,
    cups_rip_id: null,
    company_id: null,
    quantity: null,
    unit_value: null,
  }
}

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  if (!isDialogVisible.value) {
    handleClearForm();
  }
};

const openModal = async ({ id = null, invoice_id }: any | null, disabled: boolean = false) => {
  handleDialogVisible();
  disabledFiledsView.value = disabled;
  form.value.invoice_id = invoice_id;
  form.value.id = id;

  if (id) {
    await fetchDataForm();
  }
};

const fetchDataForm = async () => {
  try {
    isLoading.value = true;
    const url = form.value.id ? `/service/${form.value.id}/edit` : `/service/create`;
    const { data, response } = await useAxios(url).get();

    if (response.status === 200 && data?.form) {
      form.value = cloneObject(data.form);
      form.value.unit_value = currencyFormat(formatToCurrencyFormat(data.form.unit_value));
      dataCalculate.real_unit_value = cloneObject(data.form.unit_value);
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
    const url = form.value.id ? `/service/update/${form.value.id}` : `/service/store`;

    const payload = {
      ...form.value,
      company_id: authenticationStore.company.id,
      unit_value: dataCalculate.real_unit_value,
    };

    const { data, response } = await useAxios(url).post(payload);

    if (response.status === 200 && data) {
      handleDialogVisible();
      emit('execute');
    }

    if (data.code === 422) {
      errorsBack.value = data.errors ?? {};
    }
  } catch (error) {
    toast('Error al guardar el servicio', '', 'warning');
  } finally {
    isLoading.value = false;
  }
};

// Validators
const positiveValidator = (value: number | string) => {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  return isNaN(num) || num <= 0 ? 'El valor debe ser mayor que cero' : true;
};

// Utility functions
const parseEuropeanNumber = (value: string): number => {
  if (!value) return 0;
  return parseFloat(value.replace(/\./g, '').replace(',', '.'));
};

const dataReal = (data: any, field: string) => {
  dataCalculate[field] = data;
};

const total_value = computed(() => {
  const unitValue = parseEuropeanNumber(form.value.unit_value);
  const quantity = parseInt(form.value.quantity || '0', 10);
  const total = unitValue * quantity;
  return total.toLocaleString('es-CO', {
    style: 'currency',
    currency: 'COP',
  });
});

defineExpose({
  openModal
});
</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="65rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading">

        <VToolbar color="primary" class="rounded-t">
          <VToolbarTitle>
            {{ titleModal }}
          </VToolbarTitle>
        </VToolbar>

        <VCardText class="pt-6">
          <VForm ref="refForm" @submit.prevent>

            <VRow>
              <VCol cols="12" md="6">
                <AppSelectRemote label="Diagnostico" :requiredField="true" :rules="[requiredValidator]"
                  :disabled="disabledFiledsView" v-model="form.cups_rip_id" url="/selectInfiniteCupsRips"
                  array-info="cupsRips" :error-messages="errorsBack.cups_rip_id" @input="errorsBack.cups_rip_id = ''"
                  clearable />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField label="Cantidad" type="number" :requiredField="true" :rules="[requiredValidator]"
                  :disabled="disabledFiledsView" v-model="form.quantity" :error-messages="errorsBack.quantity"
                  @input="errorsBack.quantity = ''" clearable />
              </VCol>

              <VCol cols="12" md="6">
                <FormatCurrency label="Valor Unitario" :requiredField="true"
                  :disabled="disabledFiledsView || disabledTotal" :rules="[requiredValidator, positiveValidator]"
                  v-model="form.unit_value" @realValue="dataReal($event, 'real_unit_value')"
                  :error-messages="errorsBack.unit_value" @input="errorsBack.unit_value = ''" clearable />
              </VCol>

              <VCol cols="12">
                <div class="d-flex align-center pa-4 rounded bg-primary-lighten-5">
                  <span class="text-subtitle-1 font-weight-medium me-2">Total:</span>
                  <span class="text-h6">{{ total_value }}</span>
                </div>
              </VCol>
            </VRow>
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
  </div>
</template>
