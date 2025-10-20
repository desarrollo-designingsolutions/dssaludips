<script lang="ts" setup>
import { useGlobalLoading } from '@/composables/useGlobalLoading' 

const globalLoading = useGlobalLoading()

// ✅ COMPUTED PARA DETERMINAR SI HAY PROCESOS ACTIVOS
const hasActiveOrQueuedProcesses = computed(() => {
  return globalLoading.activeProcesses.value.length > 0 || globalLoading.queuedProcesses.value.length > 0
})

// ✅ COMPUTED PARA EL ICONO DINÁMICO
const currentIcon = computed(() => {
  if (hasActiveOrQueuedProcesses.value) {
    return 'tabler-blend-mode' // Icono que se anima mejor cuando hay procesos activos
  }

  // Si solo hay procesos completados o no hay procesos
  if (globalLoading.completedProcesses.value.length > 0) {
    return 'tabler-history' // Icono de historial para procesos completados
  }

  return 'tabler-stack-3' // Icono por defecto cuando no hay procesos
})

// ✅ COMPUTED PARA EL COLOR DINÁMICO
const currentColor = computed(() => {
  if (globalLoading.activeProcesses.value.length > 0) {
    return 'rgb(var(--v-theme-primary))' // Azul para procesos activos
  }

  if (globalLoading.queuedProcesses.value.length > 0) {
    return 'rgb(var(--v-theme-warning))' // Naranja para procesos en cola
  }

  if (globalLoading.completedProcesses.value.length > 0) {
    return 'rgb(var(--v-theme-success))' // Verde para completados
  }

  return 'rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity))' // Color por defecto
})

// ✅ COMPUTED PARA EL TEXTO DEL TOOLTIP
const tooltipText = computed(() => {
  const total = globalLoading.allProcesses.value.length
  const active = globalLoading.activeProcesses.value.length
  const queued = globalLoading.queuedProcesses.value.length
  const completed = globalLoading.completedProcesses.value.length

  if (total === 0) {
    return 'No hay procesos'
  }

  let text = `Ver Todas (${total})`

  if (active > 0) {
    text += ` • ${active} activo${active !== 1 ? 's' : ''}`
  }

  if (queued > 0) {
    text += ` • ${queued} en cola`
  }

  if (completed > 0) {
    text += ` • ${completed} completado${completed !== 1 ? 's' : ''}`
  }

  return text
})

// ✅ COMPUTED PARA DETERMINAR SI EL BOTÓN DEBE ESTAR VISIBLE
const shouldShowButton = computed(() => {
  return globalLoading.allProcesses.value.length > 0
})

// ✅ COMPUTED PARA LAS CLASES DEL ICONO (SIMPLIFICADO)
const iconClasses = computed(() => {
  const classes = ['loading-icon']

  if (hasActiveOrQueuedProcesses.value) {
    classes.push('spin-animation')
  }

  return classes.join(' ')
})
</script>

<template>
  <div>
    <IconBtn :color="currentColor" @click="globalLoading.showProcessListModal()" class="loading-btn" :class="{
      'loading-btn--animated': hasActiveOrQueuedProcesses,
      'loading-btn--active': globalLoading.activeProcesses.value.length > 0,
      'loading-btn--queued': globalLoading.queuedProcesses.value.length > 0,
      'loading-btn--completed': globalLoading.completedProcesses.value.length > 0 && globalLoading.activeProcesses.value.length === 0
    }">
      <VTooltip activator="parent" location="bottom">
        <span class="text-capitalize">{{ tooltipText }}</span>
      </VTooltip>

      <!-- ✅ ICONO CON ANIMACIÓN CONDICIONAL -->
      <VIcon :icon="currentIcon" :class="iconClasses" size="20" />

      <!-- ✅ BADGE CON CONTADOR -->
      <VBadge v-if="globalLoading.allProcesses.value.length > 0" :content="globalLoading.allProcesses.value.length"
        :color="globalLoading.activeProcesses.value.length > 0 ? 'primary' :
          globalLoading.queuedProcesses.value.length > 0 ? 'warning' : 'success'" class="loading-badge"
        :class="{ 'loading-badge--pulse': hasActiveOrQueuedProcesses }" />
    </IconBtn>
  </div>
</template>

<style scoped>
/* ✅ ANIMACIÓN DE ROTACIÓN SIMPLIFICADA */
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

@keyframes badgePulse {
  0%,
  100% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.15);
  }
}

@keyframes buttonGlow {
  0%,
  100% {
    box-shadow: 0 0 5px rgba(var(--v-theme-primary-rgb), 0.3);
  }

  50% {
    box-shadow: 0 0 15px rgba(var(--v-theme-primary-rgb), 0.6);
  }
}

/* ✅ CLASE DE ANIMACIÓN PRINCIPAL */
.loading-icon.spin-animation {
  animation: spin 1.5s linear infinite !important;
  transform-origin: center !important;
}

/* ✅ ESTILOS PARA EL BOTÓN */
.loading-btn {
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

  /* border-radius: 12px !important; */
}

.loading-btn--animated {
  animation: buttonGlow 3s ease-in-out infinite;
}

.loading-btn--active {
  background-color: rgba(var(--v-theme-primary-rgb), 0.1) !important;
}

.loading-btn--queued {
  background-color: rgba(var(--v-theme-warning-rgb), 0.1) !important;
}

.loading-btn--completed {
  background-color: rgba(var(--v-theme-success-rgb), 0.1) !important;
}

.loading-btn:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 15%);
  transform: translateY(-2px);
}

/* ✅ ESTILOS PARA EL ICONO */
.loading-icon {
  display: inline-block;
  transition: transform 0.3s ease;
}

.loading-btn:hover .loading-icon:not(.spin-animation) {
  transform: scale(1.1);
}

/* ✅ ESTILOS PARA EL BADGE */
.loading-badge {
  position: absolute !important;
  inset-block-start: -6px !important;
  inset-inline-end: -6px !important;
}

.loading-badge--pulse {
  animation: badgePulse 2s ease-in-out infinite;
}

/* ✅ REGLAS ESPECÍFICAS PARA VUETIFY */
:deep(.v-icon.spin-animation) {
  animation: spin 1.5s linear infinite !important;
  transform-origin: center !important;
}

/* ✅ FALLBACK ADICIONAL CON MAYOR ESPECIFICIDAD */
.v-btn :deep(.v-icon.spin-animation) {
  animation: spin 1.5s linear infinite !important;
  transform-origin: center !important;
}

.loading-btn :deep(.v-icon.spin-animation) {
  animation: spin 1.5s linear infinite !important;
  transform-origin: center !important;
}

/* ✅ RESPONSIVE */
@media (max-width: 600px) {
  .loading-badge {
    inset-block-start: -4px !important;
    inset-inline-end: -4px !important;
  }

  .loading-btn {
    min-block-size: 44px;
    min-inline-size: 44px;
  }
}

/* ✅ ESTADOS ADICIONALES PARA MEJOR FEEDBACK VISUAL */
.loading-btn--animated:hover {
  animation: none;
  box-shadow: 0 0 20px rgba(var(--v-theme-primary-rgb), 0.8);
}

.loading-btn:active {
  transform: translateY(0);
  transition: transform 0.1s ease;
}

/* ✅ MEJORAS PARA ACCESIBILIDAD */
@media (prefers-reduced-motion: reduce) {
  .spin-animation,
  .loading-badge--pulse,
  .loading-btn--animated {
    animation: none !important;
  }

  .loading-btn {
    transition: none;
  }
}
</style>
