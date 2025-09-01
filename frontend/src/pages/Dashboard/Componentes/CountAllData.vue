<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { DateRange } from '@/types';
import { onMounted, ref } from 'vue';

interface CountData {
  icon: string;
  color: string;
  title: string;
  value: string | number;
  secondary_data: string | number;
  change_label?: string;
  isHover: boolean;
  to: Record<string, unknown>;
}

// Initialize store
const authenticationStore = useAuthenticationStore();

// State
const countData = ref<CountData[]>(Array(6).fill({
  icon: '',
  color: '',
  title: '',
  value: 0,
  secondary_data: 0,
  change_label: undefined,
  isHover: false,
  to: {},
}));

const isLoading = ref<boolean>(false);
const dateRange = ref<DateRange>({
  startDate: "2025-01-01",
  endDate: "2025-12-31"
});
const service_vendor_id = ref()

const serviceVendors_arrayInfo = ref([])

// Methods
const updateCountData = (data: any): void => {
  const dataMapping = [
    'invoiceCountData',
    // 'approvedVsGlosaData',
    'countPendingPaymentDataStatusPending',
    'pendingPaymentsData',
    'inReviewVsPendingData',
    'averageResponseTimeData',
    'recoveredGlosasData'
  ];

  dataMapping.forEach((key, index) => {
    if (data[key]) {
      countData.value[index] = data[key];
    }
  });
};

const fetchData = async (): Promise<void> => {
  try {
    isLoading.value = true;
    const { data, response } = await useAxios('/dashboard/countAllData').get({
      params: {
        company_id: authenticationStore.company.id,
        start_date: dateRange.value.startDate,
        end_date: dateRange.value.endDate,
        service_vendor_id: service_vendor_id.value?.value,
      }
    });

    if (response.status === 200 && data) {
      updateCountData(data);
    }
  } catch (error) {
    console.error('Error fetching dashboard data:', error);
    // Here you could add error handling, like showing a notification
  } finally {
    isLoading.value = false;
  }
};


// Lifecycle
onMounted(fetchData);
</script>

<template>
  <VRow>
    <!-- Date Filter Form -->
    <VCol cols="12" class="mb-4">
      <VRow>
        <VCol cols="12" md="3">
          <AppTextField v-model="dateRange.startDate" label="Fecha de inicio" type="date" variant="outlined"
            :max="dateRange.endDate" clearable />
        </VCol>
        <VCol cols="12" md="3">
          <AppTextField v-model="dateRange.endDate" label="Fecha de fin" type="date" variant="outlined"
            :min="dateRange.startDate" clearable />
        </VCol>

        <VCol cols="12" md="3">
          <SelectServiceVendorForm label="Prestador" v-model="service_vendor_id"
            :itemsData="serviceVendors_arrayInfo" />
        </VCol>
        <VCol cols="12" md="3" class="d-flex align-center mt-6">
          <VBtn color="primary" @click="fetchData" :loading="isLoading" :disabled="isLoading">
            Buscar
          </VBtn>
        </VCol>
      </VRow>
    </VCol>

    <!-- Stats Cards -->
    <VCol v-for="(data, index) in countData" :key="index" cols="12" md="4" sm="6">
      <VCard :to="data.to" :style="`border-block-end-color: ${data.isHover
        ? `rgb(var(--v-theme-${data.color}))`
        : `rgba(var(--v-theme-${data.color}),0.38)`
        }`" @mouseenter="data.isHover = true" @mouseleave="data.isHover = false" class="h-100">
        <VSkeletonLoader :loading="isLoading" type="avatar,list-item,list-item">
          <VCardText>
            <div class="mb-2">
              <div class="text-body-1 font-weight-medium">{{ data.title }}</div>
            </div>
            <div class="d-flex align-center gap-x-2 mb-1">
              <VAvatar variant="tonal" :color="data.color" rounded size="small">
                <VIcon :icon="data.icon" size="20" />
              </VAvatar>
              <h4 class="text-h4 font-weight-bold">
                {{ data.value }}
              </h4>
            </div>
            <div v-if="data.change_label" class="text-body-2 text-error mb-1">
              {{ data.change_label }}
            </div>
            <div class="text-body-2">
              {{ data.secondary_data }}
            </div>
          </VCardText>
        </VSkeletonLoader>
      </VCard>
    </VCol>
  </VRow>
</template>
