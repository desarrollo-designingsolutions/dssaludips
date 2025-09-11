<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import IErrorsBack from '@/interfaces/Axios/IErrorsBack';
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { reactive, ref } from 'vue';
import type { VForm } from 'vuetify/components';
const { toast } = useToast()

definePage({
  path: 'invoice-Furips1/:action/:invoice_id/:id?',
  name: 'Invoice-Furips1',
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: 'invoice.furips1',
  },
});

interface IInvoiceData {
  id: string | null;
  invoice_date: string | null;
  insurance_statuse_code: string | null;
  serviceVendor_ipsable_codigo: string | null;
}
interface ISelect {
  title: string;
  value: string;
}
interface IForm {
  id: null | string;
  invoice_id: null | string;
  previousFilingNumber: null | string;
  rgoResponse: null | string;
  consecutiveClaimNumber: null | string;
  victimResidenceAddress: null | string;
  victimPhone: null | string;
  victimCondition: null | string;
  eventNature: null | string;
  otherEventDescription: null | string;
  eventOccurrenceAddress: null | string;
  eventOccurrenceDate: null | string;
  eventOccurrenceTime: null | string;
  eventDepartmentCode_id: null | string;
  eventMunicipalityCode_id: null | string;
  eventZone: null | string;
  referenceType: null | string;
  referralDate: null | string;
  departureTime: null | string;
  referringHealthProviderCode_id: null | string;
  referringProfessional: null | string;
  referringPersonPosition: null | string;
  admissionDate: null | string;
  admissionTime: null | string;
  receivingHealthProviderCode_id: null | string;
  receivingProfessional: null | string;
  interinstitutionalTransferAmbulancePlate: null | string;
  vehicleBrand: null | string;
  vehiclePlate: null | string;
  vehicleType: null | string;
  sirasFilingNumber: null | string;
  insurerCapExhaustionCharge: null | string;
  surgicalProcedureComplexity: null | string;
  ownerDocumentType_id: null | string;
  ownerDocumentNumber: null | string;
  ownerFirstLastName: null | string;
  ownerSecondLastName: null | string;
  ownerFirstName: null | string;
  ownerSecondName: null | string;
  ownerResidenceAddress: null | string;
  ownerResidencePhone: null | string;
  ownerResidenceDepartmentCode_id: null | string;
  ownerResidenceMunicipalityCode_id: null | string;
  driverFirstLastName: null | string;
  driverSecondLastName: null | string;
  driverFirstName: null | string;
  driverSecondName: null | string;
  driverDocumentType_id: null | string;
  driverDocumentNumber: null | string;
  driverResidenceAddress: null | string;
  driverResidenceDepartmentCode_id: null | string;
  driverResidenceMunicipalityCode_id: null | string;
  driverResidencePhone: null | string;
  primaryTransferAmbulancePlate: null | string;
  victimTransportFromEventSite: null | string;
  victimTransportToEnd: null | string;
  transportServiceType: null | string;
  victimPickupZone: null | string;
  doctorIdType_id: null | string;
  doctorIdNumber: null | string;
  doctorFirstLastName: null | string;
  doctorSecondLastName: null | string;
  doctorFirstName: null | string;
  doctorSecondName: null | string;
  doctorRegistrationNumber: null | string;
  totalBilledMedicalSurgical: null | string;
  totalClaimedMedicalSurgical: null | string;
  totalBilledTransport: null | string;
  totalClaimedTransport: null | string;
  enabledServicesConfirmation: null | string;
  medicalAdmissionDate: null | string;
  medicalAdmissionTime: null | string;
  medicalDischargeDate: null | string;
  medicalDischargeTime: null | string;
  primaryAdmissionDiagnosisCode_id: null | string;
  associatedAdmissionDiagnosisCode1_id: null | string;
  associatedAdmissionDiagnosisCode2_id: null | string;
  primaryDischargeDiagnosisCode_id: null | string;
  associatedDischargeDiagnosisCode1_id: null | string;
  associatedDischargeDiagnosisCode2_id: null | string;
  authorityIntervention: null | string;
  policyExcessCharge: null | string;
  referralRecipientCharge: null | string;

  mainHospitalizationCupsCode_id: null | string;
  mainSurgicalProcedureCupsCode_id: null | string;
  secondarySurgicalProcedureCupsCode_id: null | string;
  uciServiceProvided: null | string;
  claimedUciDays: null | string;
}

const form = ref<IForm>({
  id: null,
  invoice_id: null,
  previousFilingNumber: null,
  rgoResponse: null,
  consecutiveClaimNumber: null,
  victimResidenceAddress: null,
  victimPhone: null,
  victimCondition: null,
  eventNature: null,
  otherEventDescription: null,
  eventOccurrenceAddress: null,
  eventOccurrenceDate: null,
  eventOccurrenceTime: null,
  eventDepartmentCode_id: null,
  eventMunicipalityCode_id: null,
  eventZone: null,
  referenceType: null,
  referralDate: null,
  departureTime: null,
  referringHealthProviderCode_id: null,
  referringProfessional: null,
  referringPersonPosition: null,
  admissionDate: null,
  admissionTime: null,
  receivingHealthProviderCode_id: null,
  receivingProfessional: null,
  interinstitutionalTransferAmbulancePlate: null,
  vehicleBrand: null,
  vehiclePlate: null,
  vehicleType: null,
  sirasFilingNumber: null,
  insurerCapExhaustionCharge: null,
  surgicalProcedureComplexity: null,
  ownerDocumentType_id: null,
  ownerDocumentNumber: null,
  ownerFirstLastName: null,
  ownerSecondLastName: null,
  ownerFirstName: null,
  ownerSecondName: null,
  ownerResidenceAddress: null,
  ownerResidencePhone: null,
  ownerResidenceDepartmentCode_id: null,
  ownerResidenceMunicipalityCode_id: null,
  driverFirstLastName: null,
  driverSecondLastName: null,
  driverFirstName: null,
  driverSecondName: null,
  driverDocumentType_id: null,
  driverDocumentNumber: null,
  driverResidenceAddress: null,
  driverResidenceDepartmentCode_id: null,
  driverResidenceMunicipalityCode_id: null,
  driverResidencePhone: null,
  primaryTransferAmbulancePlate: null,
  victimTransportFromEventSite: null,
  victimTransportToEnd: null,
  transportServiceType: null,
  victimPickupZone: null,
  doctorIdType_id: null,
  doctorIdNumber: null,
  doctorFirstLastName: null,
  doctorSecondLastName: null,
  doctorFirstName: null,
  doctorSecondName: null,
  doctorRegistrationNumber: null,
  totalBilledMedicalSurgical: null,
  totalClaimedMedicalSurgical: null,
  totalBilledTransport: null,
  totalClaimedTransport: null,
  enabledServicesConfirmation: null,
  medicalAdmissionDate: null,
  medicalAdmissionTime: null,
  medicalDischargeDate: null,
  medicalDischargeTime: null,
  primaryAdmissionDiagnosisCode_id: null,
  associatedAdmissionDiagnosisCode1_id: null,
  associatedAdmissionDiagnosisCode2_id: null,
  primaryDischargeDiagnosisCode_id: null,
  associatedDischargeDiagnosisCode1_id: null,
  associatedDischargeDiagnosisCode2_id: null,
  authorityIntervention: null,
  policyExcessCharge: null,
  referralRecipientCharge: null,

  mainHospitalizationCupsCode_id: null,
  mainSurgicalProcedureCupsCode_id: null,
  secondarySurgicalProcedureCupsCode_id: null,
  uciServiceProvided: null,
  claimedUciDays: null,
});

const clearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}

const errorsBack = ref<IErrorsBack>({});
const loading = reactive({ form: false });
const disabledFiledsView = ref(false);
const route = useRoute()
const authenticationStore = useAuthenticationStore();


// Computed que verifica si al menos uno de los valores es true
const isLoading = computed(() => {
  return Object.values(loading).some(value => value);
});

const tabs = ref([
  { title: 'Datos Iniciales', show: true, errorsValidations: false },
  { title: 'Información del Vehículo y Atención', show: true, errorsValidations: false },
  { title: 'Transporte, Médico y Reclamaciones', show: true, errorsValidations: false },
  { title: 'Datos Adicionales', show: true, errorsValidations: false },
]);

const sections = ref([
  { title: 'I. Datos de la reclamación', show: true, errorsValidations: false },
  { title: 'III. Datos de la víctima del evento catastrófico o accidente de tránsito', show: true, errorsValidations: false },
  { title: 'IV. Datos del sitio donde ocurrió el evento catastrófico o el accidente de tránsito', show: true, errorsValidations: false },
  { title: 'IX. Datos de remisión', show: true, errorsValidations: false },
  { title: 'V. Datos del vehículo involucrado en el accidente de tránsito', show: true, errorsValidations: false },
  { title: 'VI. Datos Relacionados con la Atención de la Víctima', show: true, errorsValidations: false },
  { title: 'VII. Datos del propietario del vehículo', show: true, errorsValidations: false },
  { title: 'VIII. Datos del conductor involucrado en el accidente de tránsito', show: true, errorsValidations: false },
  { title: 'X. Transporte y movilización de la víctima', show: true, errorsValidations: false },
  { title: 'XII. Datos del médico o profesional de la salud tratante', show: true, errorsValidations: false },
  { title: 'XIII. Amparos que reclama', show: true, errorsValidations: false },
  { title: 'XIV. Confirmación servicios habilitados', show: true, errorsValidations: false },
  { title: 'XI. Certificación de la atención medica de la víctima como prueba del accidente o evento.', show: true, errorsValidations: false },
  { title: 'XV. Datos adicionales para PDF.', show: true, errorsValidations: false },
]);

const formRefs = ref<(VForm | null)[]>(new Array(sections.value.length).fill(null));
const currentTab = ref(0);

const updateValidationToFalse = () => {
  tabs.value.forEach(tab => { tab.errorsValidations = false; });
  sections.value.forEach(section => { section.errorsValidations = false; });
};

const allValidations = async () => {
  updateValidationToFalse();
  const validations = await Promise.all(
    formRefs.value.map(async (formRef, index) => {
      if (!sections.value[index].show) return true;
      const validation = await formRef?.validate?.();
      if (validation) {
        sections.value[index].errorsValidations = !validation.valid;
        const tabIndex = Math.floor(index / 4);
        tabs.value[tabIndex].errorsValidations = tabs.value[tabIndex].errorsValidations || !validation.valid;
        return validation.valid;
      }
      return true;
    }),
  );

  const firstErrorIndex = sections.value.findIndex(section => section.errorsValidations);
  if (firstErrorIndex !== -1) {
    currentTab.value = Math.floor(firstErrorIndex / 4);
  }

  return validations.every(valid => valid === true);
};

const submitForm = async () => {
  const isValid = await allValidations();
  if (isValid) {

    const url = form.value.id ? `/furips1/update/${form.value.id}` : `/furips1/store`

    loading.form = true;
    const { data, response } = await useAxios(url).post(form.value);
    loading.form = false;

    if (response.status == 200 && data) {

      form.value.id = data.furips1.id

      router.replace({ name: "Invoice-Furips1", params: { action: 'edit', invoice_id: form.value.invoice_id, id: form.value.id } });

    }
    if (data.code === 422) errorsBack.value = data.errors ?? {};


  } else {
    toast('Faltan Campos Por Diligenciar', '', 'danger')
  }
};


const limitNumberLength = () => {
  errorsBack.value.consecutiveClaimNumber = ''

  const maxLength = 12;
  if (form.value.consecutiveClaimNumber && form.value.consecutiveClaimNumber.toString().length > maxLength) {
    form.value.consecutiveClaimNumber = form.value.consecutiveClaimNumber.toString().slice(0, maxLength);
  }
};



const rgoResponseEnum_arrayInfo = ref<ISelect[]>([])
const victimConditionEnum_arrayInfo = ref<ISelect[]>([])
const eventNatureEnum_arrayInfo = ref<ISelect[]>([])
const referenceTypeEnum_arrayInfo = ref<ISelect[]>([])
const ipsCodHabilitacion_arrayInfo = ref<ISelect[]>([])
const vehicleTypeEnum_arrayInfo = ref<ISelect[]>([])
const yesNoEnum_arrayInfo = ref<ISelect[]>([])
const surgicalComplexityEnum_arrayInfo = ref<ISelect[]>([])
const ownerDocumentType_arrayInfo = ref<ISelect[]>([])
const driverDocumentType_arrayInfo = ref<ISelect[]>([])
const transportServiceTypeEnum_arrayInfo = ref<ISelect[]>([])
const zoneEnum_arrayInfo = ref<ISelect[]>([])
const municipios_arrayInfo = ref<ISelect[]>([])
const paises_arrayInfo = ref<ISelect[]>([])
const departamentos_arrayInfo = ref<ISelect[]>([])
const doctorIdType_arrayInfo = ref<ISelect[]>([])
const cie10_arrayInfo = ref<ISelect[]>([])
const cupsRips_arrayInfo = ref([])

const invoice = ref<IInvoiceData>({
  id: null,
  invoice_date: null,
  insurance_statuse_code: null,
  serviceVendor_ipsable_codigo: null,
})

const fetchDataForm = async () => {

  form.value.invoice_id = route.params.invoice_id || null
  form.value.id = route.params.id || null

  const url = form.value.id ? `/furips1/${form.value.id}/edit` : `/furips1/create/${form.value.invoice_id}`

  loading.form = true
  const { response, data } = await useAxios(url).get();
  loading.form = false


  if (response.status == 200 && data) {

    rgoResponseEnum_arrayInfo.value = data.rgoResponseEnum_arrayInfo
    victimConditionEnum_arrayInfo.value = data.victimConditionEnum_arrayInfo
    eventNatureEnum_arrayInfo.value = data.eventNatureEnum_arrayInfo
    zoneEnum_arrayInfo.value = data.zoneEnum_arrayInfo
    referenceTypeEnum_arrayInfo.value = data.referenceTypeEnum_arrayInfo
    ipsCodHabilitacion_arrayInfo.value = data.ipsCodHabilitacion_arrayInfo
    vehicleTypeEnum_arrayInfo.value = data.vehicleTypeEnum_arrayInfo
    yesNoEnum_arrayInfo.value = data.yesNoEnum_arrayInfo
    surgicalComplexityEnum_arrayInfo.value = data.surgicalComplexityEnum_arrayInfo
    ownerDocumentType_arrayInfo.value = data.ownerDocumentType_arrayInfo
    driverDocumentType_arrayInfo.value = data.driverDocumentType_arrayInfo
    transportServiceTypeEnum_arrayInfo.value = data.transportServiceTypeEnum_arrayInfo
    municipios_arrayInfo.value = data.municipios_arrayInfo
    paises_arrayInfo.value = data.paises_arrayInfo
    departamentos_arrayInfo.value = data.departamentos_arrayInfo
    doctorIdType_arrayInfo.value = data.doctorIdType_arrayInfo
    cie10_arrayInfo.value = data.cie10_arrayInfo
    cupsRips_arrayInfo.value = data.cupsRips_arrayInfo

    invoice.value = data.invoice

    //formulario 
    if (data.form) {
      form.value = cloneObject(data.form)
    }
  }
}

if (route.params.action == 'view') disabledFiledsView.value = true

onMounted(async () => {
  clearForm()

  await fetchDataForm()
})


//Validations
const vehicleBrand_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["5", "3"].includes(invoice.value.insurance_statuse_code ?? "")) {
    return {
      rules: [],
      requiredField: false
    }
  }
  return {
    rules: rules,
    requiredField: true
  }
})
const vehiclePlate_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["1", "2", "4", "5", "6", "7"].includes(invoice.value.insurance_statuse_code ?? "")) {
    return {
      rules: rules,
      requiredField: true
    }
  }
  return {
    rules: [],
    requiredField: false
  }
})
const vehicleType_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["1", "2", "4", "5", "6", "7"].includes(invoice.value.insurance_statuse_code ?? "")) {
    return {
      rules: rules,
      requiredField: true
    }
  }
  return {
    rules: [],
    requiredField: false
  }
})

const sirasFilingNumber_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (["01"].includes(form.value.eventNature ?? "")) {
    return {
      rules: rules,
      requiredField: true
    }
  }
  return {
    rules: [],
    requiredField: false
  }
})

const insurerCapExhaustionCharge_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["6"].includes(invoice.value.insurance_statuse_code ?? "")) {
    return {
      rules: rules,
      requiredField: true
    }
  }
  return {
    rules: [],
    requiredField: false
  }
})

const ownerDocumentType_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["1", "2", "4", "6"].includes(invoice.value.insurance_statuse_code ?? "")) {
    return {
      rules: rules,
      requiredField: true
    }
  }
  return {
    rules: [],
    requiredField: false
  }
})

const dynamicDocumentLengthRule = computed(() => (value: string) => {
  const tipoId = form.value.ownerDocumentType_id?.codigo;

  if (!tipoId || !value) return true;
  const maxLength = documentLengthByType[tipoId] || 20;
  return (
    value.length <= maxLength ||
    `El documento ${tipoId} debe tener máximo ${maxLength} caracteres`
  );
});

const ownerDocumentNumber_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
    dynamicDocumentLengthRule.value,
  ]

  if (invoice.value && ["1", "2", "4", "6"].includes(invoice.value.insurance_statuse_code ?? "")) {
    return {
      rules: rules,
      requiredField: true
    }
  }
  return {
    rules: [dynamicDocumentLengthRule.value],
    requiredField: false
  }
})

const insuranceStatuseCode1246_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["1", "2", "4", "6"].includes(invoice.value.insurance_statuse_code ?? "")) {
    return {
      rules: rules,
      requiredField: true
    }
  }
  return {
    rules: [],
    requiredField: false
  }
})
const insuranceStatuseCode12467_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["1", "2", "4", "6", "7"].includes(invoice.value.insurance_statuse_code ?? "")) {
    return {
      rules: rules,
      requiredField: true
    }
  }
  return {
    rules: [],
    requiredField: false
  }
})



const driverDocumentTypeRuleMaxCharacters = computed(() => (value: string) => {
  const tipoId = form.value.ownerResidenceMunicipalityCode_id?.codigo;

  if (!tipoId || !value) return true;
  const maxLength = documentLengthByType[tipoId] || 20;
  return (
    value.length <= maxLength ||
    `El documento ${tipoId} debe tener máximo ${maxLength} caracteres`
  );
});

const driverDocumentType_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
    driverDocumentTypeRuleMaxCharacters.value,
  ]

  if (invoice.value && ["1", "2", "4", "6", "7"].includes(invoice.value.insurance_statuse_code ?? "")) {
    return {
      rules: rules,
      requiredField: true
    }
  }
  return {
    rules: [driverDocumentTypeRuleMaxCharacters.value],
    requiredField: false
  }
})

const validationDependInfo = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (form.value.primaryTransferAmbulancePlate || form.value.victimTransportFromEventSite || form.value.victimTransportToEnd || form.value.transportServiceType || form.value.victimPickupZone) {
    return {
      rules: rules,
      requiredField: true
    }
  }

  return {
    rules: [],
    requiredField: false
  }
})

const validationDependInfoPreviousFilingNumberRgoResponse = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (form.value.previousFilingNumber || form.value.rgoResponse) {
    return {
      rules: rules,
      requiredField: true
    }
  }

  return {
    rules: [],
    requiredField: false
  }
})



const doctorIdNumberRuleMaxCharacters = computed(() => (value: string) => {
  const tipoId = form.value.doctorIdType_id?.codigo;

  if (!tipoId || !value) return true;
  const maxLength = documentLengthByType[tipoId] || 20;
  return (
    value.length <= maxLength ||
    `El documento ${tipoId} debe tener máximo ${maxLength} caracteres`
  );
});

const doctorIdType_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
    doctorIdNumberRuleMaxCharacters.value,
  ]
  return {
    rules: rules,
    requiredField: false
  }
})

const consecutiveClaimNumber_validation = computed(() => {
  const rules = [];

  // Solo agregar la validación si el campo tiene un valor
  if (form.value.consecutiveClaimNumber) {
    rules.push(value => greaterThanZeroValidator(value));
  }

  return {
    rules: rules,
    requiredField: false
  };
});

const otherEventDescription_validation = computed(() => {
  const rules = [];

  // Agregar la validación requerida si form.value.eventNature es 'EVENT_NATURE_017'
  if (form.value.eventNature === 'EVENT_NATURE_017') {
    rules.push(value => requiredValidator(value));
  }

  return {
    rules: rules,
    requiredField: form.value.eventNature === 'EVENT_NATURE_017'
  };
});

const downloadPDF = async () => {

  loading.form = true;
  const { response, data } = await useAxios(`/furips1/${form.value.invoice_id}/pdf`).get();

  if (response.status == 200 && data) {
    openPdfBase64(data.path);
  }

  loading.form = false;
}

const goView = (data: { action: string, invoice_id: string | null, id: string | null } = { action: "create", invoice_id: null, id: null }) => {
  disabledFiledsView.value = false;
  router.push({ name: "Invoice-Furips1", params: { action: data.action, invoice_id: data.invoice_id, id: data.id } })
}

const loadEdit = () => {
  goView({ action: 'edit', invoice_id: form.value.invoice_id, id: form.value.id })
}

const downloadTXT = async () => {
  try {
    loading.form = true;

    const today = new Date();
    const day = String(today.getDate()).padStart(2, '0');
    const month = String(today.getMonth() + 1).padStart(2, '0'); // Meses van de 0 a 11
    const year = today.getFullYear();
    const formattedDate = `${day}${month}${year}`; // ddmmyyyy

    const serviceVendor_ipsable_codigo = invoice.value.serviceVendor_ipsable_codigo ? invoice.value.serviceVendor_ipsable_codigo : ''

    const api = `/furips1/downloadTxt/${form.value.id}`
    const nameFile = `${'FURIPS1' + serviceVendor_ipsable_codigo + formattedDate}`
    const ext = "txt"

    await downloadBlob(api, nameFile, ext)

  } catch (error) {
    console.error('Error al descargar el archivo:', error);
  } finally {
    loading.form = false;

  }
};
</script>




<template>
  <div>
    <VCard :disabled="loading.form" :loading="loading.form">
      <VCardTitle class="d-flex justify-space-between">
        <span>Información del furips1</span>
        <div>
          <VRow v-if="disabledFiledsView">
            <VCol>
              <VBtn :loading="loading.form" @click="downloadTXT">TXT
              </VBtn>
            </VCol>
            <VCol>
              <VBtn :loading="loading.form" @click="downloadPDF">PDF
              </VBtn>
            </VCol>
            <VCol>
              <VBtn @click="loadEdit">Editar
              </VBtn>
            </VCol>
          </VRow>
        </div>
      </VCardTitle>

      <VCardText>
        <VTabs v-model="currentTab">
          <VTab class="text-none" v-for="(tab, index) in tabs" :key="index" v-show="tab.show">
            <VIcon start :icon="!tab.errorsValidations ? '' : 'tabler-alert-circle-filled'"
              :color="!tab.errorsValidations ? '' : 'error'" />
            {{ tab.title }}
          </VTab>
        </VTabs>
      </VCardText>

      <VCardText>
        <!-- Tab 0: Datos Iniciales -->
        <div v-show="currentTab == 0">
          <!-- Section 0: Datos de la reclamación -->
          <VCardTitle>I. Datos de la reclamación</VCardTitle>
          <VForm :ref="el => formRefs[0] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="validationDependInfoPreviousFilingNumberRgoResponse.requiredField"
                  label="Número de radicado anterior" v-model="form.previousFilingNumber" clearable :maxlength="10"
                  counter :errorMessages="errorsBack.previousFilingNumber" @input="errorsBack.previousFilingNumber = ''"
                  :rules="validationDependInfoPreviousFilingNumberRgoResponse.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="validationDependInfoPreviousFilingNumberRgoResponse.requiredField"
                  label="RGO Respuesta a Glosa u objeción" v-model="form.rgoResponse" clearable
                  :errorMessages="errorsBack.rgoResponse" @input="errorsBack.rgoResponse = ''"
                  :items="rgoResponseEnum_arrayInfo"
                  :rules="validationDependInfoPreviousFilingNumberRgoResponse.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField type="number" label="Número consecutivo de la reclamación"
                  v-model="form.consecutiveClaimNumber" clearable :maxlength="12" counter
                  :errorMessages="errorsBack.consecutiveClaimNumber" @input="limitNumberLength"
                  :rules="consecutiveClaimNumber_validation.rules" />
              </VCol>
            </VRow>
          </VForm>

          <!-- Section 1: Datos de la víctima -->

          <VCardTitle class="mt-4">III. Datos de la víctima del evento catastrófico o accidente de tránsito</VCardTitle>

          <VForm :ref="el => formRefs[1] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" label="Dirección de residencia de la víctima"
                  v-model="form.victimResidenceAddress" clearable :maxlength="40" counter
                  :errorMessages="errorsBack.victimResidenceAddress" @input="errorsBack.victimResidenceAddress = ''"
                  :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" label="Teléfono de la víctima" v-model="form.victimPhone" clearable
                  :maxlength="10" counter :errorMessages="errorsBack.victimPhone" @input="errorsBack.victimPhone = ''"
                  :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="true" label="Condición de la víctima" v-model="form.victimCondition"
                  clearable :errorMessages="errorsBack.victimCondition" @input="errorsBack.victimCondition = ''"
                  :items="victimConditionEnum_arrayInfo" :rules="[requiredValidator]" />
              </VCol>
            </VRow>
          </VForm>


          <!-- Section 2: Datos del sitio del evento -->
          <VCardTitle class="mt-4">IV. Datos del sitio donde ocurrió el evento catastrófico o el accidente de tránsito
          </VCardTitle>
          <VForm :ref="el => formRefs[2] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="true" label="Naturaleza del evento" v-model="form.eventNature" clearable
                  :errorMessages="errorsBack.eventNature" @input="errorsBack.eventNature = ''"
                  :items="eventNatureEnum_arrayInfo" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="otherEventDescription_validation.requiredField"
                  label="Descripción del otro evento" v-model="form.otherEventDescription" clearable :maxlength="25"
                  counter :errorMessages="errorsBack.otherEventDescription"
                  @input="errorsBack.otherEventDescription = ''" :rules="otherEventDescription_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" label="Dirección de ocurrencia del evento"
                  v-model="form.eventOccurrenceAddress" clearable :maxlength="40" counter
                  :errorMessages="errorsBack.eventOccurrenceAddress" @input="errorsBack.eventOccurrenceAddress = ''"
                  :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField type="date" :requiredField="true" label="Fecha de ocurrencia del evento"
                  v-model="form.eventOccurrenceDate" clearable :errorMessages="errorsBack.eventOccurrenceDate"
                  @input="errorsBack.eventOccurrenceDate = ''" :rules="[requiredValidator]"
                  :max="invoice.invoice_date" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppDateTimePicker :requiredField="true" label="Hora de ocurrencia del evento"
                  v-model="form.eventOccurrenceTime" clearable :errorMessages="errorsBack.eventOccurrenceTime"
                  @input="errorsBack.eventOccurrenceTime = ''" :rules="[requiredValidator]" :config="{
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true
                  }" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                  label="Código del departamento de ocurrencia del evento" v-model="form.eventDepartmentCode_id"
                  :errorMessages="errorsBack.eventDepartmentCode_id" @input="errorsBack.eventDepartmentCode_id = ''"
                  url="/selectInfiniteDepartamento" arrayInfo="departamentos" clearable
                  :itemsData="departamentos_arrayInfo" :firstFetch="false" :rules="[requiredValidator]">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                  label="Código del municipio de ocurrencia del evento" v-model="form.eventMunicipalityCode_id"
                  :errorMessages="errorsBack.eventMunicipalityCode_id" @input="errorsBack.eventMunicipalityCode_id = ''"
                  url="/selectInfiniteMunicipio" arrayInfo="municipios" clearable :itemsData="municipios_arrayInfo"
                  :firstFetch="false" :rules="[requiredValidator]">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="true" label="Zona de ocurrencia del evento" v-model="form.eventZone"
                  clearable :errorMessages="errorsBack.eventZone" @input="errorsBack.eventZone = ''"
                  :items="zoneEnum_arrayInfo" :rules="[requiredValidator]" />
              </VCol>
            </VRow>
          </VForm>


          <!-- Section 3: Datos de remisión -->
          <VCardTitle class="mt-4">IX. Datos de remisión</VCardTitle>
          <VForm :ref="el => formRefs[3] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="true" label="Tipo de referencia" v-model="form.referenceType" clearable
                  :errorMessages="errorsBack.referenceType" @input="errorsBack.referenceType = ''"
                  :items="referenceTypeEnum_arrayInfo" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField type="date" :requiredField="true" label="Fecha de remisión" v-model="form.referralDate"
                  clearable :errorMessages="errorsBack.referralDate" @input="errorsBack.referralDate = ''"
                  :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppDateTimePicker :requiredField="true" label="Hora de salida" v-model="form.departureTime" clearable
                  :errorMessages="errorsBack.departureTime" @input="errorsBack.departureTime = ''"
                  :rules="[requiredValidator]" :config="{
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true
                  }" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote clearable label="Código de habilitación del prestador de servicios de salud remitente"
                  v-model="form.referringHealthProviderCode_id" :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.referringHealthProviderCode_id"
                  @input="errorsBack.referringHealthProviderCode_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteIpsCodHabilitacion" array-info="ipsCodHabilitacion"
                  :itemsData="ipsCodHabilitacion_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" label="Profesional que remite" v-model="form.referringProfessional"
                  clearable :maxlength="60" counter :errorMessages="errorsBack.referringProfessional"
                  @input="errorsBack.referringProfessional = ''" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" label="Cargo de la persona que remite"
                  v-model="form.referringPersonPosition" clearable :maxlength="30" counter
                  :errorMessages="errorsBack.referringPersonPosition" @input="errorsBack.referringPersonPosition = ''"
                  :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField type="date" :requiredField="true" label="Fecha de ingreso" v-model="form.admissionDate"
                  clearable :errorMessages="errorsBack.admissionDate" @input="errorsBack.admissionDate = ''"
                  :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">

                <AppDateTimePicker :requiredField="true" label="Hora de ingreso" v-model="form.admissionTime" clearable
                  :errorMessages="errorsBack.admissionTime" @input="errorsBack.admissionTime = ''"
                  :rules="[requiredValidator]" :config="{
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true
                  }" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote clearable label="Código de habilitación del prestador de servicios de salud que recibe"
                  v-model="form.receivingHealthProviderCode_id" :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.receivingHealthProviderCode_id"
                  @input="errorsBack.receivingHealthProviderCode_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteIpsCodHabilitacion" array-info="ipsCodHabilitacion"
                  :itemsData="ipsCodHabilitacion_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" label="Profesional que recibe" v-model="form.receivingProfessional"
                  clearable :maxlength="60" counter :errorMessages="errorsBack.receivingProfessional"
                  @input="errorsBack.receivingProfessional = ''" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" label="Placa ambulancia que realiza el traslado interinstitucional"
                  v-model="form.interinstitutionalTransferAmbulancePlate" clearable :maxlength="6" counter
                  :errorMessages="errorsBack.interinstitutionalTransferAmbulancePlate"
                  @input="errorsBack.interinstitutionalTransferAmbulancePlate = ''" :rules="[requiredValidator]" />
              </VCol>
            </VRow>
          </VForm>
        </div>

        <!-- Tab 1: Información del Vehículo y Atención -->
        <div v-show="currentTab == 1">
          <!-- Section 4: Datos del vehículo -->
          <VCardTitle>V. Datos del vehículo involucrado en el accidente de tránsito</VCardTitle>
          <VForm :ref="el => formRefs[4] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="vehicleBrand_validation.requiredField" label="Marca"
                  v-model="form.vehicleBrand" clearable :maxlength="15" counter :errorMessages="errorsBack.vehicleBrand"
                  @input="errorsBack.vehicleBrand = ''" :rules="vehicleBrand_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="vehiclePlate_validation.requiredField" label="Placa"
                  v-model="form.vehiclePlate" clearable :maxlength="10" counter :errorMessages="errorsBack.vehiclePlate"
                  @input="errorsBack.vehiclePlate = ''" :rules="vehiclePlate_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="vehicleType_validation.requiredField" label="Tipo de Vehículo"
                  v-model="form.vehicleType" clearable :errorMessages="errorsBack.vehicleType"
                  @input="errorsBack.vehicleType = ''" :items="vehicleTypeEnum_arrayInfo"
                  :rules="vehicleType_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="sirasFilingNumber_validation.requiredField"
                  label="Número de radicado SIRAS (id_atencion)" v-model="form.sirasFilingNumber" clearable
                  :maxlength="20" counter :errorMessages="errorsBack.sirasFilingNumber"
                  @input="errorsBack.sirasFilingNumber = ''" :rules="sirasFilingNumber_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="insurerCapExhaustionCharge_validation.requiredField"
                  label="Cobro por agotamiento tope Aseguradora" v-model="form.insurerCapExhaustionCharge" clearable
                  :items="yesNoEnum_arrayInfo" :errorMessages="errorsBack.insurerCapExhaustionCharge"
                  @input="errorsBack.insurerCapExhaustionCharge = ''"
                  :rules="insurerCapExhaustionCharge_validation.rules" />
              </VCol>
            </VRow>
          </VForm>

          <!-- Section 5: Datos relacionados con la atención -->
          <VCardTitle class="mt-4">VI. Datos Relacionados con la Atención de la Víctima</VCardTitle>
          <VForm :ref="el => formRefs[5] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" md="4">
                <AppSelectRemote clearable label="Código CUPS de servicio principal de hospitalización"
                  v-model="form.mainHospitalizationCupsCode_id"
                  :error-messages="errorsBack.mainHospitalizationCupsCode_id"
                  @input="errorsBack.mainHospitalizationCupsCode_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCupsRips" array-info="cupsRips" :itemsData="cupsRips_arrayInfo"
                  :firstFetch="false" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelect label="Complejidad del procedimiento quirúrgico" v-model="form.surgicalProcedureComplexity"
                  clearable :errorMessages="errorsBack.surgicalProcedureComplexity"
                  @input="errorsBack.surgicalProcedureComplexity = ''" :items="surgicalComplexityEnum_arrayInfo" />
              </VCol>
              <VCol cols="12" md="4">
                <AppSelectRemote clearable label="Código CUPS del procedimiento quirúrgico principal"
                  v-model="form.mainSurgicalProcedureCupsCode_id"
                  :error-messages="errorsBack.mainSurgicalProcedureCupsCode_id"
                  @input="errorsBack.mainSurgicalProcedureCupsCode_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCupsRips" array-info="cupsRips" :itemsData="cupsRips_arrayInfo"
                  :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="4">
                <AppSelectRemote clearable label="Código CUPS del procedimiento quirúrgico secundario"
                  v-model="form.secondarySurgicalProcedureCupsCode_id"
                  :error-messages="errorsBack.secondarySurgicalProcedureCupsCode_id"
                  @input="errorsBack.secondarySurgicalProcedureCupsCode_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCupsRips" array-info="cupsRips" :itemsData="cupsRips_arrayInfo"
                  :firstFetch="false" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelect label="Se presto servicio UCI" v-model="form.uciServiceProvided" clearable
                  :errorMessages="errorsBack.uciServiceProvided" @input="errorsBack.uciServiceProvided = ''"
                  :items="yesNoEnum_arrayInfo" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField label="Días de UCI reclamados" v-model="form.claimedUciDays" clearable counter
                  :errorMessages="errorsBack.claimedUciDays" @input="errorsBack.claimedUciDays = ''" />
              </VCol>
            </VRow>
          </VForm>

          <!-- Section 6: Datos del propietario -->
          <VCardTitle class="mt-4">VII. Datos del propietario del vehículo</VCardTitle>
          <VForm :ref="el => formRefs[6] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView" label="Tipo de documento de identidad del propietario"
                  v-model="form.ownerDocumentType_id" url="/selectInfiniteTipoIdPisis" arrayInfo="tipoIdPisis"
                  :requiredField="ownerDocumentType_validation.requiredField"
                  :errorMessages="errorsBack.ownerDocumentType_id" @input="errorsBack.ownerDocumentType_id = ''"
                  :rules="ownerDocumentType_validation.rules" clearable :itemsData="ownerDocumentType_arrayInfo"
                  :firstFetch="false" :params="{
                    codigo_in: CODS_SELECT_FORM_FURIPS1_OWNERDOCUMENTTYPE,
                  }">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="ownerDocumentNumber_validation.requiredField"
                  label="Número de documento de identidad del propietario" v-model="form.ownerDocumentNumber" clearable
                  :maxlength="16" counter :errorMessages="errorsBack.ownerDocumentNumber"
                  @input="errorsBack.ownerDocumentNumber = ''" :rules="ownerDocumentNumber_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="insuranceStatuseCode1246_validation.requiredField"
                  label="Primer apellido del propietario o razón social en caso de empresa"
                  v-model="form.ownerFirstLastName" clearable :maxlength="40" counter
                  :errorMessages="errorsBack.ownerFirstLastName" @input="errorsBack.ownerFirstLastName = ''"
                  :rules="insuranceStatuseCode1246_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField label="Segundo apellido del propietario" v-model="form.ownerSecondLastName" clearable
                  :maxlength="30" counter :errorMessages="errorsBack.ownerSecondLastName"
                  @input="errorsBack.ownerSecondLastName = ''" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="insuranceStatuseCode1246_validation.requiredField"
                  label="Primer nombre del propietario" v-model="form.ownerFirstName" clearable :maxlength="20" counter
                  :errorMessages="errorsBack.ownerFirstName" @input="errorsBack.ownerFirstName = ''"
                  :rules="insuranceStatuseCode1246_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField label="Segundo nombre del propietario" v-model="form.ownerSecondName" clearable
                  :maxlength="30" counter :errorMessages="errorsBack.ownerSecondName"
                  @input="errorsBack.ownerSecondName = ''" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="insuranceStatuseCode1246_validation.requiredField"
                  label="Dirección de residencia del propietario" v-model="form.ownerResidenceAddress" clearable
                  :maxlength="40" counter :errorMessages="errorsBack.ownerResidenceAddress"
                  @input="errorsBack.ownerResidenceAddress = ''" :rules="insuranceStatuseCode1246_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="insuranceStatuseCode1246_validation.requiredField"
                  label="Teléfono de residencia del propietario" v-model="form.ownerResidencePhone" clearable
                  :maxlength="10" counter :errorMessages="errorsBack.ownerResidencePhone"
                  @input="errorsBack.ownerResidencePhone = ''" :rules="insuranceStatuseCode1246_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView"
                  :requiredField="insuranceStatuseCode1246_validation.requiredField"
                  label="Código del departamento de residencia del propietario"
                  v-model="form.ownerResidenceDepartmentCode_id"
                  :errorMessages="errorsBack.ownerResidenceDepartmentCode_id"
                  @input="errorsBack.ownerResidenceDepartmentCode_id = ''" url="/selectInfiniteDepartamento"
                  arrayInfo="departamentos" clearable :itemsData="departamentos_arrayInfo" :firstFetch="false"
                  :rules="insuranceStatuseCode1246_validation.rules">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView"
                  :requiredField="insuranceStatuseCode1246_validation.requiredField"
                  label="Código del municipio de residencia del propietario"
                  v-model="form.ownerResidenceMunicipalityCode_id"
                  :errorMessages="errorsBack.ownerResidenceMunicipalityCode_id"
                  @input="errorsBack.ownerResidenceMunicipalityCode_id = ''" url="/selectInfiniteMunicipio"
                  arrayInfo="municipios" clearable :itemsData="municipios_arrayInfo" :firstFetch="false"
                  :rules="insuranceStatuseCode1246_validation.rules">
                </AppSelectRemote>

              </VCol>
            </VRow>
          </VForm>

          <!-- Section 7: Datos del conductor -->
          <VCardTitle class="mt-4">VIII. Datos del conductor involucrado en el accidente de tránsito</VCardTitle>
          <VForm :ref="el => formRefs[7] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="insuranceStatuseCode12467_validation.requiredField"
                  label="Primer apellido del conductor" v-model="form.driverFirstLastName" clearable :maxlength="20"
                  counter :errorMessages="errorsBack.driverFirstLastName" @input="errorsBack.driverFirstLastName = ''"
                  :rules="insuranceStatuseCode12467_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField label="Segundo apellido del conductor" v-model="form.driverSecondLastName" clearable
                  :maxlength="30" counter :errorMessages="errorsBack.driverSecondLastName"
                  @input="errorsBack.driverSecondLastName = ''" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="insuranceStatuseCode12467_validation.requiredField"
                  label="Primer nombre del conductor" v-model="form.driverFirstName" clearable :maxlength="20" counter
                  :errorMessages="errorsBack.driverFirstName" @input="errorsBack.driverFirstName = ''"
                  :rules="insuranceStatuseCode12467_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField label="Segundo nombre del conductor" v-model="form.driverSecondName" clearable
                  :maxlength="30" counter :errorMessages="errorsBack.driverSecondName"
                  @input="errorsBack.driverSecondName = ''" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView" label="Tipo de documento de identidad del conductor"
                  v-model="form.driverDocumentType_id" url="/selectInfiniteTipoIdPisis" arrayInfo="tipoIdPisis"
                  :requiredField="insuranceStatuseCode12467_validation.requiredField"
                  :errorMessages="errorsBack.driverDocumentType_id" @input="errorsBack.driverDocumentType_id = ''"
                  :rules="insuranceStatuseCode12467_validation.rules" clearable
                  :itemsData="driverDocumentType_arrayInfo" :firstFetch="false" :params="{
                    codigo_in: CODS_SELECT_FORM_FURIPS1_DRIVERDOCUMENTTYPE,
                  }">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="driverDocumentType_validation.requiredField"
                  label="Número de documento de identidad del conductor" v-model="form.driverDocumentNumber" clearable
                  :maxlength="16" counter :errorMessages="errorsBack.driverDocumentNumber"
                  @input="errorsBack.driverDocumentNumber = ''" :rules="driverDocumentType_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="insuranceStatuseCode12467_validation.requiredField"
                  label="Dirección de residencia del conductor" v-model="form.driverResidenceAddress" clearable
                  :maxlength="40" counter :errorMessages="errorsBack.driverResidenceAddress"
                  @input="errorsBack.driverResidenceAddress = ''" :rules="insuranceStatuseCode12467_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView"
                  :requiredField="insuranceStatuseCode12467_validation.requiredField"
                  label="Código del departamento de residencia del conductor"
                  v-model="form.driverResidenceDepartmentCode_id"
                  :errorMessages="errorsBack.driverResidenceDepartmentCode_id"
                  @input="errorsBack.driverResidenceDepartmentCode_id = ''" url="/selectInfiniteDepartamento"
                  arrayInfo="departamentos" clearable :itemsData="departamentos_arrayInfo" :firstFetch="false"
                  :rules="insuranceStatuseCode12467_validation.rules">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView"
                  :requiredField="insuranceStatuseCode12467_validation.requiredField"
                  label="Código del municipio de residencia del conductor"
                  v-model="form.driverResidenceMunicipalityCode_id"
                  :errorMessages="errorsBack.driverResidenceMunicipalityCode_id"
                  @input="errorsBack.driverResidenceMunicipalityCode_id = ''" url="/selectInfiniteMunicipio"
                  arrayInfo="municipios" clearable :itemsData="municipios_arrayInfo" :firstFetch="false"
                  :rules="insuranceStatuseCode12467_validation.rules">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="insuranceStatuseCode12467_validation.requiredField"
                  label="Teléfono de residencia del conductor" v-model="form.driverResidencePhone" clearable
                  :maxlength="10" counter :errorMessages="errorsBack.driverResidencePhone"
                  @input="errorsBack.driverResidencePhone = ''" :rules="insuranceStatuseCode12467_validation.rules" />
              </VCol>
            </VRow>
          </VForm>
        </div>

        <!-- Tab 2: Transporte, Médico y Reclamaciones -->
        <div v-show="currentTab == 2">
          <!-- Section 8: Transporte y movilización -->
          <VCardTitle>X. Transporte y movilización de la víctima</VCardTitle>
          <VForm :ref="el => formRefs[8] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="validationDependInfo.requiredField"
                  label="Placa ambulancia traslado primario" v-model="form.primaryTransferAmbulancePlate" clearable
                  :maxlength="6" counter :errorMessages="errorsBack.primaryTransferAmbulancePlate"
                  @input="errorsBack.primaryTransferAmbulancePlate = ''" :rules="validationDependInfo.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="validationDependInfo.requiredField"
                  label="Transporte de la víctima desde el sitio del evento" v-model="form.victimTransportFromEventSite"
                  clearable :maxlength="40" counter :errorMessages="errorsBack.victimTransportFromEventSite"
                  @input="errorsBack.victimTransportFromEventSite = ''" :rules="validationDependInfo.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="validationDependInfo.requiredField"
                  label="Transporte de la víctima hasta el fin del recorrido" v-model="form.victimTransportToEnd"
                  clearable :maxlength="40" counter :errorMessages="errorsBack.victimTransportToEnd"
                  @input="errorsBack.victimTransportToEnd = ''" :rules="validationDependInfo.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="validationDependInfo.requiredField" label="Tipo de servicio del transporte"
                  v-model="form.transportServiceType" clearable :errorMessages="errorsBack.transportServiceType"
                  @input="errorsBack.transportServiceType = ''" :items="transportServiceTypeEnum_arrayInfo"
                  :rules="validationDependInfo.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="validationDependInfo.requiredField" label="Zona donde recoge víctima"
                  v-model="form.victimPickupZone" clearable :errorMessages="errorsBack.victimPickupZone"
                  @input="errorsBack.victimPickupZone = ''" :items="zoneEnum_arrayInfo"
                  :rules="validationDependInfo.rules" />
              </VCol>
            </VRow>
          </VForm>

          <!-- Section 9: Datos del médico -->
          <VCardTitle class="mt-4">XII. Datos del médico o profesional de la salud tratante</VCardTitle>
          <VForm :ref="el => formRefs[9] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView"
                  label="Tipo de documento de identidad del médico o profesional de la salud"
                  v-model="form.doctorIdType_id" url="/selectInfiniteTipoIdPisis" arrayInfo="tipoIdPisis"
                  :requiredField="true" :errorMessages="errorsBack.doctorIdType_id"
                  @input="errorsBack.doctorIdType_id = ''" :rules="[requiredValidator]" clearable
                  :itemsData="doctorIdType_arrayInfo" :firstFetch="false" :params="{
                    codigo_in: CODS_SELECT_FORM_FURIPS1_DOCTORIDTYPE,
                  }">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="doctorIdType_validation.requiredField"
                  label="Número de documento de identidad del médico o profesional de la salud"
                  v-model="form.doctorIdNumber" clearable :maxlength="16" counter
                  :errorMessages="errorsBack.doctorIdNumber" @input="errorsBack.doctorIdNumber = ''"
                  :rules="doctorIdType_validation.rules" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" label="Primer apellido del médico o profesional de la salud"
                  v-model="form.doctorFirstLastName" clearable :maxlength="20" counter
                  :errorMessages="errorsBack.doctorFirstLastName" @input="errorsBack.doctorFirstLastName = ''"
                  :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField label="Segundo apellido del médico o profesional de la salud"
                  v-model="form.doctorSecondLastName" clearable :maxlength="30" counter
                  :errorMessages="errorsBack.doctorSecondLastName" @input="errorsBack.doctorSecondLastName = ''" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" label="Primer nombre del médico o profesional de la salud"
                  v-model="form.doctorFirstName" clearable :maxlength="20" counter
                  :errorMessages="errorsBack.doctorFirstName" @input="errorsBack.doctorFirstName = ''"
                  :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField label="Segundo nombre del médico o profesional de la salud"
                  v-model="form.doctorSecondName" clearable :maxlength="30" counter
                  :errorMessages="errorsBack.doctorSecondName" @input="errorsBack.doctorSecondName = ''" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" label="Número de registro del médico"
                  v-model="form.doctorRegistrationNumber" clearable :maxlength="16" counter
                  :errorMessages="errorsBack.doctorRegistrationNumber" @input="errorsBack.doctorRegistrationNumber = ''"
                  :rules="[requiredValidator]" />
              </VCol>
            </VRow>
          </VForm>

          <!-- Section 10: Amparos que reclama -->
          <VCardTitle class="mt-4">XIII. Amparos que reclama</VCardTitle>
          <VForm :ref="el => formRefs[10] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppTextField type="number" label="Total facturado por amparo de gastos médicos quirúrgicos"
                  v-model="form.totalBilledMedicalSurgical" clearable :maxlength="15" counter
                  :errorMessages="errorsBack.totalBilledMedicalSurgical"
                  @input="errorsBack.totalBilledMedicalSurgical = ''" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField type="number" label="Total reclamado por amparo de gastos médicos quirúrgicos"
                  v-model="form.totalClaimedMedicalSurgical" clearable :maxlength="15" counter
                  :errorMessages="errorsBack.totalClaimedMedicalSurgical"
                  @input="errorsBack.totalClaimedMedicalSurgical = ''" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField type="number"
                  label="Total facturado por amparo de gastos de transporte y movilización de la víctima"
                  v-model="form.totalBilledTransport" clearable :maxlength="15" counter
                  :errorMessages="errorsBack.totalBilledTransport" @input="errorsBack.totalBilledTransport = ''" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField type="number"
                  label="Total reclamado por amparo de gastos de transporte y movilización de la víctima"
                  v-model="form.totalClaimedTransport" clearable :maxlength="15" counter
                  :errorMessages="errorsBack.totalClaimedTransport" @input="errorsBack.totalClaimedTransport = ''" />
              </VCol>
            </VRow>
          </VForm>

          <!-- Section 11: Confirmación servicios habilitados -->
          <VCardTitle class="mt-4">XIV. Confirmación servicios habilitados</VCardTitle>
          <VForm :ref="el => formRefs[11] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="true" label="Manifestación de servicios habilitados"
                  v-model="form.enabledServicesConfirmation" clearable
                  :errorMessages="errorsBack.enabledServicesConfirmation"
                  @input="errorsBack.enabledServicesConfirmation = ''" :items="yesNoEnum_arrayInfo"
                  :rules="[requiredValidator]" />
              </VCol>
            </VRow>
          </VForm>

        </div>

        <!-- Tab 3: Datos adicionales -->
        <div v-show="currentTab == 3">
          <!-- Certificación de la atención medica de la víctima como prueba del accidente o evento. -->
          <VCardTitle class="mt-4">XI. Certificación de la atención medica de la víctima como prueba del accidente o
            evento.</VCardTitle>
          <VForm :ref="el => formRefs[12] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppTextField type="date" :requiredField="true" label="Fecha de ingreso"
                  v-model="form.medicalAdmissionDate" clearable :errorMessages="errorsBack.medicalAdmissionDate"
                  @input="errorsBack.medicalAdmissionDate = ''" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppDateTimePicker :requiredField="true" label="Hora de ingreso" v-model="form.medicalAdmissionTime"
                  clearable :errorMessages="errorsBack.medicalAdmissionTime"
                  @input="errorsBack.medicalAdmissionTime = ''" :rules="[requiredValidator]" :config="{
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true
                  }" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField type="date" :requiredField="true" label="Fecha de egreso"
                  v-model="form.medicalDischargeDate" clearable :errorMessages="errorsBack.medicalDischargeDate"
                  @input="errorsBack.medicalDischargeDate = ''" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppDateTimePicker :requiredField="true" label="Hora de egreso" v-model="form.medicalDischargeTime"
                  clearable :errorMessages="errorsBack.medicalDischargeTime"
                  @input="errorsBack.medicalDischargeTime = ''" :rules="[requiredValidator]" :config="{
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true
                  }" />
              </VCol>

              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView" label="Código de diagnóstico principal de ingreso"
                  v-model="form.primaryAdmissionDiagnosisCode_id" url="/selectInfiniteCie10" arrayInfo="cie10"
                  :requiredField="true" :errorMessages="errorsBack.primaryAdmissionDiagnosisCode_id"
                  @input="errorsBack.primaryAdmissionDiagnosisCode_id = ''" :rules="[requiredValidator]" clearable
                  :itemsData="cie10_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView" label="Código de diagnóstico de ingreso asociado 1"
                  v-model="form.associatedAdmissionDiagnosisCode1_id" url="/selectInfiniteCie10" arrayInfo="cie10"
                  :errorMessages="errorsBack.associatedAdmissionDiagnosisCode1_id"
                  @input="errorsBack.associatedAdmissionDiagnosisCode1_id = ''" clearable :itemsData="cie10_arrayInfo"
                  :firstFetch="false">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView" label="Código de diagnóstico de ingreso asociado 2"
                  v-model="form.associatedAdmissionDiagnosisCode2_id" url="/selectInfiniteCie10" arrayInfo="cie10"
                  :errorMessages="errorsBack.associatedAdmissionDiagnosisCode2_id"
                  @input="errorsBack.associatedAdmissionDiagnosisCode2_id = ''" clearable :itemsData="cie10_arrayInfo"
                  :firstFetch="false">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView" label="Código diagnóstico principal de egreso"
                  v-model="form.primaryDischargeDiagnosisCode_id" url="/selectInfiniteCie10" arrayInfo="cie10"
                  :requiredField="true" :errorMessages="errorsBack.primaryDischargeDiagnosisCode_id"
                  @input="errorsBack.primaryDischargeDiagnosisCode_id = ''" :rules="[requiredValidator]" clearable
                  :itemsData="cie10_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView" label="Código de diagnóstico de egreso asociado 1"
                  v-model="form.associatedDischargeDiagnosisCode1_id" url="/selectInfiniteCie10" arrayInfo="cie10"
                  :errorMessages="errorsBack.associatedDischargeDiagnosisCode1_id"
                  @input="errorsBack.associatedDischargeDiagnosisCode1_id = ''" clearable :itemsData="cie10_arrayInfo"
                  :firstFetch="false">
                </AppSelectRemote>
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelectRemote :disabled="disabledFiledsView" label="Código de diagnóstico de egreso asociado 2"
                  v-model="form.associatedDischargeDiagnosisCode2_id" url="/selectInfiniteCie10" arrayInfo="cie10"
                  :errorMessages="errorsBack.associatedDischargeDiagnosisCode2_id"
                  @input="errorsBack.associatedDischargeDiagnosisCode2_id = ''" clearable :itemsData="cie10_arrayInfo"
                  :firstFetch="false">
                </AppSelectRemote>
              </VCol>

            </VRow>
          </VForm>
          <!-- Datos adicionales para PDF. -->
          <VCardTitle class="mt-4">XV. Datos adicionales para PDF.</VCardTitle>
          <VForm :ref="el => formRefs[13] = el" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="true" label="Intervención de autoridad" v-model="form.authorityIntervention"
                  clearable :items="yesNoEnum_arrayInfo" :errorMessages="errorsBack.authorityIntervention"
                  @input="errorsBack.authorityIntervention = ''" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppSelect :requiredField="true" label="Cobro Excedente Póliza" v-model="form.policyExcessCharge"
                  clearable :items="yesNoEnum_arrayInfo" :errorMessages="errorsBack.policyExcessCharge"
                  @input="errorsBack.policyExcessCharge = ''" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" label="Cargo del que recibe en remisión"
                  v-model="form.referralRecipientCharge" clearable :errorMessages="errorsBack.referralRecipientCharge"
                  @input="errorsBack.referralRecipientCharge = ''" :rules="[requiredValidator]" />
              </VCol>


            </VRow>
          </VForm>
        </div>

      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5">
        <BtnBack :disabled="isLoading" :loading="isLoading" />
        <VBtn v-if="!disabledFiledsView" :disabled="isLoading" :loading="isLoading" @click="submitForm()">
          Guardar
        </VBtn>
      </VCardText>
    </VCard>
  </div>
</template>
