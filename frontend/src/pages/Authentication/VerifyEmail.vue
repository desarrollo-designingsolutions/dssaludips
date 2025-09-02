<script setup lang="ts">
// import logo_designing_solutions_dark from '@images/logo_designing_solutions_dark.png';
// import logo_designing_solutions_light from '@images/logo_designing_solutions_light.png';
import authV1BottomShape from '@images/svg/auth-v1-bottom-shape.svg?raw';
import authV1TopShape from '@images/svg/auth-v1-top-shape.svg?raw';
import { VNodeRenderer } from '@layouts/components/VNodeRenderer';

definePage({
  name: "VerifyEmail",
  path: "/VerifyEmail",
  meta: {
    layout: 'blank',
    public: true,
  },
})

const route = useRoute()
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
      <VCard class="auth-card" max-width="460" :class="$vuetify.display.smAndUp ? 'pa-6' : 'pa-2'">
        <div class="d-flex justify-center">
          <!-- <VImg max-width="260"
            :src="$vuetify.theme.current.dark ? logo_designing_solutions_light : logo_designing_solutions_dark" /> -->
        </div>

        <VCardText>
          <h4 class="text-h4 mb-1">
            Verifique su correo electrónico ✉️
          </h4>
          <p class="text-body-1 mb-0">
            El enlace para cambiar la contraseña de su cuenta fue enviado a su dirección de correo electrónico: <span
              class="font-weight-medium text-high-emphasis">{{ route.query.email }}</span> Por favor, siga el enlace
            para
            continuar.
          </p>

          <VBtn block to="/" class="my-5">
            Saltar por ahora
          </VBtn>

          <div class="d-flex align-center justify-center">
            <span class="me-1">¿No has recibido el correo? </span>
            <RouterLink class="d-flex align-center justify-center" :to="{ name: 'ForgotPassword' }">
              <VIcon icon="tabler-chevron-left" size="20" class="me-1 flip-in-rtl" />
              <span>Vuelva a enviar</span>
            </RouterLink>
          </div>
        </VCardText>
      </VCard>
    </div>
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth";
</style>
