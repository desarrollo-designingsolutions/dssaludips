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

const titleModal = ref<string>("Servicio: Otros Servicios")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const isLoading = ref<boolean>(false)
const tipoOtrosServicios_arrayInfo = ref([])
const conceptoRecaudo_arrayInfo = ref([])
const cupsRips_arrayInfo = ref([])
const tipoIdPisis_arrayInfo = ref([])

const service_id = ref<null | string>(null)
const invoice = ref<null | object>({
  id: null as string | null,
  invoice_date: null as string | null,
})
const form = ref({
  id: null as string | null,
  invoice_id: null as string | null,
  numAutorizacion: null as string | null,
  idMIPRES: null as string | null,
  fechaSuministroTecnologia: null as string | null,
  tipoOS_id: null as string | null,
  codTecnologiaSalud: null as string | null,
  nomTecnologiaSalud: null as string | null,
  cantidadOS: null as string | null,
  vrUnitOS: null as string | null,
  valorPagoModerador: null as string | null,
  vrServicio: null as string | null,
  conceptoRecaudo_id: null as string | null,
  tipoDocumentoIdentificacion_id: null as string | null,
  numDocumentoIdentificacion: null as string | null,
  numFEVPagoModerador: null as string | null,
})

const dataCalculate = reactive({
  real_vrUnitOS: 0 as number,
  real_valorPagoModerador: 0 as number,
  real_vrServicio: 0 as number,
})

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
  invoice.value = {
    id: null,
    invoice_date: null,
  };
}

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  if (!isDialogVisible.value) {
    handleClearForm();
  }
};

const openModal = async ({ serviceId = null, invoice_id }: any | null, disabled: boolean = false) => {
  handleDialogVisible();
  disabledFiledsView.value = disabled;
  form.value.invoice_id = invoice_id;
  service_id.value = serviceId;

  form.value.vrUnitOS = '0,00';
  form.value.valorPagoModerador = '0,00';

  await fetchDataForm();
};

const fetchDataForm = async () => {
  try {
    isLoading.value = true;
    const url = service_id.value ? `/service/otherService/${service_id.value}/edit` : `/service/otherService/create`;
    const { data, response } = await useAxios(url).get({
      params: {
        invoice_id: form.value.invoice_id,
      }
    });

    if (response.status === 200 && data) {

      invoice.value = data.invoice

      tipoOtrosServicios_arrayInfo.value = data.tipoOtrosServicios_arrayInfo
      conceptoRecaudo_arrayInfo.value = data.conceptoRecaudo_arrayInfo
      cupsRips_arrayInfo.value = data.cupsRips_arrayInfo
      tipoIdPisis_arrayInfo.value = data.tipoIdPisis_arrayInfo

      if (data.form) {
        form.value = cloneObject(data.form);

        form.value.vrUnitOS = currencyFormat(formatToCurrencyFormat(data.form.vrUnitOS));
        dataCalculate.real_vrUnitOS = cloneObject(data.form.vrUnitOS);
        form.value.valorPagoModerador = currencyFormat(formatToCurrencyFormat(data.form.valorPagoModerador));
        dataCalculate.real_valorPagoModerador = cloneObject(data.form.valorPagoModerador);
        form.value.vrServicio = currencyFormat(formatToCurrencyFormat(data.form.vrServicio));
        dataCalculate.real_vrServicio = cloneObject(data.form.vrServicio);
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
    const url = form.value.id ? `/service/otherService/update/${form.value.id}` : `/service/otherService/store`;

    const payload = {
      ...form.value,
      service_id: service_id.value,
      company_id: authenticationStore.company.id,
      vrUnitOS: dataCalculate.real_vrUnitOS,
      valorPagoModerador: dataCalculate.real_valorPagoModerador,
      vrServicio: dataCalculate.real_vrServicio,
    };

    const { data, response } = await useAxios(url).post(payload);

    if (response.status === 200 && data.code == 200) {
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

const lessThanVrService = (value: number | string) => {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  const vl = Number(form.value.cantidadOS) * dataCalculate.real_vrUnitOS;
  return isNaN(num) || num > vl ? 'El valor debe ser menor o igual a ' + vl : true;
};

const dataReal = (data: any, field: string) => {
  dataCalculate[field] = data;
};


defineExpose({
  openModal
});


watch(
  [() => form.value.cantidadOS, () => dataCalculate.real_vrUnitOS, () => dataCalculate.real_valorPagoModerador],
  ([newCantidadOS, newVrUnitOS, newValorPagoModerador]) => {
    const quantity = parseInt(newCantidadOS || '0', 10);
    const unitValue = newVrUnitOS || 0;
    const valuePagoModerador = newValorPagoModerador || 0;
    if (valuePagoModerador <= 0) {
      form.value.numFEVPagoModerador = null;
    }
    const total = (unitValue * quantity) - valuePagoModerador;
    form.value.vrServicio = currencyFormat(formatToCurrencyFormat(total));
    dataCalculate.real_vrServicio = total;
  },
  { immediate: true }
);

const changeCodTecnologiaSalud = (event) => {
  if (event) {
    form.value.nomTecnologiaSalud = event.nombre
  }
}


// Validations
const idMIPRESRules = [
  value => lengthBetweenValidator(value, 0, 15),
];

const numAutorizacionRules = [
  value => lengthBetweenValidator(value, 0, 30),
];


const nomTecnologiaSaludRules = [
  value => lengthBetweenValidator(value, 0, 60),
];

const cantidadOSRules = [
  value => lengthBetweenValidator(value, 1, 5),
  value => requiredValidator(value),
];

const dynamicDocumentLengthRule = computed(() => (value: string) => {
  const tipoId = form.value.tipoDocumentoIdentificacion_id?.codigo;

  if (!tipoId || !value) return true;
  const maxLength = documentLengthByType[tipoId] || 20;

  return (
    value.length <= maxLength ||
    `El documento ${tipoId} debe tener máximo ${maxLength} caracteres`
  );
});

const documentRules = [
  (value: string) => requiredValidator(value) || 'El documento es requerido',
  (value: string) => lengthBetweenValidator(value, 4, 20) || 'El documento debe tener entre 4 y 20 caracteres',
  dynamicDocumentLengthRule.value, // Agregar la regla dinámica
];

const disabledConceptoRecaudo = ref<boolean>(false)
watch(
  [() => form.value.conceptoRecaudo_id],
  ([newValueConceptoRecaudo_id]) => {
    disabledConceptoRecaudo.value = false
    const valuePagoModerador = newValueConceptoRecaudo_id || null;
    if (valuePagoModerador && valuePagoModerador.codigo == "05") {
      form.value.numFEVPagoModerador = null
      form.value.valorPagoModerador = "0,00"
      dataCalculate.real_valorPagoModerador = 0
      disabledConceptoRecaudo.value = true
    }
  },
  { immediate: true }
);
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
                <AppTextField clearable label="numAutorizacion" v-model="form.numAutorizacion"
                  :rules="numAutorizacionRules" :error-messages="errorsBack.numAutorizacion"
                  @input="errorsBack.numAutorizacion = ''" :disabled="disabledFiledsView" counter maxlength="30" />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField clearable label="idMIPRES" v-model="form.idMIPRES" :rules="idMIPRESRules"
                  :error-messages="errorsBack.idMIPRES" @input="errorsBack.idMIPRES = ''" :disabled="disabledFiledsView"
                  counter maxlength="15" />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField clearable label="fechaSuministroTecnologia" v-model="form.fechaSuministroTecnologia"
                  :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.fechaSuministroTecnologia"
                  @input="errorsBack.fechaSuministroTecnologia = ''" :disabled="disabledFiledsView"
                  type="datetime-local"
                  :max="formatToDateTimeLocal(new Date(new Date(invoice?.invoice_date).getTime() + 24 * 60 * 60 * 1000))" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="tipoOS" v-model="form.tipoOS_id"
                  :error-messages="errorsBack.tipoOS_id" @input="errorsBack.tipoOS_id = ''"
                  :disabled="disabledFiledsView" url="/selectInfiniteTipoOtrosServicios" array-info="tipoOtrosServicios"
                  :itemsData="tipoOtrosServicios_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codTecnologiaSalud" v-model="form.codTecnologiaSalud"
                  :requiredField="true" :rules="[requiredValidator]" :error-messages="errorsBack.codTecnologiaSalud"
                  @input="errorsBack.codTecnologiaSalud = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCupsRips" array-info="cupsRips" :itemsData="cupsRips_arrayInfo"
                  :firstFetch="false" @update:model-value="changeCodTecnologiaSalud" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField clearable label="nomTecnologiaSalud" disabled v-model="form.nomTecnologiaSalud"
                  :rules="nomTecnologiaSaludRules" :error-messages="errorsBack.nomTecnologiaSalud"
                  @input="errorsBack.nomTecnologiaSalud = ''" counter maxlength="30" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField clearable label="cantidadOS" v-model="form.cantidadOS" :requiredField="true"
                  :rules="cantidadOSRules" :error-messages="errorsBack.cantidadOS" @input="errorsBack.cantidadOS = ''"
                  :disabled="disabledFiledsView" counter maxlength="5" />
              </VCol>
              <VCol cols="12" md="6">
                <FormatCurrency clearable label="vrUnitOS" v-model="form.vrUnitOS" :requiredField="true"
                  :rules="[requiredValidator, positiveValidator]" :error-messages="errorsBack.vrUnitOS"
                  @input="errorsBack.vrUnitOS = ''" :disabled="disabledFiledsView"
                  @realValue="dataReal($event, 'real_vrUnitOS')" />
              </VCol>
              <VCol cols="12" md="6">
                <FormatCurrency clearable label="valorPagoModerador" :rules="[lessThanVrService]"
                  v-model="form.valorPagoModerador" :disabled="disabledFiledsView || disabledConceptoRecaudo"
                  @realValue="dataReal($event, 'real_valorPagoModerador')" />
              </VCol>
              <VCol cols="12" md="6">
                <FormatCurrency clearable label="vrServicio" v-model="form.vrServicio" :requiredField="true"
                  :rules="[requiredValidator, positiveValidator, greaterThanZeroValidator]"
                  :error-messages="errorsBack.vrServicio" @input="errorsBack.vrServicio = ''" disabled
                  @realValue="dataReal($event, 'real_vrServicio')" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="conceptoRecaudo" v-model="form.conceptoRecaudo_id"
                  :requiredField="true" :rules="[requiredValidator]" :error-messages="errorsBack.conceptoRecaudo_id"
                  @input="errorsBack.conceptoRecaudo_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteConceptoRecaudo" array-info="conceptoRecaudo"
                  :itemsData="conceptoRecaudo_arrayInfo" :firstFetch="false" :params="{
                    codigo_in: CODS_SELECT_FORM_SERVICE_OTHERSERVICE_CONCEPTORECAUDO,
                  }" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="Tipo documento persona realiza/ordena servicio"
                  v-model="form.tipoDocumentoIdentificacion_id" :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.tipoDocumentoIdentificacion_id"
                  @input="errorsBack.tipoDocumentoIdentificacion_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteTipoIdPisis" array-info="tipoIdPisis" :itemsData="tipoIdPisis_arrayInfo"
                  :firstFetch="false" :params="{
                    codigo_in: CODS_SELECT_FORM_SERVICE_TIPODOCUMENTOIDENTIFICACION,
                  }" />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField clearable label="Número documento persona realiza/ordena servicio"
                  v-model="form.numDocumentoIdentificacion" :requiredField="true" :rules="documentRules"
                  :error-messages="errorsBack.numDocumentoIdentificacion"
                  @input="errorsBack.numDocumentoIdentificacion = ''" :disabled="disabledFiledsView" counter
                  maxlength="20" minlength="4" />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField clearable label="numFEVPagoModerador" v-model="form.numFEVPagoModerador"
                  :error-messages="errorsBack.numFEVPagoModerador" @input="errorsBack.numFEVPagoModerador = ''"
                  :disabled="disabledFiledsView || dataCalculate.real_valorPagoModerador <= 0" />
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
