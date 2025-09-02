<script setup lang="ts">
import ListTables from "@/pages/Redis/Components/ListTables.vue";
definePage({
  name: "Redis-Index",
});


</script>

<template>
  <div>
    <VRow>
      <!-- <VCol cols="12">
        <ListCacheKeys />
      </VCol>
      <VCol cols="12" md="6">
        <RequestVolumeChart />
      </VCol>
      <VCol cols="12" md="6">
        <ResponseTimeChart />
      </VCol>
      <VCol cols="12" md="6">
        <CacheHitRateChart />
      </VCol> -->
      <ListTables></ListTables>
    </VRow>
  </div>
</template>
