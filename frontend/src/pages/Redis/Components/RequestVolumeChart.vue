<script setup lang="ts">
import LineChart from '@core/libs/chartjs/components/LineChart';
import { computed, onMounted, ref } from 'vue';

const isLoading = ref<boolean>(false);
const labels = ref<string[]>([]);
const datasets = ref<any[]>([]);

const fetchData = async () => {
  isLoading.value = true;

  const { data, response } = await useApi<any>(
    createUrl(`cache/requestVolume`, {
      query: {
        interval: '20seconds',
        limit: 30,
      },
    })
  );


  if (response.value?.ok && data.value) {
    labels.value = data.value.labels;
    datasets.value = data.value.datasets;
  }

  isLoading.value = false;
};

onMounted(async () => {
  await fetchData();
  // Actualización en tiempo real cada 20 segundos
  const intervalId = setInterval(fetchData, 20000);
  onUnmounted(() => clearInterval(intervalId));
});

// Asegurarnos de que chartOptions se actualice dinámicamente
const chartData = computed(() => ({
  labels: labels.value,
  datasets: datasets.value,
}));

const chartOptions = computed(() => {
  return {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      x: {
        grid: { display: false },
        ticks: {
          maxRotation: 45,
          minRotation: 45,
          callback: (value, index) => {
            const date = new Date(labels.value[index]);
            return `${date.getHours()}:${date.getMinutes().toString().padStart(2, '0')}:${date.getSeconds().toString().padStart(2, '0')}`;
          },
        },
        title: {
          display: true,
          text: 'Time (HH:MM:SS)',
          color: '#666',
          font: { size: 14 },
        },
      },
      y: {
        min: 0,
        grid: { color: 'rgba(200, 200, 200, 0.3)' },
        title: {
          display: true,
          text: 'Request Volume',
          color: '#666',
          font: { size: 14 },
        },
      },
    },
    plugins: {
      legend: {
        align: 'end',
        position: 'top',
        labels: {
          padding: 20,
          boxWidth: 10,
          usePointStyle: true,
          font: { size: 12 },
        },
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleFont: { size: 14 },
        bodyFont: { size: 12 },
        callbacks: {
          label: (context) => `${context.dataset.label}: ${context.raw} requests`,
        },
      },
    },
  };
});
</script>

<template>
  <div>
    <AppCardActions title="Request Volume Dashboard" actionRefresh @refresh="fetchData" v-model:loading="isLoading">
      <VCardText class="chart-container">
        <LineChart :chart-options="chartOptions" :height="400" :chart-data="chartData" />
      </VCardText>
    </AppCardActions>
  </div>
</template>

<style scoped>
.chart-container {
  height: 400px;
}
</style>
