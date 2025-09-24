<script lang="ts" setup>
import { useToast } from '@/composables/useToast';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from 'vuetify/components/VForm';

const authenticationStore = useAuthenticationStore();
const { toast } = useToast();
const emit = defineEmits(["closeModal"]);

// Estado para la visibilidad del modal y carga
const isDialogVisible = ref<boolean>(false);
const loading = ref<boolean>(false);
const currentTab = ref<number>(0);

// Interfaces para los resultados de validación
interface ValidationError {
  Clase: string;
  Codigo: string;
  Descripcion: string;
  Observaciones: string;
  PathFuente: string;
  Fuente: string;
}

interface ValidationData {
  ResultState: boolean;
  ProcesoId: number;
  NumFactura: string;
  CodigoUnicoValidacion: string;
  CodigoUnicoValidacionToShow: string;
  FechaRadicacion: string;
  RutaArchivos: string | null;
  ResultadosValidacion: ValidationError[];
}

interface ValidationResult {
  success: boolean;
  data: ValidationData;
  errors: string[];
  status_code: number;
}

// Almacena los IDs originales, números de factura y los resultados de validación
const invoiceIds = ref<string[]>([]);
const facturaNums = ref<string[]>([]);
const validationResults = ref<Record<string, ValidationResult>>({});

// Función principal para abrir el modal
const openModal = async (ids: string[], autoValidateAll: boolean = false) => {
  invoiceIds.value = ids;
  isDialogVisible.value = true;
  currentTab.value = 0;

  // Primero cargar los datos existentes
  await loadExistingValidationData(ids);

  // Si autoValidateAll es true, ejecutar validación automáticamente
  if (autoValidateAll) {
    await submitValidation(true);
  }
};

// Carga los datos de validación existentes desde el backend
const loadExistingValidationData = async (ids: string[]) => {
  loading.value = true;
  try {
    const { data, response } = await useAxios('/rip/getValidationMetadata').post({ ids });

    if (response.status === 200 && data) {
      // Limpiar datos previos
      facturaNums.value = [];
      validationResults.value = {};

      // Procesar las facturas que devuelve el backend
      data.invoices.forEach((invoice: any) => {
        const invoiceNumber = invoice.invoice_number;
        facturaNums.value.push(invoiceNumber);

        // Solo agregar a validationResults si tiene metadata
        if (invoice.metadata) {
          validationResults.value[invoiceNumber] = {
            success: true,
            data: invoice.metadata,
            errors: [],
            status_code: 200,
          };
        }
      });
    }
  } catch (error) {
    toast('Error al cargar datos de validación existentes: ' + error.message, '', 'danger');
  } finally {
    loading.value = false;
  }
};

// Cierra el modal y limpia los datos
const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  if (!isDialogVisible.value) {
    invoiceIds.value = [];
    facturaNums.value = [];
    validationResults.value = {};
    currentTab.value = 0;
  }
};

// Envía la solicitud de validación al backend
const submitValidation = async (validateAll: boolean = false) => {
  if (!invoiceIds.value.length) {
    toast('No se proporcionaron facturas para validar', '', 'danger');
    return;
  }

  loading.value = true;
  try {
    // Determinar qué facturas validar
    const idsToValidate = validateAll ? invoiceIds.value : [invoiceIds.value[currentTab.value]];

    const { data, response } = await useAxios('/rip/validateRips').post({
      ids: idsToValidate, // Enviar IDs como espera el backend
      validate_all: validateAll,
    });

    if (response.status === 200 && data) {
      
    } else {
      toast('Error al validar facturas: ' + (data?.message || 'Error desconocido'), '', 'danger');
    }
  } catch (error) {
    toast('Excepción al validar facturas: ' + error.message, '', 'danger');
  } finally {
    loading.value = false;
  }
};

// Expone el método openModal para el componente padre
defineExpose({
  openModal,
});
</script>

<template>
  <VDialog v-model="isDialogVisible" :overlay="false" max-width="80rem" transition="dialog-transition" persistent>
    <DialogCloseBtn @click="handleDialogVisible" />
    <div>
      <VCard :disabled="loading" :loading="loading">
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>Resultados de Validación</VToolbarTitle>
          </VToolbar>
        </div>
        <VCardText>
          <!-- Mostrar mensaje si no hay facturas -->
          <div v-if="!facturaNums.length && !loading" class="text-center py-8">
            <p class="text-h6 mb-4">No hay datos de validación disponibles</p>
            <p class="text-body-2 text-medium-emphasis">
              Las facturas seleccionadas no tienen datos de validación previos.
              Utiliza los botones de validación para obtener los resultados.
            </p>
          </div>

          <!-- Tabs para cada factura (solo si hay datos) -->
          <VTabs v-if="facturaNums.length" v-model="currentTab" show-arrows>
            <VTab v-for="(numFactura, index) in facturaNums" :key="numFactura" :value="index">
              Factura {{ numFactura }}
            </VTab>
          </VTabs>

          <!-- Contenido de cada tab -->
          <VWindow v-if="facturaNums.length" v-model="currentTab" class="mt-4">
            <VWindowItem v-for="(numFactura, index) in facturaNums" :key="numFactura" :value="index">
              <div v-if="validationResults[numFactura]">
                <!-- Resumen de la factura -->
                <VCard class="mb-4" variant="outlined">
                  <VCardText>
                    <VRow>
                      <VCol cols="12" md="6">
                        <p><strong>Resultado:</strong>
                          <VChip :color="validationResults[numFactura].data.ResultState ? 'success' : 'error'"
                            size="small">
                            {{ validationResults[numFactura].data.ResultState ? 'Válido' : 'Inválido' }}
                          </VChip>
                        </p>
                        <p><strong>Proceso ID:</strong> {{ validationResults[numFactura].data.ProcesoId }}</p>
                        <p><strong>Número de Factura:</strong> {{ validationResults[numFactura].data.NumFactura }}</p>
                      </VCol>
                      <VCol cols="12" md="6">
                        <p><strong>Fecha de Radicación:</strong> {{ new
                          Date(validationResults[numFactura].data.FechaRadicacion).toLocaleString() }}</p>
                        <p><strong>Código Único de Validación:</strong> {{
                          validationResults[numFactura].data.CodigoUnicoValidacionToShow }}</p>
                      </VCol>
                    </VRow>
                  </VCardText>

                  <!-- Botón para validar esta factura específica -->
                  <VCardActions class="d-flex justify-end">
                    <VBtn :disabled="loading" :loading="loading" @click="submitValidation(false)" color="primary"
                      variant="outlined" size="small">
                      <VIcon icon="tabler-refresh" class="mr-1" />
                      Revalidar Esta Factura
                    </VBtn>
                  </VCardActions>
                </VCard>

                <!-- Tabla de errores de validación -->
                <VCard>
                  <VCardTitle>
                    Inconsistencias de Validación
                    <VChip size="small" class="ml-2">
                      {{ validationResults[numFactura].data.ResultadosValidacion.length }}
                    </VChip>
                  </VCardTitle>
                  <VCardText>
                    <VDataTable v-if="validationResults[numFactura].data.ResultadosValidacion.length > 0"
                      :items="validationResults[numFactura].data.ResultadosValidacion" :headers="[
                        { title: 'Clase', key: 'Clase' },
                        { title: 'Código', key: 'Codigo' },
                        { title: 'Descripción', key: 'Descripcion' },
                        { title: 'Observaciones', key: 'Observaciones' },
                        { title: 'Path Fuente', key: 'PathFuente' },
                      ]" :items-per-page="10" />
                    <div v-else class="text-center py-4">
                      <VIcon icon="tabler-check-circle" color="success" size="48" class="mb-2" />
                      <p class="text-h6 text-success">¡Sin inconsistencias!</p>
                      <p class="text-body-2 text-medium-emphasis">Esta factura pasó todas las validaciones.</p>
                    </div>
                  </VCardText>
                </VCard>
              </div>
              <div v-else class="text-center py-8">
                <VIcon icon="tabler-file-search" size="48" class="mb-2 text-medium-emphasis" />
                <p class="text-h6 mb-2">Sin datos de validación</p>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  No hay resultados de validación para la factura {{ numFactura }}.
                </p>
                <VBtn :disabled="loading" :loading="loading" @click="submitValidation(false)" color="primary">
                  Validar Esta Factura
                </VBtn>
              </div>
            </VWindowItem>
          </VWindow>
        </VCardText>

        <!-- Botones de acción -->
        <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5">
          <VBtn :disabled="loading" @click="handleDialogVisible" color="secondary" variant="outlined">
            Cerrar
          </VBtn>

          <!-- Botón para validar todas las facturas -->
          <VBtn :disabled="loading || !invoiceIds.length" :loading="loading" @click="submitValidation(true)"
            color="primary">
            <VIcon icon="tabler-check" class="mr-1" />
            Validar {{ invoiceIds.length > 1 ? 'Todas las Facturas' : 'Factura' }}
          </VBtn>
        </VCardText>
      </VCard>
    </div>
  </VDialog>
</template>
