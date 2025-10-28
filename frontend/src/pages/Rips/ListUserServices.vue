<script setup lang="ts">
import Emergencies from "@/pages/Rips/ServicesView/Emergencies.vue";
import Hospitalization from "@/pages/Rips/ServicesView/Hospitalization.vue";
import Medicines from "@/pages/Rips/ServicesView/Medicines.vue";
import NewlyBorn from "@/pages/Rips/ServicesView/NewlyBorn.vue";
import OtherServices from "@/pages/Rips/ServicesView/OtherServices.vue";
import Procedure from "@/pages/Rips/ServicesView/Procedure.vue";
import Queries from "@/pages/Rips/ServicesView/Queries.vue";
import { useRipInvoiceUserStore } from "@/pages/Rips/Components/useRipInvoiceUserStore";
import { router } from "@/plugins/1.router";

definePage({
  path: "Invoice-ListUserServicesView/:id/:numFactura/:ripInvoiceUser_id",
  name: "Invoice-ListUserServicesView",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "menu.invoice",
  },
});

const route = useRoute();

const loading = ref(false);
const userData = ref([]);
const servicesCount = ref([]);

const fetchUsers = async (opts = {}) => {
  loading.value = true;

  try {
    const { data, response } = await useAxios(`/ripInvoiceService/getInfoUser/${route.params?.ripInvoiceUser_id}`).get()

    if (response.status == 200 && data) {
      userData.value = data.userData;
      servicesCount.value = data.servicesCount;
    }
  } catch (error) {
    console.error('Error al obtener informacion:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchUsers();
});

const currentTab = ref(0);

const goBack = () => {
  router.push({
    name: "Invoice-ListUsers",
    params: { invoice_id: route.params?.id },
  });
}

const optionsFilter = ref({
  dialog: {
    width: 500,
    cols: '6',
    inputs: [],
  },
  filterLabels: { inputGeneral: 'Buscar en todo' }

})

const refListQueries = ref()
const refListProcedure = ref()
const refListEmergencies = ref()
const refListHospitalization = ref()
const refListNewlyBorn = ref()
const refListMedicines = ref()
const refListOtherServices = ref()

// Método para refrescar los datos
const refreshAllTables = () => {
  if (refListQueries.value) {
    refListQueries.value.refreshTable(); // Forzamos la búsqueda
  }
  if (refListProcedure.value) {
    refListProcedure.value.refreshTable(); // Forzamos la búsqueda
  }
  if (refListEmergencies.value) {
    refListEmergencies.value.refreshTable(); // Forzamos la búsqueda
  }
  if (refListHospitalization.value) {
    refListHospitalization.value.refreshTable(); // Forzamos la búsqueda
  }
  if (refListNewlyBorn.value) {
    refListNewlyBorn.value.refreshTable(); // Forzamos la búsqueda
  }
  if (refListMedicines.value) {
    refListMedicines.value.refreshTable(); // Forzamos la búsqueda
  }
  if (refListOtherServices.value) {
    refListOtherServices.value.refreshTable(); // Forzamos la búsqueda
  }
};
</script>

<template>
  <div>
    <VCard class="mt-5" v-if="userData">
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Lista de servicios
        </span>

        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn icon @click="goBack">
            <VIcon icon="tabler-arrow-narrow-left" />
            <VTooltip location="top" transition="scale-transition" activator="parent" text="Regresar">
            </VTooltip>
          </VBtn>

          <!-- Cuadro estilizado -->
          <div class="info-box">
            <div class="info-row">
              <span class="info-title">Número de factura:</span>
              <span class="info-value"> {{ userData?.numFactura }}</span>
            </div>
            <div class="info-row">
              <span class="info-title">Cant. servicios:</span>
              <span class="info-value">{{ userData?.totalServicesCount }} </span>
            </div>
          </div>

        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialog :options-filter="optionsFilter" @force-search="refreshAllTables">
        </FilterDialog>
      </VCardText>

      <VCardText>

        <VTabs v-model="currentTab" grow>
          <VTab>
            <span>Consultas</span>
            <VBadge :content="servicesCount?.consultas" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Procedimientos</span>
            <VBadge :content="servicesCount?.procedimientos" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Urgencias</span>
            <VBadge :content="servicesCount?.urgencias" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Hospitalización</span>
            <VBadge :content="servicesCount?.hospitalizacion" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Recien nacidos</span>
            <VBadge :content="servicesCount?.reciennacidos" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Medicamentos</span>
            <VBadge :content="servicesCount?.medicamentos" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Otros servicios</span>
            <VBadge :content="servicesCount?.otrosservicios" :offset-x="-18" :offset-y="0" />
          </VTab>
        </VTabs>

        <VWindow v-model="currentTab" class="my-5">
          <VDivider />
          <VWindowItem>
            <Queries ref="refListQueries"></Queries>
          </VWindowItem>
          <VWindowItem>
            <Procedure ref="refListProcedure"></Procedure>
          </VWindowItem>
          <VWindowItem>
            <Emergencies ref="refListEmergencies"></Emergencies>
          </VWindowItem>
          <VWindowItem>
            <Hospitalization ref="refListHospitalization"></Hospitalization>
          </VWindowItem>
          <VWindowItem>
            <NewlyBorn ref="refListNewlyBorn"></NewlyBorn>
          </VWindowItem>
          <VWindowItem>
            <Medicines ref="refListMedicines"></Medicines>
          </VWindowItem>
          <VWindowItem>
            <OtherServices ref="refListOtherServices"></OtherServices>
          </VWindowItem>
        </VWindow>
      </VCardText>
    </VCard>
  </div>
</template>

<style scoped>
.info-box {
  /* Bordes redondeados */
  padding: 10px;

  /* Fondo gris claro */
  border: 1px solid #e0e0e0;

  /* Borde sutil */
  border-radius: 8px;
  background-color: #f5f5f5;

  /* Espacio interno */
  min-inline-size: 200px;

  /* Ancho mínimo */
}

.info-row {
  display: flex;

  /* Título a la izquierda, valor a la derecha */
  align-items: center;
  justify-content: space-between;

  /* Espacio vertical entre filas */
  gap: 20px;
  padding-block: 5px;
  padding-inline: 0;

  /* Separación adicional entre título y valor */
}

.info-title {
  /* Color del texto */
  flex-shrink: 0;

  /* Título en negrita */
  color: #333;
  font-weight: bold;

  /* Evita que el título se encoja demasiado */
}

.info-value {
  color: #007bff;

  /* Color del valor */
  text-align: end;

  /* Alinea el valor a la derecha */
}
</style>
