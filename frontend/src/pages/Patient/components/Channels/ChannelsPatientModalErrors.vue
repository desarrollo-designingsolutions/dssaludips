<script setup lang="ts">
import ModalErrorsPatients from "@/pages/Patient/components/ModalErrorsPatients.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

//ModalErrorsPatients
const refModalErrorsPatients = ref()
const refModalQuestion = ref()

const openModalErrorsPatients = (data: any) => {
  refModalErrorsPatients.value.openModal(data, authenticationStore.user.id)
}

const channels = reactive({
  patientModalErrors: `patientModalErrors.${authenticationStore.user.id}`,
})

// Función para iniciar y manejar el canal dinámicamente
const startEchoChannel = () => {
  const channelPatientModalErrors = window.Echo.channel(channels.patientModalErrors);
  channelPatientModalErrors.listen('ModalError', (event: any) => {
    if (event.errors != null) {
      openModalErrorsPatients(event.errors);
    } else {
      if (refModalQuestion.value) {
        refModalQuestion.value.componentData.isDialogVisible = true;
        refModalQuestion.value.componentData.showBtnCancel = false;
        refModalQuestion.value.componentData.btnSuccessText = 'Ok';
        refModalQuestion.value.componentData.title = 'Pacientes cargados exitosamente.';
        refModalQuestion.value.componentData.principalIcon = 'tabler-check';
      }
    }
  });
};

onMounted(() => {
  startEchoChannel()
})


</script>

<template>
  <div>
    <ModalErrorsPatients ref="refModalErrorsPatients" />

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>
