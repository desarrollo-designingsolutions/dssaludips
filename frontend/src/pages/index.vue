<script setup lang="ts">
import CountAllData from "@/pages/Dashboard/Componentes/CountAllData.vue";
import InvoiceTrendChart from "@/pages/Dashboard/Componentes/InvoiceTrendChart.vue";
import StatusDistributionChart from "@/pages/Dashboard/Componentes/StatusDistributionChart.vue";


definePage({
  path: "/home",
  name: "Home",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "menu.home",
  },
})

import { useAuthenticationStore } from '@/stores/useAuthenticationStore';

const { company, user } = storeToRefs(useAuthenticationStore())

</script>

<template>
  <div>
    <VCard class="mb-6">
      <VCardTitle>Bienvenido a: {{ company?.name }}</VCardTitle>
    </VCard>

    <CountAllData />

    <VDivider class="my-3" />

    <VRow>
      <VCol cols="12" md="8">
        <InvoiceTrendChart />
      </VCol>
      <VCol cols="12" md="4">
        <StatusDistributionChart />
      </VCol>
    </VRow>
  </div>
</template>
