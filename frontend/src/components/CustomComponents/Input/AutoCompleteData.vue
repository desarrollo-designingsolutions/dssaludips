<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import { useApi } from '@/composables/useApi'; // Asegúrate de tener este composable
const emit = defineEmits(["execute", "update:modelValue"])

 
const elementId = computed(() => {
  const attrs = useAttrs();
  const _elementIdToken = attrs.id || attrs.label;
  return _elementIdToken
    ? `app-autoCompleteData-field-${_elementIdToken}-${Math.random()
      .toString(36)
      .slice(2, 7)}`
    : undefined;
});

const { url } = defineProps({
  url: {
    type: String,
    required: true,
  }
})
const searchQuery = ref();
const options = ref([]);
const selectedOption = ref(null);
const loading = ref(false);
const error = ref('');
const isOpen = ref(false);

// Función para realizar la búsqueda
const fetchData = async () => {
  if (!searchQuery.value) {
    options.value = [];
    return;
  }

  error.value = '';

  if (!isObject(searchQuery.value) && !isEmpty(searchQuery.value)) {

    try {
      loading.value = true;
      const { data, response } = await useApi<any>(
        createUrl(url, {
          query: {
            string: searchQuery.value
          },
        })
      );

      if (response.value?.ok && data.value) {
        options.value = data.value.data;
      } else {
        options.value = [];
      }
    } catch (err) {
      error.value = 'Error al buscar datos.';
      options.value = [];
    } finally {
      loading.value = false;
    }
  }
};

// Aplicar debounce a la función fetchData
const debouncedFetchData = debounce(fetchData, 300);

watch(searchQuery, (newQuery) => {
  if (newQuery) {
    emit("update:modelValue", newQuery);

    debouncedFetchData();
  } else {
    options.value = [];
  }
});

// Manejar la selección de un elemento
const handleSelection = (value: any) => {
  emit('update:modelValue', value)
  selectedOption.value = value;
  isOpen.value = false; // Cierra el menú después de seleccionar

};

const handleSearchInput = (value: string) => {
  searchQuery.value = value;
  emit("update:modelValue", searchQuery.value);

};

const handleFocus = () => {
  isOpen.value = true;
};

const handleBlur = () => {
  isOpen.value = false;
};

// Mensaje para el slot no-data basado en el valor de searchQuery
const noDataMessage = computed(() => {
  if (loading.value) {
    return 'Buscando información...';
  }
  if (error.value) {
    return error.value;
  }

  return isEmpty(searchQuery.value)
    ? 'Escribe algo para buscar...'
    : `No se encontraron resultados para "<strong>${searchQuery.value}</strong>".`;
});

onMounted(() => {
  searchQuery.value = useAttrs().modelValue
  emit("update:modelValue", useAttrs().modelValue);
})

</script>

<template>
  <div>
    <!-- v-bind para que funcione siempre poner el v-bond de ultimo -->
    <AppCombobox @update:modelValue="handleSelection" :hide-no-data="false" :items="options" v-model="searchQuery"
      :error-messages="error ? [error] : []" clearable :menu-props="{ openOnFocus: false, autoSelectFirst: false }"
      :menu-visible="isOpen"  @search-input="handleSearchInput" @focus="handleFocus"
      @blur="handleBlur"  v-bind="{
        ...$attrs,
        id: elementId,
      }">
         <template v-slot:no-data>
        <v-list-item>
          <v-list-item-title v-html="noDataMessage"></v-list-item-title>
        </v-list-item>
      </template>
    </AppCombobox> 
 
  </div>
</template>
