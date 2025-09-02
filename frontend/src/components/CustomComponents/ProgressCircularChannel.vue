<script setup lang="ts">

const props = defineProps({
  channel: {
    type: String,
    required: true,
  },
  tooltipText: {
    type: String,
    required: false,
    default: 'Cargando...'
  },
});

const progressValue = ref<number>(0)

onMounted(() => {
  echoChannel()
})


const echoChannel = () => {

  window.Echo.channel(`${props.channel}`)
    .listen('ProgressCircular', (event: any) => {
      progressValue.value = event.progress

      if (event.progress == 100) {
        setTimeout(() => {
          progressValue.value = 0
        }, 1000);
      }

    });
}

const sizeText = computed(() => useAttrs().sizeText as string | undefined)


</script>
<template>
  <div>
    <VTooltip location="top" v-if="progressValue > 0">
      <template #activator="{ props }">
        <VProgressCircular :rotate="360" :size="60" :width="4" v-bind="{ ...props, ...$attrs }"
          :model-value="progressValue">
          {{ progressValue }}%
        </VProgressCircular>
      </template>
      <span>{{ props.tooltipText }}</span>
    </VTooltip>

  </div>
</template>
