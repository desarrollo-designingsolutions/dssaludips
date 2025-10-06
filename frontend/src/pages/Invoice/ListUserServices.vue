<script setup lang="ts">
import Emergencies from "@/pages/Invoice/ServicesView/Emergencies.vue";
import Hospitalization from "@/pages/Invoice/ServicesView/Hospitalization.vue";
import Medicines from "@/pages/Invoice/ServicesView/Medicines.vue";
import NewlyBorn from "@/pages/Invoice/ServicesView/NewlyBorn.vue";
import OtherServices from "@/pages/Invoice/ServicesView/OtherServices.vue";
import Procedure from "@/pages/Invoice/ServicesView/Procedure.vue";
import Queries from "@/pages/Invoice/ServicesView/Queries.vue";
import { useInvoiceUserStore } from "@/pages/Invoice/Components/useInvoiceUserStore";
import { router } from "@/plugins/1.router";

definePage({
  path: "Invoice-ListUserServicesView/:id/:numFactura/:numDocumentoIdentificacion",
  name: "Invoice-ListUserServicesView",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "menu.invoice",
  },
});

const { dataUser, servicesCount, dataInvoice } = storeToRefs(useInvoiceUserStore());

const route = useRoute();
onMounted(async () => {
  if (dataUser.value) {
    servicesCount.value.consultas = dataUser.value.servicios.consultas?.length ?? 0
    servicesCount.value.procedimientos = dataUser.value.servicios.procedimientos?.length ?? 0
    servicesCount.value.urgencias = dataUser.value.servicios.urgencias?.length ?? 0
    servicesCount.value.hospitalizacion = dataUser.value.servicios.hospitalizacion?.length ?? 0
    servicesCount.value.recienNacidos = dataUser.value.servicios.recienNacidos?.length ?? 0
    servicesCount.value.medicamentos = dataUser.value.servicios.medicamentos?.length ?? 0
    servicesCount.value.otrosServicios = dataUser.value.servicios.otrosServicios?.length ?? 0
  }
});

// Computed para sumar todos los valores de servicesCount
const totalServices = computed(() => {
  return Object.values(servicesCount.value).reduce((total, count) => total + count, 0);
})

const currentTab = ref(0);

const goBack = () => {
  router.go(-1);
}
</script>

<template>
  <div>
    <VCard class="mt-5" v-if="dataUser">
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
              <span class="info-value"> {{ dataInvoice.invoice_number }}</span>
            </div>
            <div class="info-row">
              <span class="info-title">Cant. servicios:</span>
              <span class="info-value">{{ totalServices }} </span>
            </div>
          </div>

        </div>
      </VCardTitle>

      <VCardText>

        <VTabs v-model="currentTab" grow>
          <VTab>
            <span>Consultas</span>
            <VBadge :content="servicesCount.consultas" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Procedimientos</span>
            <VBadge :content="servicesCount.procedimientos" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Urgencias</span>
            <VBadge :content="servicesCount.urgencias" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Hospitalización</span>
            <VBadge :content="servicesCount.hospitalizacion" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Recien nacidos</span>
            <VBadge :content="servicesCount.recienNacidos" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Medicamentos</span>
            <VBadge :content="servicesCount.medicamentos" :offset-x="-18" :offset-y="0" />
          </VTab>
          <VTab>
            <span>Otros servicios</span>
            <VBadge :content="servicesCount.otrosServicios" :offset-x="-18" :offset-y="0" />
          </VTab>
        </VTabs>

        <VWindow v-model="currentTab" class="my-5">
          <VDivider />
          <VWindowItem>
            <Queries :data-list="dataUser.servicios.consultas"></Queries>
          </VWindowItem>
          <VWindowItem>
            <Procedure :data-list="dataUser.servicios.procedimientos"></Procedure>
          </VWindowItem>
          <VWindowItem>
            <Emergencies :data-list="dataUser.servicios.urgencias"></Emergencies>
          </VWindowItem>
          <VWindowItem>
            <Hospitalization :data-list="dataUser.servicios.hospitalizacion"></Hospitalization>
          </VWindowItem>
          <VWindowItem>
            <NewlyBorn :data-list="dataUser.servicios.recienNacidos"></NewlyBorn>
          </VWindowItem>
          <VWindowItem>
            <Medicines :data-list="dataUser.servicios.medicamentos"></Medicines>
          </VWindowItem>
          <VWindowItem>
            <OtherServices :data-list="dataUser.servicios.otrosServicios"></OtherServices>
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
