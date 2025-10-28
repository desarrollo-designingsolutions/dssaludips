<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { useRipStore } from "@/pages/Rips/Store/useRipStore";
import type { VForm } from "vuetify/components/VForm";

definePage({
  path: "Rips/Manual/ListInvoices/:id",
  name: "Rips-Manual-ListInvoices",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "rips.index",
  },
});

const { toast } = useToast();
const refForm = ref<VForm>();
const router = useRouter();
const route = useRoute();
const { dataRip } = storeToRefs(useRipStore());
const authenticationStore = useAuthenticationStore();
const tipoNotas_arrayInfo = ref([])

const loading = reactive({
  table: false,
  download: false,
  upload: false,
});

const fetchDataTable = async () => {
  loading.table = true;
  const { data, response } = await useAxios(
    `/rip/getManualInfoRipInvoice/${route.params.id}`
  ).get({
    params: {
      company_id: authenticationStore.company.id,
    }
  });
  if (response.status === 200 && data) {
    dataRip.value = data.rip_info
    tipoNotas_arrayInfo.value = data.tipoNotas_arrayInfo
  }
  loading.table = false;
};

onMounted(async () => {
  await fetchDataTable();
});

//table
const inputsTableFilter = ref([
  {
    key: "actions",
    title: "Acciones",
    type: "actions",
    sortable: false,
    active: false,
    width: "100",
    fixed: true,
  },
  {
    key: "numDocumentoIdObligado",
    title: "Nit",
    type: "number",
    sortable: false,
    active: true,
    width: "10",
  },
  {
    key: "numFactura",
    title: "Número de factura",
    type: "string",
    sortable: false,
    active: true,
    width: "200",
  },
  {
    key: "cantUsers",
    title: "Cantidad de Usuarios",
    type: "number",
    sortable: false,
    active: true,
    width: "200",
  },
  {
    key: "TipoNota",
    title: "Tipo de Nota",
    type: "booleanYesAndNot",
    sortable: false,
    active: true,
    width: "350",
  },
  {
    key: "numNota",
    title: "Número de Nota",
    type: "booleanState",
    sortable: false,
    active: true,
    width: "200",
  },
  { key: "sumVr", title: "Valor", sortable: false, width: "10" },
  { key: "status_name", title: "Estado", sortable: false, width: "10" },
  { key: "xml_status_name", title: "XML", sortable: false, width: "10" },
]);

const goView = (ripInvoice: any) => {
  router.push({
    name: "Rips-Manual-ListInvoices-ListUsers",
    params: { rip_id: route.params.id, ripInvoice_id: ripInvoice.id, numFactura: ripInvoice.numFactura },
  });
};

const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [""],
  sortDesc: [false],
});
const search = ref("");

const invoicesData = computed(() => {
  return dataRip.value.arrayData.filter((ele) => ele.delete != 1);
});
const addData = async () => {
  const validation = await refForm.value?.validate();

  if (validation?.valid) {
    dataRip.value.arrayData.push({
      numDocumentoIdObligado: dataRip.value.numDocumentoIdObligado,
      numFactura: null,
      numNota: null,
      TipoNota: null,
      usuarios: [],
      cantUsers: 0,
      sumVr: 0,
      delete: 0,
    });
  } else {
    toast("Faltan campos por diligenciar", "", "warning");
  }
};

const deleteData = (index: number) => {
  if (dataRip.value.arrayData[index].id) {
    // Si el elemento tiene un ID, establecer su propiedad 'delete' en 1
    dataRip.value.arrayData[index].delete = 1;
  } else {
    // Si el elemento no tiene un ID, eliminarlo del arreglo
    dataRip.value.arrayData.splice(index, 1);
  }
};

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

const saveData = async () => {
  if (dataRip.value.arrayData.length == 0) {
    toast("Debe agregar almenos una factura", "", "warning");
    return false;
  }
  const validation = await refForm.value?.validate();

  if (validation?.valid) {

    loading.table = true;
    const { data, response } = await useAxios(`/rip/storeInvoice`).post(
      {
        rip_id: dataRip.value.id,
        company_id: authenticationStore.company.id,
        invoicesData: dataRip.value.arrayData,
        numDocumentoIdObligado: dataRip.value.numDocumentoIdObligado,
        xml_status_id: 1,
        status_id: 1,
        sumVr: 0,
      }
    );
    if (response.status === 200 && data) {
      dataRip.value = data.rip_info
    }
    loading.table = false;

  }
};

const clearTipoNota = (index: number) => {
  dataRip.value.arrayData[index].numNota = null;
};

//RULES PERZONALIZED
const rulesFieldNumFactura = [
  (value) => requiredValidator(value),
  (value) =>
    uniqueValue(
      value,
      invoicesData.value.map((invoice) => invoice.numFactura),
      "El número de factura ya está en uso."
    ),
];

const goBack = () => {
  const hasUndefinedId = invoicesData.value.some((invoice) => {
    return (
      !invoice.hasOwnProperty("id") || // Si no tiene la propiedad
      invoice.id === null || // Si es null
      invoice.id === "" || // Si es vacío
      typeof invoice.id === "undefined"
    ); // Si es indefinido
  });

  if (hasUndefinedId) {
    toast("Error", "Existen elementos que no se han guardado", "danger");
    return false;
  }

  router.push({ name: "Rips-Index" });
};
const breadcrumbs = [
  {
    title: "Rips",
    disabled: false,
    to: `/Rips/Index`,
  },
  {
    title: "Manual",
    disabled: false,
  },
  {
    title: "Facturas",
    disabled: true,
  },
];

const paramsSelectInfinite = {
  company_id: authenticationStore.company.id,
}
</script>
<template>
  <div>
    <VBreadcrumbs :items="breadcrumbs"></VBreadcrumbs>
    <VCard title="Información de facturación">
      <VCardText>
        <VRow>
          <VCol cols="3">
            <h4>Facturas</h4>
          </VCol>
          <VCol cols="2">
            <h4>Facturas No Validadas</h4>
          </VCol>
          <VCol cols="2">
            <h4>Facturas Validadas</h4>
          </VCol>
          <VCol cols="2">
            <h4>Estado</h4>
          </VCol>
          <VCol cols="3">
            <h4>Valor</h4>
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="3">
            <span>{{ dataRip.numInvoices }}</span>
          </VCol>
          <VCol cols="2">
            <span>{{ dataRip.failedInvoices }}</span>
          </VCol>

          <VCol cols="2">
            <VChip>
              <span>{{ dataRip.successfulInvoices }}</span>
            </VChip>
          </VCol>

          <VCol cols="2">
            <VChip :color="dataRip.status_backgroundColor">
              <span>{{ dataRip.status_description }}</span>
            </VChip>
          </VCol>

          <VCol cols="3">
            <span>{{ dataRip.sumVr }}</span>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard class="mt-5" :loading="loading.table" title="Listado de facturas">
      <VCardText>
        <VForm ref="refForm" @submit.prevent="() => { }">
          <VRow>
            <VCol cols="12" offset-md="8" md="4">
              <div class="d-flex justify-end gap-3 flex-wrap">
                <VBtn color="primary" @click="addData()">
                  <VIcon start icon="tabler-file-invoice" />
                  Agregar factura
                </VBtn>
              </div>
            </VCol>
            <VCol cols="12" offset-md="8" md="4">
              <AppTextField v-model="search" density="compact" placeholder="Search ..."
                append-inner-icon="tabler-search" single-line hide-details dense outlined />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12">
              <VDataTable :search="search" :headers="inputsTableFilter" :items="invoicesData"
                :items-per-page="options.itemsPerPage" :page="options.page" :options="options">
                <template #item.numFactura="{ item }">
                  <div class="text-center">
                    <AppTextField clearable v-model="item.numFactura" :rules="rulesFieldNumFactura" />
                  </div>
                </template>

                <template #item.TipoNota="{ item, index }">
                  <div class="text-center">
                    <AppSelectRemote v-model="item.TipoNota" url="/selectInfinitetipoNota" arrayInfo="tipoNotas"
                      clearable :params="paramsSelectInfinite" :itemsData="tipoNotas_arrayInfo" :firstFetch="false"
                      @click:clear="clearTipoNota(index)">
                    </AppSelectRemote>
                  </div>
                </template>

                <template #item.numNota="{ item }">
                  <div class="text-center">
                    <AppTextField :disabled="!item.TipoNota" clearable v-model="item.numNota" />
                  </div>
                </template>

                <template #item.sumVr="{ item }">
                  <div class="text-center">
                    <span>{{ item.sumVr }}</span>
                  </div>
                </template>

                <template #item.cantUsers="{ item }">
                  <div class="text-center">
                    <span>{{ item.cantUsers }}</span>
                  </div>
                </template>

                <template #item.status_name="{ item }">
                  <div>
                    <VChip :color="item.status_background">
                      <span>{{ item.status_name }}</span>
                      <VTooltip location="top" transition="scale-transition" activator="parent"
                        text="Debe revisar el listado de inconsistencias">
                      </VTooltip>
                    </VChip>
                  </div>
                </template>
                <template #item.xml_status_name="{ item }">
                  <div>
                    <VChip :color="item.xml_status_background">
                      <span>{{ item.xml_status_name }}</span>
                      <VTooltip location="top" transition="scale-transition" activator="parent"
                        text="no ha subido el archivo xml">
                      </VTooltip>
                    </VChip>
                  </div>
                </template>
                <template #item.actions="{ item, index }">
                  <IconBtn density="compact">
                    <VIcon icon="tabler-dots-vertical" />
                    <VMenu activator="parent">
                      <VList>
                        <VListItem v-if="item.id" @click="goView(item)">
                          <VIcon icon="tabler-users" start></VIcon>
                          Gestionar Usuarios
                        </VListItem>
                        <VListItem @click="openModalQuestionDelete(index)">
                          <VIcon icon="tabler-trash" start></VIcon>
                          Eliminar
                        </VListItem>
                      </VList>
                    </VMenu>
                  </IconBtn>
                </template>
                <template #no-data> No se encontraron datos </template>
              </VDataTable>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5">
        <VBtn variant="outlined" :disabled="loading.table" :loading="loading.table" @click="goBack()" color="primary">
          <VIcon start icon="tabler-arrow-left" />
          Regresar a RIPS
        </VBtn>
        <VBtn :disabled="loading.table" :loading="loading.table" @click="openModalQuestionSave()" color="primary">
          Guardar facturas
          <VIcon end icon="tabler-device-floppy" />
        </VBtn>
      </VCardText>
    </VCard>

    <ModalQuestion ref="refModalQuestion" @success="saveData()" />
    <ModalQuestion ref="refModalQuestionDelete" @success="deleteData(indexDelete)" />
  </div>
</template>
