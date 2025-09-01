<script setup lang="ts">
import { router } from '@/plugins/1.router';
// import logo_designing_solutions_dark from '@images/logo_designing_solutions_dark.png';
// import logo_designing_solutions_light from '@images/logo_designing_solutions_light.png';
import authV1BottomShape from '@images/svg/auth-v1-bottom-shape.svg?raw';
import authV1TopShape from '@images/svg/auth-v1-top-shape.svg?raw';
import { VNodeRenderer } from '@layouts/components/VNodeRenderer';
import type { VForm } from "vuetify/components";

definePage({
  name: "ForgotPassword",
  path: "/ForgotPassword",
  meta: {
    layout: 'blank',
    public: true,
  },
})

const form = ref({
  email: '',
})

const refForm = ref<VForm>();
const loading = ref<boolean>(false)

const sendLink = async () => {
  const validation = await refForm.value?.validate();
  if (validation?.valid) {
    loading.value = true;
    const { data, response } = await useApi(`/password/email`).post(form.value);
    loading.value = false;

    if (response.value?.ok && data.value) {
      router.push({ name: "VerifyEmail", query: { email: form.value.email } })
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

      <!-- 👉 Auth card -->
      <VCard class="auth-card" max-width="460" :class="$vuetify.display.smAndUp ? 'pa-6' : 'pa-0'">
        <div class="d-flex justify-center">
          <!-- <VImg max-width="260"
            :src="$vuetify.theme.current.dark ? logo_designing_solutions_light : logo_designing_solutions_dark" /> -->
        </div>

        <VCardText>
          <h4 class="text-h4 mb-1">
            ¿Ha olvidado su contraseña? 🔒
          </h4>
          <p class="mb-0">
            Introduce tu correo electrónico y te enviaremos instrucciones para restablecer tu contraseña
          </p>
        </VCardText>

        <VCardText>
          <VForm ref="refForm" @submit.prevent="() => { }">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField :requiredField="true" clearable v-model="form.email" label="Correo electrónico"
                  type="email" placeholder="johndoe@email.com" :rules="[requiredValidator, emailValidator]" />
              </VCol>

              <!-- reset password -->
              <VCol cols="12">
                <VBtn block type="submit" @click="sendLink" :loading="loading" :disabled="loading">
                  Enviar enlace de restablecimiento
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
