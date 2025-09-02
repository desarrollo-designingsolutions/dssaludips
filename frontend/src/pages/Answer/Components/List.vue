<script setup lang="ts">
import ModalForm from "@/pages/Answer/Components/ModalForm.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

const props = defineProps({
  glosa_id: {
    type: String,
  },
  total_value: {
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
  url: "/glosaAnswer/paginate",
  headers: [
    { key: 'value_approved', title: 'Valor aprobado', width: 400 },
    { key: 'value_accepted', title: 'Valor aceptado', width: 200 },
    { key: 'date_answer', title: 'Fecha', width: 200 },
    { key: 'observation', title: 'Observación', width: 200 },
    { key: 'status', title: 'Estado', width: 200 },
    { key: 'actions', title: 'Acciones', width: 50, sortable: false },
  ],
  paramsGlobal: {
    glosa_id: props.glosa_id
  },
  actions: {
    delete: {
      url: '/glosaAnswer/delete',
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
  refModalForm.value.openModal({ glosa_id: props.glosa_id, total_value: props.total_value })
}

const openModalFormEdit = async (data: any) => {
  refModalForm.value.openModal({ glosa_id: props.glosa_id, id: data.id, total_value: props.total_value })
}

const openModalFormView = async (data: any) => {
  refModalForm.value.openModal({ glosa_id: props.glosa_id, id: data.id, total_value: props.total_value }, true)
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
          Respuestas
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn v-if="showBtnsView" @click="openModalFormCreate()">
            Crear Respuesta
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

          <template #item.status="{ item }">
            <div>
              <VChip>{{ item.status_description }}</VChip>
            </div>
          </template>

        </TableFull>

      </VCardText>
    </VCard>
    <ModalForm ref="refModalForm" @execute="handleForceSearch"></ModalForm>
  </div>
</template>
