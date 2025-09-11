<script setup lang="ts">
import IErrorsBack from '@/interfaces/Axios/IErrorsBack'
import { router } from '@/plugins/1.router'
import { useAuthenticationStore } from "@/stores/useAuthenticationStore"
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { themeConfig } from '@themeConfig'
import type { VForm } from "vuetify/components"

const refForm = ref<VForm>();

definePage({
  path: "/",
  name: "Login",
  meta: {
    layout: 'blank',
    guestOnly: true
  },
})

const authenticationStore = useAuthenticationStore();
const { loading, rememberMe } = storeToRefs(authenticationStore);

const errorsBack = ref<IErrorsBack>({});

const form = ref({
  email: '',
  password: '',
  remember: false,
})

const isPasswordVisible = ref(false)

const authThemeImg = useGenerateImageVariant(
  authV2LoginIllustrationLight,
  authV2LoginIllustrationDark,
  authV2LoginIllustrationBorderedLight,
  authV2LoginIllustrationBorderedDark,
  true)

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

const login = async () => {
  const validation = await refForm.value?.validate();
  if (validation?.valid) {
    const promise = await authenticationStore.login(form.value);

    if (promise.code == 200) {
      if (isEmpty(promise.user.company_id)) {
        router.push({ name: "Company-List" })
        return
      }
      router.push({ name: "Home" })
    };

    if (promise.code === 422) errorsBack.value = promise.errors ?? {};
  }
}

// Verificar si el usuario está autenticado al cargar el componente
const isAuthenticated = authenticationStore.checkAuthentication();
if (isAuthenticated) {
  router.push({ name: "Home" }) // Redirige a la página de inicio si ya está autenticado
}

</script>

<template>
<div>
  <VRow no-gutters class="auth-wrapper bg-surface">
    <VCol md="8" class="d-none d-md-flex">
      <VImg src="/images/imagen_acsis.png" class="auth-illustration mb-2" />
    </VCol>

    <VCol cols="12" md="4" class="auth-card-v2 d-flex align-center justify-center">
      <VCard flat :max-width="500" class="mt-12 mt-sm-0 pa-6">
        <VCardText>
          <h4 class="text-h4 mb-1">
            Bienvenidos<span class="text-capitalize">{{ themeConfig.app.title }}</span>
          </h4>
          <p class="mb-0">
            Inicie sesión en su cuenta
          </p>
        </VCardText>
        <VCardText>
          <VForm ref="refForm" @submit.prevent="() => { }">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField :requiredField="true" v-model="form.email" autofocus label="Correo electrónico"
                  type="email" placeholder="johndoe@email.com" clearable :rules="[requiredValidator, emailValidator]" />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField :requiredField="true" v-model="form.password" label="Contraseña"
                  placeholder="············" :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible" clearable :rules="[requiredValidator]" />

                <div class="d-flex align-center flex-wrap justify-space-between mt-1 mb-4">
                  <VCheckbox v-model="rememberMe" label="Recuérdame" />
                  <RouterLink class="text-primary ms-2 mb-1" :to="{ name: 'ForgotPassword' }">
                    ¿Ha olvidado su contraseña?
                  </RouterLink>
                </div>

                <VBtn class="mt-2" :loading="loading" :disabled="loading" block type="submit" @click="login()">
                  Ingresar
                </VBtn>
              </VCol>

            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth";
</style>
