<script setup lang="ts">
import ModalForm from "@/pages/InvoicePayment/Components/ModalForm.vue";

const props = defineProps({
  invoice_id: {
    type: String,
  },
  total: {
    type: String,
  },
  showBtnsView: {
    type: Boolean,
    default: true
  }
})

//TABLE
const refTableFull = ref()

const optionsTable = ref({
  url: "/invoicePayment/paginate",
  headers: [
    { key: 'value_paid', title: 'Valor pagado', width: 200 },
    { key: 'date_payment', title: 'Fecha', width: 200 },
    { key: 'observations', title: 'Observación', width: 200 },
    { key: 'actions', title: 'Acciones', width: 50, sortable: false },
  ],
  paramsGlobal: {
    invoice_id: props.invoice_id
  },
  actions: {
    delete: {
      url: '/invoicePayment/delete',
      show: props.showBtnsView // Asignar el valor de props.showBtnsView
    },
    edit: {
      show: props.showBtnsView // Asignar el valor de props.showBtnsView
    },
  }
})


//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

//ModalForm
const refModalForm = ref()

const openModalFormCreate = () => {
  refModalForm.value.openModal({ invoice_id: props.invoice_id, total: props.total })
}

const openModalFormEdit = async (data: any) => {
  refModalForm.value.openModal({ invoice_id: props.invoice_id, id: data.id, total: props.total })
}

const openModalFormView = async (data: any) => {
  refModalForm.value.openModal({ invoice_id: props.invoice_id, id: data.id, total: props.total }, true)
}

const tableLoading = ref(false); // Estado de carga de la tabla


// Nueva prop para controlar si se actualiza la URL
const disableUrlUpdate = ref(true);

// Nuevo método para manejar la búsqueda forzada desde el filtro
const handleForceSearch = (params) => {
  if (refTableFull.value) {
    // Si disableUrlUpdate está activo, pasamos los parámetros manualmente
    if (disableUrlUpdate.value && params) {
      refTableFull.value.fetchTableData(null, false, true, params);
    } else {
      refTableFull.value.fetchTableData(1, false, true);
    }
  }
};

</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Pagos
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn v-if="showBtnsView" @click="openModalFormCreate()">
            Crear Pago
          </VBtn>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialog :options-filter="optionsFilter" @force-search="handleForceSearch" :table-loading="tableLoading"
          :disable-url-update="disableUrlUpdate">
        </FilterDialog>
      </VCardText>

      <VCardText class="mt-2">
        <TableFull ref="refTableFull" :options="optionsTable" @edit="openModalFormEdit" @view="openModalFormView"
          @update:loading="tableLoading = $event" :disable-url-update="disableUrlUpdate">

        </TableFull>

      </VCardText>
    </VCard>
    <ModalForm ref="refModalForm" @execute="handleForceSearch"></ModalForm>
  </div>
</template>
