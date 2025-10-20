<script lang="ts" setup>
defineOptions({
  name: 'AppTextarea',
  inheritAttrs: false,
})

// const { class: _class, label, variant: _, ...restAttrs } = useAttrs()

const elementId = computed(() => {
  const attrs = useAttrs()
  const _elementIdToken = attrs.id || attrs.label

  return _elementIdToken ? `app-textarea-${_elementIdToken}-${Math.random().toString(36).slice(2, 7)}` : undefined
})

const label = computed(() => useAttrs().label as string | undefined)
const requiredField = computed(
  () => useAttrs().requiredField as boolean | undefined
);
const tooltip = computed(
  () => useAttrs().tooltip as object | undefined
);
</script>

<template>
  <div class="app-textarea flex-grow-1" :class="$attrs.class">
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
    <VTextarea v-bind="{
      ...$attrs,
      class: null,
      label: undefined,
      variant: 'outlined',
      id: elementId,
    }">
      <template v-for="(_, name) in $slots" #[name]="slotProps">
        <slot :name="name" v-bind="slotProps || {}" />
      </template>
    </VTextarea>
  </div>
</template>
