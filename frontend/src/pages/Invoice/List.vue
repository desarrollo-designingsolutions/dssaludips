<script setup lang="ts">
import ModalUploadFileJson from "@/pages/Invoice/Components/ModalUploadFileJson.vue";
import ModalUploadFileXml from "@/pages/Invoice/Components/ModalUploadFileXml.vue";
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

definePage({
  name: "Invoice-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "menu.invoice",
  },
});

const loading = reactive({ excel: false, json: false, zip: false })
const route = useRoute()

const authenticationStore = useAuthenticationStore();

//FILTER
const optionsFilter = ref({
  dialog: {
    cols: "12",
    width: 500,
    inputs: [
      {
        name: "status",
        label: "Estado",
        type: "selectApi",
        value: null,
        multiple: true,
        url: "/selectStatusInvoiceEnum",
        arrayInfo: "statusInvoiceEnum",
      },
      {
        type: "dateRange",
        label: "Fecha Radicación",
        name: "invoices.radication_date",
      },
    ],
  },
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

const loadingItems = reactive({});


//TABLE
const refTableFull = ref()
const optionsTable = {
  url: "/invoice/paginate",
  paramsGlobal: {
    company_id: authenticationStore.company.id,
  },
  headers: [
    { key: 'entity_name', title: 'Entidad', width: '150px', minWidth: '100px' },
    { key: 'invoice_number', title: 'No Factura', width: '200px', minWidth: '150px' },
    { key: 'type', title: 'Tipo Factura', width: '200px', minWidth: '150px' },
    { key: "value_paid", title: 'Valor Pagado', width: '250px', minWidth: '200px' },
    { key: "value_glosa", title: 'Valor Glosa', width: '250px', minWidth: '200px' },
    { key: "radication_date", title: 'Fecha Radicación', width: '250px', minWidth: '200px' },
    { key: "patient_name", title: 'Paciente', width: '250px', minWidth: '200px' },
    { key: "status", title: 'Estado' },
    { key: "status_xml", title: 'Estado XML', width: '200px', minWidth: '150px' },
    { key: 'actions', title: 'Acciones', sortable: false },
  ],
  actions: {
    changeStatus: {
      url: "/invoice/changeStatus"
    },
    view: {
      show: true,
    },
    delete: {
      url: "/invoice/delete",
      show: true
    },
  }
}

const goViewEdit = (data: any) => {
  router.push({ name: "Invoice-Form", params: { action: "edit", id: data.id } })
}

const goViewCreate = () => {
  router.push({ name: "Invoice-Form", params: { action: "create" } })
}

const goViewView = (data: any) => {
  router.push({ name: "Invoice-Form", params: { action: "view", id: data.id } })
}

const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(1, false, true); // Forzamos la búsqueda
  }
};

const downloadExcel = async () => {
  loading.excel = true;

  const { data, response } = await useAxios("/invoice/excelExport").get({
    params: {
      ...route.query,
      company_id: authenticationStore.company.id
    }
  })

  loading.excel = false;

  if (response.status == 200 && data) {
    downloadExcelBase64(data.excel, "Lista de Facturas")
  }
}

onMounted(() => {
})


//descarga de JSON
const downloadJson = async (id: string, item: string) => {
  try {
    loadingItems[id] = true;

    const api = `/invoice/downloadJson/${id}`
    const nameFile = `${item.serviceVendor_nit + '-' + id}`
    const ext = "json"

    await downloadBlob(api, nameFile, ext)


  } catch (error) {
    console.error('Error al descargar el archivo:', error);
  } finally {
    loadingItems[id] = false;

  }
};

const echoChannel = () => {

  refTableFull.value.options.tableData.forEach(element => {
    window.Echo.channel(`invoice.${element.id}`)
      .listen('InvoiceRowUpdatedNow', (event: any) => {

        element.status_xml = event.status_xml
        element.status_xml_backgroundColor = event.status_xml_backgroundColor
        element.status_xml_description = event.status_xml_description

        element.path_xml = event.path_xml

      });
  });
}

//ModalUploadFileXml
const refModalUploadFileXml = ref()

const openModalUploadFileXml = (item: any) => {
  refModalUploadFileXml.value.openModal(item)
}

//descarga de XML
const downloadFileData = async (file: any) => {
  const filePath = storageBack(file);
  descargarArchivo(filePath);
};


//descarga de ZIP
const downloadZip = async (id: string) => {
  try {
    loadingItems[id] = true;

    const api = `/invoice/downloadZip/${id}`
    const nameFile = `factura_${id}`
    const ext = "zip"

    await downloadBlob(api, nameFile, ext)


  } catch (error) {
    console.error('Error al descargar el archivo:', error);
  } finally {
    loadingItems[id] = false;

  }
};


// Computed que verifica si al menos uno de los valores es true
const isLoading = computed(() => {
  return Object.values(loading).some(value => value);
});


//ModalUploadFileJson
const refModalUploadFileJson = ref()

const openModalUploadFileJson = () => {
  refModalUploadFileJson.value.openModal()
}

const goViewFurips1 = (data: any) => {
  router.push({ name: "Invoice-Furips1", params: { action: data.furips1_id ? 'view' : 'create', invoice_id: data.id, id: data.furips1_id } })
}
const goViewFurips2 = (data: any) => {
  router.push({ name: "Invoice-Furips2", params: { action: data.furips2_id ? 'view' : 'create', invoice_id: data.id, id: data.furips2_id } })
}
const goViewFurtran = (data: any) => {
  router.push({ name: "Invoice-Furtran", params: { action: data.furtran_id ? 'view' : 'create', invoice_id: data.id, id: data.furtran_id } })
}

</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Gestión de Facturación y Auditoría
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn :loading="loading.excel" :disabled="loading.excel" size="38" color="primary" icon
            @click="downloadExcel()">
            <VIcon icon="tabler-file-spreadsheet"></VIcon>
            <VTooltip location="top" transition="scale-transition" activator="parent" text="Descargar Excel">
            </VTooltip>
          </VBtn>

          <VBtn color="primary" @click="goViewCreate()">
            Registrar Factura
          </VBtn>
          <VBtn color="primary" @click="openModalUploadFileJson()">
            Cargar Json
          </VBtn>
        </div>
      </VCardTitle>

      <!-- Sección de filtros mejorada -->
      <VCardText>
        <FilterDialog :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialog>
      </VCardText>

      <VCardText class="mt-2">

        <TableFull ref="refTableFull" :options="optionsTable" @edit="goViewEdit" @view="goViewView"
          @update:loading="tableLoading = $event" @dataFetched="echoChannel">


          <template #item.type="{ item }">
            <div>
              <VChip>{{ item.type_description }}</VChip>
            </div>
          </template>
          <template #item.status="{ item }">
            <div>
              <VChip>{{ item.status_description }}</VChip>
            </div>
          </template>

          <template #item.status_xml="{ item }">
            <div>
              <VChip :color="item.status_xml_backgroundColor">{{ item.status_xml_description }}</VChip>
            </div>
          </template>

          <template #item.actions="{ item }">
            <VMenu>
              <template #activator="{ props }">
                <VBtn color="primary" v-bind="props" :loading="loadingItems[item.id]" :disabled="loadingItems[item.id]"
                  append-icon="tabler-chevron-down">
                  Acciones
                </VBtn>
              </template>
              <VList>
                <VListItem @click="goViewView(item)">
                  <template #prepend>
                    <VIcon icon="tabler-eye" />
                  </template>
                  <span>Ver</span>
                </VListItem>
                <VListItem @click="goViewEdit(item)">
                  <template #prepend>
                    <VIcon icon="tabler-pencil" />
                  </template>
                  <span>Editar</span>
                </VListItem>
                <VListItem @click="refTableFull.openDeleteModal(item.id)">
                  <template #prepend>
                    <VIcon icon="tabler-trash" />
                  </template>
                  <span>Eliminar</span>
                </VListItem>
                <VListItem @click="downloadJson(item.id, item)">
                  <template #prepend>
                    <VIcon icon="tabler-json"></VIcon>
                  </template>
                  Descargar Json
                </VListItem>
                <VListItem v-if="item.status_xml == 'INVOICE_STATUS_XML_003'" @click="downloadFileData(item.path_xml)">
                  <template #prepend>
                    <VIcon icon="tabler-download"></VIcon>
                  </template>
                  Descargar XML
                </VListItem>
                <VListItem v-if="item.status_xml == 'INVOICE_STATUS_XML_003'" @click="downloadZip(item.id)">
                  <template #prepend>
                    <VIcon icon="tabler-zip"></VIcon>
                  </template>
                  Descargar Carpeta
                </VListItem>
                <VListItem v-if="item.status_xml != 'INVOICE_STATUS_XML_003'" @click="openModalUploadFileXml(item)">
                  <template #prepend>
                    <VIcon icon="tabler-upload"></VIcon>
                  </template>
                  Subir XML
                </VListItem>
                <VListItem v-if="item.type == 'INVOICE_TYPE_002'" @click="() => { }">

                  <VMenu>
                    <template #activator="{ props }">
                      <div class="flex items-center w-full" v-bind="props" @click.stop>
                        <VIcon icon="tabler-clipboard" class="mr-2"></VIcon>
                        <span>Anexos</span>
                        <VIcon icon="tabler-chevron-right" class="ml-auto" />
                      </div>
                    </template>
                    <VList>
                      <VListItem @click="goViewFurips1(item)">
                        <template #prepend>
                          <VIcon icon="tabler-file" />
                        </template>
                        <span>FURIPS-1</span>
                      </VListItem>
                      <VListItem @click="goViewFurips2(item)">
                        <template #prepend>
                          <VIcon icon="tabler-file" />
                        </template>
                        <span>FURIPS-2</span>
                      </VListItem>
                      <VListItem @click="goViewFurtran(item)">
                        <template #prepend>
                          <VIcon icon="tabler-file" />
                        </template>
                        <span>FURTRAN</span>
                      </VListItem>
                    </VList>
                  </VMenu>

                </VListItem>
              </VList>
            </VMenu>
          </template>
        </TableFull>

      </VCardText>
    </VCard>
    <VDivider />

    <ModalUploadFileXml ref="refModalUploadFileXml" />
    <ModalUploadFileJson ref="refModalUploadFileJson" />



  </div>
</template>
