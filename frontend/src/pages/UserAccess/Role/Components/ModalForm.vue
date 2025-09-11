<script setup lang="ts">
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import { useRoleStore } from "@/pages/UserAccess/Role/stores/useRoleStore";
import type { VForm } from "vuetify/components";

import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();
const { company } = storeToRefs(authenticationStore)

const emits = defineEmits(['updateDataTable'])
const errorsBack = ref<IErrorsBack>({});

const useStore = useRoleStore();
const { selectedElements, arrayFather, selectedFather } = storeToRefs(useStore);

const componentData = reactive({
  isDialogVisible: false,
  isLoading: false,
  titleForm: "Crear",
  menus: [],
});

const formComponent = ref({
  id: null,
  description: null,
  company_id: null,
  permissions: [] as Array<Number>,
});

const handleIsDialogVisible = (isVisible: boolean = false) => {
  componentData.isDialogVisible = isVisible;
};

const handleClearFormComponent = (): void => {
  formComponent.value.id = null;
  formComponent.value.description = null;
  formComponent.value.permissions = [];
};

const handleCreate = async () => {
  useStore.clearPermissionsSelected();
  handleClearFormComponent();
  componentData.isLoading = true;
  handleIsDialogVisible(true);
  componentData.titleForm = "Crear"

  const { data, isFetching } = await useAxios(`role/create`).get({
      params: {
        user_id: authenticationStore.user.id,
      }
    });


  formComponent.value.company_id = company.value.id

  componentData.menus = data.menus;
  arrayFather.value = componentData.menus;
  componentData.isLoading = isFetching;
};

const handleEdit = async ({ id }: any) => {
  useStore.clearPermissionsSelected();
  handleClearFormComponent();
  componentData.isLoading = true;
  handleIsDialogVisible(true);
  componentData.titleForm = "Editar"



  const { response, data, isFetching } = await useAxios(`role/${id}/edit`).get({
      params: {
        user_id: authenticationStore.user.id,
      }
    });


  if (response.status == 200 && data) {

    componentData.menus = data.menus;
    formComponent.value.id = data.role.id;
    formComponent.value.description = data.role.description;
    formComponent.value.company_id = data.role.company_id;

    selectedElements.value = data.role.permissions;
    arrayFather.value = componentData.menus;

  }
  componentData.isLoading = isFetching;
};

const handleSubmit = async () => {
  formComponent.value.permissions = selectedElements.value;

  const validation = await formRole.value?.validate();
  if (validation?.valid) {
    componentData.isLoading = true;
    const { response, data, isFetching } = await useAxios(`role`).post(
      formComponent.value
    );

    if (response.status == 200 && data) {
      emits('updateDataTable');
      handleIsDialogVisible(false);
    }
    if (data.code === 422) errorsBack.value = data.errors ?? {};
    componentData.isLoading = isFetching;

  }
};

const formRole = ref<VForm>();

defineExpose({
  handleCreate,
  handleEdit,
});
</script>
<template>
  <VDialog v-model="componentData.isDialogVisible" max-width="1080px" persistent transition="dialog-transition">
    <DialogCloseBtn @click="handleIsDialogVisible()" />
    <VCard>
      <VCardText class="pa-sm-10 pa-2">
        <h4 class="text-h4 text-center mb-2">
          {{ `${componentData.titleForm} rol` }}
        </h4>
        <p class="text-body-1 text-center mb-6">Establecer permisos del rol</p>
        <VForm ref="formRole">
          <VRow>
            <VCol cols="12">
              <VSkeletonLoader type="text" :loading="componentData.isLoading">
                <AppTextField :requiredField="true" label="Nombre de rol" v-model="formComponent.description"
                  :rules="[requiredValidator]" clearable :error-messages="errorsBack.description"
                  @input="errorsBack.description = ''" />
              </VSkeletonLoader>
            </VCol>
          </VRow>
        </VForm>
        <h5 class="text-h5 my-6">Permisos de rol</h5>
        <VSkeletonLoader type="list-item, list-item, list-item, list-item, list-item, list-item"
          :loading="componentData.isLoading">
          <VRow>
            <VCol>
              <VList border>
                <template v-for="(
father, key
                  ) in componentData.menus" :key="key">

                  <ListGroup :father="father" />
                </template>
              </VList>
            </VCol>
          </VRow>
        </VSkeletonLoader>
      </VCardText>
      <VCardText class="d-flex align-center justify-center gap-4">
        <VBtn color="secondary" @click="handleIsDialogVisible()">Cancelar</VBtn>
        <VBtn prepend-icon="tabler-device-floppy" @click="handleSubmit()" :loading="componentData.isLoading">
          Guardar cambios</VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
