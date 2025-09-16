<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

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
const loading = reactive({ downloadFile: false })
const rip_id = route.params.id;

//TABLE
const refTableFull = ref()

const optionsTable = {
  url: "/ripInvoice/paginate",
  paramsGlobal: {
    company_id: authenticationStore.company.id,
    rip_id: rip_id,
  },
  headers: [
    { key: 'invoice_number', title: 'Número de factura' },
    { key: 'users_count', title: 'Cant. Usuarios' },
    { key: 'files_count', title: 'Soportes Cargados' },
    { key: 'case_number', title: 'N° Radicado' },
    { key: 'sumVr', title: 'Valor' },
    { key: "status", title: 'Estado' },
    { key: "date", title: 'Fecha' },
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

  if(type === "excel"){
    api = `/ripInvoice/downloadExcel/${obj.id}`
    ext = "xlsx"
    nameFile = `Invoice_${obj.invoice_number}_${formattedDate}.xlsx`
  } else {
    api = `/ripInvoice/downloadJson/${obj.id}`
    ext = "json"
    nameFile = `Invoice_${obj.invoice_number}_${formattedDate}.json`
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

</script>

<template>
  <div>
    <!-- <CountAllDataInvoices ref="refCountAllDataInvoices" :rip_id="rip_id" /> -->

    <VCard class="mt-5">
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Lista de Facturas
        </span>
      </VCardTitle>

      <VCardText>
        <FilterDialog :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialog>
      </VCardText>

      <VCardText class=" mt-2">
        <TableFull ref="refTableFull" :options="optionsTable" @update:loading="tableLoading = $event">

          <template #item.actions="{ item }">
            <div>
              <VBtn icon color="primary">
                <VIcon icon="tabler-square-rounded-chevron-down"></VIcon>
                <VMenu activator="parent">
                  <VList>
                    <VListItem v-if="item.path_json" @click="downloadFileData(item, 'json')">Descargar Json
                    </VListItem>
                    <VListItem v-if="item.path_excel" @click="downloadFileData(item, 'excel')">Descargar Excel
                    </VListItem>
                    <VListItem @click="() => { }">Subir XML</VListItem>
                  </VList>
                </VMenu>
              </VBtn> 
            </div>
          </template>

          <template #item.status="{ item }">
            <div>
              <VChip :color="item.status_backgroundColor">{{ item.status_description }}</VChip>
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
  </div>
</template>
