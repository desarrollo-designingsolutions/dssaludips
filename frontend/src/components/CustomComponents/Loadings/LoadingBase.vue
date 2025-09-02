<template>
  <div>
    <LoadingV2 v-model:isLoading="isLoading" :progress="progress" :show-actions="true" @minimized="minimized = true"
      @restored="minimized = false" />
  </div>
</template>

<script setup lang="ts">
import { defineProps, ref } from 'vue';

const props = defineProps({
  progress: {
    type: Number,
    default: 0
  },
  isLoading: {
    type: Boolean,
    default: true
  }
});

// Estado de carga
const isLoading = ref(false);
const minimized = ref(false)

// Función para activar el estado de carga
const startLoading = () => {
  isLoading.value = true;
};

// Función para desactivar el estado de carga
const stopLoading = () => {
  isLoading.value = false;
};

// Retornamos los estados y funciones para usar en el template o en el componente padre
defineExpose({
  isLoading,
  startLoading,
  stopLoading
})
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  z-index: 99999;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 70%);
  block-size: 100%;
  inline-size: 100%;
  inset-block-start: 0;
  inset-inline-start: 0;
}

.loading-text {
  color: #3498db;
  font-size: 24px;
  margin-block-end: 20px;
}

.loading-spinner {
  border: 4px solid #f3f3f3;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  block-size: 50px;
  border-block-start: 4px solid #3498db;
  inline-size: 50px;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}
</style>
