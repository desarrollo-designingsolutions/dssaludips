<script setup lang="ts">
const { items, loading, select } = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  select: {
    type: Object,
    default: () => ({})
  },
  multiple: {
    type: Boolean,
    default: () => (false)
  }
})

const emit = defineEmits(["clearVmodel"])

const stateSearch = ref(false)
onMounted(() => {
  if (select.totalLinks.value > 1) {
    stateSearch.value = true
  }
})

const next = () => {
  emit("clearVmodel", true)
  select.next()
}

const prev = () => {
  emit("clearVmodel", true)
  select.prev()
}

watch(select.dataNueva, () => {
  if (stateSearch.value == false) {
    if (select.totalLinks.value > 1) {
      stateSearch.value = true
    }
  }
})


</script>

<template>
  <AppSelect v-bind="$attrs" :items="select.dataNueva.value" :multiple="multiple">
    <template v-slot:prepend-item v-if="stateSearch">
      <div>
        <VTextField clearable placeholder="Buscar...	" class="mb-2" v-model="select.searchQueryInfinite.value" />
        <v-divider></v-divider>
      </div>
    </template>


    <template v-slot:append-item v-if="stateSearch">
      <div>
        <v-divider> </v-divider>
        <div class="text-center" v-if="loading">
          <v-progress-circular size="24" color="primary" indeterminate></v-progress-circular>
        </div>
        <div v-if="!loading" class="d-flex justify-content-between aling-items-center p-2">
          <VRow>
            <VCol sm="6" class="text-left">
              <VBtn class="mt-2 ml-5" :disabled="select.page.value <= 1" @click="prev()" color="primary" icon>
                <VIcon icon="tabler-chevron-left"></VIcon>
              </VBtn>
            </VCol>
            <VCol sm="6" class="text-right">
              <VBtn class="mt-2 mr-5" :disabled="select.page.value >=
                select.totalLinks.value
                " @click="next()" color="primary" icon>
                <VIcon icon="tabler-chevron-right"></VIcon>
              </VBtn>
            </VCol>
          </VRow>
        </div>
      </div>
    </template>

      <!-- Codigo que itera sobre las ranuras disponibles en el componente padre e individualmente rinde cada ranura con sus propias propiedades -->
      <template v-for="(_, name) in $slots" #[name]="slotProps">
      <slot :name="name" v-bind="slotProps || {}" />
    </template>
    <!-- Codigo que itera sobre las ranuras disponibles en el componente padre e individualmente rinde cada ranura con sus propias propiedades -->

    
  </AppSelect>
</template>
