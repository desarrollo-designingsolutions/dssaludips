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

const titleModal = ref<string>("Servicio: Consultas")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const isLoading = ref<boolean>(false)

const cupsRips_arrayInfo = ref([])
const modalidadAtencion_arrayInfo = ref([])
const grupoServicio_arrayInfo = ref([])
const servicio_arrayInfo = ref([])
const ripsFinalidadConsultaVersion2_arrayInfo = ref([])
const cie10_arrayInfo = ref([])
const ripsTipoDiagnosticoPrincipalVersion2_arrayInfo = ref([])
const conceptoRecaudo_arrayInfo = ref([])
const ripsCausaExternaVersion2_arrayInfo = ref([])
const tipoIdPisis_arrayInfo = ref([])


const service_id = ref<null | string>(null)
const invoice = ref<null | object>({
  id: null as string | null,
  invoice_date: null as string | null,
})

const form = ref({
  id: null as string | null,
  invoice_id: null as string | null,

  fechaInicioAtencion: null as string | null,
  numAutorizacion: null as string | null,
  codConsulta_id: null as string | null,
  modalidadGrupoServicioTecSal_id: null as string | null,
  grupoServicios_id: null as string | null,
  codServicio_id: null as string | null,
  finalidadTecnologiaSalud_id: null as string | null,
  causaMotivoAtencion_id: null as string | null,
  codDiagnosticoPrincipal_id: null as string | null,
  codDiagnosticoRelacionado1_id: null as string | null,
  codDiagnosticoRelacionado2_id: null as string | null,
  codDiagnosticoRelacionado3_id: null as string | null,
  tipoDiagnosticoPrincipal_id: null as string | null,
  valorPagoModerador: null as string | null,
  vrServicio: null as string | null,
  conceptoRecaudo_id: null as string | null,
  tipoDocumentoIdentificacion_id: null as string | null,
  numDocumentoIdentificacion: null as string | null,
  numFEVPagoModerador: null as string | null,
})

const dataCalculate = reactive({
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

  form.value.valorPagoModerador = '0,00';
  form.value.vrServicio = '0,00';

  await fetchDataForm();
};

const fetchDataForm = async () => {
  try {
    isLoading.value = true;
    const url = service_id.value ? `/service/medicalConsultation/${service_id.value}/edit` : `/service/medicalConsultation/create`;
    const { data, response } = await useAxios(url).get({
      params: {
        invoice_id: form.value.invoice_id,
      }
    });

    if (response.status === 200 && data) {

      invoice.value = data.invoice

      cupsRips_arrayInfo.value = data.cupsRips_arrayInfo
      modalidadAtencion_arrayInfo.value = data.modalidadAtencion_arrayInfo
      grupoServicio_arrayInfo.value = data.grupoServicio_arrayInfo
      servicio_arrayInfo.value = data.servicio_arrayInfo
      ripsFinalidadConsultaVersion2_arrayInfo.value = data.ripsFinalidadConsultaVersion2_arrayInfo
      cie10_arrayInfo.value = data.cie10_arrayInfo
      ripsTipoDiagnosticoPrincipalVersion2_arrayInfo.value = data.ripsTipoDiagnosticoPrincipalVersion2_arrayInfo
      conceptoRecaudo_arrayInfo.value = data.conceptoRecaudo_arrayInfo
      ripsCausaExternaVersion2_arrayInfo.value = data.ripsCausaExternaVersion2_arrayInfo
      tipoIdPisis_arrayInfo.value = data.tipoIdPisis_arrayInfo


      if (data.form) {
        form.value = cloneObject(data.form);

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
    const url = form.value.id ? `/service/medicalConsultation/update/${form.value.id}` : `/service/medicalConsultation/store`;

    const payload = {
      ...form.value,
      service_id: service_id.value,
      company_id: authenticationStore.company.id,
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

const dataReal = (data: any, field: string) => {
  dataCalculate[field] = data;
};


defineExpose({
  openModal
});

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


// Validations
const numAutorizacionRules = [
  value => lengthBetweenValidator(value, 0, 30),
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



// Validations
const valorPagoModeradorRules = [
  value => positiveValidator(value),
  value => {
    if (form.value.conceptoRecaudo_id?.codigo == '02' || form.value.conceptoRecaudo_id?.codigo == '03') {
      return requiredValidator(value);
    }
    return true;
  }
];

const valorPagoModerador_requiredField = computed(() => {
  return form.value.conceptoRecaudo_id?.codigo == '02' || form.value.conceptoRecaudo_id?.codigo == '03';
});

const numFEVPagoModeradoRules = [
  value => {
    if (form.value.conceptoRecaudo_id?.codigo == '02' || form.value.conceptoRecaudo_id?.codigo == '03' || dataCalculate.real_valorPagoModerador > 0) {
      return requiredValidator(value);
    }
    return true;
  }
];
const numFEVPagoModerador_requiredField = computed(() => {

  if (form.value.conceptoRecaudo_id?.codigo == '02' || form.value.conceptoRecaudo_id?.codigo == '03') {
    return true;
  }

  if (dataCalculate.real_valorPagoModerador > 0) {
    return true
  }
  return false
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
                <AppTextField clearable label="fechaInicioAtencion" v-model="form.fechaInicioAtencion"
                  :requiredField="true" :rules="[requiredValidator]" :error-messages="errorsBack.fechaInicioAtencion"
                  @input="errorsBack.fechaInicioAtencion = ''" :disabled="disabledFiledsView" type="datetime-local"
                  :max="formatToDateTimeLocal(new Date(new Date(invoice?.invoice_date).getTime() + 24 * 60 * 60 * 1000))" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField clearable label="numAutorizacion" v-model="form.numAutorizacion"
                  :rules="numAutorizacionRules" :error-messages="errorsBack.numAutorizacion"
                  @input="errorsBack.numAutorizacion = ''" :disabled="disabledFiledsView" counter maxlength="30"
                  minlength="0" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codConsulta" v-model="form.codConsulta_id" :requiredField="true"
                  :rules="[requiredValidator]" :error-messages="errorsBack.codConsulta_id"
                  @input="errorsBack.codConsulta_id = ''" :disabled="disabledFiledsView" url="/selectInfiniteCupsRips"
                  array-info="cupsRips" :itemsData="cupsRips_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="modalidadGrupoServicioTecSal"
                  v-model="form.modalidadGrupoServicioTecSal_id" :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.modalidadGrupoServicioTecSal_id"
                  @input="errorsBack.modalidadGrupoServicioTecSal_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteModalidadAtencion" array-info="modalidadAtencion"
                  :itemsData="modalidadAtencion_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="grupoServicios" v-model="form.grupoServicios_id" :requiredField="true"
                  :rules="[requiredValidator]" :error-messages="errorsBack.grupoServicios_id"
                  @input="errorsBack.grupoServicios_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteGrupoServicio" array-info="grupoServicio" :itemsData="grupoServicio_arrayInfo"
                  :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codServicio" v-model="form.codServicio_id" :requiredField="true"
                  :rules="[requiredValidator]" :error-messages="errorsBack.codServicio_id"
                  @input="errorsBack.codServicio_id = ''" :disabled="disabledFiledsView" url="/selectInfiniteServicio"
                  array-info="servicio" :itemsData="servicio_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="finalidadTecnologiaSalud" v-model="form.finalidadTecnologiaSalud_id"
                  :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.finalidadTecnologiaSalud_id"
                  @input="errorsBack.finalidadTecnologiaSalud_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteRipsFinalidadConsultaVersion2" array-info="ripsFinalidadConsultaVersion2"
                  :itemsData="ripsFinalidadConsultaVersion2_arrayInfo" :firstFetch="false" :params="{
                    codigo_in: CODS_SELECT_FORM_SERVICE_MEDICAL_CONSULTATION_FINALIDADTECNOLOGIASALUD,
                  }" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="causaMotivoAtencion" v-model="form.causaMotivoAtencion_id"
                  :requiredField="true" :rules="[requiredValidator]" :error-messages="errorsBack.causaMotivoAtencion_id"
                  @input="errorsBack.causaMotivoAtencion_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteRipsCausaExternaVersion2" array-info="ripsCausaExternaVersion2"
                  :itemsData="ripsCausaExternaVersion2_arrayInfo" :firstFetch="false" :params="{
                    codigo_in: CODS_SELECT_FORM_SERVICE_MEDICAL_CONSULTATION_CAUSAMOTIVOATENCION,
                  }" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoPrincipal" v-model="form.codDiagnosticoPrincipal_id"
                  :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.codDiagnosticoPrincipal_id"
                  @input="errorsBack.codDiagnosticoPrincipal_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCie10" array-info="cie10" :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoRelacionado1"
                  v-model="form.codDiagnosticoRelacionado1_id"
                  :error-messages="errorsBack.codDiagnosticoRelacionado1_id"
                  @input="errorsBack.codDiagnosticoRelacionado1_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCie10" array-info="cie10" :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoRelacionado2"
                  v-model="form.codDiagnosticoRelacionado2_id"
                  :error-messages="errorsBack.codDiagnosticoRelacionado2_id"
                  @input="errorsBack.codDiagnosticoRelacionado2_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCie10" array-info="cie10" :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoRelacionado3"
                  v-model="form.codDiagnosticoRelacionado3_id"
                  :error-messages="errorsBack.codDiagnosticoRelacionado3_id"
                  @input="errorsBack.codDiagnosticoRelacionado3_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCie10" array-info="cie10" :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="tipoDiagnosticoPrincipal" v-model="form.tipoDiagnosticoPrincipal_id"
                  :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.tipoDiagnosticoPrincipal_id"
                  @input="errorsBack.tipoDiagnosticoPrincipal_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteRipsTipoDiagnosticoPrincipalVersion2"
                  array-info="ripsTipoDiagnosticoPrincipalVersion2"
                  :itemsData="ripsTipoDiagnosticoPrincipalVersion2_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <FormatCurrency clearable label="valorPagoModerador" v-model="form.valorPagoModerador"
                  :rules="valorPagoModeradorRules" :requiredField="valorPagoModerador_requiredField"
                  :error-messages="errorsBack.valorPagoModerador" @input="errorsBack.valorPagoModerador = ''"
                  :disabled="disabledFiledsView || disabledValorPagoModerador"
                  @realValue="dataReal($event, 'real_valorPagoModerador')" />
              </VCol>
              <VCol cols="12" md="6">
                <FormatCurrency clearable label="vrServicio" v-model="form.vrServicio" :requiredField="true"
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
                    codigo_in: CODS_SELECT_FORM_SERVICE_MEDICAL_CONSULTATION_CONCEPTORECAUDO,
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
                  :requiredField="numFEVPagoModerador_requiredField" :rules="numFEVPagoModeradoRules"
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
