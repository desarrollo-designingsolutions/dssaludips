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

const titleModal = ref<string>("Servicio: Medicamentos")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const isLoading = ref<boolean>(false)

const cie10_arrayInfo = ref([])
const tipoMedicamentoPosVersion2_arrayInfo = ref([])
const umm_arrayInfo = ref([])
const conceptoRecaudo_arrayInfo = ref([])
const tipoIdPisis_arrayInfo = ref([])
const upr_arrayInfo = ref([])
const ffm_arrayInfo = ref([])
const dci_arrayInfo = ref([])

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
  fechaDispensAdmon: null as string | null,
  codDiagnosticoPrincipal_id: null as string | null,
  codDiagnosticoRelacionado_id: null as string | null,
  tipoMedicamento_id: null as string | null,
  codTecnologiaSaludable_id: null as string | null,
  codTecnologiaSaludable_type: null as string | null,
  nomTecnologiaSalud: null as string | null,
  concentracionMedicamento: null as string | null,
  unidadMedida_id: null as string | null,
  formaFarmaceutica: null as string | null,
  unidadMinDispensa_id: null as string | null,
  cantidadMedicamento: null as string | null,
  diasTratamiento: null as string | null,
  vrUnitMedicamento: null as string | null,
  valorPagoModerador: null as string | null,
  vrServicio: null as string | null,
  conceptoRecaudo_id: null as string | null,
  tipoDocumentoIdentificacion_id: null as string | null,
  numDocumentoIdentificacion: null as string | null,
  numFEVPagoModerador: null as string | null,
})

const dataCalculate = reactive({
  real_vrUnitMedicamento: 0 as number,
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

  form.value.vrUnitMedicamento = '0,00';
  form.value.valorPagoModerador = '0,00';

  await fetchDataForm();
};

const fetchDataForm = async () => {
  try {
    isLoading.value = true;
    const url = service_id.value ? `/service/medicine/${service_id.value}/edit` : `/service/medicine/create`;
    const { data, response } = await useAxios(url).get({
      params: {
        invoice_id: form.value.invoice_id,
      }
    });

    if (response.status === 200 && data) {

      invoice.value = data.invoice

      cie10_arrayInfo.value = data.cie10_arrayInfo
      tipoMedicamentoPosVersion2_arrayInfo.value = data.tipoMedicamentoPosVersion2_arrayInfo
      umm_arrayInfo.value = data.umm_arrayInfo
      conceptoRecaudo_arrayInfo.value = data.conceptoRecaudo_arrayInfo
      tipoIdPisis_arrayInfo.value = data.tipoIdPisis_arrayInfo
      upr_arrayInfo.value = data.upr_arrayInfo
      ffm_arrayInfo.value = data.ffm_arrayInfo
      dci_arrayInfo.value = data.dci_arrayInfo

      type_vendors.value = data.type_vendors
      codTecnologiaSaludables.value = data.codTecnologiaSaludables

      form.value.codTecnologiaSaludable_type = codTecnologiaSaludables.value.at(0)?.value ?? null;




      if (data.form) {
        form.value = cloneObject(data.form);

        form.value.vrUnitMedicamento = currencyFormat(formatToCurrencyFormat(data.form.vrUnitMedicamento));
        dataCalculate.real_vrUnitMedicamento = cloneObject(data.form.vrUnitMedicamento);
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
    const url = form.value.id ? `/service/medicine/update/${form.value.id}` : `/service/medicine/store`;

    const payload = {
      ...form.value,
      service_id: service_id.value,
      company_id: authenticationStore.company.id,
      vrUnitMedicamento: dataCalculate.real_vrUnitMedicamento,
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
  return isNaN(num) || num < 0 ? 'El valor debe ser positivo' : true;
};

const lessThanVrService = (value: number | string) => {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  const vl = Number(form.value.cantidadMedicamento) * dataCalculate.real_vrUnitMedicamento;
  return isNaN(num) || num > vl ? 'El valor debe ser menor o igual a ' + vl : true;
};

const dataReal = (data: any, field: string) => {
  dataCalculate[field] = data;
};


defineExpose({
  openModal
});

watch(
  [() => form.value.cantidadMedicamento, () => dataCalculate.real_vrUnitMedicamento, () => dataCalculate.real_valorPagoModerador],
  ([cantidadMedicamento, newVrUnitMedicamento, newValorPagoModerador]) => {
    const quantity = parseInt(cantidadMedicamento || '0', 10);
    const unitValue = newVrUnitMedicamento || 0;
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

// Validations
const idMIPRESRules = [
  value => lengthBetweenValidator(value, 0, 15),
];

const numAutorizacionRules = [
  value => lengthBetweenValidator(value, 0, 30),
];


const codTecnologiaSaludRules = [
  value => lengthBetweenValidator(value, 1, 20),
  value => requiredValidator(value),
];

const nomTecnologiaSalud_idRules = [
  value => lengthBetweenValidator(value, 0, 30),
  value => requiredValidator(value),
];

const concentracionMedicamentoRules = [
  value => lengthBetweenValidator(value, 0, 3),
  value => requiredValidator(value),
];

const formaFarmaceutica_idRules = [
  value => requiredValidator(value),
];

const unidadMinDispensa_idRules = [
  value => requiredValidator(value),
];

const cantidadMedicamentoRules = [
  value => lengthBetweenValidator(value, 1, 10),
  value => requiredValidator(value),
];

const diasTratamientoRules = [
  value => lengthBetweenValidator(value, 1, 3),
  value => requiredValidator(value),
];

const type_vendors = ref([])

const codTecnologiaSaludables = ref<Array<{
  label: string;
  value: string;
}>>([])

interface CodTecnologiaSaludablesSelect {
  label: string;
  url: string;
  arrayInfo: string;
  itemsData: any[];
}

const codTecnologiaSaludables_select = computed<CodTecnologiaSaludablesSelect>(() => {

  if (form.value.codTecnologiaSaludable_type) {
    return codTecnologiaSaludables.value.find(item => item.value === form.value.codTecnologiaSaludable_type) || {
      label: "",
      url: "",
      arrayInfo: "",
      itemsData: [],
    };
  }

  return {
    label: "",
    url: "",
    arrayInfo: "",
    itemsData: [],
  };
});

const clearCodTecnologiaSaludable_type = () => {
  form.value.codTecnologiaSaludable_id = null
}



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


const disabledValorPagoModerador = ref<boolean>(false)
watch(
  [() => form.value.conceptoRecaudo_id],
  ([newValueConceptoRecaudo_id]) => {
    disabledValorPagoModerador.value = false
    const valuePagoModerador = newValueConceptoRecaudo_id || null;
    if (valuePagoModerador && valuePagoModerador.codigo == "05") {
      form.value.numFEVPagoModerador = null
      form.value.valorPagoModerador = "0,00"
      dataCalculate.real_valorPagoModerador = 0
      disabledValorPagoModerador.value = true
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
                <AppTextField clearable label="fechaDispensAdmon" v-model="form.fechaDispensAdmon" :requiredField="true"
                  :rules="[requiredValidator]" :error-messages="errorsBack.fechaDispensAdmon"
                  @input="errorsBack.fechaDispensAdmon = ''" :disabled="disabledFiledsView" type="datetime-local"
                  :max="formatToDateTimeLocal(new Date(new Date(invoice?.invoice_date).getTime() + 24 * 60 * 60 * 1000))" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoPrincipal" v-model="form.codDiagnosticoPrincipal_id"
                  :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.codDiagnosticoPrincipal_id"
                  @input="errorsBack.codDiagnosticoPrincipal_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCie10" array-info="cie10" :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoRelacionado" v-model="form.codDiagnosticoRelacionado_id"
                  :error-messages="errorsBack.codDiagnosticoRelacionado_id"
                  @input="errorsBack.codDiagnosticoRelacionado_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCie10" array-info="cie10" :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="tipoMedicamento" v-model="form.tipoMedicamento_id"
                  :requiredField="true" :rules="[requiredValidator]" :error-messages="errorsBack.tipoMedicamento_id"
                  @input="errorsBack.tipoMedicamento_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteTipoMedicamentoPosVersion2" array-info="tipoMedicamentoPosVersion2"
                  :itemsData="tipoMedicamentoPosVersion2_arrayInfo" :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <VRadioGroup v-model="form.codTecnologiaSaludable_type" inline>
                  <VRadio v-for="(item, index) in codTecnologiaSaludables" :key="index" :label="item.label"
                    :value="item.value" @click="clearCodTecnologiaSaludable_type" />
                </VRadioGroup>

                <AppSelectRemote clearable :label="codTecnologiaSaludables_select.label"
                  v-model="form.codTecnologiaSaludable_id" :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.codTecnologiaSaludable_id"
                  @input="errorsBack.codTecnologiaSaludable_id = ''" :disabled="disabledFiledsView"
                  :url="codTecnologiaSaludables_select.url" :array-info="codTecnologiaSaludables_select.arrayInfo"
                  :itemsData="codTecnologiaSaludables_select.itemsData" :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="nomTecnologiaSalud" v-model="form.nomTecnologiaSalud_id"
                  :requiredField="true" :rules="nomTecnologiaSalud_idRules"
                  :error-messages="errorsBack.nomTecnologiaSalud_id" @input="errorsBack.nomTecnologiaSalud_id = ''"
                  :disabled="disabledFiledsView" url="/selectInfiniteDci" array-info="dci" :itemsData="dci_arrayInfo"
                  :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField clearable label="concentracionMedicamento" v-model="form.concentracionMedicamento"
                  :requiredField="true" :rules="concentracionMedicamentoRules"
                  :error-messages="errorsBack.concentracionMedicamento"
                  @input="errorsBack.concentracionMedicamento = ''" :disabled="disabledFiledsView" counter
                  maxlength="3" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="unidadMedida" v-model="form.unidadMedida_id" :requiredField="true"
                  :rules="[requiredValidator]" :error-messages="errorsBack.unidadMedida_id"
                  @input="errorsBack.unidadMedida_id = ''" :disabled="disabledFiledsView" url="/selectInfiniteUmm"
                  array-info="umm" :itemsData="umm_arrayInfo" :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="formaFarmaceutica" v-model="form.formaFarmaceutica_id"
                  :requiredField="true" :rules="formaFarmaceutica_idRules"
                  :error-messages="errorsBack.formaFarmaceutica_id" @input="errorsBack.formaFarmaceutica_id = ''"
                  :disabled="disabledFiledsView" url="/selectInfiniteFfm" array-info="ffm" :itemsData="ffm_arrayInfo"
                  :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="unidadMinDispensa" v-model="form.unidadMinDispensa_id"
                  :requiredField="true" :rules="unidadMinDispensa_idRules"
                  :error-messages="errorsBack.unidadMinDispensa_id" @input="errorsBack.unidadMinDispensa_id = ''"
                  :disabled="disabledFiledsView" url="/selectInfiniteUpr" array-info="upr" :itemsData="upr_arrayInfo"
                  :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField clearable label="cantidadMedicamento" v-model="form.cantidadMedicamento"
                  :requiredField="true" :rules="cantidadMedicamentoRules"
                  :error-messages="errorsBack.cantidadMedicamento" @input="errorsBack.cantidadMedicamento = ''"
                  :disabled="disabledFiledsView" counter maxlength="10" minlength="1" />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField clearable label="diasTratamiento" v-model="form.diasTratamiento" :requiredField="true"
                  :rules="diasTratamientoRules" :error-messages="errorsBack.diasTratamiento"
                  @input="errorsBack.diasTratamiento = ''" :disabled="disabledFiledsView" counter maxlength="3"
                  minlength="1" />
              </VCol>

              <VCol cols="12" md="6">
                <FormatCurrency clearable label="vrUnitMedicamento" v-model="form.vrUnitMedicamento"
                  :requiredField="true" :rules="[requiredValidator, positiveValidator]"
                  :error-messages="errorsBack.vrUnitMedicamento" @input="errorsBack.vrUnitMedicamento = ''"
                  :disabled="disabledFiledsView" @realValue="dataReal($event, 'real_vrUnitMedicamento')" />
              </VCol>

              <VCol cols="12" md="6">
                <FormatCurrency clearable label="valorPagoModerador" v-model="form.valorPagoModerador"
                  :rules="[lessThanVrService, positiveValidator]" :error-messages="errorsBack.valorPagoModerador"
                  @input="errorsBack.valorPagoModerador = ''"
                  :disabled="disabledFiledsView || disabledValorPagoModerador"
                  @realValue="dataReal($event, 'real_valorPagoModerador')" />
              </VCol>

              <VCol cols="12" md="6">
                <FormatCurrency clearable disabled label="vrServicio" v-model="form.vrServicio" :requiredField="true"
                  :rules="[requiredValidator, positiveValidator, greaterThanZeroValidator]"
                  :error-messages="errorsBack.vrServicio" @input="errorsBack.vrServicio = ''"
                  :disabled="disabledFiledsView" @realValue="dataReal($event, 'real_vrServicio')" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="conceptoRecaudo" v-model="form.conceptoRecaudo_id"
                  :requiredField="true" :rules="[requiredValidator]" :error-messages="errorsBack.conceptoRecaudo_id"
                  @input="errorsBack.conceptoRecaudo_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteConceptoRecaudo" array-info="conceptoRecaudo"
                  :itemsData="conceptoRecaudo_arrayInfo" :firstFetch="false" :params="{
                    codigo_in: CODS_SELECT_FORM_SERVICE_MEDICINE_CONCEPTORECAUDO,
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
                  :requiredField="dataCalculate.real_valorPagoModerador > 0 ? true : false" :rules="ruleConceptoRecaudo"
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
