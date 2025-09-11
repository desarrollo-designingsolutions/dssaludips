<script setup lang="ts">
const titleModal = ref<string>("Errores en la validación de estructura en pacientes")
const isDialogVisible = ref<boolean>(false)
const loading = reactive({
  excel: false,
  getData: false,
})

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const errorMessages = ref([]);
const user_id = ref<string>("");
const openModal = async (element: any, userId: string) => {
  handleDialogVisible();

  user_id.value = userId

  openJson(element);
};

const openJson = async (url: any) => {
  loading.getData = true;
  errorMessages.value = [];
  const { data, response } = await useAxios("/patient/getContentJson").post({
    url_json: url
  })

  loading.getData = false;

  if (response.status == 200 && data) {
    errorMessages.value = data.data;
  }
}


// headers
const inputsTableFilter = [
  { title: 'Fila', key: 'row' },
  { title: 'Valor', key: 'column', maxWidth: '450px' },
  // { title: 'Data', key: 'data' },
  { title: 'Mensaje de error', key: 'error' },
]

const options = ref({ page: 1, itemsPerPage: 10, sortBy: [''], sortDesc: [false] })
const search = ref('')

defineExpose({
  openModal,
})
</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />

      <VCard :loading="loading.getData" :disabled="loading.getData" class="w-100">
        <!-- Toolbar -->
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText>
          <VRow>
            <VCol cols="12" sm="6">
              <AppTextField v-model="search" density="compact" placeholder="Search ..."
                append-inner-icon="tabler-search" single-line hide-details dense outlined clearable />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText>
          <VDataTable :search="search" :headers="inputsTableFilter" :items="errorMessages"
            :items-per-page="options.itemsPerPage" :page="options.page" :options="options">

          </VDataTable>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn :loading="loading.getData" color="secondary" variant="tonal" @click="handleDialogVisible()">
            Cerrar
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
