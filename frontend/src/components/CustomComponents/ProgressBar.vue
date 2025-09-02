<template>
  <div class="progress-bar">
    <div class="progress" :style="{ width: progress + '%' }"></div>
    <span class="progress-text">{{ Math.floor(progress) }}%</span>
  </div>
</template>

<script setup lang="ts">
import { defineProps } from 'vue';

const props = defineProps({
  progress: {
    type: Number,
    default: 0
  }
});
</script>

<style scoped>
.progress-bar {
  width: 90%;
  background-color: #e0e0e0;
  border-radius: 10px;
  height: 30px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  position: relative;
  display: flex;
  align-items: center;
}

.progress {
  height: 100%;
  background-color: #7367F0;
  border-radius: 10px;
  transition: width 0.3s ease;
  position: absolute;
}

.progress-text {
  color: white;
  font-size: 14px;
  position: absolute;
  width: 100%;
  text-align: center;
  z-index: 2;
}
</style>
