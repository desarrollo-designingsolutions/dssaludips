<script lang="ts" setup>
import { useToast } from '@/composables/useToast';
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import ModalListInvoicePayment from "@/pages/InvoicePayment/Components/ModalList.vue";
import ListService from "@/pages/Service/Components/List.vue";
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from 'vuetify/components/VForm';

const authenticationStore = useAuthenticationStore();

definePage({
  path: "invoice-Form/:action/:id?",
  name: "Invoice-Form",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "menu.invoice",
  },
});

const { toast } = useToast()
const errorsBack = ref<IErrorsBack>({});
const disabledFiledsView = ref<boolean>(false);
const route = useRoute()
const formValidation = ref<VForm>()
const formValidationSoat = ref<VForm>()
const loading = reactive({
  form: false,
})
const serviceVendors_arrayInfo = ref([])
const entities_arrayInfo = ref([])
const tipoNotas_arrayInfo = ref([])
const patients_arrayInfo = ref([])
const statusInvoiceEnum_arrayInfo = ref([])
const insuranceStatus_arrayInfo = ref([])

const form = ref({
  id: null as string | null,
  company_id: null as string | null,
  service_vendor_id: null as string | null,
  entity_id: null as string | null,
  patient_id: null as string | null,
  tipo_nota_id: null as string | null,
  invoice_number: null as string | null,
  note_number: null as string | null,
  radication_number: null as string | null,
  value_glosa: null as string | null,
  type: null as string | null,
  value_paid: null as string | null,
  remaining_balance: null as string | null,
  total: null as string | null,
  invoice_date: null as string | null,
  radication_date: null as string | null,
  status: null as string | null,
})

const soat = ref({
  id: null as string | null,
  policy_number: null as string | null,
  accident_date: null as string | null,
  start_date: null as string | null,
  end_date: null as string | null,
  insurance_statuse_id: null as string | null,
})

const totalValueGlosa = ref<string>('')
const totalValuePaid = ref<string>('')
const totalValueTotal = ref<string>('')
const totalValueRemainingBalance = ref<string>('')
const typeInvoiceEnumValues = ref()

const clearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}

const selectPatientKey = ref(0);

const service_date = ref();

const fetchDataForm = async () => {

  form.value.id = route.params.id || null

  const url = form.value.id ? `/invoice/${form.value.id}/edit` : `/invoice/create`

  loading.form = true
  const { response, data } = await useAxios(url).get({
    params: {
      company_id: authenticationStore.company.id,
    }
  });
  loading.form = false


  if (response.status == 200 && data) {

    serviceVendors_arrayInfo.value = data.serviceVendors_arrayInfo
    entities_arrayInfo.value = data.entities_arrayInfo
    tipoNotas_arrayInfo.value = data.tipoNotas_arrayInfo
    patients_arrayInfo.value = data.patients_arrayInfo
    statusInvoiceEnum_arrayInfo.value = data.statusInvoiceEnum_arrayInfo
    typeInvoiceEnumValues.value = data.typeInvoiceEnumValues
    insuranceStatus_arrayInfo.value = data.insuranceStatus_arrayInfo
    form.value.type = 'INVOICE_TYPE_001'

    //formulario 
    if (data.form) {
      form.value = cloneObject(data.form)

      service_date.value = data.service_date;

      if (form.value.patient_id) {
        // Forzar la recarga del componente del paciente
        selectPatientKey.value += 1;
      }

      if (form.value.type == 'INVOICE_TYPE_002') {
        soat.value = cloneObject(data?.infoDataExtra)
      }

      totalValuePaid.value = currencyFormat(formatToCurrencyFormat(form.value.value_paid));
      totalValueTotal.value = currencyFormat(formatToCurrencyFormat(form.value.total));
      totalValueRemainingBalance.value = currencyFormat(formatToCurrencyFormat(form.value.remaining_balance));

      totalValueGlosa.value = currencyFormat(formatToCurrencyFormat(form.value.value_glosa));
      dataCalculate.real_value_glosa = cloneObject(totalValueGlosa.value);

      listenForInvoiceUpdates();

    }
  }
}

const submitForm = async () => {
  const validation = await formValidation.value?.validate()

  if (!isObject(form.value.patient_id)) {
    errorsBack.value.patient_id = 'El campo es obligatorio'
    return false
  }

  if (form.value.type == 'INVOICE_TYPE_002' && validationsTypeForm.INVOICE_TYPE_002 == true) {
    toast('Faltan Campos Por Diligenciar En El Formulario De Soat', '', 'danger')
    return false;
  }

  if (validation?.valid) {

    const url = form.value.id ? `/invoice/update/${form.value.id}` : `/invoice/store`

    form.value.company_id = authenticationStore.company.id;

    let document: any = [];

    switch (form.value.type) {
      case 'INVOICE_TYPE_002':
        document = { soat: soat.value };
        break;

      default:
        break;
    }

    loading.form = true;
    const { data, response } = await useAxios(url).post({
      ...form.value,
      value_glosa: dataCalculate.real_value_glosa,
      ...document,
    });

    if (response.status == 200 && data) {

      form.value.id = data.form.id

      soat.value = {
        id: null,
        policy_number: null,
        accident_date: null,
        start_date: null,
        end_date: null,
        insurance_statuse_id: null,
      }

      if (form.value.type == 'INVOICE_TYPE_002') {
        soat.value = data.infoDataExtra
      }

      router.replace({ name: "Invoice-Form", params: { id: data.form.id } });

      listenForInvoiceUpdates()
    }
    if (data.code === 422) errorsBack.value = data.errors ?? {};

    loading.form = false;
  }
  else {
    toast('Faltan Campos Por Diligenciar', '', 'danger')
  }
}

if (route.params.action == 'view') disabledFiledsView.value = true

onMounted(async () => {
  clearForm()

  await fetchDataForm()
})

// Computed que verifica si al menos uno de los valores es true
const isLoading = computed(() => {
  return Object.values(loading).some(value => value);
});

const paramsSelectInfinite = {
  company_id: authenticationStore.company.id,
}

//FORMATO COMPONENTE MONEDA
const dataCalculate = reactive({
  real_value_glosa: 0 as number,
})

const dataReal = (data: any, field: string) => {
  dataCalculate[field] = data
}


const showUploadModal = ref(false)

const openUploadModal = () => {
  showUploadModal.value = true
}

//ModalShowFiles
const refModalShowFiles = ref()

const openModalShowFiles = () => {
  refModalShowFiles.value.openModal(form.value.id, 'Invoice')
}

//ModalListInvoicePayment
const refModalListInvoicePayment = ref()

const openModalListInvoicePayment = () => {
  refModalListInvoicePayment.value.openModal({ invoice_id: form.value.id })
}

const checkInvoiceNumber = async () => {

  if (form.value.invoice_number && form.value.service_vendor_id && form.value.entity_id) {
    const url = '/invoice/validateInvoiceNumber'

    const { response, data } = await useAxios(url).post({
      id: form.value.id,
      invoice_number: form.value.invoice_number,
      service_vendor_id: form.value.service_vendor_id?.value,
      entity_id: form.value.entity_id?.value,
      company_id: authenticationStore.company.id,
    });

    if (response.status == 200 && data) {
      if (data.exists) {
        errorsBack.value.invoice_number = data.message_invoice;
      }
    } else {
      errorsBack.value.invoice_number = "";
    }
  }
};

const listenForInvoiceUpdates = () => {
  if (form.value.id) {

    window.Echo
      .channel(`invoice.${form.value.id}`)
      .listen('InvoiceRowUpdatedNow', (event: any) => {

        totalValueGlosa.value = currencyFormat(formatToCurrencyFormat(event.value_glosa));
        totalValueTotal.value = currencyFormat(formatToCurrencyFormat(event.total));
        totalValuePaid.value = currencyFormat(formatToCurrencyFormat(event.value_paid));
        totalValueRemainingBalance.value = currencyFormat(formatToCurrencyFormat(event.remaining_balance));
      });
  }
};

const stopListening = () => {
  if (form.value.id) {
    window.Echo.leave(`invoice.${form.value.id}`);
  }
};

onUnmounted(() => {
  stopListening();
});

// Validations
const note_numberRules = [
  value => lengthBetweenValidator(value, 0, 20),
  value => {
    if (form.value.tipo_nota_id) {
      return requiredValidator(value);
    }
    return true;
  }
];
const tipoNotaRules = [
  value => {
    if (form.value.note_number) {
      return requiredValidator(value);
    }
    return true;
  }
];
const radication_dateRules = [
  value => {
    if (form.value.radication_number) {
      return requiredValidator(value);
    }
    return true;
  },
  value => {
    if (form.value.status && form.value.status !== 'INVOICE_STATUS_008' && form.value.status !== 'INVOICE_STATUS_001') {
      if (!form.value.radication_number) {
        return 'El número de radicación es obligatorio ';
      }
    }

    return true;
  }
];
const radication_dateRequiredField = computed(() => {
  if (form.value.status && form.value.status !== 'INVOICE_STATUS_008' && form.value.status !== 'INVOICE_STATUS_001') {
    return true;
  }
  if (!form.value.radication_number) {
    return false;
  }

  return true;
});

const radication_numberRules = [
  value => {
    if (form.value.radication_date) {
      return requiredValidator(value);
    }
    return true;
  },
  value => {
    if (form.value.status !== 'INVOICE_STATUS_008' && form.value.status !== 'INVOICE_STATUS_001') {
      if (!form.value.radication_number) {
        return 'El número de radicación es obligatorio ';
      }
    }

    return true;
  }
];

const radication_numberRequiredField = computed(() => {
  if (form.value.status !== 'INVOICE_STATUS_008' && form.value.status !== 'INVOICE_STATUS_001') {
    return true;
  }
  if (!form.value.radication_date) {
    return false;
  }

  return true;
});


const invoice_numberRules = [
  value => {
    if (!form.value.tipo_nota_id && !form.value.note_number) {
      return requiredValidator(value);
    }
    return true;
  }
];

const isDialogVisible = reactive({ INVOICE_TYPE_002: false })
const validationsTypeForm = reactive({ INVOICE_TYPE_002: false })

const openModalFormType = (type: any) => {
  isDialogVisible[type] = !isDialogVisible[type];
}

const acceptFormSoat = async () => {
  const validation = await formValidationSoat.value?.validate()

  if (validation?.valid) {
    validationsTypeForm.INVOICE_TYPE_002 = false

    openModalFormType('INVOICE_TYPE_002');
  }
  else {
    validationsTypeForm.INVOICE_TYPE_002 = true
    toast('Faltan Campos Por Diligenciar', '', 'danger')
  }
}

const titleTypeInvoice = computed(() => {
  return typeInvoiceEnumValues.value?.find((item: any) => item.type === form.value.type)?.title || '';
});


</script>


<template>
  <div>
    <VCard :disabled="loading.form" :loading="loading.form">
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Formulario Factura
        </span>
        <span class="pa-2 px-4 font-weight " style=" border-radius: 6px;">
          {{ titleTypeInvoice }}
        </span>
      </VCardTitle>
      <VCardText>
        <VForm ref="formValidation" @submit.prevent="() => { }" :disabled="disabledFiledsView">

          <VRow>
            <VCol cols="12">
              <AppCardActions title="Información de la Factura" action-collapsed>
                <VCardText>
                  <VRow>
                    <VCol cols="12" sm="4">
                      <AppSelectRemote :disabled="disabledFiledsView" label="Prestador" v-model="form.service_vendor_id"
                        url="/selectInfiniteServiceVendor" arrayInfo="serviceVendors" :requiredField="true"
                        :rules="[requiredValidator]" clearable :params="paramsSelectInfinite"
                        :itemsData="serviceVendors_arrayInfo" :firstFetch="false">
                      </AppSelectRemote>
                    </VCol>

                    <VCol cols="12" sm="4">
                      <AppSelectRemote :disabled="disabledFiledsView" label="Entidad" v-model="form.entity_id"
                        url="/selectInfiniteEntities" arrayInfo="entities" :requiredField="true"
                        :rules="[requiredValidator]" clearable :params="paramsSelectInfinite"
                        :itemsData="entities_arrayInfo" :firstFetch="false">
                      </AppSelectRemote>
                    </VCol>

                    <VCol cols="12" sm="4">
                      <SelectPatientForm :key="selectPatientKey" :disabled="disabledFiledsView" :requiredField="true"
                        label="Paciente" v-model="form.patient_id" :itemsData="patients_arrayInfo" :firstFetch="false"
                        :error-messages="errorsBack.patient_id" @update:modelValue="errorsBack.patient_id = ''" />
                    </VCol>

                    <VCol cols="12" sm="4">
                      <AppSelectRemote :disabled="disabledFiledsView" label="Tipo Nota" v-model="form.tipo_nota_id"
                        url="/selectInfinitetipoNota" arrayInfo="tipoNotas" clearable :params="paramsSelectInfinite"
                        :itemsData="tipoNotas_arrayInfo" :firstFetch="false" :error-messages="errorsBack.note_number"
                        @input="errorsBack.note_number = ''" :rules="tipoNotaRules"
                        :requiredField="form.note_number ? true : false">
                      </AppSelectRemote>
                    </VCol>

                    <VCol cols="12" sm="4">
                      <AppTextField v-model="form.note_number" label="Número de Nota"
                        :requiredField="form.tipo_nota_id ? true : false" :error-messages="errorsBack.note_number"
                        :rules="note_numberRules" @input="errorsBack.note_number = ''" clearable counter maxlength="20"
                        minlength="20" />
                    </VCol>

                    <VCol cols="12" sm="4">
                      <AppSelect :items="statusInvoiceEnum_arrayInfo" v-model="form.status" label="Estado"
                        :error-messages="errorsBack.note_number" @input="errorsBack.note_number = ''" clearable
                        :requiredField="true" :rules="[requiredValidator]"></AppSelect>
                    </VCol>


                    <VCol cols="12" sm="3">
                      <AppTextField clearable :requiredField="true" type="date" label="Fecha de transacción"
                        v-model="form.invoice_date" :error-messages="errorsBack.invoice_date"
                        :max="form.radication_date" :min="service_date" :rules="[requiredValidator]" />
                    </VCol>

                    <VCol cols="12" sm="3">
                      <AppTextField @blur="checkInvoiceNumber" :requiredField="form.tipo_nota_id ? false : true"
                        :rules="invoice_numberRules" v-model="form.invoice_number" label="Número de Factura"
                        :error-messages="errorsBack.invoice_number" @input="errorsBack.invoice_number = ''" clearable />
                    </VCol>

                    <VCol cols="12" sm="3">
                      <AppTextField clearable type="date" label="Fecha Radicación" v-model="form.radication_date"
                        :error-messages="errorsBack.radication_date" :min="form.invoice_date"
                        :rules="radication_dateRules" :requiredField="radication_dateRequiredField" />
                    </VCol>

                    <VCol cols="12" sm="3">
                      <AppTextField :requiredField="radication_numberRequiredField" :rules="radication_numberRules"
                        v-model="form.radication_number" label="Número de Radicado"
                        :error-messages="errorsBack.radication_number" @input="errorsBack.radication_number = ''"
                        clearable />
                    </VCol>

                    <VCol cols="12" sm="3">
                      <FormatCurrency disabled label="Valor Glosado" v-model="totalValueGlosa" />
                    </VCol>

                    <VCol cols="12" sm="3">
                      <FormatCurrency disabled label="Valor Factura" v-model="totalValueTotal" />
                    </VCol>
                    <VCol cols="12" sm="3">
                      <FormatCurrency disabled label="Valor Pagado" v-model="totalValuePaid" />
                    </VCol>

                    <VCol cols="12" sm="3">
                      <FormatCurrency disabled label="Valor restante" v-model="totalValueRemainingBalance" />
                    </VCol>

                    <VCol cols="12">
                      <VRadioGroup v-model="form.type" inline>
                        <VRadio v-for="(item, index) in typeInvoiceEnumValues" :key="index" :label="item.title"
                          :value="item.type" @click="openModalFormType(item.type)" />
                      </VRadioGroup>
                    </VCol>

                  </VRow>
                </VCardText>
              </AppCardActions>
            </VCol>
          </VRow>

          <VRow v-if="form.type == 'INVOICE_TYPE_002'">
            <VCol cols="12">
              <VBtn @click="openModalFormType('INVOICE_TYPE_002')">
                <template #append>
                  <VIcon :icon="validationsTypeForm.INVOICE_TYPE_002 ? 'tabler-x' : 'tabler-check'"
                    :color="validationsTypeForm.INVOICE_TYPE_002 ? 'danger' : 'success'"></VIcon>
                </template>
                Abrir Formulario Soat
              </VBtn>
            </VCol>
          </VRow>

        </VForm>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5">
        <VBtn v-if="form.id" color="primary" append-icon="tabler-chevron-down">
          Más Acciones
          <VMenu activator="parent">
            <VList>
              <VListItem @click="openModalListInvoicePayment()">
                Pagos
              </VListItem>
              <VListItem @click="openUploadModal">
                Añadir Soportes
              </VListItem>
              <VListItem @click="openModalShowFiles">
                Listar Soportes
              </VListItem>
            </VList>
          </VMenu>
        </VBtn>
        <BtnBack :disabled="isLoading" :loading="isLoading" />
        <VBtn v-if="!disabledFiledsView" :disabled="isLoading" :loading="isLoading" @click="submitForm()"
          color="primary">
          Guardar
        </VBtn>
      </VCardText>
    </VCard>

    <div v-if="form.id" class="mt-5">
      <ListService :invoice_id="form.id" />
    </div>

    <ModalQuestion ref="refModalQuestion" />

    <template v-if="form.id">
      <FileUploadModal ref="refFileUploadModal" v-model="showUploadModal" :maxFileSizeMB="30" :fileable_id="form.id"
        fileable_type="Invoice" />
    </template>

    <ModalShowFiles ref="refModalShowFiles"></ModalShowFiles>

    <ModalListInvoicePayment ref="refModalListInvoicePayment"></ModalListInvoicePayment>

    <VDialog v-model="isDialogVisible.INVOICE_TYPE_002" width="500">
      <!-- Dialog close btn -->
      <DialogCloseBtn @click="isDialogVisible.INVOICE_TYPE_002 = !isDialogVisible.INVOICE_TYPE_002" />

      <!-- Dialog Content -->
      <VCard>
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>Información Soat</VToolbarTitle>
          </VToolbar>
        </div>
        <VCardText>
          <VForm ref="formValidationSoat" :disabled="disabledFiledsView">

            <VRow>
              <VCol cols="12">
                <AppTextField :requiredField="true" :rules="[requiredValidator]" v-model="soat.policy_number"
                  label="Número de póliza" :error-messages="errorsBack.policy_number"
                  @input="errorsBack.policy_number = ''" clearable />
              </VCol>

              <VCol cols="12">

                <AppTextField clearable label="Fecha siniestro" v-model="soat.accident_date" :requiredField="true"
                  :rules="[requiredValidator]" :error-messages="errorsBack.accident_date"
                  @input="errorsBack.accident_date = ''" :disabled="disabledFiledsView" type="date"
                  :max="form.invoice_date" />

              </VCol>

              <VCol cols="12">
                <AppTextField clearable :requiredField="true" label="Fecha inicio de vigencia de la póliza"
                  v-model="soat.start_date" :error-messages="errorsBack.start_date" :rules="[requiredValidator]"
                  :disabled="disabledFiledsView" type="date" :max="soat.end_date" />
              </VCol>

              <VCol cols="12">
                <AppTextField clearable :requiredField="true" label="Fecha final de vigencia de la póliza"
                  v-model="soat.end_date" :error-messages="errorsBack.end_date" :rules="[requiredValidator]"
                  :disabled="disabledFiledsView" type="date" :min="soat.start_date" />
              </VCol>

              <VCol cols="12">
                <AppSelectRemote :disabled="disabledFiledsView" label="Estado de aseguramiento"
                  v-model="soat.insurance_statuse_id" url="/selectInfiniteInsuranceStatus" arrayInfo="insuranceStatus"
                  :requiredField="true" :rules="[requiredValidator]" clearable :params="paramsSelectInfinite"
                  :itemsData="insuranceStatus_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </VCol>
            </VRow>
          </VForm>

        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn :loading="isLoading" color="secondary" variant="tonal" @click="openModalFormType('INVOICE_TYPE_002')">
            Cerrar
          </VBtn>
          <VBtn :disabled="isLoading" :loading="isLoading" @click="acceptFormSoat()" color="primary">
            Continuar
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

  </div>
</template>
