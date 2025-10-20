<script setup lang="ts">
import { hexToRgb } from '@layouts/utils'
import { computed, onMounted, ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'

import { useAuthenticationStore } from "@/stores/useAuthenticationStore"
const authenticationStore = useAuthenticationStore();
const vuetifyTheme = useTheme()
const distribution = ref({ labels: [], counts: [], colors: [], total: 0 })

const fetchDistribution = async () => {
  try {
    const { data, response } = await useAxios(`/dashboard/status-distribution`).get({
      params: {
        company_id: authenticationStore.company.id,
      }
    });

    if (response.status == 200 && data) {
      distribution.value = data
    }
  } catch (error) {
    console.error('Error fetching distribution:', error)
  }
}

onMounted(() => {
  fetchDistribution()
})

// Configuración de la gráfica
const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  const labelColor = `rgba(${hexToRgb(currentTheme['on-surface'])},${variableTheme['disabled-opacity']})`

  return {
    chart: {
      type: 'donut',
      toolbar: { show: false },
    },
    labels: distribution.value.labels,
    colors: distribution.value.colors, // Usar colores del backend
    dataLabels: {
      enabled: true,
      formatter: (val: number, opts: any) => {
        return distribution.value.counts[opts.seriesIndex]
      },
      style: {
        fontSize: '15px',
        colors: [labelColor],
        fontWeight: '600',
        fontFamily: 'Public Sans',
      },
    },
    legend: {
      show: true,
      position: 'bottom',
      horizontalAlign: 'center',
      labels: {
        colors: labelColor,
        fontSize: '23px',
        fontFamily: 'Public Sans',
      },
      formatter: (seriesName: string, opts: any) => {
        const percentage = ((distribution.value.counts[opts.seriesIndex] / distribution.value.total) * 100).toFixed(0)
        return `${seriesName} (${percentage}%)`
      },
    },
    plotOptions: {
      pie: {
        donut: {
          size: '70%',
          labels: {
            show: true,
            name: {
              show: true,
              fontSize: '18px',
              fontWeight: '600',
              color: labelColor,
              offsetY: -10,
              formatter: () => 'Total',
            },
            value: {
              show: true,
              fontSize: '24px',
              fontWeight: '600',
              color: labelColor,
              offsetY: 10,
              formatter: () => distribution.value.total.toString(),
            },
            total: {
              show: true,
              label: 'Total',
              fontSize: '18px',
              fontWeight: '600',
              color: labelColor,
              formatter: () => distribution.value.total.toString(),
            },
          },
        },
      },
    },
    responsive: [
      {
        breakpoint: 480,
        options: {
          chart: { width: '100%' },
          legend: { position: 'bottom' },
        },
      },
    ],
  }
})

const series = computed(() => distribution.value.counts)
</script>

<template>
  <VCard title="Distribución por Estado" subtitle="">
    <VCardText>
      <VueApexCharts :options="chartOptions" :series="series" type="donut" height="350" />
    </VCardText>
  </VCard>
</template>
