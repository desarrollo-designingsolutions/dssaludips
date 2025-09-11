<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import IErrorsBack from '@/interfaces/Axios/IErrorsBack';
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { reactive, ref } from 'vue';
import type { VForm } from 'vuetify/components/VForm';

const { toast } = useToast()

definePage({
  path: 'invoice-Furtran/:action/:invoice_id/:id?',
  name: 'Invoice-Furtran',
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: 'invoice.furtran',
  },
});

interface IInvoiceData {
  id: string | null;
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
  previousRecordNumber: string | null;
  rgResponse: string | null;
  firstLastNameClaimant: string | null;
  secondLastNameClaimant: string | null;
  firstNameClaimant: string | null;
  secondNameClaimant: string | null;
  claimantIdType_id: string | null;
  claimantIdNumber: string | null;
  vehicleServiceType: string | null;
  vehiclePlate: string | null;
  claimantAddress: string | null;
  claimantPhone: string | null;
  claimantDepartmentCode_id: string | null;
  claimantMunicipalityCode_id: string | null;
  victimGender: string | null;
  eventType: string | null;
  pickupAddress: string | null;
  pickupDepartmentCode_id: string | null;
  pickupMunicipalityCode_id: string | null;
  pickupZone: string | null;
  transferDate: string | null;
  transferTime: string | null;
  transferPickupDepartmentCode_id: string | null;
  transferPickupMunicipalityCode_id: string | null;
  victimCondition: string | null;
  involvedVehicleType: string | null;
  involvedVehiclePlate: string | null;
  insurerCode: string | null;
  sirasRecordNumber: string | null;
  billedValue: string | null;
  claimedValue: string | null;
  serviceEnabledIndication: string | null;
  ipsName: string | null;
  ipsNit: string | null;
  ipsAddress: string | null;
  ipsPhone: string | null;
  ipsReceptionHabilitationCode_id: string | null;
}

const form = ref<IForm>({
  id: null,
  invoice_id: null,
  previousRecordNumber: null,
  rgResponse: null,
  firstLastNameClaimant: null,
  secondLastNameClaimant: null,
  firstNameClaimant: null,
  secondNameClaimant: null,
  claimantIdType_id: null,
  claimantIdNumber: null,
  vehicleServiceType: null,
  vehiclePlate: null,
  claimantAddress: null,
  claimantPhone: null,
  claimantDepartmentCode_id: null,
  claimantMunicipalityCode_id: null,
  victimGender: null,
  eventType: null,
  pickupAddress: null,
  pickupDepartmentCode_id: null,
  pickupMunicipalityCode_id: null,
  pickupZone: null,
  transferDate: null,
  transferTime: null,
  transferPickupDepartmentCode_id: null,
  transferPickupMunicipalityCode_id: null,
  victimCondition: null,
  involvedVehicleType: null,
  involvedVehiclePlate: null,
  insurerCode: null,
  sirasRecordNumber: null,
  billedValue: null,
  claimedValue: null,
  serviceEnabledIndication: null,
  ipsName: null,
  ipsNit: null,
  ipsAddress: null,
  ipsPhone: null,
  ipsReceptionHabilitationCode_id: null,
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
const formValidation = ref<VForm>()

// Computed que verifica si al menos uno de los valores es true
const isLoading = computed(() => {
  return Object.values(loading).some(value => value);
});

const submitForm = async () => {
  const validation = await formValidation.value?.validate()

  if (validation?.valid) {
    const url = form.value.id ? `/furtran/update/${form.value.id}` : `/furtran/store`

    loading.form = true;
    const { data, response } = await useAxios(url).post(form.value);
    loading.form = false;

    if (response.status == 200 && data) {
      if (data.code == 200) {
        form.value.id = data.furtran.id
        router.replace({ name: "Invoice-Furtran", params: { action: 'edit', invoice_id: form.value.invoice_id, id: form.value.id } });
      }
    }
    if (data.code === 422) errorsBack.value = data.errors ?? {};

  } else {
    toast('Faltan Campos Por Diligenciar', '', 'danger')
  }
}

const invoice = ref<IInvoiceData>({
  id: null,
  insurance_statuse_code: null,
  serviceVendor_ipsable_codigo: null,
})

const rgResponseEnum_arrayInfo = ref<ISelect[]>([])
const tipoIdPisis_arrayInfo = ref<ISelect[]>([])
const vehicleServiceTypeEnum_arrayInfo = ref<ISelect[]>([])
const municipios_arrayInfo = ref<ISelect[]>([])
const departamentos_arrayInfo = ref<ISelect[]>([])
const genderEnum_arrayInfo = ref<ISelect[]>([])
const eventTypeEnum_arrayInfo = ref<ISelect[]>([])
const zoneEnum_arrayInfo = ref<ISelect[]>([])
const victimConditionEnum_arrayInfo = ref<ISelect[]>([])
const vehicleTypeEnum_arrayInfo = ref<ISelect[]>([])
const yesNoEnum_arrayInfo = ref<ISelect[]>([])
const ipsCodHabilitacion_arrayInfo = ref<ISelect[]>([])

const fetchDataForm = async () => {
  form.value.invoice_id = route.params.invoice_id || null
  form.value.id = route.params.id || null

  const url = form.value.id ? `/furtran/${form.value.id}/edit` : `/furtran/create/${form.value.invoice_id}`

  loading.form = true
  const { response, data } = await useAxios(url).get();
  loading.form = false

  if (response.status == 200 && data) {

    rgResponseEnum_arrayInfo.value = data.rgResponseEnum_arrayInfo
    tipoIdPisis_arrayInfo.value = data.tipoIdPisis_arrayInfo
    vehicleServiceTypeEnum_arrayInfo.value = data.vehicleServiceTypeEnum_arrayInfo
    municipios_arrayInfo.value = data.municipios_arrayInfo
    departamentos_arrayInfo.value = data.departamentos_arrayInfo
    genderEnum_arrayInfo.value = data.genderEnum_arrayInfo
    eventTypeEnum_arrayInfo.value = data.eventTypeEnum_arrayInfo
    zoneEnum_arrayInfo.value = data.zoneEnum_arrayInfo
    victimConditionEnum_arrayInfo.value = data.victimConditionEnum_arrayInfo
    vehicleTypeEnum_arrayInfo.value = data.vehicleTypeEnum_arrayInfo
    yesNoEnum_arrayInfo.value = data.yesNoEnum_arrayInfo
    ipsCodHabilitacion_arrayInfo.value = data.ipsCodHabilitacion_arrayInfo

    invoice.value = data.invoice

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




const dynamicDocumentLengthRule = computed(() => (value: string) => {
  const tipoId = form.value.claimantIdType_id?.codigo;

  if (!tipoId || !value) return true;
  const maxLength = documentLengthByType[tipoId] || 20;
  return (
    value.length <= maxLength ||
    `El documento ${tipoId} debe tener máximo ${maxLength} caracteres`
  );
});

const claimantIdNumber_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
    dynamicDocumentLengthRule.value,
  ]
  return {
    rules: rules,
    requiredField: true
  }
})

const victimCondition_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["EVENT_TYPE_001"].includes(form.value.eventType ?? "")) {
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

const vehicleServiceType_validation = computed(() => {
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

const involvedVehiclePlate_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["1", "2", "4", "5", "6"].includes(invoice.value.insurance_statuse_code ?? "")) {
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

const insurerCode_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["1", "4", "6"].includes(invoice.value.insurance_statuse_code ?? "")) {
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

const downloadPDF = async () => {

  loading.form = true;
  const { response, data } = await useAxios(`/furtran/${form.value.invoice_id}/pdf`).get();

  if (response.status == 200 && data) {
    openPdfBase64(data.path);
  }

  loading.form = false;
}

const goView = (data: { action: string, invoice_id: string | null, id: string | null } = { action: "create", invoice_id: null, id: null }) => {
  disabledFiledsView.value = false;
  router.push({ name: "Invoice-Furtran", params: { action: data.action, invoice_id: data.invoice_id, id: data.id } })
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

    const api = `/furtran/downloadTxt/${form.value.id}`
    const nameFile = `${'FURTRAN' + serviceVendor_ipsable_codigo + formattedDate}`
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
        <span>Información del Furtran</span>
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
        <VForm ref="formValidation" @submit.prevent="() => { }" :disabled="disabledFiledsView">
          <!-- I. Datos del transportador reclamante -->
          <VCardTitle>I. Datos del transportador reclamante</VCardTitle>
          <VRow>
            <VCol cols="12" sm="4">
              <AppTextField label="Número del radicado anterior" v-model="form.previousRecordNumber" clearable
                :maxlength="10" counter :errorMessages="errorsBack.previousRecordNumber"
                @input="errorsBack.previousRecordNumber = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelect label="RG Respuesta a Glosa u objeción" v-model="form.rgResponse" clearable
                :errorMessages="errorsBack.rgResponse" @input="errorsBack.rgResponse = ''"
                :items="rgResponseEnum_arrayInfo" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]"
                label="Primer apellido de la persona natural reclamante o conductor de la ambulancia"
                v-model="form.firstLastNameClaimant" clearable :maxlength="20" counter
                :errorMessages="errorsBack.firstLastNameClaimant" @input="errorsBack.firstLastNameClaimant = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField label="Segundo apellido de la persona natural reclamante o conductor de la ambulancia"
                v-model="form.secondLastNameClaimant" clearable :maxlength="30" counter
                :errorMessages="errorsBack.secondLastNameClaimant" @input="errorsBack.secondLastNameClaimant = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]"
                label="Primer nombre de la persona natural reclamante o conductor de la ambulancia"
                v-model="form.firstNameClaimant" clearable :maxlength="20" counter
                :errorMessages="errorsBack.firstNameClaimant" @input="errorsBack.firstNameClaimant = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField label="Segundo nombre de la persona natural reclamante o conductor de la ambulancia"
                v-model="form.secondNameClaimant" clearable :maxlength="30" counter
                :errorMessages="errorsBack.secondNameClaimant" @input="errorsBack.secondNameClaimant = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelectRemote :disabled="disabledFiledsView"
                label="Tipo de documento de identificación del reclamante o conductor de la ambulancia"
                v-model="form.claimantIdType_id" url="/selectInfiniteTipoIdPisis" arrayInfo="tipoIdPisis"
                :requiredField="true" :errorMessages="errorsBack.claimantIdType_id"
                @input="errorsBack.claimantIdType_id = ''" :rules="[requiredValidator]" clearable
                :itemsData="tipoIdPisis_arrayInfo" :firstFetch="false" :params="{
                  codigo_in: CODS_SELECT_FORM_FURTRAN_CLAIMANIDTYPE,
                }">
              </AppSelectRemote>
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="claimantIdNumber_validation.requiredField"
                :rules="claimantIdNumber_validation.rules"
                label="Número de documento de identidad del reclamante o conductor de la ambulancia"
                v-model="form.claimantIdNumber" clearable :maxlength="16" counter
                :errorMessages="errorsBack.claimantIdNumber" @input="errorsBack.claimantIdNumber = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelect :requiredField="true" :rules="[requiredValidator]"
                label="Tipo de Vehículo o de servicio de ambulancia" v-model="form.vehicleServiceType" clearable
                :errorMessages="errorsBack.vehicleServiceType" @input="errorsBack.vehicleServiceType = ''"
                :items="vehicleServiceTypeEnum_arrayInfo" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]"
                label="Placa del vehículo en el que se realizó el traslado" v-model="form.vehiclePlate" clearable
                :maxlength="10" counter :errorMessages="errorsBack.vehiclePlate"
                @input="errorsBack.vehiclePlate = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]" label="Dirección del reclamante"
                v-model="form.claimantAddress" clearable :maxlength="40" counter
                :errorMessages="errorsBack.claimantAddress" @input="errorsBack.claimantAddress = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]" label="Teléfono del reclamante"
                v-model="form.claimantPhone" clearable :maxlength="10" counter :errorMessages="errorsBack.claimantPhone"
                @input="errorsBack.claimantPhone = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                label="Código del departamento de residencia del reclamante" v-model="form.claimantDepartmentCode_id"
                :errorMessages="errorsBack.claimantDepartmentCode_id" @input="errorsBack.claimantDepartmentCode_id = ''"
                url="/selectInfiniteDepartamento" arrayInfo="departamentos" clearable
                :itemsData="departamentos_arrayInfo" :firstFetch="false" :rules="[requiredValidator]">
              </AppSelectRemote>
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                label="Código del municipio de residencia del reclamante" v-model="form.claimantMunicipalityCode_id"
                :errorMessages="errorsBack.claimantMunicipalityCode_id"
                @input="errorsBack.claimantMunicipalityCode_id = ''" url="/selectInfiniteMunicipio"
                arrayInfo="municipios" clearable :itemsData="municipios_arrayInfo" :firstFetch="false"
                :rules="[requiredValidator]">
              </AppSelectRemote>
            </VCol>

          </VRow>

          <!-- II. Relación de víctima trasladada -->
          <VCardTitle>II. Relación de víctima trasladada</VCardTitle>
          <VRow>
            <VCol cols="12" sm="4">
              <AppSelect :requiredField="true" :rules="[requiredValidator]" label="Sexo de la víctima"
                v-model="form.victimGender" clearable :errorMessages="errorsBack.victimGender"
                @input="errorsBack.victimGender = ''" :items="genderEnum_arrayInfo" />
            </VCol>
          </VRow>

          <!-- III. Identificación del tipo de evento -->
          <VCardTitle>III. Identificación del tipo de evento</VCardTitle>
          <VRow>
            <VCol cols="12" sm="4">
              <AppSelect :requiredField="true" :rules="[requiredValidator]"
                label="Tipo de evento que suscita la movilización" v-model="form.eventType" clearable
                :errorMessages="errorsBack.eventType" @input="errorsBack.eventType = ''"
                :items="eventTypeEnum_arrayInfo" />
            </VCol>
          </VRow>

          <!-- IV. Lugar donde se recoge a las víctimas -->
          <VCardTitle>IV. Lugar donde se recoge a las víctimas</VCardTitle>
          <VRow>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]" label="Dirección donde recoge la víctima"
                v-model="form.pickupAddress" clearable :maxlength="40" counter :errorMessages="errorsBack.pickupAddress"
                @input="errorsBack.pickupAddress = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                label="Código departamento donde se recoge la víctima" v-model="form.pickupDepartmentCode_id"
                :errorMessages="errorsBack.pickupDepartmentCode_id" @input="errorsBack.pickupDepartmentCode_id = ''"
                url="/selectInfiniteDepartamento" arrayInfo="departamentos" clearable
                :itemsData="departamentos_arrayInfo" :firstFetch="false" :rules="[requiredValidator]">
              </AppSelectRemote>
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                label="Código municipio donde se recoge la víctima" v-model="form.pickupMunicipalityCode_id"
                :errorMessages="errorsBack.pickupMunicipalityCode_id" @input="errorsBack.pickupMunicipalityCode_id = ''"
                url="/selectInfiniteMunicipio" arrayInfo="municipios" clearable :itemsData="municipios_arrayInfo"
                :firstFetch="false" :rules="[requiredValidator]">
              </AppSelectRemote>
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelect :requiredField="true" :rules="[requiredValidator]" label="Zona donde se recoge la víctima"
                v-model="form.pickupZone" clearable :errorMessages="errorsBack.pickupZone"
                @input="errorsBack.pickupZone = ''" :items="zoneEnum_arrayInfo" />
            </VCol>
          </VRow>

          <!-- V. Certificación de traslado de víctimas -->
          <VCardTitle>V. Certificación de traslado de víctimas</VCardTitle>
          <VRow>
            <VCol cols="12" sm="4">
              <AppTextField type="date" :requiredField="true" :rules="[requiredValidator]"
                label="Fecha de traslado de la víctima al primer centro asistencial" v-model="form.transferDate" counter
                :errorMessages="errorsBack.transferDate" @input="errorsBack.transferDate = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppDateTimePicker :requiredField="true" :rules="[requiredValidator]"
                label="Hora de traslado al centro asistencial" v-model="form.transferTime" clearable counter
                :errorMessages="errorsBack.transferTime" @input="errorsBack.transferTime = ''" :config="{
                  enableTime: true,
                  noCalendar: true,
                  dateFormat: 'H:i',
                  time_24hr: true
                }" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                label="Código del departamento donde se recoge la víctima"
                v-model="form.transferPickupDepartmentCode_id"
                :errorMessages="errorsBack.transferPickupDepartmentCode_id"
                @input="errorsBack.transferPickupDepartmentCode_id = ''" url="/selectInfiniteDepartamento"
                arrayInfo="departamentos" clearable :itemsData="departamentos_arrayInfo" :firstFetch="false"
                :rules="[requiredValidator]">
              </AppSelectRemote>
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                label="Código del municipio donde se recoge la víctima" v-model="form.transferPickupMunicipalityCode_id"
                :errorMessages="errorsBack.transferPickupMunicipalityCode_id"
                @input="errorsBack.transferPickupMunicipalityCode_id = ''" url="/selectInfiniteMunicipio"
                arrayInfo="municipios" clearable :itemsData="municipios_arrayInfo" :firstFetch="false"
                :rules="[requiredValidator]">
              </AppSelectRemote>
            </VCol>







            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]"
                label="Nombre IPS que atendió a la víctima" v-model="form.ipsName" counter
                :errorMessages="errorsBack.ipsName" @input="errorsBack.ipsName = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]"
                label="NIT de IPS que atendió a la víctima" v-model="form.ipsNit" counter
                :errorMessages="errorsBack.ipsNit" @input="errorsBack.ipsNit = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]"
                label="Dirección de IPS que atendió a la víctima" v-model="form.ipsAddress" counter
                :errorMessages="errorsBack.ipsAddress" @input="errorsBack.ipsAddress = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]"
                label="Teléfono de IPS que atendió a la víctima" v-model="form.ipsPhone" counter
                :errorMessages="errorsBack.ipsPhone" @input="errorsBack.ipsPhone = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelectRemote :disabled="disabledFiledsView" :requiredField="true"
                label="Código de habilitación de la IPS que recibió a la víctima"
                v-model="form.ipsReceptionHabilitationCode_id"
                :errorMessages="errorsBack.ipsReceptionHabilitationCode_id"
                @input="errorsBack.ipsReceptionHabilitationCode_id = ''" url="/selectInfiniteIpsCodHabilitacion"
                arrayInfo="ipsCodHabilitacion" clearable :itemsData="ipsCodHabilitacion_arrayInfo" :firstFetch="false"
                :rules="[requiredValidator]" />
            </VCol>


          </VRow>

          <!-- VI. Datos obligatorios si el evento es un accidente de tránsito -->
          <VCardTitle>VI. Datos obligatorios si el evento es un accidente de tránsito</VCardTitle>
          <VRow>
            <VCol cols="12" sm="4">
              <AppSelect :requiredField="victimCondition_validation.requiredField"
                :rules="victimCondition_validation.rules" label="Condición de la víctima" v-model="form.victimCondition"
                clearable :errorMessages="errorsBack.victimCondition" @input="errorsBack.victimCondition = ''"
                :items="victimConditionEnum_arrayInfo" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelect :requiredField="vehicleServiceType_validation.requiredField"
                :rules="vehicleServiceType_validation.rules" label="Tipo de Vehículo" v-model="form.involvedVehicleType"
                clearable :errorMessages="errorsBack.involvedVehicleType" @input="errorsBack.involvedVehicleType = ''"
                :items="vehicleTypeEnum_arrayInfo" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="involvedVehiclePlate_validation.requiredField"
                :rules="involvedVehiclePlate_validation.rules" label="Placa del Vehículo involucrado"
                v-model="form.involvedVehiclePlate" clearable :maxlength="10" counter
                :errorMessages="errorsBack.involvedVehiclePlate" @input="errorsBack.involvedVehiclePlate = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="insurerCode_validation.requiredField" :rules="insurerCode_validation.rules"
                label="Código de la aseguradora" v-model="form.insurerCode" clearable :maxlength="6" counter
                :errorMessages="errorsBack.insurerCode" @input="errorsBack.insurerCode = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]" label="Número de radicado SIRAS"
                v-model="form.sirasRecordNumber" clearable :maxlength="20" counter
                :errorMessages="errorsBack.sirasRecordNumber" @input="errorsBack.sirasRecordNumber = ''" />
            </VCol>
          </VRow>

          <!-- VII. Amparo reclamado -->
          <VCardTitle>VII. Amparo reclamado</VCardTitle>
          <VRow>
            <VCol cols="12" sm="4">
              <AppTextField type="number" :requiredField="true" :rules="[requiredValidator, greaterThanZeroValidator]"
                label="Valor facturado" v-model="form.billedValue" clearable :maxlength="15" counter
                :errorMessages="errorsBack.billedValue" @input="errorsBack.billedValue = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField type="number" :requiredField="true" :rules="[requiredValidator, greaterThanZeroValidator]"
                label="Valor reclamado" v-model="form.claimedValue" clearable :maxlength="15" counter
                :errorMessages="errorsBack.claimedValue" @input="errorsBack.claimedValue = ''" />
            </VCol>
          </VRow>

          <!-- VIII. Manifestación del servicio habilitado del prestador de servicios de salud -->
          <VCardTitle>VIII. Manifestación del servicio habilitado del prestador de servicios de salud</VCardTitle>
          <VRow>
            <VCol cols="12" sm="4">
              <AppSelect :requiredField="true" :rules="[requiredValidator]"
                label="Manifestación de servicios habilitados" v-model="form.serviceEnabledIndication" clearable
                :errorMessages="errorsBack.serviceEnabledIndication" @input="errorsBack.serviceEnabledIndication = ''"
                :items="yesNoEnum_arrayInfo" />
            </VCol>
          </VRow>
        </VForm>
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
