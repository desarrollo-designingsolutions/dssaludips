<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import ModalUploadZip from '@/pages/Rips/Components/ModalUploadZip.vue';
import ModalUploadExcel from '@/pages/Rips/Components/ModalUploadExcel.vue';
import { router } from "@/plugins/1.router";

definePage({
  name: "Rips-Index",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "rips.index",
  },
});

const authenticationStore = useAuthenticationStore();
const loading = reactive({ downloadFile: false })

//TABLE
const refTableFull = ref()

const optionsTable = {
  url: "/rip/paginate",
  paramsGlobal: {
    company_id: authenticationStore.company.id,
  },
  headers: [ 
    { key: 'type', title: 'Tipo' },
    { key: 'numInvoices', title: 'Facturas' },
    { key: 'successfulInvoices', title: 'Data Completa' },
    { key: 'failedInvoices', title: 'Data Incompleta' }, 
    { key: 'created_at', title: 'Fecha de creación' }, 
    { key: 'status', title: 'Estado' },
    { key: 'actions', title: 'Acciones', sortable: false },
  ],
}

//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo', filing_invoice_pre_radicated_count: 'Facturas preradicadas' }
})
 

const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};


//ModalUploadZip
const refModalUploadZip = ref()
const openModalUploadZip = () => {
  refModalUploadZip.value.openModal()
}

const goView = (item: any) => {
  router.push({ name: "Rips-ListInvoices", params: { id: item.id } })
}

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
    api = `/rip/downloadExcel/${obj.id}`
    ext = "xlsx"
    nameFile = `Invoice_${obj.id}_${formattedDate}`
  } else {
    api = `/rip/downloadJson/${obj.id}`
    ext = "json"
    nameFile = `Invoice_${obj.id}_${formattedDate}`
  }
  
  await downloadBlob(api, nameFile, ext)

  loading.downloadFile = false;
};
 
 //ModalUploadExcel
const refModalUploadExcel = ref()
const openModalUploadExcel = (item: any) => {
  refModalUploadExcel.value.openModal(null, item)
}

const echoChannel = () => {
  refTableFull.value.options.tableData.forEach(element => {
    window.Echo.channel(`rip.${element.id}`)
      .listen('.RipRowUpdatedNow', (event: any) => {
        element.status = event.status
        element.status_backgroundColor = event.status_backgroundColor
        element.status_description = event.status_description
      });
  });
}

</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Lista de rips
        </span>
        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VMenu location="bottom">
            <template #activator="{ props }">
              <VBtn v-bind="props" append-icon="tabler-circle-chevrons-down">
                Agregar rips
              </VBtn>
            </template>

            <VList>
              <VListItem @click="openModalUploadZip()">
                Añadir ZIP
              </VListItem> 
            </VList>
          </VMenu>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialog :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialog>
      </VCardText>

      <VCardText>
        <TableFull ref="refTableFull" :options="optionsTable" @update:loading="tableLoading = $event" @dataFetched="echoChannel">
          <template #item.type="{ item }">
            <div>
              <VChip>{{ item.type_description }}</VChip>
            </div>
          </template>

          <template #item.successfulInvoices="{ item }">
              <VChip color="success">
                <span>{{ item.successfulInvoices }}</span>
              </VChip>
          </template>

          <template #item.failedInvoices="{ item }">
              <VChip color="error">
                <span>{{ item.failedInvoices }}</span>
              </VChip>
          </template>

          <template #item.status="{ item }">
            <div>
              <VChip :color="item.status_backgroundColor">{{ item.status_description }}</VChip>
            </div>
          </template>

          <template #item.actions="{ item }">
            <div>
              <VBtn icon color="primary">
                <VIcon icon="tabler-square-rounded-chevron-down"></VIcon>
                <VMenu activator="parent">
                  <VList>
                    <VListItem v-if="item.path_json" @click="downloadFileData(item, 'json')">Descargar Json
                    </VListItem> 
                    <VListItem v-if="item.status == 'RIP_STATUS_001'" @click="downloadFileData(item, 'excel')">Descargar Excel
                    </VListItem>
                    <VListItem v-if="item.status == 'RIP_STATUS_001'" @click="openModalUploadExcel(item)">Subir
                      Excel</VListItem>
                    <VListItem @click="goView(item)">
                      Ingresar
                    </VListItem>
                  </VList>
                </VMenu>
              </VBtn>
            </div>
          </template>
        </TableFull>
      </VCardText>
    </VCard>  

    <ModalUploadZip ref="refModalUploadZip" :maxFileSizeMB="200" />

    <ModalUploadExcel ref="refModalUploadExcel" :maxFileSizeMB="200" />


  </div>
</template>
