<script setup lang="ts">
import IErrorsBack from '@/interfaces/Axios/IErrorsBack';
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import logo_designing_solutions_dark from '@images/logo_designing_solutions_dark.png';
import logo_designing_solutions_light from '@images/logo_designing_solutions_light.png';
import authV1BottomShape from '@images/svg/auth-v1-bottom-shape.svg?raw';
import authV1TopShape from '@images/svg/auth-v1-top-shape.svg?raw';
import { VNodeRenderer } from '@layouts/components/VNodeRenderer';
import type { VForm } from "vuetify/components";



definePage({
  path: "/",
  name: "Login",
  meta: {
    layout: 'blank',
    guestOnly: true
  },
})

const refForm = ref<VForm>();
const authenticationStore = useAuthenticationStore();
const { loading, tokenGoogle, rememberMe, user } = storeToRefs(authenticationStore);
const errorsBack = ref<IErrorsBack>({});
const isPasswordVisible = ref(false)
const form = ref({
  email: '',
  password: '',
  remember: false,
})

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
        <VCardText class="d-flex justify-center">
          <h5 class="text-h5 ">
            Ingrese a su cuenta
          </h5>
        </VCardText>

        <VCardText>
          <VForm ref="refForm" @submit.prevent="() => { }">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField :requiredField="true" v-model="form.email" autofocus label="Correo electrónico"
                  type="email" placeholder="johndoe@email.com" />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField :requiredField="true" v-model="form.password" label="Contraseña"
                  placeholder="············" :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible" />

                <!-- remember me checkbox -->
                <div class="d-flex align-center flex-wrap justify-space-between mt-1 mb-4">
                  <VCheckbox v-model="rememberMe" label="Recuérdame" />

                  <RouterLink class="text-primary ms-2 mb-1" :to="{ name: 'ForgotPassword' }">
                    ¿Ha olvidado su contraseña?
                  </RouterLink>
                </div>

                <!-- login button -->
                <VBtn class="mt-2" :loading="loading" :disabled="loading" block type="submit" @click="login()">
                  Ingresar
                </VBtn>
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
