<script setup lang="ts">
import { hexToRgb } from '@layouts/utils'
import { computed, onMounted, ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'

import { useAuthenticationStore } from "@/stores/useAuthenticationStore"
const authenticationStore = useAuthenticationStore();

const vuetifyTheme = useTheme()
const currentTab = ref<number>(0)
const refVueApexChart = ref()

// Obtener datos del backend
const trends = ref({ months: [], amounts: [], counts: [] })

const fetchTrends = async () => {
  try {

    const { data, response } = await useAxios(`/dashboard/trend`).get({
      params: {
        company_id: authenticationStore.company.id,
      }
    });

    if (response.status == 200 && data) {
      trends.value = data
    }

  } catch (error) {
    console.error('Error fetching trends:', error)
  }
}

onMounted(() => {
  fetchTrends()
})

// Configuración de la gráfica
const chartConfigs = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  const labelPrimaryColor = `rgba(${hexToRgb(currentTheme.primary)},${variableTheme['dragged-opacity']})`
  const legendColor = `rgba(${hexToRgb(currentTheme['on-background'])},${variableTheme['high-emphasis-opacity']})`
  const borderColor = `rgba(${hexToRgb(String(variableTheme['border-color']))},${variableTheme['border-opacity']})`
  const labelColor = `rgba(${hexToRgb(currentTheme['on-surface'])},${variableTheme['disabled-opacity']})`

  return [
    {
      title: 'Montos',
      icon: 'tabler-currency-dollar',
      chartOptions: {
        chart: {
          parentHeightOffset: 0,
          type: 'bar',
          toolbar: { show: false },
        },
        plotOptions: {
          bar: {
            columnWidth: '32%',
            borderRadiusApplication: 'end',
            borderRadius: 4,
            distributed: true,
            dataLabels: { position: 'top' },
          },
        },
        grid: {
          show: false,
          padding: { top: 0, bottom: 0, left: -10, right: -10 },
        },
        colors: [
          labelPrimaryColor, labelPrimaryColor, labelPrimaryColor, labelPrimaryColor,
          labelPrimaryColor, labelPrimaryColor, labelPrimaryColor, `rgba(${hexToRgb(currentTheme.primary)}, 1)`,
          labelPrimaryColor,
        ],
        dataLabels: {
          enabled: true,
          formatter(val: number) {
            return `$${val.toLocaleString()}` // Formato de moneda
          },
          offsetY: -25,
          style: {
            fontSize: '15px',
            colors: [legendColor],
            fontWeight: '600',
            fontFamily: 'Public Sans',
          },
        },
        legend: { show: false },
        tooltip: { enabled: false },
        xaxis: {
          categories: trends.value.months,
          axisBorder: { show: true, color: borderColor },
          axisTicks: { show: false },
          labels: {
            style: {
              colors: labelColor,
              fontSize: '13px',
              fontFamily: 'Public Sans',
            },
          },
        },
        yaxis: {
          labels: {
            offsetX: -15,
            formatter(val: number) {
              return `$${val.toLocaleString()}`
            },
            style: {
              fontSize: '13px',
              colors: labelColor,
              fontFamily: 'Public Sans',
            },
            min: 0,
            max: Math.max(...trends.value.amounts) * 1.2, // Ajustar máximo dinámicamente
            tickAmount: 6,
          },
        },
        responsive: [
          { breakpoint: 1441, options: { plotOptions: { bar: { columnWidth: '41%' } } } },
          {
            breakpoint: 590,
            options: {
              plotOptions: { bar: { columnWidth: '61%' } },
              yaxis: { labels: { show: false } },
              grid: { padding: { right: 0, left: -20 } },
              dataLabels: { style: { fontSize: '12px', fontWeight: '400' } },
            },
          },
        ],
      },
      series: [{ data: trends.value.amounts }],
    },
    {
      title: 'Cantidad',
      icon: 'tabler-chart-bar',
      chartOptions: {
        chart: {
          parentHeightOffset: 0,
          type: 'bar',
          toolbar: { show: false },
        },
        plotOptions: {
          bar: {
            columnWidth: '32%',
            borderRadiusApplication: 'end',
            borderRadius: 4,
            distributed: true,
            dataLabels: { position: 'top' },
          },
        },
        grid: {
          show: false,
          padding: { top: 0, bottom: 0, left: -10, right: -10 },
        },
        colors: [
          labelPrimaryColor, labelPrimaryColor, labelPrimaryColor, labelPrimaryColor,
          labelPrimaryColor, labelPrimaryColor, `rgba(${hexToRgb(currentTheme.primary)}, 1)`,
          labelPrimaryColor, labelPrimaryColor,
        ],
        dataLabels: {
          enabled: true,
          formatter(val: number) {
            return `${val}`
          },
          offsetY: -25,
          style: {
            fontSize: '15px',
            colors: [legendColor],
            fontWeight: '600',
            fontFamily: 'Public Sans',
          },
        },
        legend: { show: false },
        tooltip: { enabled: false },
        xaxis: {
          categories: trends.value.months,
          axisBorder: { show: true, color: borderColor },
          axisTicks: { show: false },
          labels: {
            style: {
              colors: labelColor,
              fontSize: '13px',
              fontFamily: 'Public Sans',
            },
          },
        },
        yaxis: {
          labels: {
            offsetX: -15,
            formatter(val: number) {
              return `${val}`
            },
            style: {
              fontSize: '13px',
              colors: labelColor,
              fontFamily: 'Public Sans',
            },
            min: 0,
            max: Math.max(...trends.value.counts) * 1.2, // Ajustar máximo dinámicamente
            tickAmount: 6,
          },
        },
        responsive: [
          { breakpoint: 1441, options: { plotOptions: { bar: { columnWidth: '41%' } } } },
          {
            breakpoint: 590,
            options: {
              plotOptions: { bar: { columnWidth: '61%' } },
              yaxis: { labels: { show: false } },
              grid: { padding: { right: 0 } },
              dataLabels: { style: { fontSize: '12px', fontWeight: '400' } },
            },
          },
        ],
      },
      series: [{ data: trends.value.counts }],
    },
  ]
})

</script>

<template>
  <VCard title="Tendencia de Facturación" subtitle="Año Actual">
    <template #append>
    </template>
    <VCardText>
      <VSlideGroup v-model="currentTab" show-arrows mandatory class="mb-10">
        <VSlideGroupItem v-for="(report, index) in chartConfigs" :key="report.title" v-slot="{ isSelected, toggle }"
          :value="index">
          <div style="block-size: 100px; inline-size: 110px;"
            :style="isSelected ? 'border-color:rgb(var(--v-theme-primary)) !important' : ''"
            :class="isSelected ? 'border' : 'border border-dashed'"
            class="d-flex flex-column justify-center align-center cursor-pointer rounded py-4 px-5 me-4"
            @click="toggle">
            <VAvatar rounded size="38" :color="isSelected ? 'primary' : ''" variant="tonal" class="mb-2">
              <VIcon size="22" :icon="report.icon" />
            </VAvatar>
            <h6 class="text-base font-weight-medium mb-0">
              {{ report.title }}
            </h6>
          </div>
        </VSlideGroupItem>
      </VSlideGroup>

      <VueApexCharts ref="refVueApexChart" :key="currentTab" :options="chartConfigs[Number(currentTab)].chartOptions"
        :series="chartConfigs[Number(currentTab)].series" height="230" class="mt-3" />
    </VCardText>
  </VCard>
</template>
