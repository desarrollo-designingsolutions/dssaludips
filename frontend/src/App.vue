<script setup lang="ts">
import ScrollToTop from '@core/components/ScrollToTop.vue'
import initCore from '@core/initCore'
import { initConfigStore, useConfigStore } from '@core/stores/config'
import { hexToRgb } from '@core/utils/colorConverter'
import { useTheme } from 'vuetify'

const { global } = useTheme()

// ℹ️ Sync current theme with initial loader theme
initCore()
initConfigStore()

const configStore = useConfigStore()

const isDialogVisible = ref(false)
const logout = () => {
  isDialogVisible.value = true
}
</script>

<template>
  <div>
    <TimeOutLogout @logout="logout" />
  <VLocaleProvider :rtl="configStore.isAppRTL">
    <!-- ℹ️ This is required to set the background color of active nav link based on currently active global theme's primary -->
    <VApp :style="`--v-global-theme-primary: ${hexToRgb(global.current.value.colors.primary)}`">
      <RouterView />

      <ScrollToTop />
    </VApp>
  </VLocaleProvider>

  <VDialog v-model="isDialogVisible" persistent max-width="40rem" transition="dialog-transition">
      <!-- Dialog close btn -->
      <DialogCloseBtn @click="isDialogVisible = false" />
      <!-- Toolbar -->
      <div>
        <VToolbar color="primary">
          <VToolbarTitle>Sesión cerrada por inactividad</VToolbarTitle>
        </VToolbar>
      </div>
      <VCard>
        <VCardText>
          Tu sesión ha sido cerrada automáticamente debido a un período prolongado de inactividad. Para continuar, por
          favor, inicia sesión nuevamente.
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
