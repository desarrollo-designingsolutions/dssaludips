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
  cupsRips_arrayInfo,
  viaIngresoUsuario_arrayInfo,
  modalidadAtencion_arrayInfo,
  grupoServicio_arrayInfo,
  servicio_arrayInfo,
  ripsFinalidadConsultaVersion2_arrayInfo,
  cie10_arrayInfo,
  conceptoRecaudo_arrayInfo
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
  { key: "idMIPRES", title: 'id Mipres', sortable: false, minWidth: "200px" },
  { key: "numAutorizacion", title: 'No Autorización', sortable: false, minWidth: "200px" },
  { key: "codProcedimiento", title: 'Codigo de Procedimiento', sortable: false, minWidth: "350px" },
  { key: "viaIngresoServicioSalud", title: 'Via Ingreso Servicio Salud', sortable: false, minWidth: "350px" },
  { key: "modalidadGrupoServicioTecSal", title: 'Modalidad Grupo Servicio TecSal', sortable: false, minWidth: "350px" },
  { key: "grupoServicios", title: 'Grupo Servicio', sortable: false, minWidth: "350px" },
  { key: "codServicio", title: 'Codigo Servicio', sortable: false, minWidth: "200px" },
  { key: "finalidadTecnologiaSalud", title: 'Finalidad Tecnologia Salud', sortable: false, minWidth: "350px" },
  { key: "codDiagnosticoPrincipal", title: 'Diagnostico Principal', sortable: false, minWidth: "350px" },
  { key: "codDiagnosticoRelacionado", title: 'Diagnostico Relacionado', sortable: false, minWidth: "350px" },
  { key: "codComplicacion", title: 'Código Complicación', sortable: false, minWidth: "350px" },
  { key: "valorPagoModerador", title: 'Valor Pago Moderador', sortable: false, minWidth: "200px" },
  { key: "vrServicio", title: 'Valor Servicio', sortable: false, minWidth: "200px" },
  { key: "conceptoRecaudo", title: 'Concepto Recaudo', sortable: false, minWidth: "350px" },
])


const options = ref({ page: 1, itemsPerPage: 10, sortBy: [''], sortDesc: [false] })
const search = ref('')
const dataServices = ref<Array<object>>([])
const route = useRoute()

const loading = reactive({
  table: false,
})

onMounted(async () => {
  if (dataServicesRipUser.value.procedimientos) {
    dataServices.value = dataServicesRipUser.value.procedimientos;
  }
})

const saveData = async () => {
  if (dataServices.value.length == 0) {
    toast("Debe agregar almenos un Servicio de procedimientos", "", "warning");
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
        typeService: 'procedimientos'
      }
    );
    if (response.status === 200 && data) {
      dataServicesRipUser.value = data.ripInvoiceUser_info.servicios
      servicesCount.value = data.ripInvoiceUser_info.servicesCount
      dataServices.value = dataServicesRipUser.value.procedimientos;
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
      idMIPRES: null,
      numAutorizacion: null,
      codProcedimiento: null,
      viaIngresoServicioSalud: null,
      modalidadGrupoServicioTecSal: null,
      grupoServicios: null,
      codServicio: null,
      finalidadTecnologiaSalud: null,
      tipoDocumentoIdentificacion: null,
      numDocumentoIdentificacion: null,
      codDiagnosticoPrincipal: null,
      codDiagnosticoRelacionado: null,
      codComplicacion: null,
      valorPagoModerador: null,
      numFEVPagoModerador: null,
      consecutivo: null,
      vrServicio: null,
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
  refModalQuestion.value.componentData.title = `¿Está seguro que desea guardar ${dataServices.value.length > 1 ? 'los registros' : 'el registro' }?`
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
                <VIcon start icon="tabler-plus" />Agregar Procedimiento
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

            <template #item.idMIPRES="{ item, index }">
              <div class="text-center">
                <AppTextField clearable v-model="item.idMIPRES" />
              </div>
            </template>

            <template #item.numAutorizacion="{ item, index }">
              <div class="text-center">
                <AppTextField clearable v-model="item.numAutorizacion" />
              </div>
            </template>

            <template #item.codProcedimiento="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.codProcedimiento" url="/selectInfiniteCupsRips" arrayInfo="cupsRips"
                  clearable :params="paramsSelectInfinite" :itemsData="cupsRips_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.viaIngresoServicioSalud="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.viaIngresoServicioSalud" url="/selectInfiniteViaIngresoUsuario"
                  arrayInfo="viaIngresoUsuario" clearable :params="paramsSelectInfinite"
                  :itemsData="viaIngresoUsuario_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.modalidadGrupoServicioTecSal="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.modalidadGrupoServicioTecSal" url="/selectInfiniteModalidadAtencion"
                  arrayInfo="modalidadAtencion" clearable :params="paramsSelectInfinite"
                  :itemsData="modalidadAtencion_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.grupoServicios="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.grupoServicios" url="/selectInfiniteGrupoServicio"
                  arrayInfo="grupoServicio" clearable :params="paramsSelectInfinite"
                  :itemsData="grupoServicio_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.codServicio="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.codServicio" url="/selectInfiniteServicio" arrayInfo="servicio" clearable
                  :params="paramsSelectInfinite" :itemsData="servicio_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>


            <template #item.finalidadTecnologiaSalud="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.finalidadTecnologiaSalud"
                  url="/selectInfiniteRipsFinalidadConsultaVersion2" arrayInfo="ripsFinalidadConsultaVersion2" clearable
                  :params="paramsSelectInfinite" :itemsData="ripsFinalidadConsultaVersion2_arrayInfo"
                  :firstFetch="false">
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

            <template #item.codDiagnosticoRelacionado="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.codDiagnosticoRelacionado" url="/selectInfiniteCie10" arrayInfo="cie10"
                  clearable :params="paramsSelectInfinite" :itemsData="cie10_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.codComplicacion="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.codComplicacion" url="/selectInfiniteCie10" arrayInfo="cie10" clearable
                  :params="paramsSelectInfinite" :itemsData="cie10_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.valorPagoModerador="{ item, index }">
              <div class="text-center">
                <AppTextField clearable v-model="item.valorPagoModerador" />
              </div>
            </template>

            <template #item.vrServicio="{ item, index }">
              <div class="text-center">
                <AppTextField clearable v-model="item.vrServicio" />
              </div>
            </template>

            <template #item.conceptoRecaudo="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.conceptoRecaudo" url="/selectInfiniteConceptoRecaudo"
                  arrayInfo="conceptoRecaudo" clearable :params="paramsSelectInfinite"
                  :itemsData="conceptoRecaudo_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
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
          Guardar Procedimientos
          <VIcon end icon="tabler-device-floppy" />
        </VBtn>
      </VCardText>
    </VCard>

    <ModalQuestion ref="refModalQuestion" @success="saveData()" />
    <ModalQuestion ref="refModalQuestionDelete" @success="deleteData(indexDelete)" />
  </div>
</template>
