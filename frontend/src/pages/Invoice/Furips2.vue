<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import IErrorsBack from '@/interfaces/Axios/IErrorsBack';
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { reactive, ref } from 'vue';
import type { VForm } from 'vuetify/components/VForm';

const { toast } = useToast()

definePage({
  path: 'invoice-Furips2/:action/:invoice_id/:id?',
  name: 'Invoice-Furips2',
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: 'invoice.furips2',
  },
});

interface IInvoiceData {
  id: string | null;
  furips1_consecutiveClaimNumber: string | null;
  serviceVendor_ipsable_codigo: string | null;
}
interface ISelect {
  title: string;
  value: string;
}
interface CodTecnologiaSaludablesSelect {
  label: string;
  url: string;
  arrayInfo: string;
  itemsData: any[];
}
interface IForm {
  id: null | string;
  invoice_id: null | string;
  consecutiveNumberClaim: string | null;
  serviceType: string | null;
  serviceCode_type: string | null;
  serviceCode_id: string | null;
  serviceDescription: string | null;
  serviceQuantity: string | null;
  serviceValue: string | null;
  totalFactoryValue: string | null;
  totalClaimedValue: string | null;
  victimResidenceAddress: string | null;
}

const form = ref<IForm>({
  id: null,
  invoice_id: null,
  consecutiveNumberClaim: null,
  serviceType: null,
  serviceCode_type: null,
  serviceCode_id: null,
  serviceDescription: null,
  serviceQuantity: null,
  serviceValue: null,
  totalFactoryValue: null,
  totalClaimedValue: null,
  victimResidenceAddress: null,
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
    const url = form.value.id ? `/furips2/update/${form.value.id}` : `/furips2/store`

    loading.form = true;
    const { data, response } = await useAxios(url).post(form.value);
    loading.form = false;

    if (response.status == 200 && data) {
      if (data.code == 200) {
        form.value.id = data.furips2.id
        router.replace({ name: "Invoice-Furips2", params: { action: 'edit', invoice_id: form.value.invoice_id, id: form.value.id } });
      }
    }
    if (data.code === 422) errorsBack.value = data.errors ?? {};

  } else {
    toast('Faltan Campos Por Diligenciar', '', 'danger')
  }
}

const invoice = ref<IInvoiceData>({
  id: null,
  furips1_consecutiveClaimNumber: null,
  serviceVendor_ipsable_codigo: null,
});

const serviceTypeEnum_arrayInfo = ref<ISelect[]>([])
const codTecnologiaSaludables = ref<ISelect[]>([])
const decreto780de2026_arrayInfo = ref<ISelect[]>([])

const fetchDataForm = async () => {

  form.value.invoice_id = route.params.invoice_id || null
  form.value.id = route.params.id || null

  const url = form.value.id ? `/furips2/${form.value.id}/edit` : `/furips2/create/${form.value.invoice_id}`

  loading.form = true
  const { response, data } = await useAxios(url).get();
  loading.form = false


  if (response.status == 200 && data) {

    serviceTypeEnum_arrayInfo.value = data.serviceTypeEnum_arrayInfo
    codTecnologiaSaludables.value = data.codTecnologiaSaludables
    decreto780de2026_arrayInfo.value = data.decreto780de2026_arrayInfo

    invoice.value = data.invoice

    //formulario 
    if (data.form) {
      form.value = cloneObject(data.form)


      if (form.value.serviceType == 'SERVICE_TYPE_001') {
        disabledServiceCodeType.value = false
      }
      if (form.value.serviceType == 'SERVICE_TYPE_002') {
        disabledServiceCodeType.value = false
      }
    }
  }
}

if (route.params.action == 'view') disabledFiledsView.value = true

onMounted(async () => {
  clearForm()
  await fetchDataForm()
})


//Validations
const serviceDescription_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["SERVICE_TYPE_003", "SERVICE_TYPE_004", "SERVICE_TYPE_005", "SERVICE_TYPE_006", "SERVICE_TYPE_007", "SERVICE_TYPE_008"].includes(form.value.serviceType ?? "")) {
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


const codTecnologiaSaludables_select = computed<CodTecnologiaSaludablesSelect>(() => {

  if (form.value.serviceCode_type) {
    return codTecnologiaSaludables.value.find(item => item.value === form.value.serviceCode_type) || {
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


const serviceCode_id_validation = computed(() => {
  const rules = [
    value => requiredValidator(value),
  ]

  if (invoice.value && ["SERVICE_TYPE_001", "SERVICE_TYPE_002"].includes(form.value.serviceType ?? "")) {
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

const clearserviceCode_type = () => {
  form.value.serviceCode_id = null
}

const disabledServiceCodeType = ref<boolean>(true)

const changeServiceType = (event: any) => {
  disabledServiceCodeType.value = true

  form.value.serviceCode_type = null
  form.value.serviceCode_id = null

  if (event == 'SERVICE_TYPE_001') {
    disabledServiceCodeType.value = false
    form.value.serviceCode_type = codTecnologiaSaludables_modify.value.at(0)?.value ?? null;

  }

  if (event == 'SERVICE_TYPE_002') {
    disabledServiceCodeType.value = false
    form.value.serviceCode_type = codTecnologiaSaludables_modify.value.at(0)?.value ?? null;

  }
}

const codTecnologiaSaludables_modify = computed(() => {
  if (form.value.serviceType) {
    if (form.value.serviceType == "SERVICE_TYPE_001") {
      // Omite el último elemento del array
      return codTecnologiaSaludables.value.slice(0, -1);
    } else if (form.value.serviceType == "SERVICE_TYPE_002") {
      // Omite los dos primeros elementos del array
      return codTecnologiaSaludables.value.slice(2);
    } else {
      // No omite ningún elemento
      return codTecnologiaSaludables.value;
    }
  } else {
    return []
  }
});

const totalFactoryValue = computed(() => {
  const serviceQuantity = Number(form.value.serviceQuantity)
  const serviceValue = Number(form.value.serviceValue)
  const totalFactoryValue = serviceQuantity * serviceValue

  form.value.totalFactoryValue = String(totalFactoryValue)

  return totalFactoryValue
});

const goView = (data: { action: string, invoice_id: string | null, id: string | null } = { action: "create", invoice_id: null, id: null }) => {
  disabledFiledsView.value = false;
  router.push({ name: "Invoice-Furips2", params: { action: data.action, invoice_id: data.invoice_id, id: data.id } })
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

    const api = `/furips2/downloadTxt/${form.value.id}`
    const nameFile = `${'FURIPS2' + serviceVendor_ipsable_codigo + formattedDate}`
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
        <span>Información del Furips2</span>
        <div>
          <VRow v-if="disabledFiledsView">
            <VCol>
              <VBtn :loading="loading.form" @click="downloadTXT">TXT
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
          <VRow>
            <VCol cols="12" sm="4">
              <AppTextField label="Número consecutivo de la reclamación" v-model="form.consecutiveNumberClaim" clearable
                :maxlength="20" counter :errorMessages="errorsBack.consecutiveNumberClaim"
                @input="errorsBack.consecutiveNumberClaim = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelect :requiredField="true" :rules="[requiredValidator]" label="Tipo de servicio"
                v-model="form.serviceType" clearable :errorMessages="errorsBack.serviceType"
                @input="errorsBack.serviceType = ''" :items="serviceTypeEnum_arrayInfo"
                @update:modelValue="changeServiceType" />
            </VCol>
            <VCol cols="12" sm="4">
              <VRadioGroup :disabled="disabledFiledsView || disabledServiceCodeType" v-model="form.serviceCode_type"
                inline>
                <VRadio v-for="(item, index) in codTecnologiaSaludables_modify" :key="index" :label="item.label"
                  :value="item.value" @click="clearserviceCode_type" />
              </VRadioGroup>

              <AppSelectRemote clearable label="Código del servicio" v-model="form.serviceCode_id"
                :requiredField="serviceCode_id_validation.requiredField" :rules="serviceCode_id_validation.rules"
                :error-messages="errorsBack.serviceCode_id" @input="errorsBack.serviceCode_id = ''"
                :disabled="disabledFiledsView || disabledServiceCodeType" :url="codTecnologiaSaludables_select.url"
                :array-info="codTecnologiaSaludables_select.arrayInfo"
                :itemsData="codTecnologiaSaludables_select.itemsData" :firstFetch="false" />

            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="serviceDescription_validation.requiredField"
                :rules="serviceDescription_validation.rules" label="Descripción del servicio o elemento reclamado"
                v-model="form.serviceDescription" clearable :maxlength="100" counter
                :errorMessages="errorsBack.serviceDescription" @input="errorsBack.serviceDescription = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField type="number" :requiredField="true" :rules="[requiredValidator, greaterThanZeroValidator]"
                label="Cantidád de servicios" v-model="form.serviceQuantity" clearable :maxlength="15" counter
                :errorMessages="errorsBack.serviceQuantity" @input="errorsBack.serviceQuantity = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator, greaterThanZeroValidator]"
                label="Valor unitario" v-model="form.serviceValue" clearable :maxlength="15" counter
                :errorMessages="errorsBack.serviceValue" @input="errorsBack.serviceValue = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator, greaterThanZeroValidator]"
                label="Valor total facturado" v-model="totalFactoryValue" clearable :maxlength="15" counter
                :errorMessages="errorsBack.totalFactoryValue" @input="errorsBack.totalFactoryValue = ''" disabled />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator, greaterThanZeroValidator]"
                label="Valor total reclamado" v-model="form.totalClaimedValue" clearable :maxlength="15" counter
                :errorMessages="errorsBack.totalClaimedValue" @input="errorsBack.totalClaimedValue = ''" />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]"
                label="Dirección de residencia de la víctima" v-model="form.victimResidenceAddress" clearable
                :maxlength="40" counter :errorMessages="errorsBack.victimResidenceAddress"
                @input="errorsBack.victimResidenceAddress = ''" />
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
