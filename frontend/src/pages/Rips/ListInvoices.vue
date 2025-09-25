<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import ModalUploadExcel from '@/pages/Rips/Components/ModalUploadExcel.vue';
import ModalUploadXml from '@/pages/Rips/Components/ModalUploadXml.vue';
import ModalValidateRips from '@/pages/Rips/Components/ModalValidateRips.vue';
const { toast } = useToast();

definePage({
  path: "Rips/ListInvoices/:id",
  name: "Rips-ListInvoices",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "rips.index",

  },
});

const route = useRoute()
const authenticationStore = useAuthenticationStore();
const loading = reactive({ downloadFile: false, finishRips: false })
const rip_id = route.params.id;

//TABLE
const refTableFull = ref()

const optionsTable = {
  showSelect: true,
  url: "/ripInvoice/paginate",
  paramsGlobal: {
    company_id: authenticationStore.company.id,
    rip_id: rip_id,
  },
  headers: [
    { key: 'invoice_number', title: 'Número de factura' },
    { key: 'count_users', title: 'Cant. Usuarios' },
    { key: 'sumVr', title: 'Valor' },
    { key: 'status', title: 'Estado' },
    { key: 'status_xml', title: 'Estado XML' },
    { key: 'actions', title: 'Acciones', sortable: false },
  ],
  actions: {
    delete: {
      url: "/rip/delete",
    },
  }
}

//FILTER
const filterTable = ref()
const optionsFilter = ref({
  dialog: {
    width: 500,
    cols: '6',
    inputs: [],
  },
  filterLabels: { inputGeneral: 'Buscar en todo' }

})

//descarga de archivos
const downloadFileData = async (obj: any, type: string) => {
  loading.downloadFile = true;

  const today = new Date();
  const day = String(today.getDate()).padStart(2, '0');
  const month = String(today.getMonth() + 1).padStart(2, '0'); // Meses van de 0 a 11
  const year = today.getFullYear();
  const formattedDate = `${day}${month}${year}`; // ddmmyyyy

  let api = ""
  let ext = ""
  let nameFile = ""

  if (type === "excel") {
    api = `/ripInvoice/downloadExcel/${obj.id}`
    ext = "xlsx"
    nameFile = `Invoice_${obj.invoice_number}_${formattedDate}`
  } else if (type === "json") {
    api = `/ripInvoice/downloadJson/${obj.id}`
    ext = "json"
    nameFile = `Invoice_${obj.invoice_number}_${formattedDate}`
  } else if (type === "xml") {
    api = `/ripInvoice/downloadXml/${obj.id}`
    ext = "xml"
    nameFile = `Invoice_${obj.invoice_number}_${formattedDate}`
  }

  await downloadBlob(api, nameFile, ext)

  loading.downloadFile = false;
};



const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};

//ModalUploadXml
const refModalUploadXml = ref()
const openModalUploadXml = (item: any) => {
  refModalUploadXml.value.openModal(item)
}

//ModalUploadExcel
const refModalUploadExcel = ref()
const openModalUploadExcel = (item: any) => {
  refModalUploadExcel.value.openModal(item, null)
}

const echoChannel = () => {
  refTableFull.value.options.tableData.forEach(element => {
    window.Echo.channel(`rip_invoice.${element.id}`)
      .listen('.RipInvoiceRowUpdatedNow', (event: any) => {
        element.status = event.status
        element.status_backgroundColor = event.status_backgroundColor
        element.status_description = event.status_description

        element.status_xml = event.status_xml
        element.status_xml_backgroundColor = event.status_xml_backgroundColor
        element.status_xml_description = event.status_xml_description

        element.path_xml = event.path_xml
      })
      .listen('RipValidationStatusUpdated', (event: any) => {
        // console.log("event", event);

        element.status = event.status
        element.status_backgroundColor = event.status_backgroundColor
        element.status_description = event.status_description

      });
  });
}

const invoicesIds = ref<Array<string>>([]);


//ModalQuestion
const refModalQuestion = ref()

const finishRips = async () => {
  if (invoicesIds.value.length === 0) {
    toast("Debes seleccionar al menos una factura", "", "info")
    return
  }
  loading.finishRips = true
  try {
    const { data, response } = await useAxios(`/ripInvoice/getCountRipInvoicestoValidate`).post({
      invoices_ids: invoicesIds.value,
    })


    if (response.status === 200 && data) {
      const countRipInvoicesWithoutXml = data.countRipInvoicesWithoutXml ?? 0
      const totalInvoices = data.totalInvoices ?? 0
      if (countRipInvoicesWithoutXml > 0) {
        refModalQuestion.value.componentData.isDialogVisible = true
        refModalQuestion.value.componentData.principalIcon = 'tabler-help-hexagon'
        refModalQuestion.value.componentData.btnSuccessText = 'Aceptar'
        refModalQuestion.value.componentData.title = `¿Desea validar ${totalInvoices} facturas?`
        refModalQuestion.value.componentData.subTitle = `Tenga en cuenta que tiene ${countRipInvoicesWithoutXml} factura sin xml valido, y solo se podran validar las que cumplan con todos los requisitos`

      } else {
        // Caso en que no hay facturas sin XML
        refModalQuestion.value.componentData.isDialogVisible = true
        refModalQuestion.value.componentData.principalIcon = 'tabler-help-hexagon'
        refModalQuestion.value.componentData.btnSuccessText = 'Aceptar'
        refModalQuestion.value.componentData.title = `¿Desea validar ${totalInvoices} facturas?`
        refModalQuestion.value.componentData.subTitle = `Todas las facturas seleccionadas tienen soporte XML.`

      }
    }
  } catch (error) {
    toast("Error al verificar las facturas", "", "error")
  } finally {
    loading.finishRips = false
  }
}

const validateRips = async () => {
  const invoicesWithXml = refTableFull.value.options.tableData
    .filter(element =>
      invoicesIds.value.includes(element.id) && element.path_xml
    )
    .map(element => element.id);


  if (invoicesWithXml.length == 0) { 
    toast("No se pueden enviar a validar ya que no tienen XML", "", "info")
    return
  }

  try {


    const { data, response } = await useAxios('/rip/validateRips').post({
      ids: invoicesWithXml, 
    });

    if (response.status === 200 && data) {
      toast('Validación iniciada correctamente', '', 'success');
    } else {
      toast('Error al validar facturas: ' + (data?.message || 'Error desconocido'), '', 'danger');

    }
  } catch (error) {
    console.error(error);


  } finally {
    loading.value = false;
  }
};


//ModalValidateRips
const refModalValidateRips = ref()
const openModalValidateRips = (item: any) => {
  refModalValidateRips.value.openModal([item.id]);
};

</script>

<template>
  <div>
    <!-- <CountAllDataInvoices ref="refCountAllDataInvoices" :rip_id="rip_id" /> -->

    <VCard class="mt-5">
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Lista de Facturas
        </span>
        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn @click="finishRips">
            <template #prepend>
              <VIcon icon="tabler-circle-plus"></VIcon>
            </template>
            Validar con el ministerio
          </VBtn>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialog :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialog>
      </VCardText>

      <VCardText class=" mt-2">
        <TableFull v-model:selected="invoicesIds" ref="refTableFull" :options="optionsTable"
          @update:loading="tableLoading = $event" @dataFetched="echoChannel">

          <template #item.actions="{ item }">
            <div>
              <VBtn icon color="primary">
                <VIcon icon="tabler-square-rounded-chevron-down"></VIcon>
                <VMenu activator="parent">
                  <VList>
                    <VListItem v-if="item.path_json" @click="downloadFileData(item, 'json')">Descargar Json
                    </VListItem>
                    <VListItem v-if="item.status == 'RIP_INVOICE_STATUS_001'" @click="downloadFileData(item, 'excel')">
                      Descargar Excel
                    </VListItem>
                    <VListItem v-if="item.status == 'RIP_INVOICE_STATUS_001'" @click="openModalUploadExcel(item)">Subir
                      Excel</VListItem>
                    <VListItem v-if="!item.path_xml" @click="openModalUploadXml(item)">Subir XML</VListItem>
                    <VListItem v-if="item.path_xml" @click="downloadFileData(item, 'xml')">Descargar XML</VListItem>
                    <VListItem @click="openModalValidateRips(item)">Ver inconsistencias
                    </VListItem>
                  </VList>
                </VMenu>
              </VBtn>
            </div>
          </template>

          <template #item.status="{ item }">
            <div>
              <VChip :color="item.status_backgroundColor">{{ item.status_description }}
              </VChip>
            </div>
          </template>
          <template #item.status_xml="{ item }">
            <div>
              <VChip :color="item.status_xml_backgroundColor">{{ item.status_xml_description }}</VChip>
            </div>
          </template>

        </TableFull>
      </VCardText>
    </VCard>

    <ModalQuestion ref="refModalQuestion" @success="validateRips()" />

    <ModalUploadXml ref="refModalUploadXml" :maxFileSizeMB="200" />

    <ModalUploadExcel ref="refModalUploadExcel" :maxFileSizeMB="200" />

    <ModalValidateRips ref="refModalValidateRips" />

  </div>
</template>
