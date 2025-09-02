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
const emit = defineEmits(["execute", "created"])

const titleModal = ref<string>("Servicio: Urgencias")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const isLoading = ref<boolean>(false)

const cie10_arrayInfo = ref([])
const ripsCausaExternaVersion2_arrayInfo = ref([])
const condicionyDestinoUsuarioEgreso_arrayInfo = ref([])

const service_id = ref<null | string>(null)
const invoice = ref<null | object>({
  id: null as string | null,
  invoice_date: null as string | null,
})
const form = ref({
  id: null as string | null,
  invoice_id: null as string | null,

  fechaInicioAtencion: null as string | null,
  causaMotivoAtencion_id: null as string | null,
  codDiagnosticoPrincipal_id: null as string | null,
  codDiagnosticoPrincipalE_id: null as string | null,
  codDiagnosticoRelacionadoE1_id: null as string | null,
  codDiagnosticoRelacionadoE2_id: null as string | null,
  codDiagnosticoRelacionadoE3_id: null as string | null,
  condicionDestinoUsuarioEgreso_id: null as string | null,
  codDiagnosticoCausaMuerte_id: null as string | null,
  fechaEgreso: null as string | null,
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

  await fetchDataForm();
};

const fetchDataForm = async () => {
  try {
    isLoading.value = true;
    const url = service_id.value ? `/service/urgency/${service_id.value}/edit` : `/service/urgency/create`;
    const { data, response } = await useAxios(url).get({
      params: {
        invoice_id: form.value.invoice_id,
      }
    });

    if (response.status === 200 && data) {

      invoice.value = data.invoice

      cie10_arrayInfo.value = data.cie10_arrayInfo
      ripsCausaExternaVersion2_arrayInfo.value = data.ripsCausaExternaVersion2_arrayInfo
      condicionyDestinoUsuarioEgreso_arrayInfo.value = data.condicionyDestinoUsuarioEgreso_arrayInfo

      if (data.form) {
        form.value = cloneObject(data.form);
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
    const url = form.value.id ? `/service/urgency/update/${form.value.id}` : `/service/urgency/store`;

    const payload = {
      ...form.value,
      service_id: service_id.value,
      company_id: authenticationStore.company.id,
    };

    const { data, response } = await useAxios(url).post(payload);

    if (response.status === 200 && data.code == 200) {
      if (!form.value.id) {
        emit("created")
      }
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


defineExpose({
  openModal
});



// Computada para la fecha máxima de fechaInicioAtencion
const fechaInicioAtencionMaxDate = computed(() => {
  if (form.value.fechaEgreso) {
    return formatToDateTimeLocal(new Date(new Date(form.value.fechaEgreso).getTime() + 24 * 60 * 60 * 1000));
  } else {
    return formatToDateTimeLocal(new Date(new Date(invoice?.value.invoice_date).getTime() + 24 * 60 * 60 * 1000));
  }
});



const fechaInicioAtencionRules = [
  (value: string) => requiredValidator(value) || 'El campo es requerido',
  (value: string) => {

    const maxDate = form.value.fechaEgreso
      ? form.value.fechaEgreso
      : invoice.value?.invoice_date;

    return maxDateValidator(value, maxDate)
  },
]

const fechaEgresoRules = [
  (value: string) => requiredValidator(value) || 'El campo es requerido',
  (value: string) => {

    const maxDate = form.value.fechaInicioAtencion
      ? form.value.fechaInicioAtencion
      : invoice.value?.invoice_date;

    return minDateValidator(value, maxDate)
  },
]

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
                  :requiredField="true" :rules="fechaInicioAtencionRules"
                  :error-messages="errorsBack.fechaInicioAtencion" @input="errorsBack.fechaInicioAtencion = ''"
                  :disabled="disabledFiledsView" type="datetime-local" :max="fechaInicioAtencionMaxDate" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="causaMotivoAtencion" v-model="form.causaMotivoAtencion_id"
                  :requiredField="true" :rules="[requiredValidator]" :error-messages="errorsBack.causaMotivoAtencion_id"
                  @input="errorsBack.causaMotivoAtencion_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteRipsCausaExternaVersion2" array-info="ripsCausaExternaVersion2"
                  :itemsData="ripsCausaExternaVersion2_arrayInfo" :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoPrincipal" v-model="form.codDiagnosticoPrincipal_id"
                  :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.codDiagnosticoPrincipal_id"
                  @input="errorsBack.codDiagnosticoPrincipal_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCie10" array-info="cie10" :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoPrincipalE" v-model="form.codDiagnosticoPrincipalE_id"
                  :error-messages="errorsBack.codDiagnosticoPrincipalE_id" :requiredField="true"
                  :rules="[requiredValidator]" @input="errorsBack.codDiagnosticoPrincipalE_id = ''"
                  :disabled="disabledFiledsView" url="/selectInfiniteCie10" array-info="cie10"
                  :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoRelacionadoE1"
                  v-model="form.codDiagnosticoRelacionadoE1_id"
                  :error-messages="errorsBack.codDiagnosticoRelacionadoE1_id"
                  @input="errorsBack.codDiagnosticoRelacionadoE1_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCie10" array-info="cie10" :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoRelacionadoE2"
                  v-model="form.codDiagnosticoRelacionadoE2_id"
                  :error-messages="errorsBack.codDiagnosticoRelacionadoE2_id"
                  @input="errorsBack.codDiagnosticoRelacionadoE2_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCie10" array-info="cie10" :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoRelacionadoE3"
                  v-model="form.codDiagnosticoRelacionadoE3_id"
                  :error-messages="errorsBack.codDiagnosticoRelacionadoE3_id"
                  @input="errorsBack.codDiagnosticoRelacionadoE3_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCie10" array-info="cie10" :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="condicionDestinoUsuarioEgreso"
                  v-model="form.condicionDestinoUsuarioEgreso_id" :requiredField="true" :rules="[requiredValidator]"
                  :error-messages="errorsBack.condicionDestinoUsuarioEgreso_id"
                  @input="errorsBack.condicionDestinoUsuarioEgreso_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCondicionyDestinoUsuarioEgreso" array-info="condicionyDestinoUsuarioEgreso"
                  :itemsData="condicionyDestinoUsuarioEgreso_arrayInfo" :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelectRemote clearable label="codDiagnosticoCausaMuerte" v-model="form.codDiagnosticoCausaMuerte_id"
                  :error-messages="errorsBack.codDiagnosticoCausaMuerte_id"
                  @input="errorsBack.codDiagnosticoCausaMuerte_id = ''" :disabled="disabledFiledsView"
                  url="/selectInfiniteCie10" array-info="cie10" :itemsData="cie10_arrayInfo" :firstFetch="false" />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField clearable label="fechaEgreso" v-model="form.fechaEgreso" :requiredField="true"
                  :rules="fechaEgresoRules" :error-messages="errorsBack.fechaEgreso"
                  @input="errorsBack.fechaEgreso = ''" :disabled="disabledFiledsView" type="datetime-local"
                  :min="form.fechaInicioAtencion"
                  :max="formatToDateTimeLocal(new Date(new Date(invoice?.invoice_date).getTime() + 24 * 60 * 60 * 1000))" />
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
