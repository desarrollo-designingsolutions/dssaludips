<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { useRipStore } from "@/pages/Rips/Store/useRipStore";
import type { VForm } from "vuetify/components/VForm";

definePage({
  path: "Rips/Manual/ListInvoices/ListUsers/:rip_id/:ripInvoice_id/:numFactura",
  name: "Rips-Manual-ListInvoices-ListUsers",
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
const tipoIdPisis_arrayInfo = ref([])
const ripsTipoUsuarioVersion2s_arrayInfo = ref([])
const sexos_arrayInfo = ref([])
const paises_arrayInfo = ref([])
const municipios_arrayInfo = ref([])
const zonaVersion2s_arrayInfo = ref([])

const dataInvoice = ref();

const loading = reactive({
  table: false,
  download: false,
  upload: false,
  selectTipoIdPisis: false,
  codPaisResidencia: false,
  selectMunicipio: false,
  codPaisOrigen: false,
});

const fetchDataTable = async () => {
  loading.table = true;
  const { data, response } = await useAxios(
    `/rip/getManualInfoUsers/${route.params.ripInvoice_id}`
  ).get();

  if (response.status === 200 && data) {
    dataInvoice.value = data.ripInvoice_info
    tipoIdPisis_arrayInfo.value = data.tipoIdPisis_arrayInfo
    ripsTipoUsuarioVersion2s_arrayInfo.value = data.ripsTipoUsuarioVersion2s_arrayInfo
    sexos_arrayInfo.value = data.sexos_arrayInfo
    paises_arrayInfo.value = data.paises_arrayInfo
    municipios_arrayInfo.value = data.municipios_arrayInfo
    zonaVersion2s_arrayInfo.value = data.zonaVersion2s_arrayInfo
  }

  loading.table = false;
};

onMounted(async () => {
  fetchDataTable();
});

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
  {
    key: "tipoDocumentoIdentificacion",
    title: "Tipo de Documento",
    sortable: false,
    minWidth: "350px",
  },
  {
    key: "numDocumentoIdentificacion",
    title: "No Documento",
    sortable: false,
    minWidth: "200px",
  },
  {
    key: "tipoUsuario",
    title: "Tipo de Usuario",
    sortable: false,
    minWidth: "350px",
  },
  {
    key: "fechaNacimiento",
    title: "Fecha de Nacimiento",
    sortable: false,
    minWidth: "200px",
  },
  { key: "codSexo", title: "Sexo", sortable: false, minWidth: "200px" },
  {
    key: "codPaisResidencia",
    title: "Pais Residencia",
    sortable: false,
    minWidth: "350px",
  },
  {
    key: "codMunicipioResidencia",
    title: "Municipio Residencia",
    sortable: false,
    minWidth: "350px",
  },
  {
    key: "codZonaTerritorialResidencia",
    title: "Zona Territorial Residencia",
    sortable: false,
    minWidth: "350px",
  },
  { key: "incapacidad", title: "Incapacidad", sortable: false, minWidth: "200px" },
  { key: "codPaisOrigen", title: "Pais Origen", sortable: false, minWidth: "350px" },
]);

const goView = (user_id: number, numDocumentoIdentificacion: number) => {
  router.push({
    name: "Rips-Manual-ListInvoices-ListUsers-ListServices",
    params: {
      rip_id: route.params.rip_id,
      ripInvoice_id: route.params.ripInvoice_id,
      numFactura: route.params.numFactura,
      ripInvoiceUser_id: user_id,
      numDocumentoIdentificacion: numDocumentoIdentificacion,
    },
  });
};

const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [""],
  sortDesc: [false],
});
const search = ref("");

const usersData = computed(() => {
  return dataInvoice.value?.users?.filter((ele) => ele.delete != 1);
});

const addData = async () => {
  const validation = await refForm.value?.validate();
  if (validation?.valid) {
    dataInvoice.value?.users.push({
      tipoDocumentoIdentificacion: null,
      numDocumentoIdentificacion: null,
      tipoUsuario: null,
      fechaNacimiento: null,
      codSexo: null,
      codPaisResidencia: null,
      codMunicipioResidencia: null,
      codZonaTerritorialResidencia: null,
      incapacidad: null,
      consecutivo: null,
      codPaisOrigen: null,
    });
    // loadDataSelectsIninites();
  } else {
    toast("Faltan campos por diligenciar", "", "warning");
  }
};
const deleteData = (index: number) => {
  console.log(index);
  if (dataInvoice.value.users[index].id) {
    // Si el elemento tiene un ID, establecer su propiedad 'delete' en 1
    dataInvoice.value.users[index].delete = 1;
  } else {
    // Si el elemento no tiene un ID, eliminarlo del arreglo
    dataInvoice.value.users.splice(index, 1);
  }
};

const saveData = async () => {
  if (dataInvoice.value.users.length == 0) {
    toast("Debe agregar almenos un usuario", "", "warning");
    return false;
  }
  const validation = await refForm.value?.validate();

  if (validation?.valid) {

    loading.table = true;
    const { data, response } = await useAxios(`/rip/storeUsers`).post(
      {
        ripInvoice_id: route.params?.ripInvoice_id,
        company_id: authenticationStore.company.id,
        usersData: dataInvoice.value?.users,
        numDocumentoIdObligado: dataInvoice.value.numDocumentoIdObligado,
      }
    );
    if (response.status === 200 && data) {
      dataInvoice.value = data.ripInvoice_info
    }
    if (response.status === 422 && data) {
      const errors = response.data.errors;
      const duplicateMessage = 'El número de documento debe ser único dentro del listado.';

      const duplicateKeys = Object.keys(errors).filter(key => {
        const arr = errors[key];
        return Array.isArray(arr) && arr.includes(duplicateMessage);
      });

      if (duplicateKeys.length > 0) {
        toast(`Hay ${duplicateKeys.length} usuarios con número de documento repetido`, "", "warning");
        return;
      }
    }
    loading.table = false;

  } else {
    toast("Faltan campos por diligenciar", "", "warning");
  }
};

//RULES PERZONALIZED
const rulesFieldNumDocumentoIdentificacion = [
  (value) => requiredValidator(value),
  (value) =>
    uniqueValue(
      value,
      usersData.value.map((user) => user.numDocumentoIdentificacion),
      "El número de documento ya está en uso."
    ),
];

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

const goBack = () => {
  const hasUndefinedId = usersData.value.some((user: any) => {
    return (
      !user.hasOwnProperty("id") || // Si no tiene la propiedad
      user.id === null || // Si es null
      user.id === "" || // Si es vacío
      typeof user.id === "undefined"
    ); // Si es indefinido
  });

  if (hasUndefinedId) {
    toast("Error", "Existen elementos que no se han guardado", "danger");
    return false;
  }

  router.push({
    name: "Rips-Manual-ListInvoices",
    params: { id: route.params?.rip_id },
  });
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
    title: `Factura: ${route.params?.numFactura}`,
    disabled: false,
    to: `/Rips/Components/Manual/Rips/Manual/ListInvoices/${route.params?.rip_id}`,
  },
  {
    title: `Usuarios`,
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
    <VCard title="Información de usuarios">
      <VCardText>
        <VRow>
          <VCol cols="2">
            <h4>NIT</h4>
          </VCol>

          <VCol cols="2">
            <h4>Nro Factura</h4>
          </VCol>

          <VCol cols="2">
            <h4>Cantidad de usuarios</h4>
          </VCol>

          <VCol cols="2">
            <h4>Tipo de nota</h4>
          </VCol>

          <VCol cols="2">
            <h4>Número de nota</h4>
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="2">
            <span>{{ dataInvoice?.numDocumentoIdObligado }}</span>
          </VCol>

          <VCol cols="2">
            <span>{{ dataInvoice?.numFactura }}</span>
          </VCol>
          <VCol cols="2">
            <span>{{ dataInvoice?.cantUsers }}</span>
          </VCol>

          <VCol cols="2">
            <span>{{ dataInvoice?.tipoNota }}</span>
          </VCol>

          <VCol cols="2">
            <span>{{ dataInvoice?.numNota }}</span>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard class="mt-5" :loading="loading.table" title="Listado de usuarios">
      <VCardText>
        <VForm ref="refForm" @submit.prevent="() => { }">
          <VRow>
            <VCol cols="12" offset-md="8" md="4">
              <div class="d-flex justify-end gap-3 flex-wrap">
                <VBtn color="primary" @click="addData()">
                  <VIcon start icon="tabler-user-plus" />
                  Agregar usuario
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
              <VDataTable :search="search" :headers="inputsTableFilter" :items="usersData"
                :items-per-page="options.itemsPerPage" :page="options.page" :options="options">
                <template #item.tipoDocumentoIdentificacion="{ item, index }">
                  <div class="text-center">

                    <AppSelectRemote v-model="item.tipoDocumentoIdentificacion" url="/selectInfiniteTipoIdPisis"
                      arrayInfo="tipoIdPisis" clearable :params="paramsSelectInfinite"
                      :itemsData="tipoIdPisis_arrayInfo" :firstFetch="false">
                    </AppSelectRemote>
                  </div>
                </template>
                <template #item.numDocumentoIdentificacion="{ item }">
                  <div class="text-center">
                    <AppTextField clearable v-model="item.numDocumentoIdentificacion"
                      :rules="rulesFieldNumDocumentoIdentificacion" />
                  </div>
                </template>

                <template #item.tipoUsuario="{ item, index }">
                  <div class="text-center">
                    <AppSelectRemote v-model="item.tipoUsuario" url="/selectInfiniteTipoUsuario"
                      arrayInfo="ripsTipoUsuarioVersion2s" clearable :params="paramsSelectInfinite"
                      :itemsData="ripsTipoUsuarioVersion2s_arrayInfo" :firstFetch="false">
                    </AppSelectRemote>
                  </div>
                </template>

                <template #item.fechaNacimiento="{ item }">
                  <div class="text-center">
                    <AppDateTimePicker v-model="item.fechaNacimiento" clearable />
                  </div>
                </template>

                <template #item.codSexo="{ item, index }">
                  <div class="text-center">
                    <AppSelectRemote v-model="item.codSexo" url="/selectInfiniteSexo" arrayInfo="sexos" clearable
                      :params="paramsSelectInfinite" :itemsData="sexos_arrayInfo" :firstFetch="false">
                    </AppSelectRemote>
                  </div>
                </template>

                <template #item.codPaisResidencia="{ item, index }">
                  <div class="text-center">
                    <AppSelectRemote v-model="item.codPaisResidencia" url="/selectInfinitePais" arrayInfo="paises"
                      clearable :params="paramsSelectInfinite" :itemsData="paises_arrayInfo" :firstFetch="false">
                    </AppSelectRemote>
                  </div>
                </template>

                <template #item.codMunicipioResidencia="{ item, index }">
                  <div class="text-center">
                    <AppSelectRemote v-model="item.codMunicipioResidencia" url="/selectInfiniteMunicipio"
                      arrayInfo="municipios" clearable :params="paramsSelectInfinite" :itemsData="municipios_arrayInfo"
                      :firstFetch="false">
                    </AppSelectRemote>
                  </div>
                </template>

                <template #item.codZonaTerritorialResidencia="{ item, index }">
                  <div class="text-center">
                    <AppSelectRemote v-model="item.codZonaTerritorialResidencia" url="/selectInfiniteZonaVersion2"
                      arrayInfo="zonaVersion2s" clearable :params="paramsSelectInfinite"
                      :itemsData="zonaVersion2s_arrayInfo" :firstFetch="false">
                    </AppSelectRemote>
                  </div>
                </template>

                <template #item.incapacidad="{ item, index }">
                  <div class="text-center">
                      <AppSelect v-model="item.incapacidad" :items="['Si', 'No']" clearable />
                  </div>
                </template>

                <template #item.codPaisOrigen="{ item, index }">
                  <div class="text-center">
                    <AppSelectRemote v-model="item.codPaisOrigen" url="/selectInfinitePais" arrayInfo="paises" clearable
                      :params="paramsSelectInfinite" :itemsData="paises_arrayInfo" :firstFetch="false">
                    </AppSelectRemote>
                  </div>
                </template>

                <template #item.actions="{ item, index }">
                  <IconBtn density="compact">
                    <VIcon icon="tabler-dots-vertical" />
                    <VMenu activator="parent">
                      <VList>
                        <VListItem v-if="item.id" @click="
                          goView(item.id, item.numDocumentoIdentificacion)
                          ">
                          <VIcon icon="tabler-files" start></VIcon>Gestionar
                          Servicios
                        </VListItem>
                        <VListItem @click="openModalQuestionDelete(index)">
                          <VIcon icon="tabler-trash" start />Eliminar
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
          Regresar a facturas
        </VBtn>
        <VBtn :disabled="loading.table" :loading="loading.table" @click="openModalQuestionSave()" color="primary">
          Guardar usuarios
          <VIcon end icon="tabler-device-floppy" />
        </VBtn>
      </VCardText>
    </VCard>

    <ModalQuestion ref="refModalQuestion" @success="saveData()" />
    <ModalQuestion ref="refModalQuestionDelete" @success="deleteData(indexDelete)" />
  </div>
</template>
