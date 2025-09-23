<script lang="ts" setup>
import { useToast } from '@/composables/useToast';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from 'vuetify/components/VForm'; 
const authenticationStore = useAuthenticationStore();
const { toast } = useToast();
const emit = defineEmits(["closeModal"]);

// State for modal visibility and loading
const isDialogVisible = ref<boolean>(false);
const loading = ref<boolean>(false);

// Store RIP IDs and validation results
const ripIds = ref<string[]>([]);
const validationResults = ref<Record<string, { success: boolean; data: any; errors: any[] }>>({});

// Toggle modal visibility
const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  if (!isDialogVisible.value) {
    ripIds.value = []; // Clear IDs when closing
    validationResults.value = {}; // Clear results
  }
};

// Open modal and store IDs
const openModal = (ids: string[]) => {
  ripIds.value = ids;
  isDialogVisible.value = true;
};

// Submit RIP validation request
const submitValidation = async () => {
  if (!ripIds.value.length) {
    toast('No se proporcionaron IDs para validar', '', 'danger');
    return;
  }

  loading.value = true;
  try {
    const { data, response } = await useAxios('/rip/validateRips').post({ ids: ripIds.value });

    if (response.status === 200 && data) {
      validationResults.value = data; // Store results (e.g., { "1": { success, data, errors }, "2": {...} })

      // Check for any errors in results
      const hasErrors = Object.values(data).some((result: any) => !result.success);
      if (hasErrors) {
        toast('Algunas validaciones fallaron. Revisa los detalles.', '', 'warning');
      } else {
        toast('Validaciones completadas exitosamente', '', 'success');
        emit("closeModal", data); // Emit results to parent
        handleDialogVisible();
      }
    } else {
      toast('Error al validar RIPS: ' + (data?.message || 'Error desconocido'), '', 'danger');
    }
  } catch (error) {
    toast('Excepción al validar RIPS: ' + error.message, '', 'danger');
  } finally {
    loading.value = false;
  }
};

// Expose openModal for parent component
defineExpose({
  openModal,
});
</script>

<template>
  <VDialog v-model="isDialogVisible" :overlay="false" max-width="60rem" transition="dialog-transition" persistent>
    <DialogCloseBtn @click="handleDialogVisible" />
    <div>
      <VCard :disabled="loading" :loading="loading">
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>Validación de RIPS</VToolbarTitle>
          </VToolbar>
        </div>

        <VCardText>
          <!-- Show selected IDs -->
          <p v-if="ripIds.length">Validando {{ ripIds.length }} factura(s): {{ ripIds.join(', ') }}</p>
          <p v-else>No hay facturas seleccionadas.</p>

          <!-- Display validation results -->
          <VDataTable
            v-if="Object.keys(validationResults).length"
            :items="Object.entries(validationResults).map(([id, result]) => ({ id, ...result }))"
            :headers="[
              { title: 'ID Factura', key: 'id' },
              { title: 'Estado', key: 'success', value: (item) => item.success ? 'Éxito' : 'Error' },
              { title: 'Errores', key: 'errors', value: (item) => item.errors.length ? item.errors.map(e => e.descripcion).join('; ') : 'Ninguno' },
            ]"
            :items-per-page="10"
            class="mt-4"
          >
            <!-- Custom slot for errors to show details -->
            <template v-slot:item.errors="{ item }">
              <VList v-if="item.errors.length">
                <VListItem v-for="(error, index) in item.errors" :key="index">
                  <VListItemTitle>{{ error.descripcion }}</VListItemTitle>
                  <VListItemSubtitle>{{ error.observaciones }} (Path: {{ error.path }})</VListItemSubtitle>
                </VListItem>
              </VList>
              <span v-else>Ninguno</span>
            </template>
          </VDataTable>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5">
          <VBtn :disabled="loading" :loading="loading" @click="handleDialogVisible" color="secondary">
            Cerrar
          </VBtn>
          <VBtn :disabled="loading || !ripIds.length" :loading="loading" @click="submitValidation" color="primary">
            Validar
          </VBtn>
        </VCardText>
      </VCard>
    </div>
  </VDialog>
</template>
