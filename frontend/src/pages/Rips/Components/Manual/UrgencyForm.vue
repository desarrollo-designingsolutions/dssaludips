<script setup lang="ts">
import type { VForm } from 'vuetify/components/VForm';
import { useRipStore } from "@/pages/Rips/Store/useRipStore";
import { useRipManualStore } from "@/pages/Rips/Store/useRipManualStore";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();
const { toast } = useToast();
const router = useRouter()

const { dataRip, dataUser, dataServicesRipUser, servicesCount } = storeToRefs(useRipStore())
const {
  ripsCausaExternaVersion2_arrayInfo,
  cie10_arrayInfo,
  condicionyDestinoUsuarioEgreso_arrayInfo
} = storeToRefs(useRipManualStore())
const refForm = ref<VForm>()

//table 
const inputsTableFilter = ref([
  {
    key: "actions",
    title: "Acciones",
    type: "actions",
    sortable: false,
    minWidth: "100px",
    fixed: true,
  },
  { key: "fechaInicioAtencion", title: 'Fecha Inicio Atención', sortable: false, minWidth: "200px" },
  { key: "causaMotivoAtencion", title: 'Causa Motivo Atención', sortable: false, minWidth: "350px" },
  { key: "codDiagnosticoPrincipal", title: 'Diagnostico Principal', sortable: false, minWidth: "350px" },
  { key: "codDiagnosticoPrincipalE", title: 'Diagnostico Principal E', sortable: false, minWidth: "350px" },
  { key: "codDiagnosticoRelacionadoE1", title: 'Diagnostico Principal E1', sortable: false, minWidth: "350px" },
  { key: "codDiagnosticoRelacionadoE2", title: 'Diagnostico Principal E2', sortable: false, minWidth: "350px" },
  { key: "codDiagnosticoRelacionadoE3", title: 'Diagnostico Principal E3', sortable: false, minWidth: "350px" },
  { key: "condicionDestinoUsuarioEgreso", title: 'Condicion Destino Usuario Egreso', sortable: false, minWidth: "350px" },
  { key: "codDiagnosticoCausaMuerte", title: 'Diagnostico Causa de Muerte', sortable: false, minWidth: "350px" },
  { key: "fechaEgreso", title: 'Fecha De Egreso', sortable: false, minWidth: "200px" },
])


const options = ref({ page: 1, itemsPerPage: 10, sortBy: [''], sortDesc: [false] })
const search = ref('')
const dataServices = ref<Array<object>>([])
const route = useRoute()

const loading = reactive({
  table: false,
})

onMounted(async () => {
  if (dataServicesRipUser.value.urgencias) {
    dataServices.value = dataServicesRipUser.value.urgencias;
  }
})

const saveData = async () => {
  if (dataServices.value.length == 0) {
    toast("Debe agregar almenos un Servicio de urgencias", "", "warning");
    return false;
  }
  const validation = await refForm.value?.validate();

  if (validation?.valid) {

    loading.table = true;
    const { data, response } = await useAxios(`/rip/storeServices`).post(
      {
        ripInvoice_id: route.params?.ripInvoice_id,
        ripInvoiceUser_id: route.params?.ripInvoiceUser_id,
        company_id: authenticationStore.company.id,
        serviceData: dataServices.value,
        typeService: 'Urgencias'
      }
    );
    if (response.status === 200 && data) {
      dataServicesRipUser.value = data.ripInvoiceUser_info.servicios
      servicesCount.value = data.ripInvoiceUser_info.servicesCount
      dataServices.value = dataServicesRipUser.value.urgencias;
    }
    loading.table = false;

  } else {
    toast("Faltan campos por diligenciar", "", "warning");
  }
};

const addData = async () => {

  const validation = await refForm.value?.validate()

  if (validation?.valid) {
    dataServices.value.push({
      codPrestador: null,
      fechaInicioAtencion: null,
      causaMotivoAtencion: null,
      codDiagnosticoPrincipal: null,
      codDiagnosticoPrincipalE: null,
      codDiagnosticoRelacionadoE1: null,
      codDiagnosticoRelacionadoE2: null,
      codDiagnosticoRelacionadoE3: null,
      condicionDestinoUsuarioEgreso: null,
      codDiagnosticoCausaMuerte: null,
      fechaEgreso: null,
      consecutivo: null,
      numFEVPagoModerador: null,
      numDocumentoIdentificacion: null,
      delete: 0,
    })
  } else {
    toast('Faltan campos por diligenciar', '', 'warning')
  }
}

const proceduresData = computed(() => {
  return dataServices.value.filter((ele) => ele.delete != 1);
});

const deleteData = (index: number) => {
  if (dataServices.value[index].id) {
    // Si el elemento tiene un ID, establecer su propiedad 'delete' en 1
    dataServices.value[index].delete = 1;
  } else {
    // Si el elemento no tiene un ID, eliminarlo del arreglo
    dataServices.value.splice(index, 1);
  }
};

const goBack = () => {
  const hasUndefinedId = dataServices.value.some(service => {
    return !service.hasOwnProperty('id') || // Si no tiene la propiedad
      service.id === null ||           // Si es null
      service.id === '' ||             // Si es vacío
      typeof service.id === 'undefined'; // Si es indefinido
  });

  if (hasUndefinedId) {
    toast("Error", "Existen elementos que no se han guardado", "danger");
    return false
  }

  router.push({
    name: 'Rips-Manual-ListInvoices-ListUsers', params: {
      id: route.params?.rip_id,
      ripInvoice_id: route.params?.ripInvoice_id,
      numFactura: route.params?.numFactura,
    }
  })
}

const paramsSelectInfinite = {
  company_id: authenticationStore.company.id,
}

//ModalQuestionSave
const refModalQuestion = ref();
const openModalQuestionSave = () => {
  refModalQuestion.value.componentData.isDialogVisible = true
  refModalQuestion.value.componentData.principalIcon = 'tabler-help'
  refModalQuestion.value.componentData.btnSuccessText = 'Sí'
  refModalQuestion.value.componentData.btnCancelText = 'No'
  refModalQuestion.value.componentData.title = `¿Está seguro que desea guardar el registro?`
}

//ModalQuestionDelete
const indexDelete = ref();
const refModalQuestionDelete = ref();
const openModalQuestionDelete = (index: number) => {
  indexDelete.value = index;
  refModalQuestionDelete.value.componentData.isDialogVisible = true
  refModalQuestionDelete.value.componentData.principalIcon = 'tabler-trash'
  refModalQuestionDelete.value.componentData.btnSuccessText = 'Sí'
  refModalQuestionDelete.value.componentData.btnCancelText = 'No'
  refModalQuestionDelete.value.componentData.title = `¿Está seguro que desea eliminar el registro?`
}

</script>
<template>
  <div>
    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" offset-md="8" md="4">
            <div class="d-flex justify-end gap-3 flex-wrap">
              <VBtn color="primary" @click="addData()">
                <VIcon start icon="tabler-plus" />Agregar Urgencia
              </VBtn>
            </div>
          </VCol>
          <VCol cols="12" offset-md="8" md="4">
            <AppTextField v-model="search" density="compact" placeholder="Search ..." append-inner-icon="tabler-search"
              single-line hide-details dense outlined />
          </VCol>
        </VRow>
      </VCardText>

      <VCardText>
        <VForm ref="refForm" @submit.prevent="() => { }">
          <VDataTable :search="search" :headers="inputsTableFilter" :items="proceduresData"
            :items-per-page="options.itemsPerPage" :page="options.page" :options="options">

            <template #item.fechaInicioAtencion="{ item, index }">
              <div class="text-center">
                <AppDateTimePicker v-model="item.fechaInicioAtencion" :rules="[requiredValidator]"
                  :config="{ enableTime: true, dateFormat: 'Y-m-d H:i' }" />
              </div>
            </template>

            <template #item.causaMotivoAtencion="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.causaMotivoAtencion" url="/selectInfiniteRipsCausaExternaVersion2"
                  arrayInfo="ripsCausaExternaVersion2" clearable :params="paramsSelectInfinite"
                  :itemsData="ripsCausaExternaVersion2_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.codDiagnosticoPrincipal="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.codDiagnosticoPrincipal" url="/selectInfiniteCie10" arrayInfo="cie10"
                  clearable :params="paramsSelectInfinite" :itemsData="cie10_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.codDiagnosticoPrincipalE="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.codDiagnosticoPrincipalE" url="/selectInfiniteCie10" arrayInfo="cie10"
                  clearable :params="paramsSelectInfinite" :itemsData="cie10_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.codDiagnosticoRelacionadoE1="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.codDiagnosticoRelacionadoE1" url="/selectInfiniteCie10" arrayInfo="cie10"
                  clearable :params="paramsSelectInfinite" :itemsData="cie10_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.codDiagnosticoRelacionadoE2="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.codDiagnosticoRelacionadoE2" url="/selectInfiniteCie10" arrayInfo="cie10"
                  clearable :params="paramsSelectInfinite" :itemsData="cie10_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.codDiagnosticoRelacionadoE3="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.codDiagnosticoRelacionadoE3" url="/selectInfiniteCie10" arrayInfo="cie10"
                  clearable :params="paramsSelectInfinite" :itemsData="cie10_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.condicionDestinoUsuarioEgreso="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.condicionDestinoUsuarioEgreso" url="/selectInfiniteCondicionyDestinoUsuarioEgreso" arrayInfo="condicionyDestinoUsuarioEgreso"
                  clearable :params="paramsSelectInfinite" :itemsData="condicionyDestinoUsuarioEgreso_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.codDiagnosticoCausaMuerte="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.codDiagnosticoCausaMuerte" url="/selectInfiniteCie10" arrayInfo="cie10"
                  clearable :params="paramsSelectInfinite" :itemsData="cie10_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.fechaEgreso="{ item, index }">
              <div class="text-center">
                <AppDateTimePicker v-model="item.fechaEgreso" :rules="[requiredValidator]"
                  :config="{ enableTime: true, dateFormat: 'Y-m-d H:i' }" />
              </div>
            </template>

            <template #item.actions="{ item, index }">
              <IconBtn density="compact">
                <VIcon icon="tabler-dots-vertical" />
                <VMenu activator="parent">
                  <VList>
                    <VListItem @click="openModalQuestionDelete(index)">
                      <VIcon icon="tabler-trash" start />Eliminar
                    </VListItem>
                  </VList>
                </VMenu>
              </IconBtn>
            </template>
            <template #no-data> No se encontraron datos </template>
          </VDataTable>
        </VForm>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5">
        <VBtn variant="outlined" :disabled="loading.table" :loading="loading.table" color="primary" @click="goBack()">
          <VIcon start icon="tabler-arrow-left" />
          Regresar a usuarios
        </VBtn>
        <VBtn :disabled="loading.table" :loading="loading.table" @click="openModalQuestionSave()" color="primary">
          Guardar Urgencias
          <VIcon end icon="tabler-device-floppy" />
        </VBtn>
      </VCardText>
    </VCard>

    <ModalQuestion ref="refModalQuestion" @success="saveData()" />
    <ModalQuestion ref="refModalQuestionDelete" @success="deleteData(indexDelete)" />
  </div>
</template>
