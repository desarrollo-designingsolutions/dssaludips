<script setup lang="ts">
import ModalListAnswer from "@/pages/Answer/Components/ModalList.vue";
import ModalForm from "@/pages/Glosa/Components/ModalForm.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

const props = defineProps({
  service_id: {
    type: String,
  },
  total_value: {
    type: String,
  },
  showBtnsView: {
    type: Boolean,
    default: true
  },
  invoice_id: {
    type: String,
  },
})

//TABLE
const refTableFull = ref()

const optionsTable = ref({
  url: "/glosa/paginate",
  headers: [
    { key: 'code_glosa_description', title: 'Código de glosa', width: 400 },
    { key: 'glosa_value', title: 'Valor', width: 200 },
    { key: 'date', title: 'Fecha', width: 200 },
    { key: 'observation', title: 'Observación', width: 200 },
    { key: 'actions', title: 'Acciones', width: 50, sortable: false },
  ],
  paramsGlobal: {
    service_id: props.service_id
  },
  actions: {
    delete: {
      url: '/glosa/delete',
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
  refModalForm.value.openModal({ service_id: props.service_id, total_value: props.total_value, invoice_id: props.invoice_id })
}

const openModalFormEdit = async (data: any) => {
  refModalForm.value.openModal({ service_id: props.service_id, id: data.id, total_value: props.total_value, invoice_id: props.invoice_id })
}

const openModalFormView = async (data: any) => {
  refModalForm.value.openModal({ service_id: props.service_id, id: data.id, total_value: props.total_value, invoice_id: props.invoice_id }, true)
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

//ModalListAnswer
const refModalListAnswer = ref()

const openModalListAnswer = (data: any) => {
  refModalListAnswer.value.openModal({
    ...data,
  })
}

</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Glosas
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn v-if="showBtnsView" @click="openModalFormCreate()">
            Crear Glosa
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

          <template #item.actions2="{ item }">

            <VListItem @click="openModalListAnswer(item)">
              <template #prepend>
                <VIcon size="22" icon="tabler-square-rounded-arrow-right" />
              </template>
              <span>Listado Respuestas</span>
            </VListItem>
          </template>

        </TableFull>

      </VCardText>
    </VCard>
    <ModalForm ref="refModalForm" @execute="handleForceSearch"></ModalForm>

    <ModalListAnswer ref="refModalListAnswer"></ModalListAnswer>
  </div>
</template>
