<script setup lang="ts">
import LineChart from '@core/libs/chartjs/components/LineChart';
import { onMounted, ref } from 'vue';


const isLoading = ref<boolean>(false);
const labels = ref([]);
const datasets = ref([]);

const fetchData = async () => {
  isLoading.value = true

  const { data, response } = await useApi<any>(
    createUrl(`cache/hitRate`, {
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
  isLoading.value = false



};


onMounted(async () => {
  await fetchData();
  const intervalId = setInterval(fetchData, 20000);
  onUnmounted(() => clearInterval(intervalId));
});

const chartData = computed(() => {
  return {
    labels: labels.value,
    datasets: datasets.value,
  };
});

const chartOptions = {
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
      ticks: {
        stepSize: 20, // Marcas cada 20% (0%, 20%, 40%, 60%, 80%, 100%)
        callback: (value) => `${value}%`, // Mostrar % en las etiquetas
      },
      grid: { color: 'rgba(200, 200, 200, 0.3)' },
      title: {
        display: true,
        text: 'Hit Rate (%)',
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
        label: (context) => `${context.dataset.label}: ${context.raw}%`,
      },
    },
  },
};

</script>

<template>
  <div>
    <AppCardActions title="tasa de aciertos de caché (Cache Hit Rate)" actionRefresh @refresh="fetchData"
      v-model:loading="isLoading">
      <VCardText>
        <LineChart :chart-options="chartOptions" :height="400" :chart-data="chartData" />
      </VCardText>
    </AppCardActions>
  </div>
</template>
