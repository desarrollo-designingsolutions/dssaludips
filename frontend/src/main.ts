import { createApp } from 'vue'

import App from '@/App.vue'
import { registerPlugins } from '@core/utils/plugins'

// Styles
import '@core/scss/template/index.scss'
import '@styles/styles.scss'

import { autoAnimatePlugin } from '@formkit/auto-animate/vue'
import Editor from '@tinymce/tinymce-vue'
import axios from 'axios'


import Echo from 'laravel-echo'

import Pusher from 'pusher-js'
window.Pusher = Pusher;
const accessToken = useCookie("accessToken").value;

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
  wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],

  authorizer: (channel: { name: any }, options: any) => {
    return {
      authorize: (socketId: any, callback: (arg0: boolean, arg1: any) => void) => {
        axios.post(`${import.meta.env.VITE_API_BASE_URL}/broadcasting/auth`, {
          socket_id: socketId,
          channel_name: channel.name
        }, {
          headers: {
            'Authorization': `Bearer ${accessToken}` // Agregar el Bearer Token a los encabezados
          }
        })
          .then(response => {
            callback(false, response.data);
          })
          .catch(error => {
            callback(true, error.response);
          });
      }
    };
  },
});



// Create vue app
const app = createApp(App)

// Register plugins
registerPlugins(app)
app.use(autoAnimatePlugin);
app.component('Editor', Editor)

// Mount vue app
app.mount('#app')
