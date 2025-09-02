<script setup lang="ts">
import ModalFormMasiveGlosa from "@/pages/Glosa/Components/ModalFormMasive.vue";
import ModalListGlosa from "@/pages/Glosa/Components/ModalList.vue";
import DataUrgeHosBorn from "@/pages/Service/Components/DataUrgeHosBorn.vue";
import ModalFormHospitalization from "@/pages/Service/Components/ModalFormHospitalization.vue";
import ModalFormMedicalConsultation from "@/pages/Service/Components/ModalFormMedicalConsultation.vue";
import ModalFormMedicine from "@/pages/Service/Components/ModalFormMedicine.vue";
import ModalFormNewlyBorn from "@/pages/Service/Components/ModalFormNewlyBorn.vue";
import ModalFormOtherService from "@/pages/Service/Components/ModalFormOtherService.vue";
import ModalFormProcedure from "@/pages/Service/Components/ModalFormProcedure.vue";
import ModalFormUrgency from "@/pages/Service/Components/ModalFormUrgency.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const { toast } = useToast();

const props = defineProps<{
  invoice_id: string,
}>()

const loading = reactive({
  btnCreate: false,
  excel: false,
})

const invoice_id = props.invoice_id;
const authenticationStore = useAuthenticationStore();
const servicesIds = ref<Array<string>>([]);

//FILTER
const refFilterDialog = ref()

const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo' }
})

//TABLE
const refTableFull = ref()

const optionsTable = {
  url: `/service/paginate`,
  headers: [
    { key: 'codigo_servicio', title: 'Código' },
    { key: 'nombre_servicio', title: 'Descripcion' },
    { key: 'type', title: 'Tipo' },
    { key: 'quantity', title: 'Cantidad' },
    { key: "unit_value", title: 'Valor Unitario' },
    { key: "total_value", title: 'Valor Total' },
    { key: 'actions', title: 'Acciones', sortable: false },
  ],
  showSelect: true,
  paramsGlobal: {
    company_id: authenticationStore.company.id,
    user_id: authenticationStore.user.id,
    invoice_id: invoice_id,
  },
  actions: {
    delete: {
      url: '/service/delete',
    },
  }
}


const tableLoading = ref(false); // Estado de carga de la tabla

// Nueva prop para controlar si se actualiza la URL
const disableUrlUpdate = ref(true);

// Nuevo método para manejar la búsqueda forzada desde el filtro
const handleForceSearch = (params) => {
  if (refTableFull.value) {
    fetchDataBtn();
    // Si disableUrlUpdate está activo, pasamos los parámetros manualmente
    if (disableUrlUpdate.value && params) {
      refTableFull.value.fetchTableData(null, false, true, params);
    } else {
      refTableFull.value.fetchTableData(1, false, true);
    }
  }
};


//ModalListGlosa
const refModalListGlosa = ref()

const openModalListGlosa = (data: any) => {
  refModalListGlosa.value.openModal({
    ...data,
    invoice_id: invoice_id,
  })
}

//ModalFormMasiveGlosa
const refModalFormMasiveGlosa = ref()

const openModalFormMasiveGlosa = () => {
  if (servicesIds.value.length > 0) {
    refModalFormMasiveGlosa.value.openModal(servicesIds.value)
  } else {
    toast("Debe seleccionar almenos un servicio", "", "info")
  }
}

const typeServiceEnumValues = ref<Array<{
  type: string,
  name: string,
}>>([]);

const fetchDataBtn = async () => {
  loading.btnCreate = true

  const { data, response } = await useAxios("/service/loadBtnCreate").get({
    params: {
      invoice_id: props.invoice_id
    }
  });

  if (response.status == 200 && data) {
    typeServiceEnumValues.value = data.typeServiceEnumValues;
  }
  loading.btnCreate = false
}


//Modales se los tipos de servicios
const refModalFormOtherService = ref()
const refModalFormMedicalConsultation = ref()
const refModalFormUrgency = ref()
const refModalFormProcedure = ref()
const refModalFormMedicine = ref()
const refModalFormNewlyBorn = ref()
const refModalFormHospitalization = ref()

// Mapeo entre los tipos de servicio y sus referencias a modales
const serviceModalMap = {
  "SERVICE_TYPE_001": refModalFormMedicalConsultation,
  "SERVICE_TYPE_002": refModalFormProcedure,
  "SERVICE_TYPE_003": refModalFormUrgency,
  "SERVICE_TYPE_004": refModalFormHospitalization,
  "SERVICE_TYPE_005": refModalFormNewlyBorn,
  "SERVICE_TYPE_006": refModalFormMedicine,
  "SERVICE_TYPE_007": refModalFormOtherService,
}

// Función auxiliar genérica para abrir modal con parámetros
const openServiceModal = (type: string, params: Record<string, any> = {}, isViewMode: boolean = false) => {
  const modalRef = serviceModalMap[type]?.value

  if (modalRef && typeof modalRef.openModal === 'function') {
    modalRef.openModal(params, isViewMode)
  } else {
    console.warn(`No se encontró un modal para el tipo de servicio: ${type}`)
  }
}

// Funciones específicas usando la función genérica

const openModalFormServiceCreate = (type: string) => {
  openServiceModal(type, { invoice_id: invoice_id })
}

const openModalFormServiceEdit = (data: any) => {
  openServiceModal(data.type, { serviceId: data.id, invoice_id: invoice_id })
}

const openModalFormServiceView = (data: any) => {
  openServiceModal(data.type, { serviceId: data.id, invoice_id: invoice_id }, true)
}

onMounted(() => {
  fetchDataBtn()
})



</script>

<template>
  <div>
    <DataUrgeHosBorn class="mb-3" :invoice_id="invoice_id" />
    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Servicios
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <!-- <VBtn class="me-2 mb-2" @click="openModalFormMasiveGlosa">
            <template #prepend>
              <VIcon start icon="tabler-folder" />
            </template>
Glosa Masiva
</VBtn> -->

          <VBtn color="primary" append-icon="tabler-chevron-down" :loading="loading.btnCreate"
            :disabled="loading.btnCreate">
            Crear Servicio
            <VMenu activator="parent">
              <VList>
                <VListItem v-for="(item, index) in typeServiceEnumValues" :key="index"
                  @click="openModalFormServiceCreate(item.type)">
                  <template #prepend>
                    <VIcon start :icon="item.icon" />
                  </template>
                  {{ item.name }}
                </VListItem>
              </VList>
            </VMenu>
          </VBtn>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialog ref="refFilterDialog" :options-filter="optionsFilter" @force-search="handleForceSearch"
          :table-loading="tableLoading">
        </FilterDialog>
      </VCardText>

      <VCardText class="mt-2">

        <TableFull v-model:selected="servicesIds" ref="refTableFull" :options="optionsTable"
          @update:loading="tableLoading = $event" @edit="openModalFormServiceEdit" @view="openModalFormServiceView">

          <template #item.type="{ item }">
            <div>
              <VChip>{{ item.type_description }}</VChip>
            </div>
          </template>

          <template #item.actions2="{ item }">

            <VListItem @click="openModalListGlosa(item)">
              <template #prepend>
                <VIcon size="22" icon="tabler-square-rounded-arrow-right" />
              </template>
              <span>Listado Glosas</span>
            </VListItem>
          </template>

        </TableFull>
      </VCardText>
    </VCard>


    <ModalFormOtherService ref="refModalFormOtherService" @execute="handleForceSearch"></ModalFormOtherService>

    <ModalFormMedicalConsultation ref="refModalFormMedicalConsultation" @execute="handleForceSearch">
    </ModalFormMedicalConsultation>
    <ModalFormUrgency ref="refModalFormUrgency" @execute="handleForceSearch">
    </ModalFormUrgency>
    <ModalFormHospitalization ref="refModalFormHospitalization" @execute="handleForceSearch" />

    <ModalFormProcedure ref="refModalFormProcedure" @execute="handleForceSearch">
    </ModalFormProcedure>

    <ModalFormMedicine ref="refModalFormMedicine" @execute="handleForceSearch">
    </ModalFormMedicine>

    <ModalFormNewlyBorn ref="refModalFormNewlyBorn" @execute="handleForceSearch">
    </ModalFormNewlyBorn>

    <ModalListGlosa ref="refModalListGlosa"></ModalListGlosa>

    <ModalFormMasiveGlosa ref="refModalFormMasiveGlosa"></ModalFormMasiveGlosa>
  </div>
</template>
