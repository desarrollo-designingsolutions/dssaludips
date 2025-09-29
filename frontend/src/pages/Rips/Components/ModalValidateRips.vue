<script lang="ts" setup>
import { useToast } from '@/composables/useToast'; 
const { toast } = useToast();
const emit = defineEmits(["closeModal"]);

// Estado para la visibilidad del modal y carga
const isDialogVisible = ref<boolean>(false);
const loading = ref<boolean>(false);
const currentTab = ref<number>(0);

// ✅ NUEVO: Estado específico para validación en progreso
const validationInProgress = ref<Record<string, boolean>>({});

// ✅ NUEVO: Filtro para la tabla de errores
const errorFilter = ref<string>('');

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

// Estados en tiempo real
const currentInvoiceStatus = ref<Record<string, any>>({});
const realTimeStatus = ref<Record<string, any>>({});

// ✅ NUEVO: Mapeo de invoice_id a número de factura
const invoiceIdToNumberMap = ref<Record<string, string>>({});

// Función principal para abrir el modal
const openModal = async (ids: string[]) => {
  invoiceIds.value = ids;
  isDialogVisible.value = true;
  currentTab.value = 0;
  
  // Limpiar estados anteriores
  realTimeStatus.value = {};
  currentInvoiceStatus.value = {};
  validationInProgress.value = {};
  invoiceIdToNumberMap.value = {};
  errorFilter.value = ''; // ✅ Limpiar filtro al abrir

  // Primero cargar los datos existentes
  await loadExistingValidationData(ids);

  // Configurar WebSockets después de tener los datos
  ids.forEach(element => {
    echoChannel(element);
  });
};

// ✅ MEJORADO: Obtener número de factura desde invoice_id
const getInvoiceNumber = (invoiceId: string) => {
  return invoiceIdToNumberMap.value[invoiceId] || invoiceId;
};

// ✅ MEJORADO: Obtener invoice_id desde número de factura
const getInvoiceId = (invoiceNumber: string) => {
  return Object.keys(invoiceIdToNumberMap.value).find(
    id => invoiceIdToNumberMap.value[id] === invoiceNumber
  ) || invoiceNumber;
};

// ✅ MEJORADO: Obtener el estado final
const getFinalStatus = (invoiceNumber: string) => {
  const invoiceId = getInvoiceId(invoiceNumber);
  
  // 1. Priorizar estado en tiempo real (WebSocket)
  if (realTimeStatus.value[invoiceId]) {
    return realTimeStatus.value[invoiceId];
  }
  
  // 2. Fallback al estado actual de la BD
  if (currentInvoiceStatus.value[invoiceNumber]) {
    return currentInvoiceStatus.value[invoiceNumber];
  }
  
  return null;
};

// ✅ MEJORADO: Verificar si una factura está siendo procesada
const isProcessing = (invoiceNumber: string) => {
  const status = getFinalStatus(invoiceNumber);
  const invoiceId = getInvoiceId(invoiceNumber);
  
  // Verificar por estado o por flag de validación en progreso
  return (status && (status.status === 'RIP_INVOICE_STATUS_005' || status.status === 'RIP_INVOICE_STATUS_006')) ||
         validationInProgress.value[invoiceId] === true;
};

// ✅ MEJORADO: Verificar si una factura terminó de procesarse
const isCompleted = (invoiceNumber: string) => {
  const status = getFinalStatus(invoiceNumber);
  return status && (status.status === 'RIP_INVOICE_STATUS_001' || status.status === 'RIP_INVOICE_STATUS_007');
};

// ✅ Computada: errores filtrados
const filteredErrors = computed(() => {
  const searchTerm = errorFilter.value.toLowerCase();
  const currentInvoice = facturaNums.value[currentTab.value];
  const errors = validationResults.value[currentInvoice]?.data?.ResultadosValidacion || [];

  if (!searchTerm) return errors;

  return errors.filter(error =>
    error.Clase.toLowerCase().includes(searchTerm) ||
    error.Codigo.toLowerCase().includes(searchTerm) ||
    error.Descripcion.toLowerCase().includes(searchTerm) ||
    error.Observaciones.toLowerCase().includes(searchTerm) ||
    error.PathFuente.toLowerCase().includes(searchTerm)
  );
});

// Carga los datos de validación existentes desde el backend
const loadExistingValidationData = async (ids: string[]) => {
  loading.value = true;
  try {
    const { data, response } = await useAxios('/rip/getValidationMetadata').post({ ids });

    console.log("🔍 DATA RECIBIDA:", data);

    if (response.status === 200 && data) {
      // Limpiar datos previos
      facturaNums.value = [];
      validationResults.value = {};
      currentInvoiceStatus.value = {};
      invoiceIdToNumberMap.value = {};

      // Procesar las facturas que devuelve el backend
      data.invoices.forEach((invoice: any) => {
        const invoiceNumber = invoice.invoice_number;
        const invoiceId = invoice.id;
        
        facturaNums.value.push(invoiceNumber);
        
        // ✅ GUARDAR MAPEO
        invoiceIdToNumberMap.value[invoiceId] = invoiceNumber;

        // Guardar estado actual de la factura
        currentInvoiceStatus.value[invoiceNumber] = {
          status: invoice.status,
          status_backgroundColor: invoice.status_backgroundColor,
          status_description: invoice.status_description,
          invoice_id: invoiceId,
          timestamp: new Date().toISOString()
        };

        console.log("📊 Estado actual de factura:", invoiceNumber, invoice.status);

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

      console.log("🗂️ Mapeo creado:", invoiceIdToNumberMap.value);
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
    // Limpiar WebSockets al cerrar
    invoiceIds.value.forEach(id => {
      window.Echo.leave(`rip_invoice_modal.${id}`);
    });
    
    invoiceIds.value = [];
    facturaNums.value = [];
    validationResults.value = {};
    realTimeStatus.value = {};
    currentInvoiceStatus.value = {};
    validationInProgress.value = {};
    invoiceIdToNumberMap.value = {};
    currentTab.value = 0;
    errorFilter.value = '';
  }
};

// ✅ MEJORADO: Envía la solicitud de validación al backend
const submitValidation = async (validateAll: boolean = false) => {
  if (!invoiceIds.value.length) {
    toast('No se proporcionaron facturas para validar', '', 'danger');
    return;
  }

  // Determinar qué facturas validar
  const idsToValidate = validateAll ? invoiceIds.value : [invoiceIds.value[currentTab.value]];
  
  // ✅ 1. MARCAR COMO EN PROGRESO INMEDIATAMENTE
  idsToValidate.forEach(invoiceId => {
    validationInProgress.value[invoiceId] = true;
    
    // ✅ 2. ACTUALIZAR ESTADO VISUAL INMEDIATAMENTE
    realTimeStatus.value[invoiceId] = {
      status: 'RIP_INVOICE_STATUS_005',
      status_backgroundColor: 'warning',
      status_description: 'En espera validación JSON',
      timestamp: new Date().toISOString()
    };
    
    console.log("🚀 Validación iniciada para:", invoiceId);
  });

  loading.value = true;

  try {
    const { data, response } = await useAxios('/rip/validateRips').post({
      ids: idsToValidate, 
    });

    if (response.status === 200 && data) {
      // toast('Validación iniciada correctamente', '', 'success');
      // console.log("✅ Petición enviada, WebSockets manejarán las actualizaciones");
    } else {
      toast('Error al validar facturas: ' + (data?.message || 'Error desconocido'), '', 'danger');
      
      // ✅ REVERTIR EN CASO DE ERROR
      idsToValidate.forEach(invoiceId => {
        validationInProgress.value[invoiceId] = false;
        delete realTimeStatus.value[invoiceId];
      });
    }
  } catch (error) {
    toast('Excepción al validar facturas: ' + error.message, '', 'danger');
    
    // ✅ REVERTIR EN CASO DE EXCEPCIÓN
    idsToValidate.forEach(invoiceId => {
      validationInProgress.value[invoiceId] = false;
      delete realTimeStatus.value[invoiceId];
    });
  } finally {
    loading.value = false;
  }
};

// ✅ MEJORADO: WebSocket para recibir actualizaciones en tiempo real
const echoChannel = (id: string) => {
  console.log("🔌 Conectando WebSocket MODAL para invoice_id:", id);
  
  window.Echo.channel(`rip_invoice_modal.${id}`) 
    .listen('RipValidationStatusUpdated', (event: any) => {
      console.log("📡 MODAL - Evento WebSocket recibido:", event);
      
      const invoiceNumber = getInvoiceNumber(event.invoice_id);
      console.log("🔍 Número de factura correspondiente:", invoiceNumber);
      
      // ✅ 1. ACTUALIZAR ESTADO EN TIEMPO REAL
      realTimeStatus.value[event.invoice_id] = {
        status: event.status,
        status_backgroundColor: event.status_backgroundColor,
        status_description: event.status_description,
        // result: event.result,
        error: event.error,
        timestamp: event.timestamp
      };
      
      // ✅ 2. QUITAR MARCA DE EN PROGRESO CUANDO TERMINE
      if (event.status === 'RIP_INVOICE_STATUS_001' || event.status === 'RIP_INVOICE_STATUS_007') {
        validationInProgress.value[event.invoice_id] = false;
        console.log("✅ Validación terminada para:", event.invoice_id);
      }
      
      console.log("🔄 Estado actualizado:", event.invoice_id, "Estado:", event.status);
      console.log("📊 Estados en tiempo real:", realTimeStatus.value);
      
      // ✅ 3. FORZAR ACTUALIZACIÓN DE LA VISTA
      setTimeout(() => {
        // Esto fuerza la reactividad de Vue
      }, 0);
      
      // ✅ 4. RECARGAR DATOS CUANDO TERMINE LA VALIDACIÓN
      if (event.status === 'RIP_INVOICE_STATUS_001' || event.status === 'RIP_INVOICE_STATUS_007') {
        console.log("🔄 Recargando datos para factura:", event.invoice_id);
        setTimeout(() => {
          loadExistingValidationData([event.invoice_id]);
        }, 1000);
      }
    })
    .error((error: any) => {
      console.error("❌ Error en WebSocket:", error);
    });
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
      <VCard :loading="loading">
        <div>
          <VToolbar color="primary">
            <VToolbarTitle>Resultados de Validación</VToolbarTitle>
            <VSpacer />
            <VChip v-if="facturaNums.length" color="info" variant="outlined">
              {{ facturaNums.filter(num => isProcessing(num)).length }} procesando
            </VChip>
          </VToolbar>
        </div>
        <!-- ✅ Contenedor con scroll interno -->
        <VCardText class="modal-content">
          <!-- ALERTA DE PROGRESO -->
          <VAlert 
            v-if="invoiceIds.length > 1" 
            color="info" 
            class="mb-4"
            :icon="false"
            variant="outlined"
          >
            <template #prepend>
              <VProgressCircular
                v-if="facturaNums.some(num => isProcessing(num))"
                indeterminate
                color="info"
                size="24"
                width="2"
              />
              <VIcon v-else icon="tabler-info-circle" />
            </template>
            
            <VAlertTitle class="text-body-1">
              Progreso de validación
            </VAlertTitle>
            
            <div class="mt-1">
              <span class="text-caption">
                Completadas: {{ facturaNums.filter(num => isCompleted(num)).length }} / {{ facturaNums.length }}
                • Procesando: {{ facturaNums.filter(num => isProcessing(num)).length }}
              </span>
              <VProgressLinear 
                :model-value="(facturaNums.filter(num => isCompleted(num)).length / facturaNums.length) * 100" 
                color="info"
                height="6"
                class="mt-1"
              />
            </div>
          </VAlert>

          <!-- Mostrar mensaje si no hay facturas -->
          <div v-if="!facturaNums.length && !loading" class="text-center py-8">
            <p class="text-h6 mb-4">No hay datos de validación disponibles</p>
          </div>

          <!-- Tabs para cada factura -->
          <VTabs v-if="facturaNums.length" v-model="currentTab" show-arrows>
            <VTab v-for="(numFactura, index) in facturaNums" :key="numFactura" :value="index">
              <div class="d-flex align-center">
                <span>Factura {{ numFactura }}</span>
                <!-- ✅ INDICADOR MEJORADO -->
                <VProgressCircular 
                  v-if="isProcessing(numFactura)"
                  indeterminate 
                  size="16" 
                  width="2"
                  class="ml-2"
                  color="primary"
                />
                <VIcon 
                  v-else-if="isCompleted(numFactura)"
                  :color="getFinalStatus(numFactura)?.status === 'RIP_INVOICE_STATUS_001' ? 'success' : 'error'"
                  size="16"
                  class="ml-2"
                  :icon="getFinalStatus(numFactura)?.status === 'RIP_INVOICE_STATUS_001' ? 'tabler-check' : 'tabler-x'"
                />
                <VIcon 
                  v-else
                  icon="tabler-clock"
                  size="16"
                  class="ml-2"
                  color="grey"
                />
              </div>
            </VTab>
          </VTabs>

          <!-- Contenido de cada tab -->
          <VWindow v-if="facturaNums.length" v-model="currentTab" class="mt-4">
            <VWindowItem v-for="(numFactura, index) in facturaNums" :key="numFactura" :value="index">
              <!-- ✅ ALERTA MEJORADA -->
              <VAlert 
                v-if="getFinalStatus(numFactura) || isProcessing(numFactura)" 
                :color="getFinalStatus(numFactura)?.status_backgroundColor || 'info'" 
                class="mb-4"
                :icon="false"
                variant="outlined"
              >
                <template #prepend>
                  <VProgressCircular
                    v-if="isProcessing(numFactura)"
                    indeterminate
                    :color="getFinalStatus(numFactura)?.status_backgroundColor || 'primary'"
                    size="24"
                    width="2"
                  />
                  <VIcon 
                    v-else 
                    :icon="getFinalStatus(numFactura)?.status === 'RIP_INVOICE_STATUS_001' ? 'tabler-check' : 
                            getFinalStatus(numFactura)?.status === 'RIP_INVOICE_STATUS_007' ? 'tabler-x' : 'tabler-clock'" 
                  />
                </template>
                
                <VAlertTitle class="text-body-1">
                  {{ getFinalStatus(numFactura)?.status_description || 'Estado desconocido' }}
                  <VChip v-if="getFinalStatus(numFactura)" size="small" class="ml-2">
                    {{ getFinalStatus(numFactura).status }}
                  </VChip>
                </VAlertTitle>
                
                <div v-if="isProcessing(numFactura)" class="text-caption">
                  ⏳ La validación está en proceso. Por favor espere...
                </div>
                <div v-else-if="getFinalStatus(numFactura)?.error" class="text-caption">
                  ❌ Error: {{ getFinalStatus(numFactura).error }}
                </div>
              </VAlert>

              <!-- ✅ CONTENIDO CON MEJOR BLOQUEO -->
              <div :class="{ 'opacity-60': isProcessing(numFactura), 'pointer-events-none': isProcessing(numFactura) }">
                <div v-if="validationResults[numFactura] && !isProcessing(numFactura)"> 
                  <!-- Resumen de la factura -->
                  <VCard class="mb-4" variant="outlined">
                    <VCardText>
                      <VRow>
                        <VCol cols="12" md="6">
                          <p><strong>Resultado:</strong>
                            <VChip :color="validationResults[numFactura].data.ResultState ? 'success' : 'error'" size="small">
                              {{ validationResults[numFactura].data.ResultState ? 'Válido' : 'Inválido' }}
                            </VChip>
                          </p>
                          <p><strong>Número de Factura:</strong> {{ validationResults[numFactura].data.NumFactura }}</p>
                        </VCol>
                        <VCol cols="12" md="6">
                          <p><strong>Fecha de Radicación:</strong> {{ new Date(validationResults[numFactura].data.FechaRadicacion).toLocaleString() }}</p>
                        </VCol>
                      </VRow>
                    </VCardText>

                    <VCardActions class="d-flex justify-end">
                      <VBtn 
                        :disabled="isProcessing(numFactura)" 
                        :loading="isProcessing(numFactura)" 
                        @click="submitValidation(false)" 
                        color="primary"
                        variant="outlined" 
                        size="small"
                      >
                        <VIcon icon="tabler-refresh" class="mr-1" />
                        {{ isProcessing(numFactura) ? 'Validando...' : 'Revalidar Esta Factura' }}
                      </VBtn>
                    </VCardActions>
                  </VCard>

                  <!-- ✅ Tabla de errores con filtro y scroll -->
                  <VCard class="mt-4">
                    <VCardTitle class="d-flex align-center">
                      Inconsistencias de Validación
                      <VChip size="small" class="ml-2">
                        {{ filteredErrors.length }} / {{ validationResults[numFactura]?.data?.ResultadosValidacion?.length || 0 }}
                      </VChip>
                      <VSpacer />
                      <VTextField
                        v-model="errorFilter"
                        density="compact"
                        variant="outlined"
                        label="Buscar..."
                        prepend-inner-icon="tabler-search"
                        hide-details
                        class="ms-4"
                        style="max-width: 250px"
                      />
                    </VCardTitle>

                    <div class="table-container">
                      <VDataTable
                        v-if="filteredErrors.length > 0"
                        :items="filteredErrors"
                        :headers="[
                          { title: 'Clase', key: 'Clase', sortable: true },
                          { title: 'Código', key: 'Codigo', sortable: true },
                          { title: 'Descripción', key: 'Descripcion', sortable: false },
                          { title: 'Observaciones', key: 'Observaciones', sortable: false },
                          { title: 'PathFuente', key: 'PathFuente', sortable: false },
                        ]"
                        :items-per-page="-1"
                        hide-default-footer
                        class="elevation-0"
                      >
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

                      <div v-else-if="!isProcessing(numFactura)" class="text-center py-8">
                        <VIcon icon="tabler-check-circle" color="success" size="48" class="mb-2" />
                        <p class="text-h6 text-success">¡Sin inconsistencias!</p>
                      </div>
                    </div>
                  </VCard>
                </div>
                
                <div v-else-if="!isProcessing(numFactura)" class="text-center py-8">
                  <VIcon icon="tabler-file-search" size="48" class="mb-2 text-medium-emphasis" />
                  <p class="text-h6 mb-2">Sin datos de validación</p>
                  <VBtn 
                    :disabled="isProcessing(numFactura)" 
                    :loading="isProcessing(numFactura)" 
                    @click="submitValidation(false)" 
                    color="primary"
                  >
                    {{ isProcessing(numFactura) ? 'Validando...' : 'Validar Esta Factura' }}
                  </VBtn>
                </div>
                
                <div v-else class="text-center py-8">
                  <VProgressCircular indeterminate size="64" width="4" color="primary" class="mb-4" />
                  <p class="text-h6 mb-2">Validando factura {{ numFactura }}</p>
                  <p class="text-body-2 text-medium-emphasis">Esto puede tomar unos momentos...</p>
                </div>
              </div>
            </VWindowItem>
          </VWindow>
        </VCardText>

        <!-- Botones de acción -->
        <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5 pt-0">
          <VBtn @click="handleDialogVisible" color="secondary" variant="outlined">
            Cerrar
          </VBtn>

          <VBtn 
            :disabled="facturaNums.some(num => isProcessing(num))" 
            :loading="facturaNums.some(num => isProcessing(num))" 
            @click="submitValidation(true)"
            color="primary"
          >
            <VIcon icon="tabler-check" class="mr-1" />
            Validar {{ invoiceIds.length > 1 ? 'Todas las Facturas' : 'Factura' }}
          </VBtn>
        </VCardText>
      </VCard>
    </div>
  </VDialog>
</template>

<style scoped>
.opacity-60 {
  opacity: 0.6;
}
.pointer-events-none {
  pointer-events: none;
}

/* ✅ Scroll interno para el contenido del modal */
.modal-content {
  max-height: 70vh;
  overflow-y: auto;
  padding-bottom: 0;
}

/* ✅ Scroll para la tabla de errores */
.table-container {
  max-height: 400px;
  overflow-y: auto;
}

.text-wrap {
  white-space: normal;
  word-break: break-word;
}
</style>
