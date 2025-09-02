<script lang="ts" setup>


const elementId = computed(() => {
  const attrs = useAttrs();
  const _elementIdToken = attrs.id || attrs.label;

  return _elementIdToken
    ? `app-textFile-field-${_elementIdToken}-${Math.random()
      .toString(36)
      .slice(2, 7)}`
    : undefined;
});

const label = computed(() => useAttrs().label as string | undefined);
const label2 = computed(() => useAttrs().label2 as string | undefined);
const requiredField = computed(
  () => useAttrs().requiredField as boolean | undefined
);
const tooltip = computed(
  () => useAttrs().tooltip as object | undefined
);

</script>
<template>
  <div>
    <VLabel v-if="label" :for="elementId" class="mb-1 text-body-2 text-high-emphasis d-block text-wrap">
      {{ label }}
      <span v-if="requiredField">&nbsp; <b class="text-warning">*</b></span>
      <VTooltip v-if="tooltip" :location="tooltip.location ?? 'top'">
        <template #activator="{ props }">
          <VIcon v-if="tooltip.icon" v-bind="props" :icon="tooltip.icon" />
        </template>
        <span>{{ tooltip.text }}</span>
      </VTooltip>
    </VLabel>
    <VFileInput v-bind="{
      ...$attrs,
      class: null,
      label: label2,
      id: elementId,
    }">

    </VFileInput>
  </div>
</template>
