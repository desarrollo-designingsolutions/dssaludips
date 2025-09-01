<script setup lang="ts">
import { TableOptions } from '@/components/CustomComponents/Table/TableOptions'; // Ajusta la ruta según tu proyecto 
import { defineEmits, defineProps, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

/**
 * Componente TableFullNew: una tabla configurable para Vue 3 con Vuetify.
 * Permite mostrar datos de una API con opciones de paginación, ordenamiento, selección y acciones personalizables.
 * Diseñado para integrarse con FilterDialogNew para filtrado dinámico.
 */

const props = defineProps<{
  options: TableOptions;
  'onUpdate:selected'?: (value: any[]) => void; // Para v-model
  disableUrlUpdate?: boolean; // Nueva prop para deshabilitar la actualización de URL
}>();

const emit = defineEmits<{
  (e: 'view', item: any): void;
  (e: 'edit', item: any): void;
  (e: 'deleteSuccess', id: string | number): void;
  (e: 'dataFetched', data: any): void;
  (e: 'update:selected', value: any[]): void; // Emit para v-model  
  (e: 'update:loading', value: boolean): void; // Emit para loading 
}>();

const loading = ref<boolean>(false);
const tableData = ref<any[]>([]);
const sortBy = ref<any[]>([]);
const router = useRouter();
const route = useRoute();
const isFetching = ref(false);
const manualParams = ref<object | null>(null); // Para almacenar parámetros manuales cuando disableUrlUpdate es true

// Configuración reactiva basada en props.options
const options = reactive<TableOptions>({
  url: props.options.url,
  headers: props.options.headers || [],
  paramsGlobal: props.options.paramsGlobal || {},
  showSelect: props.options.showSelect || false,
  multiSort: props.options.multiSort || true,
  sortBy: props.options.sortBy || [],
  selected: props.options.selected || [],
  tableData: props.options.tableData || [],
  pagination: {
    show: props.options.pagination?.show ?? true,
    rowsPerPage: props.options.pagination?.rowsPerPage || 10,
    currentPage: props.options.pagination?.currentPage || 1,
    totalPages: props.options.pagination?.totalPages || 0,
    totalItems: props.options.pagination?.totalItems || 0,
  },
  actions: {
    view: { show: props.options.actions?.view?.show ?? true, icon: props.options.actions?.view?.icon || 'tabler-eye', tooltip: props.options.actions?.view?.tooltip || 'Ver' },
    edit: { show: props.options.actions?.edit?.show ?? true, icon: props.options.actions?.edit?.icon || 'tabler-pencil', tooltip: props.options.actions?.edit?.tooltip || 'Editar' },
    delete: {
      show: props.options.actions?.delete?.show ?? true,
      url: props.options.actions?.delete?.url || null,
      icon: props.options.actions?.delete?.icon || 'tabler-trash',
      tooltip: props.options.actions?.delete?.tooltip || 'Eliminar'
    },
    changeStatus: {
      show: props.options.actions?.changeStatus?.show ?? true,
      url: props.options.actions?.changeStatus?.url || null,
      dbField: props.options.actions?.changeStatus?.dbField || 'is_active',
      btnActive: {
        text: props.options.actions?.changeStatus?.btnActive?.text || 'Activo',
        value: props.options.actions?.changeStatus?.btnActive?.value ?? 1,
        newFunction: props.options.actions?.changeStatus?.btnActive?.newFunction
      },
      btnInactive: {
        text: props.options.actions?.changeStatus?.btnInactive?.text || 'Inactivo',
        value: props.options.actions?.changeStatus?.btnInactive?.value ?? 0,
        newFunction: props.options.actions?.changeStatus?.btnInactive?.newFunction
      }
    },
    style: props.options.actions?.style || 'dropdown',
  },
});

// Sincronizamos selected con el v-model
const selected = ref(options.selected);
watch(selected, (newValue) => {
  emit('update:selected', newValue); // Emitimos cambios al padre
});

// Observamos cambios en loading y los emitimos
watch(loading, (newValue) => {
  emit('update:loading', newValue);
});

let fetchDebounceTimeout: NodeJS.Timeout | null = null;

/**
 * Obtiene los datos de la tabla desde la API y actualiza la URL del navegador.
 * @param page Número de página opcional para la paginación.
 * @param fromWatch Indica si la llamada viene del watcher.
 * @param force Indica si se debe forzar la petición incluso si ya hay una en curso.
 * @param customParams Parámetros personalizados para la petición (usado cuando disableUrlUpdate es true).
 */
const fetchTableData = async (page: number | null = null, fromWatch = false, force = false, customParams: object | null = null) => {
  if (!options.url || (isFetching.value && !force)) return; // Solo evita si no es forzado

  // Clear any pending debounce
  if (fetchDebounceTimeout) {
    clearTimeout(fetchDebounceTimeout);
  }

  // Set a new debounce
  fetchDebounceTimeout = setTimeout(async () => {
    isFetching.value = true;
    loading.value = true;
    if (page !== null) options.pagination.currentPage = page;

    // Si tenemos parámetros personalizados, los usamos
    if (customParams !== null) {
      manualParams.value = customParams;
    }

    // Determinamos qué parámetros usar para la consulta
    const sourceParams = props.disableUrlUpdate && (manualParams.value || customParams)
      ? (customParams || manualParams.value)
      : route.query;

    const sortQuery = options.sortBy.length
      ? options.sortBy.map(s => `${s.order === 'desc' ? '-' : ''}${s.key}`).join(',')
      : '';

    // Construimos los parámetros de consulta
    const queryParams = {
      page: options.pagination.currentPage.toString(),
      perPage: options.pagination.rowsPerPage.toString(),
      ...(sortQuery && { sort: sortQuery }),
      ...options.paramsGlobal,
    };

    // Si no estamos usando disableUrlUpdate, añadimos los parámetros de options.params
    if (!props.disableUrlUpdate) {
      Object.assign(queryParams, options.params);
    }

    // Si estamos usando parámetros personalizados, los combinamos
    if (props.disableUrlUpdate && (manualParams.value || customParams)) {
      // Añadimos todos los filtros del objeto sourceParams
      for (const key in sourceParams) {
        if (key.startsWith('filter[')) {
          queryParams[key] = sourceParams[key];
        }
      }
    }

    // Solo actualizamos la URL si no está deshabilitado
    if (!props.disableUrlUpdate && !fromWatch) {
      router.push({ query: queryParams });
    }

    const { data, response } = await useAxios(`${options.url}`).get({ params: queryParams });

    loading.value = false;
    isFetching.value = false;

    if (response.status == 200 && data && data.code === 200) {
      tableData.value = data.tableData || [];
      options.pagination.totalPages = data.lastPage || 0;
      options.pagination.totalItems = data.totalData || 0; // Ajusté totalData a total
      if (data.currentPage && !fromWatch) {
        options.pagination.currentPage = data.currentPage;
      }
      options.pagination.rowsPerPage = data.totalPage || options.pagination.rowsPerPage;
      options.tableData = data.tableData || [];
      // Sincronizamos rowsPerPageInput con el valor confirmado por la API
      rowsPerPageInput.value = options.pagination.rowsPerPage;
      emit('dataFetched', data);
    }

    fetchDebounceTimeout = null;
  }, 100); // Small delay to prevent duplicate calls
};

/**
 * Cambia el estado de un ítem enviando una petición a la API.
 * @param obj Objeto que representa el ítem.
 * @param field Campo de la base de datos a modificar.
 * @param value Nuevo valor para el campo.
 */
const changeStatus = async (obj: object, field: string, value: number | string) => {
  const { data, response } = await useAxios(`${options.actions.changeStatus.url}`).post({
    id: obj.id,
    value: value,
    field: field,
  });

  if (response.status == 200 && data) {
    obj[field] = value;
    obj.is_active = value; // Actualizamos is_active si es el campo por defecto
  }
};

/**
 * Ejecuta una función personalizada o changeStatus según corresponda.
 * @param newFunction Función opcional a ejecutar.
 * @param item Ítem sobre el que se ejecuta la acción.
 * @param dbField Campo de la base de datos.
 * @param newValue Nuevo valor a establecer.
 */
type FunctionNewAction = (item: object) => void;
const callActionOrChangeStatus = (newFunction: FunctionNewAction | undefined, item: object, dbField: string, newValue: number | string) => {
  if (newFunction && typeof newFunction === 'function') {
    newFunction(item); // Ejecuta la función personalizada
  } else if (newFunction === undefined) {
    changeStatus(item, dbField, newValue); // Ejecuta changeStatus si no hay función
  }
  // Si newFunction es null, no se hace nada
};

/**
 * Maneja el cambio de ordenamiento.
 * @param sortByData Datos de ordenamiento desde VDataTable.
 */
const updateSortBy = (sortByData: any) => {
  sortBy.value = sortByData;
  options.sortBy = sortByData;
  fetchTableData(null, false);
};

/**
 * Ejecuta la acción de eliminar un ítem tras confirmación.
 * @param id ID del ítem a eliminar.
 */
const deleteItem = async (id: string | number) => {
  if (!options.actions.delete?.show || !options.actions.delete?.url) return;

  loading.value = true;
  const deleteUrl = `${options.actions.delete.url}/${id}`;
  const { data, response } = await useAxios(deleteUrl).delete();
  loading.value = false;

  if (data && (data.code === 200 || !data.code)) {
    emit('deleteSuccess', id);
    await fetchTableData();
  }
};

/**
 * Abre el modal de confirmación para eliminar.
 * @param id ID del ítem a eliminar.
 */
const openDeleteModal = (id: string | number) => {
  refModalQuestion.value.componentData.title = "¿Está seguro que desea eliminar el registro?";
  refModalQuestion.value.openModal(id);
};

const refModalQuestion = ref();

// Observa cambios en route.query para actualizar la tabla
watch(() => route.query, (newQuery, oldQuery) => {
  // Si disableUrlUpdate está activo, no reaccionamos a cambios en la URL
  if (isFetching.value || props.disableUrlUpdate) return;

  const hasFiltersChanged = JSON.stringify({ ...newQuery, page: undefined, perPage: undefined, sort: undefined }) !==
    JSON.stringify({ ...oldQuery, page: undefined, perPage: undefined, sort: undefined });

  if (hasFiltersChanged) {
    options.params = { ...newQuery };
    delete options.params.page;
    delete options.params.perPage;
    delete options.params.sort;
    fetchTableData(1, true);
  }
}, { deep: true });

// Carga inicial de datos
onMounted(() => {
  // Solo inicializamos desde la URL si no está deshabilitado
  if (!props.disableUrlUpdate) {
    if (route.query.page) {
      options.pagination.currentPage = parseInt(route.query.page as string) || 1;
    }
    if (route.query.perPage) {
      options.pagination.rowsPerPage = parseInt(route.query.perPage as string) || 10;
      rowsPerPageInput.value = options.pagination.rowsPerPage; // Sincronizamos el input
    }
    if (route.query.sort) {
      const sortParts = (route.query.sort as string).split(',');
      options.sortBy = sortParts.map(part => ({
        key: part.startsWith('-') ? part.slice(1) : part,
        order: part.startsWith('-') ? 'desc' : 'asc'
      }));
      sortBy.value = options.sortBy;
    }

    options.params = { ...route.query };
    delete options.params.page;
    delete options.params.perPage;
    delete options.params.sort;
  }

  fetchTableData(null, true);
});

// Variable intermedia para el input del número de filas por página
const rowsPerPageInput = ref(options.pagination?.rowsPerPage || 10);

// Función de debounce manual
const debounce = <T extends (...args: any[]) => void>(func: T, wait: number) => {
  let timeout: ReturnType<typeof setTimeout> | null = null;
  return (...args: Parameters<T>) => {
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

// Función con debounce para fetchTableData
const debouncedFetchTableData = debounce((page: number) => {
  // Actualizamos options.pagination.rowsPerPage solo cuando se ejecuta la petición
  options.pagination.rowsPerPage = Number(rowsPerPageInput.value) || 10; // Aseguramos que sea un número válido
  fetchTableData(page);
}, 500);

defineExpose({
  openDeleteModal,
  fetchTableData,
  options,
});
</script>

<template>
  <div>
    <VDataTable :headers="options.headers" :items="tableData" :loading="loading" :show-select="options.showSelect"
      v-model="selected" :multi-sort="options.multiSort" :items-per-page="options.pagination.rowsPerPage"
      :page="options.pagination.currentPage" @update:sortBy="updateSortBy">
      <!-- Slots dinámicos -->
      <template v-for="(_, name) in $slots" #[name]="slotProps">
        <slot :name="name" v-bind="slotProps || {}" />
      </template>

      <!-- Estado is_active -->
      <template #item.is_active="{ item }">
        <div class="d-flex align-center" v-if="options.actions.changeStatus.show">
          <VChip color="success"
            v-if="item[options.actions.changeStatus.dbField] == options.actions.changeStatus.btnActive.value"
            @click="callActionOrChangeStatus(options.actions.changeStatus.btnActive.newFunction, item, options.actions.changeStatus.dbField, options.actions.changeStatus.btnInactive.value)">
            <VIcon start size="16" icon="tabler-bell" />
            {{ options.actions.changeStatus.btnActive.text }}
            <VTooltip location="top" transition="scale-transition" activator="parent" text="Estado" />
          </VChip>
          <VChip color="error" v-else
            @click="callActionOrChangeStatus(options.actions.changeStatus.btnInactive.newFunction, item, options.actions.changeStatus.dbField, options.actions.changeStatus.btnActive.value)">
            <VIcon start size="16" icon="tabler-alert-circle" />
            {{ options.actions.changeStatus.btnInactive.text }}
            <VTooltip location="top" transition="scale-transition" activator="parent" text="Estado" />
          </VChip>
        </div>
      </template>

      <!-- Acciones -->
      <template #item.actions="{ item, index }">
        <div class="text-center">
          <template v-if="options.actions?.style === 'dropdown'">
            <VBtn color="primary" append-icon="tabler-chevron-down">
              Acciones
              <VMenu activator="parent">
                <VList>
                  <VListItem v-if="options.actions?.view?.show" @click="emit('view', item)">
                    <template #prepend>
                      <VIcon :icon="options.actions?.view.icon" />
                    </template>
                    <span>Ver</span>
                  </VListItem>
                  <VListItem v-if="options.actions?.edit?.show" @click="emit('edit', item)">
                    <template #prepend>
                      <VIcon :icon="options.actions?.edit.icon" />
                    </template>
                    <span>Editar</span>
                  </VListItem>
                  <VListItem v-if="options.actions?.delete?.show" @click="openDeleteModal(item.id)">
                    <template #prepend>
                      <VIcon :icon="options.actions?.delete.icon" />
                    </template>
                    <span>Eliminar</span>
                  </VListItem>
                  <slot name="item.actions2" :item="item" :index="index"></slot>
                </VList>
              </VMenu>
            </VBtn>
          </template>

          <template v-else>
            <VBtn v-if="options.actions?.view?.show" icon :color="options.actions?.view.color || 'warning'" class="mx-1"
              @click="emit('view', item)">
              <VIcon :icon="options.actions?.view.icon" />
              <VTooltip activator="parent" location="top">{{ options.actions?.view.tooltip }}</VTooltip>
            </VBtn>
            <VBtn v-if="options.actions?.edit?.show" icon :color="options.actions?.edit.color || 'primary'" class="mx-1"
              @click="emit('edit', item)">
              <VIcon :icon="options.actions?.edit.icon" />
              <VTooltip activator="parent" location="top">{{ options.actions?.edit.tooltip }}</VTooltip>
            </VBtn>
            <VBtn v-if="options.actions?.delete?.show" icon :color="options.actions?.delete.color || 'error'"
              class="mx-1" @click="openDeleteModal(item.id)">
              <VIcon :icon="options.actions?.delete.icon" />
              <VTooltip activator="parent" location="top">{{ options.actions?.delete.tooltip }}</VTooltip>
            </VBtn>
            <slot name="item.actions2" :item="item" :index="index"></slot>
          </template>
        </div>
      </template>

      <!-- Paginación -->
      <template #bottom>
        <VCardText class="pt-2">
          <VRow>
            <VCol lg="2" cols="3">
              <VTextField v-model="rowsPerPageInput" @update:model-value="debouncedFetchTableData(1)"
                label="Filas por página" type="number" min="1" hide-details variant="underlined" />
            </VCol>
            <VCol class="d-flex align-center justify-space-between">
              <span class="text-sm text-disabled">
                Mostrando {{ (options.pagination.currentPage - 1) * options.pagination.rowsPerPage + 1 }} a
                {{ Math.min(options.pagination.currentPage * options.pagination.rowsPerPage,
                  options.pagination.totalItems) }} de
                {{ options.pagination.totalItems }} registros
              </span>
              <VPagination v-model="options.pagination.currentPage" :length="options.pagination.totalPages"
                :total-visible="5" size="small" @update:modelValue="fetchTableData($event, false)" />
            </VCol>
          </VRow>
        </VCardText>
      </template>

      <!-- Estado de carga y sin datos -->
      <template #loading>
        <VSkeletonLoader type="table-row@10, divider" />
      </template>
      <template #no-data>
        <span>No se encontraron resultados</span>
      </template>
    </VDataTable>

    <ModalQuestion ref="refModalQuestion" @success="deleteItem" />
  </div>
</template>
