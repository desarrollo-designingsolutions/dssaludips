<template>
  <v-dialog v-model="isOpen" max-width="60rem" persistent>
    <v-card class="process-list-card">
      <v-card-title class="d-flex align-center justify-space-between pa-6 pb-4">
        <div class="d-flex align-center">
          <v-icon icon="tabler-stack-2" class="me-3" color="primary" size="28" />
          <div>
            <h2 class="text-h5 font-weight-bold">Procesos de Importación</h2>
            <p class="text-body-2 text-medium-emphasis mb-0">Gestiona tus importaciones activas y completadas</p>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <v-chip :color="getOverallStatusColor()" size="large" variant="elevated">
            <v-icon start icon="tabler-files" />
            {{ globalLoading.allProcesses.value.length }} proceso{{ globalLoading.allProcesses.value.length !== 1 ? 's' : '' }}
          </v-chip>
          <!-- ✅ BOTÓN PARA LIMPIAR COMPLETADOS -->
          <v-btn v-if="globalLoading.completedProcesses.value.length > 0" variant="outlined" color="warning" size="small"
            @click="clearCompleted">
            <v-icon start icon="tabler-trash" />
            Limpiar ({{ globalLoading.completedProcesses.value.length }})
          </v-btn>
        </div>
      </v-card-title>
      <!-- Progreso General -->
      <v-card-text v-if="globalLoading.activeProcesses.value.length > 0" class="px-6 py-0">
        <v-card class="overall-progress-card pa-4 mb-4" color="grey-50" variant="tonal">
          <div class="d-flex justify-space-between align-center mb-3">
            <div class="d-flex align-center">
              <v-icon icon="tabler-chart-line" color="primary" class="me-2" />
              <span class="text-h6 font-weight-medium">Progreso General</span>
            </div>
            <span class="text-h5 font-weight-bold text-primary">{{ Math.round(overallProgress) }}%</span>
          </div>
          <v-progress-linear :model-value="overallProgress" height="12" rounded color="primary" class="mb-3" />
          <div class="d-flex justify-space-between text-body-2 text-medium-emphasis">
            <span>{{ globalLoading.completedProcesses.value.length }} de {{ globalLoading.allProcesses.value.length }} procesos completados</span>
            <span>{{ globalLoading.activeProcesses.value.length }} activo{{ globalLoading.activeProcesses.value.length !== 1 ? 's' : '' }}</span>
          </div>
        </v-card>
      </v-card-text>
      <v-card-text class="pa-4">
        <!-- ✅ USAR LISTA ORDENADA -->
        <v-list class="process-list">
          <v-list-item v-for="process in globalLoading.sortedProcesses.value" :key="process.batch_id"
            class="process-item pa-4 ma-2 rounded-lg" :class="{
              'active-process': process.status === 'active',
              'queued-process': process.status === 'queued',
              'completed-process': process.status === 'completed',
              'error-process': process.status === 'error'  
            }">
            <!-- Process Header -->
            <div class="d-flex align-center justify-space-between mb-3">
              <div class="d-flex align-center">
                <v-avatar :color="getStatusColor(process.status)" size="48" class="me-3">
                  <v-icon :icon="getStatusIcon(process.status)" color="white" size="24"
                    :class="process.status === 'active' ? 'spin-animation' : ''" />
                </v-avatar>
                <div>
                  <h3 class="text-h6 font-weight-bold">{{ process.metadata?.file_name }}</h3>
                  <div class="d-flex align-center mt-1">
                    <v-chip :color="getStatusColor(process.status)" size="small" class="me-2">
                      {{ getStatusText(process) }}
                    </v-chip>
                    <v-chip v-if="process.metadata?.connection_status"
                      :color="getConnectionColor(process.metadata.connection_status)" size="small" variant="outlined">
                      <v-icon start :icon="getConnectionIcon(process.metadata.connection_status)" size="14" />
                      {{ process.metadata.connection_status }}
                    </v-chip>
                  </div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-h5 font-weight-bold text-primary">{{ Math.round(process.progress) }}%</div>
                <div class="text-caption text-medium-emphasis">{{ formatTime(process.started_at) }}</div>
              </div>
            </div>
            <!-- Progress Bar -->
            <div class="mb-3">
              <v-progress-linear :model-value="process.progress" height="8" rounded
                :color="getStatusColor(process.status)" class="mb-2" />
              <div class="d-flex justify-space-between text-body-2 text-medium-emphasis">
                <span>{{ process.current_action }}</span>
                <span v-if="process.metadata?.processing_speed">
                  {{ process.metadata.processing_speed }} reg/s
                </span>
              </div>
            </div>
            <!-- ✅ METADATA DETALLADA -->
            <div v-if="process.metadata" class="metadata-section">
              <v-row class="metadata-grid">
                <!-- Registros -->
                <v-col cols="12" sm="6" md="3">
                  <v-card class="metadata-card pa-3" variant="tonal" color="info">
                    <div class="d-flex align-center">
                      <v-icon icon="tabler-database" color="info" class="me-2" />
                      <div>
                        <div class="text-caption text-medium-emphasis">Registros</div>
                        <div class="text-h6 font-weight-bold">
                          {{ process.metadata.processed_records || 0 }} / {{ process.metadata.total_records || 0 }}
                        </div>
                      </div>
                    </div>
                  </v-card>
                </v-col>
                <!-- Hojas -->
                <v-col cols="12" sm="6" md="3">
                  <v-card class="metadata-card pa-3" variant="tonal" color="success">
                    <div class="d-flex align-center">
                      <v-icon icon="tabler-file-spreadsheet" color="success" class="me-2" />
                      <div>
                        <div class="text-caption text-medium-emphasis">Hojas</div>
                        <div class="text-h6 font-weight-bold">
                          {{ process.metadata.current_sheet || 1 }} / {{ process.metadata.total_sheets || 1 }}
                        </div>
                      </div>
                    </div>
                  </v-card>
                </v-col>
                <!-- Errores -->
                <v-col cols="12" sm="6" md="3">
                  <v-card class="metadata-card pa-3" variant="tonal"
                    :color="(process.metadata.errors_count || 0) > 0 ? 'error' : 'grey'">
                    <div class="d-flex align-center">
                      <v-icon
                        :icon="(process.metadata.errors_count || 0) > 0 ? 'tabler-alert-circle' : 'tabler-check-circle'"
                        :color="(process.metadata.errors_count || 0) > 0 ? 'error' : 'success'" class="me-2" />
                      <div>
                        <div class="text-caption text-medium-emphasis">Errores</div>
                        <div class="text-h6 font-weight-bold">{{ process.metadata.errors_count || 0 }}</div>
                      </div>
                    </div>
                  </v-card>
                </v-col>
                <!-- Tiempo Estimado -->
                <v-col cols="12" sm="6" md="3">
                  <v-card class="metadata-card pa-3" variant="tonal" color="warning">
                    <div class="d-flex align-center">
                      <v-icon icon="tabler-clock" color="warning" class="me-2" />
                      <div>
                        <div class="text-caption text-medium-emphasis">Tiempo Rest.</div>
                        <div class="text-h6 font-weight-bold">
                          {{ formatEstimatedTime(process.metadata.estimated_time_remaining) }}
                        </div>
                      </div>
                    </div>
                  </v-card>
                </v-col>
              </v-row>
              <!-- Información adicional para procesos activos -->
              <div v-if="process.status === 'active'" class="mt-3">
                <v-card class="active-details pa-3" variant="tonal" color="primary">
                  <div class="d-flex align-center justify-space-between">
                    <div class="d-flex align-center">
                      <v-icon icon="tabler-user" color="primary" class="me-2" />
                      <div>
                        <div class="text-caption text-medium-emphasis">Procesando</div>
                        <div class="text-body-1 font-weight-medium">{{ process.current_element }}</div>
                      </div>
                    </div>
                    <div v-if="process.metadata.last_activity" class="text-right">
                      <div class="text-caption text-medium-emphasis">Última actividad</div>
                      <div class="text-body-2">{{ formatLastActivity(process.metadata.last_activity) }}</div>
                    </div>
                  </div>
                </v-card>
              </div>
              <!-- Información para procesos en cola -->
              <div v-else-if="process.status === 'queued'" class="mt-3">
                <v-card class="queued-details pa-3" variant="tonal" color="warning">
                  <div class="d-flex align-center">
                    <v-icon icon="tabler-hourglass" color="warning" class="me-2" />
                    <div>
                      <div class="text-caption text-medium-emphasis">Estado</div>
                      <div class="text-body-1 font-weight-medium">En cola de espera</div>
                    </div>
                  </div>
                </v-card>
              </div>
              <!-- Información para procesos completados -->
              <div v-else-if="process.status === 'completed'" class="mt-3">
                <v-card class="completed-details pa-3" variant="tonal" color="success">
                  <div class="d-flex align-center justify-space-between">
                    <div class="d-flex align-center">
                      <v-icon icon="tabler-circle-check" color="success" class="me-2" />
                      <div>
                        <div class="text-caption text-medium-emphasis">Completado</div>
                        <div class="text-body-1 font-weight-medium">
                          {{ process.metadata.total_records || 0 }} registros procesados
                        </div>
                      </div>
                    </div>
                    <div v-if="process.completed_at" class="text-right">
                      <div class="text-caption text-medium-emphasis">Finalizado</div>
                      <div class="text-body-2">{{ formatTime(process.completed_at) }}</div>
                    </div>
                  </div>
                </v-card>
              </div>
              <!-- Información para procesos con error -->
              <div v-else-if="process.status === 'error'" class="mt-3">
                <v-card class="error-details pa-3" variant="tonal" color="error">
                  <div class="d-flex align-center justify-space-between">
                    <div class="d-flex align-center">
                      <v-icon icon="tabler-alert-triangle" color="error" class="me-2" />
                      <div>
                        <div class="text-caption text-medium-emphasis">Estado</div>
                        <div class="text-body-1 font-weight-medium">
                          Error: {{ process.metadata?.errors_count || 0 }} errores encontrados
                        </div>
                      </div>
                    </div>
                    <div v-if="process.completed_at" class="text-right">
                      <div class="text-caption text-medium-emphasis">Finalizado</div>
                      <div class="text-body-2">{{ formatTime(process.completed_at) }}</div>
                    </div>
                  </div>
                </v-card>
              </div>
            </div>
            <!-- Actions -->
            <div class="d-flex justify-end mt-3">
              <v-btn v-if="(process.status === 'completed' || process.status === 'error') && process.metadata?.errors_count > 0" icon size="small"
                variant="text" color="warning" @click.stop="$emit('showDataProcess', process.batch_id)">
                <v-icon icon="tabler-eye" />
                <v-tooltip activator="parent" location="top">Visualizar errores</v-tooltip>
              </v-btn>
              <v-btn v-if="(process.status === 'completed' || process.status === 'error')" icon size="small" variant="text" color="error"
                @click.stop="$emit('removeProcess', process.batch_id)">
                <v-icon icon="tabler-trash" />
                <v-tooltip activator="parent" location="top">Eliminar proceso</v-tooltip>
              </v-btn>
            </div>
          </v-list-item>
        </v-list>
      </v-card-text>
      <v-card-actions class="pa-6 pt-4">
        <v-spacer />
        <v-btn variant="elevated" color="primary" @click="$emit('close')">
          <v-icon start icon="tabler-x" />
          Cerrar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { useGlobalLoading } from '@/composables/useGlobalLoading'; // Importar el composable
import { computed } from 'vue';

// No necesitamos definir las interfaces aquí si ya están en useGlobalLoading
// interface ProcessMetadata { ... }
// interface ProcessInfo { ... }

// Usar el composable directamente
const globalLoading = useGlobalLoading();

const emit = defineEmits<{
  removeProcess: [batchId: string];
  showDataProcess: [batchId: string];
  clearCompleted: [];
  close: [];
}>();

// La visibilidad del modal ahora se controla directamente desde el composable
const isOpen = computed({
  get: () => globalLoading.showProcessList.value,
  set: (value) => {
    if (!value) {
      globalLoading.hideProcessListModal();
    }
  },
});

const overallProgress = computed(() => {
  if (globalLoading.allProcesses.value.length === 0) return 0;
  const totalProgress = globalLoading.allProcesses.value.reduce((sum, process) => sum + process.progress, 0);
  return totalProgress / globalLoading.allProcesses.value.length;
});

const getOverallStatusColor = () => {
  if (globalLoading.activeProcesses.value.length === 0) return 'success';
  return 'primary';
};

const clearCompleted = () => {
  emit('clearCompleted'); // Este emit sigue siendo necesario para que el padre (GlobalLoadingManager) lo maneje
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'active': return 'primary';
    case 'completed': return 'success';
    case 'error': return 'error';
    case 'queued': return 'warning';
    default: return 'secondary';
  }
};

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'active': return 'tabler-loader-2';
    case 'completed': return 'tabler-circle-check-filled';
    case 'error': return 'tabler-alert-circle';
    case 'queued': return 'tabler-clock';
    default: return 'tabler-help-circle';
  }
};

const getStatusText = (process: any) => { // Usar 'any' o la interfaz de useGlobalLoading
  switch (process.status) {
    case 'active': return 'Procesando';
    case 'completed': return 'Completado';
    case 'error': return 'Error';
    case 'queued': return 'En Cola';
    default: return 'Desconocido';
  }
};

const getConnectionColor = (status: string) => {
  switch (status) {
    case 'connected': return 'success';
    case 'connecting': return 'warning';
    case 'reconnecting': return 'info';
    case 'error': return 'error';
    case 'disconnected': return 'secondary';
    default: return 'secondary';
  }
};

const getConnectionIcon = (status: string) => {
  switch (status) {
    case 'connected': return 'tabler-wifi';
    case 'connecting': return 'tabler-loader';
    case 'reconnecting': return 'tabler-refresh';
    case 'error': return 'tabler-wifi-off';
    case 'disconnected': return 'tabler-wifi-off';
    default: return 'tabler-help-circle';
  }
};

const formatTime = (timestamp?: string) => {
  if (!timestamp) return 'N/A';
  return new Date(timestamp).toLocaleTimeString();
};

const formatLastActivity = (timestamp: string) => {
  const now = new Date();
  const activity = new Date(timestamp);
  const diffSeconds = Math.floor((now.getTime() - activity.getTime()) / 1000);
  if (diffSeconds < 60) return `Hace ${diffSeconds}s`;
  if (diffSeconds < 3600) return `Hace ${Math.floor(diffSeconds / 60)}m`;
  return formatTime(timestamp);
};

const formatEstimatedTime = (seconds?: number) => {
  console.log("seconds", seconds);
  if (!seconds || seconds === 0) return 'N/A';
  if (seconds < 60) return `${seconds}s`;
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${minutes}m ${remainingSeconds}s`;
};
</script>

<style scoped>
.spin-animation {
  animation: spin 2s linear infinite !important;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

.process-list-card {
  background: linear-gradient(145deg, #fafafa 0%, #f5f5f5 100%);
}

.v-theme--dark .process-list-card {
  background: linear-gradient(145deg, #1e1e1e 0%, #2d2d2d 100%);
}

.process-item {
  border: 1px solid rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
}

.v-theme--dark .process-item {
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.active-process {
  background: rgba(25, 118, 210, 0.04);
  border-color: rgba(25, 118, 210, 0.2);
}

.queued-process {
  background: rgba(255, 152, 0, 0.04);
  border-color: rgba(255, 152, 0, 0.2);
}

/* ✅ NUEVO ESTILO PARA COMPLETADOS */
.completed-process {
  background: rgba(76, 175, 80, 0.04);
  border-color: rgba(76, 175, 80, 0.2);
  opacity: 0.8;
}
/* ✅ NUEVO ESTILO PARA ERRORES */
.error-process {
  background: rgba(244, 67, 54, 0.04); /* Rojo */
  border-color: rgba(244, 67, 54, 0.2); /* Borde rojo */
  opacity: 0.8;
}
.v-theme--dark .error-process {
  background: rgba(244, 67, 54, 0.1); /* Rojo más oscuro para tema oscuro */
  border-color: rgba(244, 67, 54, 0.3);
}


.process-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.metadata-grid {
  margin: 0 -8px;
}

.metadata-card {
  height: 100%;
  transition: all 0.2s ease;
}

.metadata-card:hover {
  transform: translateY(-1px);
}

.overall-progress-card {
  border: 1px solid rgba(25, 118, 210, 0.2);
}

.active-details,
.queued-details,
.completed-details {
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.v-theme--dark .active-details,
.v-theme--dark .queued-details,
.v-theme--dark .completed-details {
  border: 1px solid rgba(255, 255, 255, 0.08);
}
</style>
