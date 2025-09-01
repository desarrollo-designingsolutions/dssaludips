<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();
const { company } = storeToRefs(authenticationStore);

const getColorAvatar = computed(() => {
  return {
    0: "primary",
    1: "secondary",
    2: "info",
  };
});

const tables = ref([])
const isLoading = ref<boolean>(false)

const getAll = async () => {
  isLoading.value = true;
  const { data, response } = await useAxios(`/cache/getTables`).get({ params: { ...optionsFilter.value.params, } });


  if (response.status == 200 && data && data.code === 200) {
    tables.value = data.tables;
  }
  isLoading.value = false;
};

const synchronizeTables = async (name: string) => {
  // isLoading.value = true;
  const { data, response } = await useAxios(`/cache/synchronizeTables`).post({ table_name: name });


  if (response.status == 200 && data && data.code === 200) {
    // tables.value = data.tables;
  }
  // isLoading.value = false;
};

onMounted(() => {
  optionsFilter.value.params = { ...route.query };
  init(route.query, '');
})


//FILTER
const optionsFilter = ref({
  params: {
  },
  filterLabels: {
    inputGeneral: 'Buscar general',
  },
})

const route = useRoute();

const init = async (newQuery: any, oldQuery: any) => {
  if (isLoading.value) return;

  const hasFiltersChanged = JSON.stringify({ ...newQuery, page: undefined, perPage: undefined, sort: undefined }) !==
    JSON.stringify({ ...oldQuery, page: undefined, perPage: undefined, sort: undefined });

  if (hasFiltersChanged) {
    optionsFilter.value.params = { ...newQuery };
    getAll();
  } else {
    getAll();
  }
};

// Observa cambios en route.query para actualizar la tabla
watch(() => route.query, (newQuery, oldQuery) => {
  init(newQuery, oldQuery);
}, { deep: true });
</script>

<template>
  <div>
    <VCard title="Tablas del sistema">
      <VCardText>
        <FilterDialog :options-filter="optionsFilter"></FilterDialog>
      </VCardText>

      <VCardText>
        <VSkeletonLoader type="image, list-item-two-line" :loading="isLoading">
          <VRow>
            <VCol cols="12" sm="6" lg="4" v-for="(table, key) in tables" :key="key">
              <VCard variant="elevated" density="default">
                <VCardText style="block-size: 40px;inline-size: 100%;" class="d-flex align-center pb-4">
                  <div class="text-body-1">
                    <b>Total registros</b>: {{ table.count_records || 0 }}
                  </div>
                  <VSpacer />
                </VCardText>
                <VCardText>
                  <VRow>
                    <VCol cols="12" md="8">
                      <h5 class="text-h5">{{ table.title }}</h5>
                      <div class="d-flex mt-5  gap-3 flex-wrap">
                        <VBtn @click="synchronizeTables(table.title)">
                          <template #prepend>
                            <VIcon icon="tabler-edit"></VIcon>
                          </template>
                          Sincronizar
                        </VBtn>
                      </div>
                    </VCol>
                    <VCol cols="12" md="4">
                      <div>
                        <ProgressCircularChannel :channel="'synchronize_table.' + table.title"
                          tooltipText="Cargando la sincronizacion de la tabla" />
                      </div>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VSkeletonLoader>
      </VCardText>

    </VCard>

  </div>
</template>
