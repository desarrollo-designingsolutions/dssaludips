<script setup lang="ts">
// import OtherServicesForm from '@/pages/Rips/Manual/Components/OtherServicesForm.vue';
import MedicalConsultationForm from '@/pages/Rips/Components/Manual/MedicalConsultationForm.vue';
import ProcedureForm from '@/pages/Rips/Components/Manual/ProcedureForm.vue';
import UrgencyForm from '@/pages/Rips/Components/Manual/UrgencyForm.vue';
import HospitalizationForm from '@/pages/Rips/Components/Manual/HospitalizationForm.vue';
import NewlyBornForm from '@/pages/Rips/Components/Manual/NewlyBornForm.vue';
import MedicinesForm from '@/pages/Rips/Components/Manual/MedicinesForm.vue';
import { useRipStore } from "@/pages/Rips/Store/useRipStore";
import { useRipManualStore } from "@/pages/Rips/Store/useRipManualStore";

const ripManualStore = useRipManualStore();

definePage({
  path: "Rips/Manual/ListInvoices/ListUsers/ListServices/:rip_id/:ripInvoice_id/:numFactura/:ripInvoiceUser_id/:numDocumentoIdentificacion",
  name: "Rips-Manual-ListInvoices-ListUsers-ListServices",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "rips.index",
  },
});

const { dataRip, dataUser, dataServicesRipUser, servicesCount } = storeToRefs(useRipStore())
const route = useRoute()
const keyComponent = ref<number>(1)
const currentTab = ref(0)

const fetchDataTable = async () => {

  const { data, response } = await useAxios(
    `/rip/getManualInfoServices/${route.params.ripInvoiceUser_id}`
  ).get();

  if (response.status === 200 && data) {
    dataUser.value = data.ripInvoiceUser_info
    dataServicesRipUser.value = data.ripInvoiceUser_info.servicios
    servicesCount.value = data.ripInvoiceUser_info.servicesCount
    keyComponent.value++
  }
};

onMounted(async () => {
  fetchDataTable();
  ripManualStore.ripInvoiceServicesSelectsInfinite();
});

const breadcrumbs = [
  {
    title: "Rips",
    disabled: false,
  },
  {
    title: "Manual",
    disabled: false,
  },
  {
    title: `Factura: ${route.params?.numFactura}`,
    disabled: false,
    to: `/Rips/Components/Manual/Rips/Manual/ListInvoices/${route.params?.rip_id}`,
  },
  {
    title: `Usuario: ${route.params?.numDocumentoIdentificacion}`,
    disabled: false,
    to: `/Rips/Components/Manual/Rips/Manual/ListInvoices/ListUsers/${route.params?.rip_id}/${route.params?.ripInvoice_id}/${route.params?.numFactura}`
  },
  {
    title: `Servicios`,
    disabled: true,
  },
];
</script>

<template>
  <div>
    <VBreadcrumbs :items="breadcrumbs"></VBreadcrumbs>
    <VCard title="Información de servicios">
      <VCardText>
        <VRow>
          <VCol cols="2">
            <h4>Consecutivo</h4>
          </VCol>

          <VCol cols="2">
            <h4>Tipo de documento</h4>
          </VCol>

          <VCol cols="2">
            <h4>Nro de documento</h4>
          </VCol>

          <VCol cols="2">
            <h4>Tipo de usuario</h4>
          </VCol>

          <VCol cols="2">
            <h4>Fecha de nacimiento</h4>
          </VCol>

          <VCol cols="2">
            <h4>Sexo</h4>
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="2">
            <span>{{ dataUser?.consecutivo }}</span>
          </VCol>

          <VCol cols="2">
            <span>{{ dataUser?.tipoDocumentoIdentificacion }}</span>
          </VCol>
          <VCol cols="2">
            <span>{{ dataUser?.numDocumentoIdentificacion }}</span>
          </VCol>

          <VCol cols="2">
            <span>{{ dataUser?.tipoUsuario }}</span>
          </VCol>

          <VCol cols="2">
            <span>{{ dataUser?.fechaNacimiento }}</span>
          </VCol>
          <VCol cols="2">
            <span>{{ dataUser?.codSexo }}</span>
          </VCol>
        </VRow>
      </VCardText>

    </VCard>

    <VCard class="mt-5" title="Listado de servicios">
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
            <!-- <VBadge :content="servicesCount?.otrosServicios" :offset-x="-18" :offset-y="0" /> -->
          </VTab>
        </VTabs>

        <VWindow v-model="currentTab" class="my-5">
          <VWindowItem>
            <MedicalConsultationForm :key="keyComponent" :data-list="dataUser?.servicios?.consultas">
            </MedicalConsultationForm>
          </VWindowItem>
          <VWindowItem>
            <ProcedureForm :key="keyComponent" :data-list="dataUser?.servicios?.procedimientos"></ProcedureForm>
          </VWindowItem>
          <VWindowItem>
            <UrgencyForm :key="keyComponent" :data-list="dataUser?.servicios?.urgencias"></UrgencyForm>
          </VWindowItem>
          <VWindowItem>
            <HospitalizationForm :key="keyComponent" :data-list="dataUser?.servicios?.hospitalizacion"></HospitalizationForm>
          </VWindowItem>
          <VWindowItem>
            <NewlyBornForm :key="keyComponent" :data-list="dataUser?.servicios?.reciennacidos"></NewlyBornForm>
          </VWindowItem>
          <VWindowItem>
            <MedicinesForm :key="keyComponent" :data-list="dataUser?.servicios?.medicamentos"></MedicinesForm>
          </VWindowItem>
          <VWindowItem>
            <!-- <OtherServicesForm :key="keyComponent" :data-list="dataUser?.servicios?.otrosServicios"></OtherServicesForm> -->
          </VWindowItem>
        </VWindow>
      </VCardText>
    </VCard>
  </div>
</template>
