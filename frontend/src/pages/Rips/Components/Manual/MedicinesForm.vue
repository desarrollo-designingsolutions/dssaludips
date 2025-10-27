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
  ripsCausaExternaVersion2_arrayInfo,
  cie10_arrayInfo,
  ripsTipoDiagnosticoPrincipalVersion2_arrayInfo,
  conceptoRecaudo_arrayInfo,
  condicionyDestinoUsuarioEgreso_arrayInfo,
  sexos_arrayInfo,
  tipoMedicamentoPosVersion2_arrayInfo,
  dci_arrayInfo,
  umm_arrayInfo,
  ffm_arrayInfo,
  upr_arrayInfo,
  codTecnologiaSaludables,
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
  { key: "numAutorizacion", title: 'No Autorización', sortable: false, minWidth: "200px" },
  { key: "idMIPRES", title: 'id Mipres', sortable: false, minWidth: "200px" },
  { key: "fechaDispensAdmon", title: 'Fecha Dispens Admon', sortable: false, minWidth: "200px" },
  { key: "codDiagnosticoPrincipal", title: 'Diagnostico Principal', sortable: false, minWidth: "350px" },
  { key: "codDiagnosticoRelacionado", title: 'Diagnostico Relacionado', sortable: false, minWidth: "350px" },
  { key: "tipoMedicamento", title: 'Tipo Medicamento', sortable: false, minWidth: "350px" },
  { key: "codTecnologiaSalud", title: 'Cod Tecnologia Salud', sortable: false, minWidth: "200px" },
  { key: "nomTecnologiaSalud", title: 'Nombre Tecnología Salud', sortable: false, minWidth: "200px" },
  { key: "concentracionMedicamento", title: 'Concentracion Medicamento', sortable: false, minWidth: "200px" },
  { key: "unidadMedida", title: 'Unidad de Medida', sortable: false, minWidth: "350px" },
  { key: "formaFarmaceutica", title: 'Forma Farmaceutica', sortable: false, minWidth: "200px" },
  { key: "unidadMinDispensa", title: 'Unidad Min Dispensa', sortable: false, minWidth: "200px" },
  { key: "cantidadMedicamento", title: 'Cantidad Medicamento', sortable: false, minWidth: "200px" },
  { key: "diasTratamiento", title: 'DÍas Tratamiento', sortable: false, minWidth: "200px" },
  { key: "vrUnitMedicamento", title: 'Valor Unit. Medicamento', sortable: false, minWidth: "200px" },
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
  if (dataServicesRipUser.value.medicamentos) {
    dataServices.value = dataServicesRipUser.value.medicamentos;
  }
})

const saveData = async () => {
  if (dataServices.value.length == 0) {
    toast("Debe agregar almenos un Servicio de medicamentos", "", "warning");
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
        typeService: 'medicamentos'
      }
    );
    if (response.status === 200 && data) {
      dataServicesRipUser.value = data.ripInvoiceUser_info.servicios
      servicesCount.value = data.ripInvoiceUser_info.servicesCount
      dataServices.value = dataServicesRipUser.value.medicamentos;
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
      numAutorizacion: null,
      idMIPRES: null,
      fechaDispensAdmon: null,
      codDiagnosticoPrincipal: null,
      codDiagnosticoRelacionado: null,
      tipoMedicamento: null,
      codTecnologiaSalud: null,
      nomTecnologiaSalud: null,
      concentracionMedicamento: null,
      unidadMedida: null,
      formaFarmaceutica: null,
      unidadMinDispensa: null,
      cantidadMedicamento: null,
      diasTratamiento: null,
      tipoDocumentoIdentificacion: null,
      numDocumentoIdentificacion: null,
      vrUnitMedicamento: null,
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

const queryData = computed(() => {
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

interface CodTecnologiaSaludablesSelect {
  label: string;
  url: string;
  arrayInfo: string;
  itemsData: any[];
}

const getCodTecnologiaSaludablesSelect = (medicine: any): CodTecnologiaSaludablesSelect => {
  if (medicine?.codTecnologiaSaludable_type) {
    return (
      codTecnologiaSaludables.value.find(
        (item) => item.value === medicine.codTecnologiaSaludable_type
      ) || {
        label: "",
        url: "",
        arrayInfo: "",
        itemsData: [],
      }
    );
  }

  return {
    label: "",
    url: "",
    arrayInfo: "",
    itemsData: [],
  };
};

</script>
<template>
  <div>
    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" offset-md="8" md="4">
            <div class="d-flex justify-end gap-3 flex-wrap">
              <VBtn color="primary" @click="addData()">
                <VIcon start icon="tabler-plus" />Agregar Medicamento
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
          <VDataTable :search="search" :headers="inputsTableFilter" :items="queryData"
            :items-per-page="options.itemsPerPage" :page="options.page" :options="options">

            <template #item.numAutorizacion="{ item, index }">
              <div class="text-center">
                <AppTextField clearable v-model="item.numAutorizacion" />
              </div>
            </template>

            <template #item.idMIPRES="{ item, index }">
              <div class="text-center">
                <AppTextField clearable v-model="item.idMIPRES" />
              </div>
            </template>

            <template #item.fechaDispensAdmon="{ item, index }">
              <div class="text-center">
                <AppDateTimePicker v-model="item.fechaDispensAdmon" :rules="[requiredValidator]"
                  :config="{ enableTime: true, dateFormat: 'Y-m-d H:i' }" />
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

            <template #item.tipoMedicamento="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.tipoMedicamento" url="/selectInfiniteTipoMedicamentoPosVersion2"
                  arrayInfo="tipoMedicamentoPosVersion2" clearable :params="paramsSelectInfinite"
                  :itemsData="tipoMedicamentoPosVersion2_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.codTecnologiaSalud="{ item, index }">
              <VRadioGroup v-model="item.codTecnologiaSaludable_type" inline>
                <VRadio v-for="(radioItem, index) in codTecnologiaSaludables" :key="index" :label="radioItem.label"
                  :value="radioItem.value" @click="item.codTecnologiaSaludable_id = null" />
              </VRadioGroup>

              <AppSelectRemote clearable v-model="item.codTecnologiaSaludable_id"
                :url="getCodTecnologiaSaludablesSelect(item)?.url"
                :array-info="getCodTecnologiaSaludablesSelect(item)?.arrayInfo"
                :itemsData="getCodTecnologiaSaludablesSelect(item)?.itemsData" :firstFetch="false" />

            </template>

            <template #item.nomTecnologiaSalud="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.nomTecnologiaSalud" url="/selectInfiniteDci" arrayInfo="dci" clearable
                  :params="paramsSelectInfinite" :itemsData="dci_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.concentracionMedicamento="{ item, index }">
              <div class="text-center">
                <AppTextField clearable v-model="item.concentracionMedicamento" />
              </div>
            </template>

            <template #item.unidadMedida="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.unidadMedida" url="/selectInfiniteUmm" arrayInfo="umm" clearable
                  :params="paramsSelectInfinite" :itemsData="umm_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.formaFarmaceutica="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.formaFarmaceutica" url="/selectInfiniteFfm" arrayInfo="ffm" clearable
                  :params="paramsSelectInfinite" :itemsData="ffm_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.unidadMinDispensa="{ item, index }">
              <div class="text-center">
                <AppSelectRemote v-model="item.unidadMinDispensa" url="/selectInfiniteUpr" arrayInfo="upr" clearable
                  :params="paramsSelectInfinite" :itemsData="upr_arrayInfo" :firstFetch="false">
                </AppSelectRemote>
              </div>
            </template>

            <template #item.cantidadMedicamento="{ item, index }">
              <div class="text-center">
                <AppTextField clearable v-model="item.cantidadMedicamento" />
              </div>
            </template>

            <template #item.diasTratamiento="{ item, index }">
              <div class="text-center">
                <AppTextField clearable v-model="item.diasTratamiento" />
              </div>
            </template>

            <template #item.vrUnitMedicamento="{ item, index }">
              <div class="text-center">
                <AppTextField clearable v-model="item.vrUnitMedicamento" />
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
          Guardar Medicamentos
          <VIcon end icon="tabler-device-floppy" />
        </VBtn>
      </VCardText>
    </VCard>

    <ModalQuestion ref="refModalQuestion" @success="saveData()" />
    <ModalQuestion ref="refModalQuestionDelete" @success="deleteData(indexDelete)" />
  </div>
</template>
