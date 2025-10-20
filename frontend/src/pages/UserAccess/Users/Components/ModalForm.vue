<script setup lang="ts">
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import type { VForm } from "vuetify/components/VForm";

import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();
const { company } = storeToRefs(authenticationStore)

const errorsBack = ref<IErrorsBack>({});
const refForm = ref<VForm>();
const emit = defineEmits(["execute"])

const titleModal = ref<string>("Crear usuario")
const isDialogVisible = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)

const isLoading = ref<boolean>(false)

const serviceVendors_arrayInfo = ref([])

const form = ref({
  id: null as null | string,
  name: null as null | string,
  surname: null as null | string,
  email: null as null | string,
  password: null as null | string,
  confirmedPassword: null as null | string,
  role_id: null as null | string,
  company_id: null as null | string,
  service_vendor_ids: null as null | string,
})

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const openModal = async (id: string | null = null, disabled: boolean = false) => {
  disabledFiledsView.value = disabled

  handleClearForm()
  handleDialogVisible();

  titleModal.value = id ? "Editar usuario" : "Crear usuario"

  form.value.id = id
  form.value.company_id = company.value.id
  await fetchDataForm();
};

const roles = ref([])
const companies = ref([])
const fetchDataForm = async () => {

  const url = form.value.id ? `/user/${form.value.id}/edit` : `/user/create`

  isLoading.value = true
  const { data, response } = await useAxios(`${url}`).get({
    params: {
      company_id: company.value.id
    }
  });
  isLoading.value = false

  if (response.status == 200 && data && data.code === 200) {
    roles.value = data.roles
    companies.value = data.companies

    serviceVendors_arrayInfo.value = data.serviceVendors_arrayInfo

    if (data.form) {
      form.value = data.form
    }
  }
}


const submitForm = async () => {
  const validation = await refForm.value?.validate();

  if (validation?.valid) {

    const url = form.value.id ? `/user/update/${form.value.id}` : `/user/store`

    form.value.company_id = company.value.id

    isLoading.value = true
    const { data, response } = await useAxios(`${url}`).post(form.value);
    isLoading.value = false

    if (response.status == 200 && data && data.code === 200) {
      emit("execute")
      handleDialogVisible()
    }

  }
}

const rulesFieldPassword = computed(() => {
  if (!form.value.id) {
    return [
      value => requiredValidator(value),
    ]
  }
  return []
})
const rulesFieldConfirmedPassword = computed(() => {
  if (!form.value.id) {
    return [
      value => requiredValidator(value),
      value => confirmedValidator(form.value.password, value),
    ]
  }
  return [
    value => confirmedValidator(form.value.password, value),
  ]
})

const paramsSelectInfinite = {
  company_id: authenticationStore.company.id,
}



defineExpose({
  openModal
})

</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" :overlay="false" max-width="40rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading" class="w-100">
        <!-- Toolbar -->
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>{{ titleModal }}</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText>
          <VForm ref="refForm" @submit.prevent="() => { }">
            <VRow>
              <VCol cols="12">
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView" label="Nombres"
                  :rules="[requiredValidator]" v-model="form.name" :error-messages="errorsBack.name" />
              </VCol>
              <VCol cols="12">
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView" label="Apellidos"
                  :rules="[requiredValidator]" v-model="form.surname" :error-messages="errorsBack.surname" />
              </VCol>

              <VCol cols="12">
                <AppTextField :requiredField="true" clearable :disabled="disabledFiledsView" label="Correo" type="email"
                  :rules="[requiredValidator, emailValidator]" v-model="form.email"
                  :error-messages="errorsBack.email" />
              </VCol>

              <VCol cols="12">
                <AppTextField :requiredField="form.id ? false : true" clearable :disabled="disabledFiledsView"
                  label="Contraseña" type="password" :rules="rulesFieldPassword" v-model="form.password"
                  :error-messages="errorsBack.password" />
              </VCol>

              <VCol cols="12">
                <AppTextField :requiredField="form.id ? false : true" label="Confirmar Contraseña" type="password"
                  :rules="rulesFieldConfirmedPassword" v-model="form.confirmedPassword" />
              </VCol>
              <VCol cols="12">
                <AppSelect :requiredField="true" :items="roles" label="Rol" :rules="[requiredValidator]"
                  v-model="form.role_id" :error-messages="errorsBack.role_id" clearable
                  :disabled="disabledFiledsView" />
              </VCol>
              <!-- <VCol cols="12" v-if="user.role_id == ROLE_SUPERADMIN_UUID">
                <AppSelect :requiredField="true" :items="companies" label="Compañia" :rules="[requiredValidator]"
                  v-model="form.company_id" :error-messages="errorsBack.company_id" clearable
                  :disabled="disabledFiledsView" />
              </VCol> -->
              <VCol cols="12">
                <AppSelectRemote :disabled="disabledFiledsView" label="Prestadores" v-model="form.service_vendor_ids"
                  url="/selectInfiniteServiceVendor" arrayInfo="serviceVendors" clearable :params="paramsSelectInfinite"
                  :itemsData="serviceVendors_arrayInfo" :firstFetch="false" multiple>
                </AppSelectRemote>
              </VCol>
            </VRow>

          </VForm>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5">
          <VBtn color="secondary" :disabled="isLoading" :loading="isLoading" @click="handleDialogVisible">Cancelar
          </VBtn>
          <VBtn v-if="!disabledFiledsView" :disabled="isLoading" :loading="isLoading" @click="submitForm()"
            color="primary">
            Guardar
          </VBtn>
        </VCardText>


      </VCard>
    </VDialog>



  </div>
</template>
