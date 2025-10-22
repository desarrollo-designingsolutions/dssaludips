<script lang="ts" setup>
import ModalUploadExcel from '@/pages/Rips/Components/ModalUploadExcel.vue';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import { useToast } from '@/composables/useToast';
import { useGlobalLoading } from '@/composables/useGlobalLoading';

const globalLoading = useGlobalLoading();
const { toast } = useToast();
const authenticationStore = useAuthenticationStore();

const emit = defineEmits(["closeModal"]);

// ======= Estado base (1 sola factura) =======
const isDialogVisible = ref<boolean>(false);
const loading = ref<boolean>(false);

// Identificadores & datos
const invoiceId = ref<string | null>(null);
const invoiceNumber = ref<string | null>(null);
const pathExcel = ref<string | null>(null);

// Resultado de validación (único)
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
const validationResult = ref<ValidationResult | null>(null);

// Estado actual y en tiempo real
const currentStatus = ref<any | null>(null);
const realtimeStatus = ref<any | null>(null);

// Flags
const validationInProgress = ref<boolean>(false);

// Filtro
const errorFilter = ref<string>('');

// ======= API =======
const openModal = async (id: string) => {
  invoiceId.value = id;
  isDialogVisible.value = true;

  // limpiar estados
  invoiceNumber.value = null;
  validationResult.value = null;
  currentStatus.value = null;
  realtimeStatus.value = null;
  validationInProgress.value = false;
  errorFilter.value = '';

  await loadExistingValidationData(id);
  echoChannel(id);
};

const loadExistingValidationData = async (id: string) => {
  loading.value = true;
  try {
    const { data, response } = await useAxios('/rip/getValidationMetadata').post({ ids: id });

    if (response.status === 200 && data && data.invoices?.length) {
      const inv = data.invoices[0];

      invoiceNumber.value = inv.invoice_number ?? null;
      pathExcel.value = inv.path_excel ?? null;
      currentStatus.value = {
        status: inv.status,
        status_backgroundColor: inv.status_backgroundColor,
        status_description: inv.status_description,
        invoice_id: inv.id,
        timestamp: new Date().toISOString(),
      };

      if (inv.metadata) {
        validationResult.value = {
          success: true,
          data: inv.metadata,
          errors: [],
          status_code: 200,
        };
      }
    }
  } catch (e: any) {
    toast('Error al cargar datos de validación: ' + e.message, '', 'danger');
  } finally {
    loading.value = false;
  }
};

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  if (!isDialogVisible.value) {
    if (invoiceId.value) window.Echo.leave(`rip_invoice_modal.${invoiceId.value}`);
    invoiceId.value = null;
    invoiceNumber.value = null;
    validationResult.value = null;
    currentStatus.value = null;
    realtimeStatus.value = null;
    validationInProgress.value = false;
    errorFilter.value = '';
  }
};

// Helpers de estado
const getFinalStatus = computed(() => {
  return realtimeStatus.value ?? currentStatus.value ?? null;
});
const isProcessing = computed(() => {
  const st = getFinalStatus.value?.status;
  return validationInProgress.value || st === 'RIP_INVOICE_STATUS_005' || st === 'RIP_INVOICE_STATUS_006';
});
const isCompleted = computed(() => {
  const st = getFinalStatus.value?.status;
  return st === 'RIP_INVOICE_STATUS_001' || st === 'RIP_INVOICE_STATUS_007';
});

// Filtro de errores
const filteredErrors = computed(() => {
  const errs = validationResult.value?.data?.ResultadosValidacion || [];
  const q = errorFilter.value.trim().toLowerCase();
  if (!q) return errs;
  return errs.filter(e =>
    e.Clase.toLowerCase().includes(q) ||
    e.Codigo.toLowerCase().includes(q) ||
    e.Descripcion.toLowerCase().includes(q) ||
    e.Observaciones.toLowerCase().includes(q) ||
    e.PathFuente.toLowerCase().includes(q)
  );
});

// Validar (esta única factura)
const submitValidation = async () => {
  if (!invoiceId.value) {
    toast('No se proporcionó factura para validar', '', 'danger');
    return;
  }

  validationInProgress.value = true;
  realtimeStatus.value = {
    status: 'RIP_INVOICE_STATUS_005',
    status_backgroundColor: 'warning',
    status_description: 'En espera validación JSON',
    timestamp: new Date().toISOString()
  };
  loading.value = true;

  try {
    const { data, response } = await useAxios('/rip/validateRips').post({
      ids: invoiceId.value,
      company_id: authenticationStore.company.id,
      user_id: authenticationStore.user.id,
    });

    if (response.status === 200 && data) {
      globalLoading.startLoading(data.batch_id);
      // notificación opcional
    } else {
      toast('Error al validar: ' + (data?.message || 'Error desconocido'), '', 'danger');
      validationInProgress.value = false;
      realtimeStatus.value = null;
    }
  } catch (e: any) {
    toast('Excepción al validar: ' + e.message, '', 'danger');
    validationInProgress.value = false;
    realtimeStatus.value = null;
  } finally {
    loading.value = false;
  }
};

// WebSocket (único canal)
const echoChannel = (id: string) => {
  window.Echo.channel(`rip_invoice_modal.${id}`)
    .listen('RipValidationStatusUpdated', (event: any) => {
      realtimeStatus.value = {
        status: event.status,
        status_backgroundColor: event.status_backgroundColor,
        status_description: event.status_description,
        error: event.error,
        timestamp: event.timestamp
      };

      if (event.status === 'RIP_INVOICE_STATUS_001' || event.status === 'RIP_INVOICE_STATUS_007') {
        validationInProgress.value = false;
        // refrescar metadata cuando termina
        setTimeout(() => loadExistingValidationData(id), 1000);
      }
    })
    .error((err: any) => console.error('WS error:', err));
};

// Exponer
defineExpose({ openModal });

//ModalUploadExcel
const refModalUploadExcel = ref()
const openModalUploadExcel = () => {
  const invoice = { id: invoiceId }
  refModalUploadExcel.value.openModal(invoice, null)
}

//descarga de archivos
const downloadFileData = async (type: string) => {
  loading.downloadFile = true;

  const today = new Date();
  const day = String(today.getDate()).padStart(2, '0');
  const month = String(today.getMonth() + 1).padStart(2, '0'); // Meses van de 0 a 11
  const year = today.getFullYear();
  const formattedDate = `${day}${month}${year}`; // ddmmyyyy

  let api = ""
  let ext = ""
  let nameFile = ""

  if (type === "excel") {
    api = `/ripInvoice/downloadExcel/${invoiceId.value}`
    ext = "xlsx"
    nameFile = `Invoice_${invoiceNumber.value}_${formattedDate}`
  } else if (type === "json") {
    api = `/ripInvoice/downloadJson/${invoiceId.value}`
    ext = "json"
    nameFile = `Invoice_${invoiceNumber.value}_${formattedDate}`
  } else if (type === "xml") {
    api = `/ripInvoice/downloadXml/${invoiceId.value}`
    ext = "xml"
    nameFile = `Invoice_${invoiceNumber.value}_${formattedDate}`
  }

  await downloadBlob(api, nameFile, ext)

  loading.downloadFile = false;
};
</script>

<template>
  <VDialog v-model="isDialogVisible" :overlay="false" max-width="70rem" transition="dialog-transition" persistent>
    <DialogCloseBtn @click="handleDialogVisible" />

    <VCard :loading="loading">
      <!-- Toolbar sin “procesando” ni tabs -->
      <VToolbar color="primary">
        <VToolbarTitle>
          Resultados de Validación —
          <span v-if="invoiceNumber">Factura {{ invoiceNumber }}</span>
          <span v-else>#{{ invoiceId }}</span>
        </VToolbarTitle>
        <VSpacer />
      </VToolbar>

      <VCardText class="modal-content">
        <!-- Estado -->
        <VAlert v-if="getFinalStatus" :color="getFinalStatus?.status_backgroundColor || 'info'" class="mb-4"
          :icon="false" variant="outlined">
          <template #prepend>
            <VProgressCircular v-if="isProcessing" indeterminate
              :color="getFinalStatus?.status_backgroundColor || 'primary'" size="24" width="2" />
            <VIcon v-else :icon="getFinalStatus?.status === 'RIP_INVOICE_STATUS_001' ? 'tabler-check' :
              getFinalStatus?.status === 'RIP_INVOICE_STATUS_007' ? 'tabler-x' : 'tabler-clock'" />
          </template>

          <VAlertTitle class="text-body-1">
            {{ getFinalStatus?.status_description || 'Estado desconocido' }}
            <VChip v-if="getFinalStatus" size="small" class="ml-2">
              {{ getFinalStatus.status }}
            </VChip>
          </VAlertTitle>

          <div v-if="isProcessing" class="text-caption">
            ⏳ La validación está en proceso. Por favor espere...
          </div>
          <div v-else-if="getFinalStatus?.error" class="text-caption">
            ❌ Error: {{ getFinalStatus.error }}
          </div>
        </VAlert>

        <!-- Resumen -->
        <VCard class="mb-4" variant="outlined" v-if="validationResult && !isProcessing">
          <VCardText>
            <VRow>
              <VCol cols="12" md="6">
                <p><strong>Resultado:</strong>
                  <VChip :color="validationResult.data.ResultState ? 'success' : 'error'" size="small">
                    {{ validationResult.data.ResultState ? 'Válido' : 'Inválido' }}
                  </VChip>
                </p>
                <p><strong>Número de Factura:</strong> {{ validationResult.data.NumFactura }}</p>
              </VCol>
              <VCol cols="12" md="6">
                <p><strong>Fecha de Radicación:</strong> {{ new
                  Date(validationResult.data.FechaRadicacion).toLocaleString() }}
                </p>
              </VCol>
            </VRow>
          </VCardText>

          <VCardActions class="d-flex justify-end">
            <VBtn :disabled="isProcessing" :loading="isProcessing" @click="submitValidation" color="primary"
              variant="outlined" size="small">
              <VIcon icon="tabler-refresh" class="mr-1" />
              {{ isProcessing ? 'Validando...' : 'Revalidar Esta Factura' }}
            </VBtn>
            <VBtn :disabled="isProcessing" :loading="isProcessing" @click="openModalUploadExcel()" color="primary" variant="outlined" size="small">
              <VIcon icon="tabler-upload" class="mr-1" />
              Subir Excel
            </VBtn>
            <VBtn v-if="pathExcel" :disabled="isProcessing" :loading="isProcessing" @click="downloadFileData('excel')" color="primary" variant="outlined" size="small">
              <VIcon icon="tabler-download" class="mr-1" />
              Descargar Excel
            </VBtn>
          </VCardActions>
        </VCard>

        <!-- Tabla de inconsistencias -->
        <VCard class="mt-4" v-if="validationResult && !isProcessing">
          <VCardTitle class="d-flex align-center">
            Inconsistencias de Validación
            <VChip size="small" class="ml-2">
              {{ filteredErrors.length }} / {{ validationResult?.data?.ResultadosValidacion?.length || 0 }}
            </VChip>
            <VSpacer />
            <VTextField v-model="errorFilter" density="compact" variant="outlined" label="Buscar..."
              prepend-inner-icon="tabler-search" hide-details class="ms-4" style="max-width: 250px" />
          </VCardTitle>

          <div class="table-container">
            <VDataTable v-if="filteredErrors.length" :items="filteredErrors" :headers="[
              { title: 'Clase', key: 'Clase', sortable: true },
              { title: 'Código', key: 'Codigo', sortable: true },
              { title: 'Descripción', key: 'Descripcion', sortable: false },
              { title: 'Observaciones', key: 'Observaciones', sortable: false },
              { title: 'PathFuente', key: 'PathFuente', sortable: false },
            ]" :items-per-page="-1" hide-default-footer class="elevation-0">
              <template #item.Descripcion="{ item }">
                <div class="text-wrap">{{ item.Descripcion }}</div>
              </template>
              <template #item.Observaciones="{ item }">
                <div class="text-wrap">{{ item.Observaciones }}</div>
              </template>
              <template #item.PathFuente="{ item }">
                <div class="text-wrap text-caption">{{ item.PathFuente }}</div>
              </template>
            </VDataTable>

            <div v-else-if="!isProcessing" class="text-center py-8">
              <VIcon icon="tabler-check-circle" color="success" size="48" class="mb-2" />
              <p class="text-h6 text-success">¡Sin inconsistencias!</p>
            </div>
          </div>
        </VCard>

        <!-- Si no hay datos -->
        <div v-if="!validationResult && !isProcessing && !loading" class="text-center py-8">
          <VIcon icon="tabler-file-search" size="48" class="mb-2 text-medium-emphasis" />
          <p class="text-h6 mb-2">Sin datos de validación</p>
          <VBtn :disabled="isProcessing" :loading="isProcessing" @click="submitValidation" color="primary">
            {{ isProcessing ? 'Validando...' : 'Validar Factura' }}
          </VBtn>
        </div>

        <!-- Vista de proceso -->
        <div v-if="isProcessing" class="text-center py-8">
          <VProgressCircular indeterminate size="64" width="4" color="primary" class="mb-4" />
          <p class="text-h6 mb-2">
            Validando factura {{ invoiceNumber || ('#' + invoiceId) }}
          </p>
          <p class="text-body-2 text-medium-emphasis">Esto puede tomar unos momentos...</p>
        </div>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5 pt-0">
        <VBtn @click="handleDialogVisible" color="secondary" variant="outlined">
          Cerrar
        </VBtn>
        <VBtn :disabled="isProcessing" :loading="isProcessing" @click="submitValidation" color="primary">
          <VIcon icon="tabler-check" class="mr-1" />
          {{ isProcessing ? 'Validando...' : 'Validar Factura' }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <ModalUploadExcel ref="refModalUploadExcel" :maxFileSizeMB="200" />
</template>

<style scoped>
.opacity-60 {
  opacity: 0.6;
}

.pointer-events-none {
  pointer-events: none;
}

/* Scroll interno para el contenido del modal */
.modal-content {
  max-height: 70vh;
  overflow-y: auto;
  padding-bottom: 0;
}

/* Scroll para la tabla de errores */
.table-container {
  max-height: 400px;
  overflow-y: auto;
}

.text-wrap {
  white-space: normal;
  word-break: break-word;
}
</style>
