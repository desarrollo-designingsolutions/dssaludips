<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
  url: {
    type: String,
    required: true
  },
  modelValue: {
    type: [Array, Object, String, Number],
    default: () => []
  },
  params: {
    type: [Object],
    default: () => { }
  },
  multiple: {
    type: Boolean,
    default: false
  },
  itemTitle: {
    type: String,
    default: 'title'
  },
  itemValue: {
    type: String,
    default: 'value'
  },
  searchParam: {
    type: String,
    default: 'searchQueryInfinite'
  },
  arrayInfo: {
    type: String,
    required: true,
  },
  disabled: {
    type: Boolean,
    required: false,
  },
  itemsData: {
    type: Array,
    required: false,
    default: () => [],
  },
  firstFetch: {
    type: Boolean,
    required: false,
    default: true,
  },
});

const emit = defineEmits(['update:modelValue']);

// Valor computado para manejar la doble vía
const localValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

// Estado reactivo
const items = ref<any[]>([]);
const search = ref('');
const page = ref(1);
const loading = ref(false);
const hasMore = ref(true);
const error = ref('');
const isSearching = ref(false); // Nueva variable para controlar si estamos en modo búsqueda
const fetching = ref(false);

// Debounce para la búsqueda
let searchTimeout: ReturnType<typeof setTimeout>;

// Función principal para cargar datos
const loadItems = async (newSearch = false) => {
  error.value = '';
  if (loading.value) return;

  loading.value = true;
  fetching.value = true;

  try {
    if (newSearch) {
      page.value = 1;
      items.value = [];
      hasMore.value = true;
      isSearching.value = !!search.value; // Marcamos que estamos en modo búsqueda si hay texto
    }

    const { data } = await useAxios(props.url).post({
      [props.searchParam]: search.value,
      page: page.value,
      ...props.params,
    })

    const arrayInfo = props.arrayInfo + "_arrayInfo";
    const countLinks = props.arrayInfo + "_countLinks";

    if (data.data) { // Si usa paginación estilo Laravel
      items.value = newSearch ? data.data : [...items.value, ...data.data];
      hasMore.value = data.current_page < data.last_page;
    } else { // Si es un endpoint personalizado
      items.value = newSearch ? data[arrayInfo] : [...items.value, ...data[arrayInfo]];
      hasMore.value = data[countLinks].length > 1;
    }

    page.value++;
  } catch (err) {
    error.value = 'Error al buscar datos.' + err;
  } finally {
    loading.value = false;
    fetching.value = false;
  }
};

// Búsqueda con debounce
watch(search, (newVal) => {
  clearTimeout(searchTimeout);

  // Si se borra la búsqueda y tenemos itemsData, restauramos los datos originales
  if (!newVal && props.itemsData.length > 0 && isSearching.value) {
    isSearching.value = false;
    items.value = [...props.itemsData];
    return;
  }

  // Solo hacemos búsqueda si hay texto
  if (newVal) {
    searchTimeout = setTimeout(() => loadItems(true), 500);
  }
});

// Watcher para itemsData, solo actualiza si no estamos en modo búsqueda
watch(() => props.itemsData, (newItemsData) => {
  if (newItemsData && newItemsData.length > 0 && !isSearching.value) {
    // Solo actualizamos los items si no estamos en medio de una búsqueda
    items.value = [...newItemsData];
  }
}, { deep: true });

// Cargar datos iniciales
onMounted(() => {
  // Si hay datos proporcionados, los usamos directamente
  if (props.itemsData.length > 0) {
    items.value = [...props.itemsData];
    return; // No hacemos petición si ya tenemos datos
  }

  // Si está deshabilitado, no hacemos petición
  if (props.disabled) {
    return;
  }

  // Solo hacemos la petición si firstFetch es true
  if (props.firstFetch) {
    loadItems();
  }
});

// Método para limpiar la búsqueda
const clearText = () => {
  search.value = '';
  // Si tenemos itemsData, restauramos los datos originales
  if (props.itemsData.length > 0) {
    isSearching.value = false;
    items.value = [...props.itemsData];
  } else if (isSearching.value) {
    // Si no tenemos itemsData pero estábamos buscando, recargamos los datos
    isSearching.value = false;
    loadItems(true);
  }
}

// Mensaje para el slot no-data basado en el valor de localValue
const noDataMessage = computed(() => {
  if (loading.value) {
    return 'Buscando información...';
  }
  if (error.value) {
    return error.value;
  }

  return isEmpty(search.value)
    ? 'Escribe algo para buscar...'
    : `No se encontraron resultados para "<strong>${search.value}</strong>".`;
});
</script>

<template>
  <AppSelect @blur="clearText" returnObject v-model="localValue" :items="items" :item-title="itemTitle"
    :item-value="itemValue" :multiple="multiple" :search="search" :loading="fetching"
    @update:search="(value) => search = value" clearable :disabled="props.disabled">
    <template #prepend-item class="sticky-search">
      <VListItem>
        <AppTextField v-model="search" placeholder="Teclee para buscar..." variant="outlined" density="compact"
          hide-details clearable @click:clear="loadItems(true)" />
      </VListItem>
    </template>
    <!-- Codigo que itera sobre las ranuras disponibles en el componente padre e individualmente rinde cada ranura con sus propias propiedades -->
    <template v-for="(_, name) in $slots" #[name]="slotProps">
      <slot :name="name" v-bind="slotProps || {}" />
    </template>
    <!-- Codigo que itera sobre las ranuras disponibles en el componente padre e individualmente rinde cada ranura con sus propias propiedades -->

    <template v-slot:no-data>
      <VListItem>
        <VListItemTitle class="text-center" v-html="noDataMessage"></VListItemTitle>
      </VListItem>
    </template>
  </AppSelect>
</template>
