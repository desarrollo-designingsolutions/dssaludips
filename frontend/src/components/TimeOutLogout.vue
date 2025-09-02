<template>
  <!-- Tu interfaz de usuario y contenido aquí -->
</template>

<script>
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { onMounted, onUnmounted } from 'vue';

export default {
  setup(_, { emit }) {
    const authenticationStore = useAuthenticationStore();

    // const sessionTimeout = 5000; // Tiempo de sesión en milisegundos (5 seg en este ejemplo)
    const sessionTimeout = 7200000; // Tiempo de sesión en milisegundos (2 horas en este ejemplo)
    let timeoutId;

    const logout = () => {
      // Aquí puedes implementar la lógica para cerrar la sesión del usuario
      authenticationStore.logout();
      router.push("/");
      emit("logout", true);
    };

    const resetTimeout = () => {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(logout, sessionTimeout);
    };

    const setupListeners = () => {
      ['click', 'mousemove', 'keypress'].forEach(event => {
        window.addEventListener(event, resetTimeout);
      });
    };

    onMounted(() => {
      setupListeners();
      resetTimeout();
    });

    onUnmounted(() => {
      ['click', 'mousemove', 'keypress'].forEach(event => {
        window.removeEventListener(event, resetTimeout);
      });
    });

    return {
      resetTimeout
    };
  }
};
</script>
