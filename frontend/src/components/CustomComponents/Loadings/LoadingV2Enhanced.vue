<template>
  <v-overlay v-if="!isMinimized && isLoading" :model-value="isLoading"
    class="loading-overlay d-flex align-center justify-center" :persistent="true" :opacity="0.98" :z-index="9999">
    <v-card class="loading-card" elevation="0" rounded="xl" min-width="420" width="90%" max-width="500">
      <v-card-text class="pa-8">
        <!-- Header Section -->
        <div class="text-center mb-6">
          <!-- Status Indicator -->
          <div class="d-flex align-center justify-center mb-4">
            <v-avatar size="72" class="loading-avatar" :color="progress >= 100 ? 'success' : 'primary'">
              <v-icon :size="progress >= 100 ? '32' : '28'" :icon="currentIcon" color="white"
                :class="progress < 100 ? 'spin-animation' : ''" />
            </v-avatar>
          </div>
          <!-- Title and Subtitle -->
          <h2 class="text-h5 font-weight-bold text-white mb-2">
            {{ currentTitle }}
          </h2>
          <p class="text-body-1 text-grey-400 mb-0">
            {{ currentSubtitle }}
          </p>
        </div>

        <!-- Progress Section -->
        <div class="progress-section mb-6">
          <div class="d-flex justify-space-between align-center mb-3">
            <span class="text-body-2 font-weight-medium text-grey-300">
              Progreso
            </span>
            <div class="d-flex align-center">
              <span class="text-h6 font-weight-bold text-primary mr-2">
                {{ Math.round(progress) }}%
              </span>
              <v-icon v-if="progress >= 100" icon="tabler-eye" size="16" color="grey-400" />
            </div>
          </div>
          <v-progress-linear :model-value="progress" height="6" rounded color="primary" bg-color="grey-800"
            class="loading-progress" />
        </div>

        <!-- Steps Section -->
        <div class="steps-section mb-6">
          <v-card class="steps-card pa-4" color="grey-900" variant="elevated" elevation="2" rounded="lg">
            <div v-for="(step, index) in loadingSteps" :key="index" class="step-item d-flex align-center mb-3"
              :class="{ 'mb-0': index === loadingSteps.length - 1 }">
              <v-icon :icon="getStepIcon(index)" :color="getStepColor(index)" size="20" class="step-icon mr-3"
                :class="{ 'spin-animation': index === currentStepIndex && progress < 100 }" />
              <span class="text-body-2" :class="getStepTextClass(index)">
                {{ step }}
              </span>
            </div>
          </v-card>
        </div>

        <!-- Tips Section -->
        <div v-if="showTips" class="tips-section mb-6">
          <v-card class="tip-card pa-3" color="grey-800" variant="flat" rounded="lg">
            <div class="d-flex align-center">
              <v-icon icon="tabler-bulb" color="warning" size="20" class="mr-3" />
              <span class="text-body-2 text-grey-300 tip-text">
                {{ currentTip }}
              </span>
            </div>
          </v-card>
        </div>

        <!-- Action Buttons -->
        <div class="actions-section d-flex justify-space-between align-center">
          <v-btn variant="elevated" color="info" class="text-none" @click="minimize">
            <v-icon start icon="tabler-minimize" size="18" />
            Minimizar
          </v-btn>
          <div class="d-flex gap-2">
            <v-btn v-if="showMultipleButton" variant="elevated" color="warning" class="text-none"
              @click="$emit('show-multiple')">
              <v-icon start icon="tabler-stack-2" size="18" />
              Ver todos ({{ totalProcesses || 2 }})
            </v-btn>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-overlay>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
  progress: {
    type: Number,
    default: 0,
    validator: (value: number) => value >= 0 && value <= 100
  },
  isLoading: {
    type: Boolean,
    default: true
  },
  title: {
    type: String,
    default: 'Procesando'
  },
  subtitle: {
    type: String,
    default: 'Por favor espera mientras completamos la operación'
  },
  showTips: {
    type: Boolean,
    default: true
  },
  showMultipleButton: {
    type: Boolean,
    default: false
  },
  totalProcesses: {
    type: Number,
    default: 2
  },
  tips: {
    type: Array as () => string[],
    default: () => [
      '💡 Puedes cargar múltiples archivos simultáneamente',
      '🚀 El proceso continuará ejecutándose en segundo plano',
      '⚡ Recibirás una notificación cuando termine',
      '🎨 Puedes cambiar entre modo claro y oscuro',
      '🔧 El progreso se guarda automáticamente'
    ]
  },
  steps: {
    type: Array as () => string[],
    default: () => [
      'Validando archivo Excel',
      'Procesando estructura de datos',
      'Importando registros de estudiantes',
      'Guardando notas en base de datos',
      'Finalizando importación'
    ]
  }
})

const emit = defineEmits(['minimized', 'restored', 'completed', 'show-multiple'])

// Estado local
const currentTipIndex = ref(0)
const currentStepIndex = ref(0)
const isMinimized = ref(false)
let tipInterval: NodeJS.Timeout | null = null

// Funciones de control
const minimize = () => {
  isMinimized.value = true
  emit('minimized')
}

const restore = () => {
  isMinimized.value = false
  emit('restored')
}

// Computed properties
const progress = computed(() => {
  const progressValue = Math.max(0, Math.min(100, props.progress))
  if (progressValue >= 100) {
    emit('completed')
  }
  return progressValue
})

const currentTitle = computed(() => {
  const progressValue = progress.value;

  if (progressValue >= 100) return '🎉 ¡Proceso completado con éxito!';
  if (progressValue >= 90) return '🔍 Realizando verificaciones finales...';
  if (progressValue >= 80) return '📤 Guardando resultados en el sistema...';
  if (progressValue >= 70) return '⚙️ Optimizando datos procesados...';
  if (progressValue >= 60) return '📊 Procesando información recolectada...';
  if (progressValue >= 50) return '📦 Organizando datos intermedios...';
  if (progressValue >= 40) return '🔄 Cargando módulos secundarios...';
  if (progressValue >= 30) return '🚀 Proceso en marcha, avanzando...';
  if (progressValue >= 20) return '⚡ Preparando componentes principales...';
  if (progressValue >= 10) return '🔌 Conectando con los servicios...';
  if (progressValue >= 0) return '⏳ Inicializando sistema...';

  return props.title || 'Preparando carga...';
});

const currentSubtitle = computed(() => {
  const progressValue = progress.value
  if (progressValue >= 100) return 'Todo está listo para usar'
  if (progressValue >= 80) return 'Aplicando configuraciones finales'
  if (progressValue >= 60) return 'Organizando la información'
  if (progressValue >= 40) return 'Descargando componentes necesarios'
  if (progressValue >= 0) return 'Preparando el sistema'
  return props.subtitle
})

const currentIcon = computed(() => {
  const progressValue = progress.value
  if (progressValue >= 100) return 'tabler-checks'
  if (progressValue >= 80) return 'tabler-circle-check'
  if (progressValue >= 60) return 'tabler-database'
  if (progressValue >= 40) return 'tabler-settings'
  if (progressValue >= 20) return 'tabler-rotate-2'
  return 'tabler-rotate-clockwise-2'
})

const currentTip = computed(() => {
  return props.tips[currentTipIndex.value] || ''
})

const loadingSteps = computed(() => props.steps)

// Watch para actualizar el step actual basado en el progreso
watch(() => props.progress, (newProgress) => {
  const stepProgress = Math.floor((newProgress / 100) * props.steps.length)
  currentStepIndex.value = Math.min(stepProgress, props.steps.length - 1)
})

// Métodos para los pasos
const getStepIcon = (index: number) => {
  if (index < currentStepIndex.value) return 'tabler-circle-check-filled'
  if (index === currentStepIndex.value && progress.value < 100) return 'tabler-loader-2'
  if (index === currentStepIndex.value && progress.value >= 100) return 'tabler-circle-check-filled'
  return 'tabler-circle'
}

const getStepColor = (index: number) => {
  if (index < currentStepIndex.value) return 'success'
  if (index === currentStepIndex.value) return progress.value >= 100 ? 'success' : 'primary'
  return 'grey-600'
}

const getStepTextClass = (index: number) => {
  if (index < currentStepIndex.value) return 'text-success font-weight-medium'
  if (index === currentStepIndex.value) return progress.value >= 100 ? 'text-success font-weight-medium' : 'text-primary font-weight-medium'
  return 'text-grey-500'
}

// Funciones de control de tips
const startTipRotation = () => {
  if (props.showTips && props.tips.length > 1) {
    tipInterval = setInterval(() => {
      currentTipIndex.value = (currentTipIndex.value + 1) % props.tips.length
    }, 5000)
  }
}

const stopTipRotation = () => {
  if (tipInterval) {
    clearInterval(tipInterval)
    tipInterval = null
  }
}

// Lifecycle
onMounted(() => {
  startTipRotation()
})

onUnmounted(() => {
  stopTipRotation()
})

watch(() => props.isLoading, (newValue) => {
  if (newValue) {
    startTipRotation()
  } else {
    stopTipRotation()
  }
})

// Exponer funciones
defineExpose({
  minimize,
  restore,
  isMinimized
})
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

.loading-overlay {
  position: fixed !important;
  backdrop-filter: blur(16px) saturate(180%) !important;
  background: rgba(15, 23, 42, 0.85) !important;
  height: 100vh !important;
  width: 100vw !important;
  top: 0 !important;
  left: 0 !important;
}

.loading-card {
  background: linear-gradient(145deg, #1e293b 0%, #334155 100%) !important;
  border: 1px solid rgba(148, 163, 184, 0.1) !important;
  backdrop-filter: blur(20px) !important;
  box-shadow:
    0 25px 50px -12px rgba(0, 0, 0, 0.5),
    0 0 0 1px rgba(255, 255, 255, 0.05) !important;
}

.loading-avatar {
  animation: avatarPulse 3s ease-in-out infinite;
  box-shadow:
    0 0 30px rgba(59, 130, 246, 0.4),
    0 0 60px rgba(59, 130, 246, 0.2);
  transition: all 0.3s ease;
}

.loading-progress {
  position: relative;
  overflow: hidden;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
}

.steps-card {
  background: rgba(17, 24, 39, 0.8) !important;
  border: 1px solid rgba(75, 85, 99, 0.3) !important;
}

.step-item {
  transition: all 0.3s ease;
}

.step-icon {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tip-card {
  background: rgba(31, 41, 55, 0.8) !important;
  border: 1px solid rgba(75, 85, 99, 0.2) !important;
  animation: fadeInUp 0.6s ease-out;
  transition: all 0.3s ease;
}

.tip-card:hover {
  background: rgba(31, 41, 55, 0.9) !important;
  transform: translateY(-1px);
}

.tip-text {
  animation: fadeIn 0.8s ease-out;
  line-height: 1.5;
}

.actions-section {
  border-top: 1px solid rgba(75, 85, 99, 0.2);
  padding-top: 1.5rem;
  margin-top: 0.5rem;
}

/* Enhanced Animations */
@keyframes avatarPulse {

  0%,
  100% {
    box-shadow:
      0 0 30px rgba(59, 130, 246, 0.4),
      0 0 60px rgba(59, 130, 246, 0.2);
    transform: scale(1);
  }

  50% {
    box-shadow:
      0 0 40px rgba(59, 130, 246, 0.6),
      0 0 80px rgba(59, 130, 246, 0.3);
    transform: scale(1.02);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}

/* Responsive Design */
@media (max-width: 600px) {
  .loading-card {
    margin: 16px;
    min-width: unset;
  }

  .loading-avatar {
    width: 60px !important;
    height: 60px !important;
  }

  .actions-section {
    flex-direction: column;
    gap: 12px;
  }
}

/* Dark theme optimizations */
.v-theme--dark .loading-card {
  background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%) !important;
}

.v-theme--dark .steps-card {
  background: rgba(15, 23, 42, 0.9) !important;
}

.v-theme--dark .tip-card {
  background: rgba(15, 23, 42, 0.8) !important;
}

/* Smooth transitions for all interactive elements */
* {
  transition: all 0.2s ease;
}

/* Enhanced button styles */
.v-btn {
  text-transform: none !important;
  font-weight: 500 !important;
  border-radius: 8px !important;
}

.v-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
