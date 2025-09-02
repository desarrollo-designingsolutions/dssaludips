<script setup lang="ts">
definePage({
  name: "Role-List",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "menu.role",
  },
});

import ModalForm from '@/pages/UserAccess/Role/Components/ModalForm.vue';
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

const breadcrumbs = [
  {
    title: "Acceso de usuarios",
    disabled: false,
  },
  {
    title: "Roles",
    disabled: true,
  },
];

const roles = ref([])
const isLoading = ref<boolean>(false)

const getAll = async () => {
  isLoading.value = true;
  const { data, response } = await useApi<any>(
    createUrl(`/role/list`, {
      query: {
        company_id: company.value.id,
        ...optionsFilter.value.params,
      },
    })
  );
  isLoading.value = false;

  if (response.value?.ok && data.value?.code == 200) {
    roles.value = data.value.tableData;
  }
};

onMounted(() => {
  optionsFilter.value.params = { ...route.query };
  init(route.query, '');
})

const reloadTable = async () => {
  await getAll()
}


//ModalForm
const refModalForm = ref()

const addRol = () => {
  refModalForm.value.handleCreate();
};

const editRol = (item: any) => {
  refModalForm.value.handleEdit(item);
};

const deleteRol = async (id: string) => {
  isLoading.value = true;
  const { response, data } = await useApi(`/role/${id}`).delete();
  isLoading.value = false;

  getAll();

};

//ModalQuestion
const refModalQuestion = ref()
const openModalQuestionDelete = (id: string) => {
  refModalQuestion.value.componentData.title = "¿Está seguro que desea eliminar el registro?"
  refModalQuestion.value.openModal(id)
}

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
    <VBreadcrumbs :items="breadcrumbs"></VBreadcrumbs>

    <VCard title="Listado de roles">
      <VCardText>
        <VBtn color="primary" @click="addRol()">
          <template #prepend>
            <VIcon icon="tabler-plus"></VIcon>
          </template>
          Añadir nuevo rol
        </VBtn>
      </VCardText>

      <VCardText>
        <FilterDialog :options-filter="optionsFilter"></FilterDialog>
      </VCardText>

      <VCardText>
        <VSkeletonLoader type="image, list-item-two-line" :loading="isLoading">
          <VRow>
            <VCol cols="12" sm="6" lg="4" v-for="(role, key) in roles" :key="key">
              <VCard variant="elevated" density="default">
                <VCardText style="block-size: 40px;inline-size: 100%;" class="d-flex align-center pb-4">
                  <div class="text-body-1">
                    <b>Total usuarios</b>: {{ role.count_users || 0 }}
                  </div>
                  <VSpacer />
                  <div class="v-avatar-group demo-avatar-group">
                    <VAvatar :color="getColorAvatar[keyUser]" size="40" v-for="(user, keyUser) in role.users"
                      :key="keyUser">
                      {{ avatarText(`${user.name} ${user.surname}`) }}
                      <VTooltip activator="parent" location="top">
                        {{ `${user.name} ${user.surname}` }}
                      </VTooltip>
                    </VAvatar>
                    <VAvatar :color="$vuetify.theme.current.dark ? '#373b50' : '#eeedf0'
                      " size="40" v-show="role.count_users_extras > 0">
                      +{{ role.count_users_extras }}
                    </VAvatar>
                  </div>
                </VCardText>
                <VCardText>
                  <h5 class="text-h5">{{ role.description }}</h5>
                  <div class="d-flex mt-5  gap-3 flex-wrap">
                    <VBtn @click="editRol(role)">
                      <template #prepend>
                        <VIcon icon="tabler-edit"></VIcon>
                      </template>
                      Editar rol
                    </VBtn>
                    <VBtn v-if="role.users?.length == 0" color="error" @click="openModalQuestionDelete(role.id)">
                      <template #prepend>
                        <VIcon icon="tabler-trash"></VIcon>
                      </template>
                      Eliminar rol
                    </VBtn>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VSkeletonLoader>
      </VCardText>

    </VCard>

    <ModalForm ref="refModalForm" @updateDataTable="reloadTable" />

    <ModalQuestion ref="refModalQuestion" @success="deleteRol" />


  </div>
</template>
