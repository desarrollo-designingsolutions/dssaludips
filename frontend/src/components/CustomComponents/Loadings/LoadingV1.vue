<template>
  <!-- Overlay completo -->
  <Transition name="fade" appear>
    <div v-if="isLoading && !isMinimized" class="loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner-container">
          <div class="loading-spinner"></div>
          <div class="loading-pulse"></div>
        </div>

        <div class="loading-text-container">
          <h3 class="loading-title">{{ currentTitle }}</h3>
          <p class="loading-subtitle">{{ currentSubtitle }}</p>
        </div>

        <div class="progress-section">
          <div class="d-flex justify-space-between align-center mb-2">
            <span class="progress-label">Progreso</span>
            <span class="progress-percentage">
              {{ Math.round(clampedProgress) }}%
            </span>
          </div>
          <div class="progress-bar-container">
            <div class="progress-bar" :style="{ width: clampedProgress + '%' }">
              <div class="progress-shimmer"></div>
            </div>
          </div>
        </div>

        <div class="loading-steps">
          <div v-for="(step, index) in loadingSteps" :key="index" class="step-item">
            <div class="step-icon-container">
              <v-icon :icon="getStepIcon(index)" :color="getStepColor(index)" size="20" class="step-icon" />
            </div>
            <span class="step-text" :class="getStepTextClass(index)">
              {{ step }}
            </span>
          </div>
        </div>

        <div v-if="showTips && currentTip" class="loading-tips">
          <div class="tip-container">
            <v-icon icon="tabler-bulb" color="#F59E0B" size="20" />
            <p class="tip-text">{{ currentTip }}</p>
          </div>
        </div>

        <div v-if="showActions" class="action-buttons">
          <!-- <button class="cancel-button" @click="$emit('cancel')">
            <v-icon icon="tabler-close" size="16" />
            Cancelar
          </button> -->
          <button class="minimize-button" @click="minimize">
            <v-icon icon="tabler-window-minimize" size="16" />
            Minimizar
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Versión minimizada -->
  <Transition name="slide-up">
    <div v-if="isMinimized && isLoading" class="minimized-overlay">
      <div class="minimized-content" @click="restore">
        <div class="minimized-progress">
          <div class="progress-circle">
            {{ Math.round(clampedProgress) }}%
          </div>
        </div>
        <div class="minimized-text">
          <span>{{ currentTitle }}</span>
        </div>
        <!-- <button class="minimized-close" @click.stop="$emit('cancel')">
          <v-icon icon="tabler-x" size="16" />
        </button> -->
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useTheme } from 'vuetify'

const theme = useTheme()

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
  showActions: {
    type: Boolean,
    default: false
  },
  tips: {
    type: Array as () => string[],
    default: () => [
      '💡 Tip: Puedes cambiar entre modo claro y oscuro',
      '🚀 Optimizando el rendimiento de la aplicación',
      '⚡ Cargando recursos de forma inteligente',
      '🎨 Aplicando el tema personalizado',
      '🔧 Configurando las preferencias del usuario'
    ]
  },
  steps: {
    type: Array as () => string[],
    default: () => [
      'Inicializando componentes',
      'Cargando configuración',
      'Conectando servicios',
      'Preparando interfaz',
      'Finalizando proceso'
    ]
  }
})

const emit = defineEmits(['cancel', 'minimize', 'restore'])

// Estado local
const isMinimized = ref(false)
const currentTipIndex = ref(0)
const currentStepIndex = ref(0)
let tipInterval: NodeJS.Timeout | null = null

// Computed properties
const clampedProgress = computed(() => {
  return Math.max(0, Math.min(100, props.progress))
})

const currentTitle = computed(() => {
  const progress = clampedProgress.value
  if (progress < 20) return props.title
  if (progress < 40) return 'Cargando recursos...'
  if (progress < 60) return 'Procesando datos...'
  if (progress < 80) return 'Casi terminado...'
  if (progress < 95) return 'Finalizando...'
  return '¡Completado!'
})

const currentSubtitle = computed(() => {
  const progress = clampedProgress.value
  if (progress < 20) return props.subtitle
  if (progress < 40) return 'Descargando componentes necesarios'
  if (progress < 60) return 'Organizando la información'
  if (progress < 80) return 'Aplicando configuraciones finales'
  if (progress < 95) return 'Preparando la experiencia'
  return 'Todo está listo para usar'
})

const currentTip = computed(() => {
  return props.tips[currentTipIndex.value] || ''
})

const loadingSteps = computed(() => props.steps)

// Métodos para los pasos
const getStepIcon = (index: number) => {
  if (index < currentStepIndex.value) return 'tabler-circle-check'
  if (index === currentStepIndex.value) return 'tabler-loader'
  return 'tabler-circle'
}

const getStepColor = (index: number) => {
  if (index < currentStepIndex.value) return '#10B981' // verde
  if (index === currentStepIndex.value) return '#3B82F6' // azul
  return '#9CA3AF' // gris
}

const getStepTextClass = (index: number) => {
  if (index < currentStepIndex.value) return 'text-success'
  if (index === currentStepIndex.value) return 'text-primary'
  return 'text-gray'
}

// Minimizar y restaurar
function minimize() {
  isMinimized.value = true
  emit('minimize')
}

function restore() {
  isMinimized.value = false
  emit('restore')
}

// Watchers
watch(() => props.progress, (newProgress) => {
  const stepProgress = Math.floor((newProgress / 100) * props.steps.length)
  currentStepIndex.value = Math.min(stepProgress, props.steps.length - 1)
})

// Funciones de control
const startTipRotation = () => {
  if (props.showTips && props.tips.length > 1) {
    tipInterval = setInterval(() => {
      currentTipIndex.value = (currentTipIndex.value + 1) % props.tips.length
    }, 4000)
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
  if (props.isLoading) {
    startTipRotation()
  }
})

onUnmounted(() => {
  stopTipRotation()
})

watch(() => props.isLoading, (newValue) => {
  if (newValue) {
    startTipRotation()
  } else {
    stopTipRotation()
    isMinimized.value = false
  }
})
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(8px);
  background: rgba(255, 255, 255, 95%);
  block-size: 100%;
  inline-size: 100%;
  inset-block-start: 0;
  inset-inline-start: 0;
}

.loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px;
  border-radius: 16px;
  background: white;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 10%);
  inline-size: 90%;
  max-inline-size: 500px;
  text-align: center;
}

.loading-spinner-container {
  position: relative;
  margin-block-end: 30px;
}

.loading-spinner {
  position: relative;
  z-index: 2;
  border: 3px solid #f0f0f0;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  block-size: 60px;
  border-block-start: 3px solid #3498db;
  inline-size: 60px;
}

.loading-pulse {
  position: absolute;
  border: 2px solid #3498db;
  border-radius: 50%;
  animation: pulse 2s ease-in-out infinite;
  block-size: 80px;
  inline-size: 80px;
  inset-block-start: -10px;
  inset-inline-start: -10px;
  opacity: 0.3;
}

.loading-text-container {
  margin-block-end: 20px;
}

.loading-title {
  animation: fadeInUp 0.6s ease-out;
  color: #2c3e50;
  font-size: 24px;
  font-weight: 600;
  margin-block: 0 8px;
  margin-inline: 0;
}

.loading-subtitle {
  margin: 0;
  animation: fadeInUp 0.6s ease-out 0.2s both;
  color: #7f8c8d;
  font-size: 16px;
  line-height: 1.4;
}

/* Progress section */
.progress-section {
  inline-size: 100%;
  margin-block-end: 20px;
}

.progress-label {
  color: #4b5563;
  font-size: 14px;
  font-weight: 500;
}

.progress-percentage {
  color: #3b82f6;
  font-size: 14px;
  font-weight: 700;
}

.progress-bar-container {
  overflow: hidden;
  border-radius: 4px;
  background-color: #f3f4f6;
  block-size: 8px;
}

.progress-bar {
  position: relative;
  border-radius: 4px;
  background-color: #3b82f6;
  block-size: 100%;
  transition: inline-size 0.3s ease;
}

.progress-shimmer {
  position: absolute;
  animation: shimmer 2s infinite;
  background:
    linear-gradient(90deg,
      transparent,
      rgba(255, 255, 255, 50%),
      transparent);
  block-size: 100%;
  inline-size: 30%;
  inset-block-start: 0;
  inset-inline-start: 0;
}

/* Loading steps */
.loading-steps {
  inline-size: 100%;
  margin-block-end: 20px;
}

.step-item {
  display: flex;
  align-items: center;
  border-block-end: 1px solid #f3f4f6;
  padding-block: 8px;
  padding-inline: 0;
}

.step-item:last-child {
  border-block-end: none;
}

.step-icon-container {
  margin-inline-end: 12px;
}

.step-icon {
  animation: spin 1s linear infinite;
}

.step-text {
  flex-grow: 1;
  font-size: 14px;
  text-align: start;
}

.text-success {
  color: #10b981;
}

.text-primary {
  color: #3b82f6;
  font-weight: 500;
}

.text-gray {
  color: #9ca3af;
}

/* Tips section */
.loading-tips {
  inline-size: 100%;
  margin-block-start: 20px;
}

.tip-container {
  display: flex;
  align-items: flex-start;
  border-radius: 8px;
  background-color: #fffbeb;
  padding-block: 12px;
  padding-inline: 16px;
}

.tip-text {
  margin: 0;
  color: #92400e;
  font-size: 14px;
  line-height: 1.5;
  margin-inline-start: 10px;
  text-align: start;
}

/* Action buttons */
.action-buttons {
  display: flex;
  gap: 12px;
  margin-block-start: 24px;
}

.cancel-button,
.minimize-button {
  display: flex;
  align-items: center;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  padding-block: 8px;
  padding-inline: 16px;
  transition: all 0.2s ease;
}

.cancel-button {
  border: 1px solid #e5e7eb;
  background-color: white;
  color: #4b5563;
}

.cancel-button:hover {
  border-color: #d1d5db;
  background-color: #f9fafb;
}

.minimize-button {
  border: none;
  background-color: #eff6ff;
  color: #3b82f6;
}

.minimize-button:hover {
  background-color: #dbeafe;
}

/* Animations */
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

@keyframes pulse {

  0%,
  100% {
    opacity: 0.3;
    transform: scale(1);
  }

  50% {
    opacity: 0.1;
    transform: scale(1.1);
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

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }

  100% {
    transform: translateX(200%);
  }
}

/* Transition for the overlay */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

/* Dark mode */
.dark .loading-content {
  background-color: #1f2937;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 30%);
}

.dark .loading-title {
  color: #f9fafb;
}

.dark .loading-subtitle {
  color: #d1d5db;
}

.dark .progress-bar-container {
  background-color: #374151;
}

.dark .step-item {
  border-block-end-color: #374151;
}

.dark .step-text.text-gray {
  color: #6b7280;
}

.dark .tip-container {
  background-color: #453411;
}

.dark .tip-text {
  color: #fcd34d;
}

.dark .cancel-button {
  border-color: #4b5563;
  background-color: #374151;
  color: #e5e7eb;
}

.dark .cancel-button:hover {
  background-color: #4b5563;
}

.dark .minimize-button {
  background-color: #1e3a8a;
  color: #93c5fd;
}

.dark .minimize-button:hover {
  background-color: #1e40af;
}

/* Responsive design */
@media (max-width: 480px) {
  .loading-content {
    margin: 20px;
    max-inline-size: 320px;
    padding-block: 30px;
    padding-inline: 20px;
  }

  .loading-title {
    font-size: 20px;
  }

  .loading-subtitle {
    font-size: 14px;
  }

  .action-buttons {
    flex-direction: column;
    inline-size: 100%;
  }

  .cancel-button,
  .minimize-button {
    justify-content: center;
    inline-size: 100%;
  }
}

/* ------------------ */

/* Versión minimizada */

/* ------------------ */
.minimized-overlay {
  position: fixed;
  z-index: 99999;
  inset-block-end: 20px;
  inset-inline-end: 20px;
}

.minimized-content {
  display: flex;
  align-items: center;
  border-radius: 50px;
  background: white;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 10%);
  cursor: pointer;
  padding-block: 12px;
  padding-inline: 16px;
  transition: all 0.3s ease;
}

.minimized-content:hover {
  box-shadow: 0 15px 30px rgba(0, 0, 0, 15%);
  transform: translateY(-3px);
}

.minimized-progress {
  position: relative;
  margin-inline-end: 12px;
}

.progress-circle {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background-color: #3b82f6;
  block-size: 40px;
  color: white;
  font-size: 12px;
  font-weight: bold;
  inline-size: 40px;
}

.minimized-text {
  overflow: hidden;
  color: #2c3e50;
  font-size: 14px;
  font-weight: 500;
  margin-inline-end: 12px;
  max-inline-size: 200px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.minimized-close {
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 50%;
  background-color: #f3f4f6;
  block-size: 28px;
  color: #6b7280;
  cursor: pointer;
  inline-size: 28px;
  transition: all 0.2s ease;
}

.minimized-close:hover {
  background-color: #e5e7eb;
  color: #ef4444;
}

/* Animación para la versión minimizada */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

/* Modo oscuro para minimizado */
.dark .minimized-content {
  background: #1f2937;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 30%);
}

.dark .minimized-text {
  color: #f9fafb;
}

.dark .minimized-close {
  background-color: #374151;
  color: #9ca3af;
}

.dark .minimized-close:hover {
  background-color: #4b5563;
  color: #ef4444;
}
</style>
