<script setup lang="ts">
import logo_designing_solutions_dark from '@images/logo_designing_solutions_dark.png';
import logo_designing_solutions_light from '@images/logo_designing_solutions_light.png';
import authV1BottomShape from '@images/svg/auth-v1-bottom-shape.svg?raw';
import authV1TopShape from '@images/svg/auth-v1-top-shape.svg?raw';
import { VNodeRenderer } from '@layouts/components/VNodeRenderer';
import { themeConfig } from '@themeConfig';
import type { VForm } from "vuetify/components";

definePage({
  name: "ResetPassword",
  path: "/ResetPassword/:token",
  meta: {
    layout: 'blank',
    public: true,
  },
})

const form = ref({
  email: '',
  password: '',
  password_confirmation: '',
})

const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const refForm = ref<VForm>();
const loading = ref<boolean>(false)
const route = useRoute()
const rulesFieldConfirmedPassword = [
  value => requiredValidator(value),
  value => confirmedValidator(form.value.password_confirmation, value),
]


const resetPassword = async () => {
  const validation = await refForm.value?.validate();
  if (validation?.valid) {
    form.value.email = route.query.email
    form.value.token = route.params.token

    loading.value = true;
    const { data, response } = await useApi(`/password/reset`).post(form.value);
    loading.value = false;

    if (response.value?.ok && data.value) {
    }
  }
}

</script>

<template>
  <div class="auth-wrapper d-flex align-center justify-center pa-4">
    <div class="position-relative my-sm-16">
      <!-- 👉 Top shape -->
      <VNodeRenderer :nodes="h('div', { innerHTML: authV1TopShape })"
        class="text-primary auth-v1-top-shape d-none d-sm-block" />

      <!-- 👉 Bottom shape -->
      <VNodeRenderer :nodes="h('div', { innerHTML: authV1BottomShape })"
        class="text-primary auth-v1-bottom-shape d-none d-sm-block" />

      <!-- 👉 Auth Card -->
      <VCard class="auth-card" max-width="460" :class="$vuetify.display.smAndUp ? 'pa-6' : 'pa-2'">
        <div class="d-flex justify-center">
          <VImg max-width="260"
            :src="$vuetify.theme.current.dark ? logo_designing_solutions_light : logo_designing_solutions_dark" />
        </div>

        <VCardText>
          <h4 class="text-h4 mb-1">
            Restablecer contraseña 🔒
          </h4>
          <p class="mb-0">
            Su nueva contraseña debe ser diferente de las utilizadas anteriormente
          </p>
        </VCardText>

        <VCardText>
          <VForm ref="refForm" @submit.prevent="() => { }">
            <VRow>
              <!-- password -->
              <VCol cols="12">
                <AppTextField v-model="form.password" autofocus label="Nueva contraseña" placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                  :rules="[requiredValidator, passwordValidator]" />
              </VCol>

              <!-- Confirm Password -->
              <VCol cols="12">
                <AppTextField v-model="form.password_confirmation" label="Confirmar contraseña"
                  placeholder="············" :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                  :rules="rulesFieldConfirmedPassword" />
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

              <!-- reset password -->
              <VCol cols="12">
                <VBtn block type="submit" @click="resetPassword" :loading="loading" :disabled="loading">
                  Establecer nueva contraseña
                </VBtn>
              </VCol>

              <!-- back to login -->
              <VCol cols="12">
                <RouterLink class="d-flex align-center justify-center" :to="{ name: 'Login' }">
                  <VIcon icon="tabler-chevron-left" size="20" class="me-1 flip-in-rtl" />
                  <span>Volver al inicio de sesión</span>
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </div>
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth";
</style>
