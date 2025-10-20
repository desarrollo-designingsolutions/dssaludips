<script setup lang="ts">
import ModalErrorsPatientStructure from "@/pages/Patient/components/ModalErrorsPatientStructure.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

//ModalErrorsPatientStructure
const refModalErrorsPatientStructure = ref()
const refModalQuestion = ref()

const openModalErrorsPatientStructure = (data: any) => {
  refModalErrorsPatientStructure.value.openModal(data, authenticationStore.user.id)
}

const channels = reactive({
  patientStructureModalErrors: `patientStructureModalErrors.${authenticationStore.user.id}`,
})

// Función para iniciar y manejar el canal dinámicamente
const startEchoChannel = () => {
  const channelPatientStructureModalErrors = window.Echo.channel(channels.patientStructureModalErrors);
  channelPatientStructureModalErrors.listen('ModalError', (event: any) => {

    if (event.errors != null) {
      openModalErrorsPatientStructure(event.errors);
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
    <ModalErrorsPatientStructure ref="refModalErrorsPatientStructure" />

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>
