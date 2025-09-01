<script setup lang="ts">

// Definimos los tipos para los datos que esperamos de la API
interface RedisStats {
  total_keys: number;
  keys_by_type: {
    string: number;
    set: number;
    list: number;
    hash: number;
    zset: number;
  };
  memory_used: { human: string; bytes: number };
  memory_peak: { human: string; bytes: number };
  uptime: { seconds: number; days: number };
  clients_connected: number;
  commands_processed: number;
  hits: number;
  misses: number;
  hit_rate: number;
}

const isLoading = ref(true);
const redisStats = ref<RedisStats | null>(null);

// Función para obtener los datos de la API
const fetchData = async () => {
  isLoading.value = true;

  const { data, response } = await useApi<any>(
    createUrl(`cache/redisStats`, {
      query: {
        interval: '20seconds', // Aunque no lo usamos en redisStats, lo dejamos por consistencia
        limit: 30,
      },
    })
  );

  if (response.value?.ok && data.value?.data) {
    redisStats.value = data.value.data; // Asignamos los datos al ref
  }

  isLoading.value = false;
};

// Ejecutamos fetchData al montar el componente
onMounted(() => {
  fetchData();
});

// Datos para el gráfico de tipos de claves
const keyTypeLabels = computed(() => {
  return redisStats.value ? Object.keys(redisStats.value.keys_by_type) : [];
});

const keyTypeValues = computed(() => {
  return redisStats.value ? Object.values(redisStats.value.keys_by_type) : [];
});

</script>

<template>
  <div>
    <!-- Indicador de carga -->
    <v-progress-circular v-if="isLoading" indeterminate color="primary" class="mx-auto d-block mt-5" />

    <!-- Contenido principal -->
    <v-row v-else-if="redisStats" class="mt-5">
      <!-- Tarjeta de Resumen General -->
      <v-col cols="12" md="4">
        <v-card elevation="2">
          <v-card-title>Resumen de Redis</v-card-title>
          <v-card-text>
            <p><strong>Total de Claves:</strong> {{ redisStats.total_keys }}</p>
            <p><strong>Memoria Usada:</strong> {{ redisStats.memory_used.human }}</p>
            <p><strong>Pico de Memoria:</strong> {{ redisStats.memory_peak.human }}</p>
            <p><strong>Tiempo Activo:</strong> {{ redisStats.uptime.days }} días</p>
            <p><strong>Clientes Conectados:</strong> {{ redisStats.clients_connected }}</p>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Tarjeta de Hit Rate -->
      <v-col cols="12" md="4">
        <v-card elevation="2">
          <v-card-title>Eficiencia del Caché</v-card-title>
          <v-card-text>
            <p><strong>Hit Rate:</strong> {{ redisStats.hit_rate }}%</p>
            <p><strong>Aciertos:</strong> {{ redisStats.hits }}</p>
            <p><strong>Fallos:</strong> {{ redisStats.misses }}</p>
            <v-sparkline :value="[redisStats.hits, redisStats.misses]" :labels="['Hits', 'Misses']" color="primary"
              line-width="2" padding="16" auto-draw />
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Tarjeta de Claves por Tipo -->
      <v-col cols="12" md="4">
        <v-card elevation="2">
          <v-card-title>Claves por Tipo</v-card-title>
          <v-card-text>
            <v-sparkline :value="keyTypeValues" :labels="keyTypeLabels" color="secondary" line-width="2" padding="16"
              auto-draw />
            <div v-for="(value, type) in redisStats.keys_by_type" :key="type" class="mt-2">
              <strong>{{ type }}:</strong> {{ value }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Tarjeta de Comandos Procesados -->
      <v-col cols="12">
        <v-card elevation="2">
          <v-card-title>Actividad</v-card-title>
          <v-card-text>
            <p><strong>Comandos Procesados:</strong> {{ redisStats.commands_processed }}</p>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Mensaje si no hay datos -->
    <v-row v-else>
      <v-col>
        <p class="text-center">No se pudieron cargar las estadísticas de Redis.</p>
      </v-col>
    </v-row>
  </div>
</template>

<style scoped>
/* Puedes agregar estilos personalizados aquí si es necesario */
</style>
