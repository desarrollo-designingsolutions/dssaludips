<script setup lang="ts">
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import type { VForm } from "vuetify/components/VForm";

import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();

const errorsBack = ref<IErrorsBack>({});
const emit = defineEmits(["execute"]);
const { toast } = useToast();

const componentData = reactive({
  titleDialog: "Cambiar contraseña",
  isDialogVisible: false,
  isLoading: false,
  first_time: false as boolean,
  statuses: [],
  form: {
    id: null as null | string | number,
    new_password: "",
  },
});

const handleClearForm = () => {
  componentData.form = {
    id: null,
    new_password: "",
  };

  errorsBack.value = {}
  isPasswordVisible.value = false

};

const handleDialogVisible = () => {
  componentData.isDialogVisible = !componentData.isDialogVisible;
};

const openDialog = async (id: null | number | string, first_time: boolean) => {
  handleClearForm();
  handleDialogVisible();

  componentData.form.id = id;
  componentData.first_time = first_time;
};

const submitForm = async () => {
  const validation = await refForm.value?.validate();

  if (validation?.valid) {

    componentData.isLoading = true;

    const { response, data } = await useAxios(`/user/changePassword`).post({
      id: componentData.form.id,
      new_password: componentData.form.new_password,
      type_user: authenticationStore.user.type_user,
    });
    componentData.isLoading = false;

    if (response.status == 200 && data && data?.code == 200) {
      handleDialogVisible();

      emit("execute");
    }

    if (data.code == 422) {
      errorsBack.value = data.errors ?? {}
    }
  }
};

const refForm = ref<VForm>();

defineExpose({
  openDialog,
});

const isPasswordVisible = ref(false);

</script>

<template>
  <div>
    <VDialog v-model="componentData.isDialogVisible" persistent max-width="40rem" transition="dialog-transition">
      <!-- Dialog close btn -->
      <DialogCloseBtn @click="handleDialogVisible()" v-if="!componentData.first_time" />
      <!-- Toolbar -->
      <div>
        <VToolbar color="primary">
          <VToolbarTitle>{{ componentData.titleDialog }}</VToolbarTitle>
        </VToolbar>
      </div>
      <VCard>
        <VCardText>
          <VForm ref="refForm" @submit.prevent="() => { }">
            <VRow>
              <VCol cols="12" v-if="componentData.first_time">
                <h4>¡Bienvenido! Como es tu primera vez ingresando al sistema, es necesario que cambies tu contraseña.
                  Por favor, elige una nueva contraseña que sea segura y fácil de recordar.</h4>

              </VCol>
              <VCol cols="12">
                <AppTextField clearable v-model="componentData.form.new_password" label="Contraseña"
                  placeholder="············" :type="isPasswordVisible ? 'text' : 'password'" :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'
                    " @click:append-inner="isPasswordVisible = !isPasswordVisible"
                  :rules="[requiredValidator, passwordValidator]" :error-messages="errorsBack.new_password"
                  @input="errorsBack.new_password = ''" />
              </VCol>
              <VCol cols="12">
                <span>
                  <p>Requisitos de la Contraseña:</p>
                  <ul class="ml-4">
                    <li>Longitud mínima de 8 caracteres.</li>
                    <li>Al menos una letra mayúscula (A-Z).</li>
                    <li>Al menos una letra minúscula (a-z).</li>
                    <li>Al menos un carácter especial de este conjunto: !@#$%&*().</li>
                    <li>Al menos un número (0-9).</li>
                  </ul>
                </span>

              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn v-if="!componentData.first_time" :loading="componentData.isLoading" color="secondary" variant="tonal"
            @click="handleDialogVisible()">
            <VIcon start icon="tabler-x" />
            Cancelar
          </VBtn>
          <VBtn :disabled="componentData.isLoading" :loading="componentData.isLoading" @click="submitForm()"
            color="primary">
            <VIcon start icon="tabler-device-floppy" />
            Cambiar contraseña
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
