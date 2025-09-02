import { createPinia } from 'pinia'
import type { App } from 'vue'

import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'


export const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)

export default function (app: App) {
  app.use(pinia)
}
