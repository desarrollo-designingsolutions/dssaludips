<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import axios from 'axios';
import { computed, ref } from 'vue';
const globalLoading = useGlobalLoading();

const { toast } = useToast()
const authenticationStore = useAuthenticationStore();

interface FileUpload {
  id: string
  name: string
  size: number
  file: File
  progress: number
  status: 'pending' | 'uploading' | 'completed' | 'error'
  errorMessage?: string
  xhr?: XMLHttpRequest
}

interface FileUploadProps {
  maxFileSizeMB?: number
  allowedExtensions?: string[]
  maxFiles?: number
}

const props = withDefaults(defineProps<FileUploadProps>(), {
  maxFileSizeMB: 5, // 5MB para archivos ZIP
  allowedExtensions: () => [".zip"],
  maxFiles: 1
})

// Configuración de Axios
const accessToken = useCookie("accessToken").value;
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'multipart/form-data',
    'Accept': 'application/json',
    'Authorization': `Bearer ${accessToken}`,
  },
})

const successDialog = ref(false)
const currentStep = ref<'upload' | 'uploading'>('upload')

// File management
const files = ref<FileUpload[]>([])
const isDragOver = ref(false)
const fileInput = ref<HTMLInputElement>()

// Progress messages
const getProgressMessage = (progress: number): string => {
  if (progress < 20) return "Iniciando la carga del archivo ZIP...";
  if (progress < 40) return "Verificando la integridad del archivo ZIP...";
  if (progress < 60) return "Subiendo el archivo ZIP al servidor...";
  if (progress < 80) return "Preparando los datos para la cola...";
  if (progress < 100) return "Finalizando la carga del archivo...";
  return "¡Carga completada! Los datos están listos para procesarse en la cola.";
};

const getProgressColor = (progress: number): string => {
  if (progress < 30) return "orange"
  if (progress < 70) return "primary"
  return "success"
}

// Computed properties
const allFilesCompleted = computed(() => {
  return files.value.length > 0 && files.value.every(file => file.status === 'completed')
})

// Métodos para manejar archivos
const generateFileId = () => Math.random().toString(36).substr(2, 9)

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatSizeInfo = (): string => {
  return props.maxFileSizeMB > 0
    ? `Límite de tamaño: ${props.maxFileSizeMB} MB`
    : 'Tamaño de archivo ilimitado'
}

const isValidFile = (file: File): boolean => {
  if (props.maxFileSizeMB !== null) {
    const maxSizeBytes = props.maxFileSizeMB * 1024 * 1024
    if (file.size > maxSizeBytes) {
      return false
    }
  }

  if (props.allowedExtensions.length > 0) {
    const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase()
    if (!props.allowedExtensions.includes(fileExtension || '')) {
      return false
    }
  }

  return true
}

const getErrorMessage = (file: File): string => {
  if (props.maxFileSizeMB > 0) {
    const maxSizeBytes = props.maxFileSizeMB * 1024 * 1024
    if (file.size > maxSizeBytes) {
      return `El archivo excede el tamaño máximo de ${props.maxFileSizeMB} MB`
    }
  }

  if (props.allowedExtensions.length > 0) {
    const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase()
    if (!props.allowedExtensions.includes(fileExtension || '')) {
      return `Formato no válido. Use: ${props.allowedExtensions.join(', ')}`
    }
  }

  return 'Archivo no válido'
}

const addFiles = (fileList: FileList) => {
  const filesToAdd = Array.from(fileList)

  // Para CV solo permitimos un archivo, reemplazamos si ya hay uno
  if (files.value.length > 0) {
    files.value = [] // Limpiar archivo anterior
  }

  const file = filesToAdd[0] // Solo tomar el primer archivo

  if (!isValidFile(file)) {
    const errorFile: FileUpload = {
      id: generateFileId(),
      name: file.name,
      size: file.size,
      file: file,
      progress: 0,
      status: 'error',
      errorMessage: getErrorMessage(file)
    }
    files.value.push(errorFile)
    return
  }

  const fileUpload: FileUpload = {
    id: generateFileId(),
    name: file.name,
    size: file.size,
    file: file,
    progress: 0,
    status: 'pending'
  }
  files.value.push(fileUpload)
}

const removeFile = (fileId: string) => {
  files.value = files.value.filter(file => file.id !== fileId)
}

const onDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false

  if (event.dataTransfer?.files) {
    addFiles(event.dataTransfer.files)
  }
}

const openFileDialog = () => {
  fileInput.value?.click()
}

const onFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files) {
    addFiles(target.files)
    target.value = ''
  }
}

// Métodos para subir archivos
const startUpload = async () => {
  currentStep.value = 'uploading'

  const filesToUpload = files.value.filter(f => f.status === 'pending')

  const uploadSingleFile = async (file: FileUpload) => {
    const formData = new FormData();
    formData.append('file', file.file);
    formData.append('company_id', String(authenticationStore.company.id));
    formData.append('user_id', String(authenticationStore.user.id));
    formData.append('reconciliation_group_id', reconciliation_group_id.value);

    try {
      file.status = 'uploading';
      file.progress = 0;
      file.errorMessage = undefined;

      const xhr = new XMLHttpRequest();
      file.xhr = xhr;

      await new Promise((resolve, reject) => {
        xhr.upload.addEventListener('progress', (progressEvent) => {
          if (progressEvent.lengthComputable) {
            file.progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          }
        });

        xhr.addEventListener('load', () => {
          if (xhr.status >= 200 && xhr.status < 300) {
            resolve(xhr.response);
          } else {
            reject(new Error(xhr.statusText));
          }
        });

        xhr.addEventListener('error', () => {
          reject(new Error('Error en la conexión'));
        });

        xhr.addEventListener('abort', () => {
          reject(new Error('Subida cancelada'));
        });

        xhr.open('POST', `${api.defaults.baseURL}/rip/uploadFileZip`);
        xhr.setRequestHeader('Authorization', `Bearer ${accessToken}`);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(formData);
      });

      file.status = 'completed';
      file.progress = 100;

      const response = JSON.parse(xhr.response)
      if (response.status == "success") {
        globalLoading.startLoading(response.batch_id);
      }

    } catch (error: any) {
      if (error.message !== 'Subida cancelada') {
        file.status = 'error';
        file.errorMessage = error.response?.data?.message || 'Error al subir el archivo';
      }
    } finally {
      file.xhr = undefined;
    }
  };

  for (const file of filesToUpload) {
    await uploadSingleFile(file);
  }

  if (allFilesCompleted.value) {
    setTimeout(() => {
      successDialog.value = true
    }, 500)
  }
}

const hasUploadsInProgress = computed(() => {
  return files.value.some(file => file.status === 'uploading')
})

const isCancelling = ref(false)

const cancelUploads = async () => {
  isCancelling.value = true
  try {
    const uploadsToCancel = files.value.filter(f => f.status === 'uploading')
    await Promise.all(uploadsToCancel.map(file => {
      if (file.xhr) {
        file.xhr.abort()
      }
    }))

    files.value.forEach(file => {
      if (file.status !== 'completed') {
        file.status = 'error'
        file.errorMessage = 'Subida cancelada por el usuario'
        file.xhr = undefined
      }
    })

    handleDialogVisible()
  } finally {
    isCancelling.value = false
  }
}

const loadNewFiles = () => {
  resetState()
}

const closeSuccessDialog = () => {
  successDialog.value = false
}

const resetState = () => {
  files.value = []
  currentStep.value = 'upload'
  isDragOver.value = false
}

const acceptString = computed(() => {
  return props.allowedExtensions.length > 0
    ? props.allowedExtensions.join(',')
    : undefined
})

const refModalQuestionCancelUpload = ref()

const openModalQuestionCancelUpload = () => {
  const completedCount = files.value.filter(f => f.status === 'completed').length

  refModalQuestionCancelUpload.value.openModal()
  refModalQuestionCancelUpload.value.componentData.title =
    completedCount > 0
      ? `¿Cancelar subidas? ${completedCount} archivo ya fue guardado`
      : "¿Está seguro que desea cancelar la subida?"

  refModalQuestionCancelUpload.value.componentData.actions = [
    {
      text: 'Cancelar subida',
      color: 'error',
      action: () => cancelUploads()
    },
    {
      text: 'Continuar',
      color: 'primary',
      action: () => { }
    }
  ].filter(Boolean)
}

const isDialogVisible = ref<boolean>(false)

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
};

const reconciliation_group_id = ref<string>("")
const openModal = async (reconciliationGroupId: string) => {
  handleDialogVisible();
  resetState()

  reconciliation_group_id.value = reconciliationGroupId
};

defineExpose({
  openModal,
});
</script>

<template>
  <VDialog v-model="isDialogVisible" :overlay="false" max-width="600px" transition="dialog-transition" persistent
    class="upload-dialog">
    <DialogCloseBtn @click="openModalQuestionCancelUpload()" v-if="hasUploadsInProgress" />
    <DialogCloseBtn @click="handleDialogVisible()" v-else />
    <v-card class="upload-card" elevation="12">
      <div>
        <VToolbar color="primary">
          <VToolbarTitle>
            <v-icon class="mr-2">tabler-file-spreadsheet</v-icon>
            Carga de archivo ZIP
          </VToolbarTitle>
        </VToolbar>
      </div>

      <v-card-text>
        <!-- File Upload Section -->
        <div v-if="currentStep === 'upload'" class="upload-section">
          <div ref="dropzone" class="modern-dropzone" :class="{ 'dragover': isDragOver }" @drop="onDrop"
            @dragover.prevent="isDragOver = true" @dragleave.prevent="isDragOver = false" @dragenter.prevent>
            <div class="dropzone-content">
              <div class="icon-container">
                <v-icon size="64" color="primary">tabler-file-spreadsheet</v-icon>
              </div>

              <h3 class="text-h5 font-weight-medium mb-2">Arrastra tu archivo ZIP aquí</h3>
              <p class="text-body-1 text-medium-emphasis mb-4">o selecciona desde tu computadora</p>

              <v-btn color="primary" variant="elevated" size="large" class="upload-btn" @click="openFileDialog">
                <v-icon start>tabler-folder-open</v-icon>
                Seleccionar archivo
              </v-btn>
            </div>
          </div>

          <div class="file-requirements mt-6">
            <v-card variant="tonal" color="info" class="pa-4">
              <div class="d-flex align-center mb-3">
                <v-icon color="info" class="mr-2">tabler-info-circle</v-icon>
                <h4 class="text-subtitle-1 font-weight-medium">Requisitos del archivo</h4>
              </div>
              <div class="requirements-list">
                <div class="requirement-item">
                  <v-icon size="16" color="success" class="mr-2">tabler-check</v-icon>
                  <span class="text-body-2">{{ formatSizeInfo() }}</span>
                </div>
                <div class="requirement-item">
                  <v-icon size="16" color="success" class="mr-2">tabler-check</v-icon>
                  <span class="text-body-2">Formato: ZIP únicamente</span>
                </div>
                <div class="requirement-item">
                  <v-icon size="16" color="success" class="mr-2">tabler-check</v-icon>
                  <span class="text-body-2">Solo se permite un archivo</span>
                </div>
              </div>
            </v-card>
          </div>

          <!-- File Preview -->
          <div v-if="files.length > 0" class="file-list mt-4">
            <div v-for="file in files" :key="file.id" class="file-card">
              <div class="file-icon-section">
                <v-icon size="40" :color="file.status === 'error' ? 'error' : 'primary'">
                  tabler-file-spreadsheet
                </v-icon>
              </div>

              <div class="file-details">
                <h4 class="text-subtitle-1 font-weight-medium">{{ file.name }}</h4>
                <p class="text-body-2 text-medium-emphasis">{{ formatFileSize(file.size) }}</p>

                <v-alert v-if="file.errorMessage" type="error" density="compact" class="mt-2">
                  <v-icon start>tabler-alert-triangle</v-icon>
                  {{ file.errorMessage }}
                </v-alert>
              </div>

              <div class="file-actions">
                <v-btn icon="tabler-x" size="small" variant="text" color="error" @click="removeFile(file.id)" />
              </div>
            </div>
          </div>
        </div>

        <!-- Upload Progress Section -->
        <div v-if="currentStep === 'uploading'" class="upload-progress-section">
          <div class="progress-header text-center mb-6">
            <h3 class="text-h5 font-weight-medium">
              <v-icon start color="primary">tabler-upload</v-icon>
              Cargando archivo ZIP
            </h3>
          </div>

          <div v-for="file in files" :key="file.id" class="progress-card">
            <div class="progress-file-info">
              <div class="progress-file-icon">
                <v-icon size="32" color="primary">tabler-file-spreadsheet</v-icon>
              </div>

              <div class="progress-details">
                <h4 class="text-subtitle-1 font-weight-medium">{{ file.name }}</h4>
                <p class="text-body-2 text-medium-emphasis">{{ formatFileSize(file.size) }}</p>
              </div>
            </div>

            <!-- Enhanced Progress Bar -->
            <div class="progress-section">
              <div class="progress-info d-flex justify-space-between align-center mb-2">
                <span class="text-body-1 font-weight-medium">{{ getProgressMessage(file.progress) }}</span>
                <span class="text-h6 font-weight-bold" :class="`text-${getProgressColor(file.progress)}`">{{
                  file.progress }}%</span>
              </div>

              <v-progress-linear v-if="file.status === 'uploading'" :model-value="file.progress"
                :color="getProgressColor(file.progress)" height="12" rounded class="progress-bar mb-3" striped
                :stream="true" />

              <div v-if="file.status === 'completed'" class="status-completed">
                <v-icon color="success" size="24" class="mr-2">tabler-circle-check</v-icon>
                <span class="text-success font-weight-medium">¡Archivo cargado exitosamente!</span>
              </div>

              <div v-if="file.status === 'error'" class="status-error">
                <v-icon color="error" size="24" class="mr-2">tabler-alert-circle</v-icon>
                <span class="text-error font-weight-medium">{{ file.errorMessage || 'Error al cargar el archivo'
                  }}</span>
              </div>
            </div>
          </div>
        </div>
      </v-card-text>

      <VDivider />
      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <v-btn @click="openModalQuestionCancelUpload" v-if="hasUploadsInProgress" :disabled="!hasUploadsInProgress"
          :loading="isCancelling">
          Cancelar Subidas
        </v-btn>
        <v-btn @click="handleDialogVisible" v-else>
          Cancelar
        </v-btn>

        <v-btn v-if="currentStep === 'upload'" color="primary" :disabled="files.length === 0" @click="startUpload">
          Guardar
        </v-btn>

        <v-btn v-if="currentStep === 'uploading'" color="primary" :disabled="!allFilesCompleted" @click="loadNewFiles">
          Cargar nuevos archivos
        </v-btn>
      </VCardText>
    </v-card>

    <input ref="fileInput" type="file" :accept="acceptString" style="display: none" @change="onFileSelect" />
  </VDialog>

  <!-- Success Modal -->
  <VDialog v-model="successDialog" max-width="500px">
    <DialogCloseBtn @click="closeSuccessDialog()" />
    <VCard>
      <v-card-text class="text-h5 text-center">
        <v-icon color="success" size="5rem" class="mb-2">tabler-circle-check</v-icon>
        <h2>¡Subida exitosa!</h2>
        <span>El archivo ZIP se ha cargado correctamente.</span>
      </v-card-text>
      <VCardText class="d-flex justify-center">
        <v-btn @click="closeSuccessDialog">
          OK
        </v-btn>
      </VCardText>
    </VCard>
  </VDialog>

  <ModalQuestion ref="refModalQuestionCancelUpload" @success="cancelUploads" />
</template>

<style scoped>
.modern-dropzone {
  border: 3px dashed rgb(var(--v-theme-outline));
  border-radius: 16px;
  padding: 48px 24px;
  text-align: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: rgb(var(--v-theme-primary-container));
  position: relative;
  overflow: hidden;
  border-color: rgb(var(--v-theme-primary));
  transform: translateY(-2px);
  box-shadow: 0 8px 32px rgba(var(--v-theme-primary), 0.2);
}


.modern-dropzone.dragover {
  border-color: rgb(var(--v-theme-primary));
  background: rgb(var(--v-theme-primary-container));
  transform: scale(1.02);
  box-shadow: 0 8px 32px rgba(var(--v-theme-primary), 0.3);
}

.dropzone-content {
  position: relative;
  z-index: 1;
}

.icon-container {
  margin-bottom: 24px;
}

.upload-btn {
  text-transform: none !important;
  font-weight: 600 !important;
  border-radius: 12px !important;
}

.requirements-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.requirement-item {
  display: flex;
  align-items: center;
}

.file-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border: 1px solid rgb(var(--v-theme-outline));
  border-radius: 12px;
  background: rgb(var(--v-theme-surface));
  transition: all 0.3s ease;
}

.file-card:hover {
  box-shadow: 0 4px 16px rgba(var(--v-theme-shadow), 0.15);
  transform: translateY(-2px);
}

.file-icon-section {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 60px;
  background: rgb(var(--v-theme-primary-container));
  border-radius: 12px;
}

.file-details {
  flex: 1;
}

.progress-header {
  margin-bottom: 32px;
}

.progress-card {
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgb(var(--v-theme-outline));
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(var(--v-theme-shadow), 0.1);
}

.progress-file-info {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
}

.progress-file-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: rgb(var(--v-theme-primary-container));
  border-radius: 12px;
}

.progress-section {
  display: flex;
  flex-direction: column;
}

.progress-bar {
  border-radius: 6px !important;
}

.status-completed,
.status-error {
  display: flex;
  align-items: center;
  padding: 12px;
  border-radius: 8px;
}

.status-completed {
  background: rgb(var(--v-theme-success-container));
}

.status-error {
  background: rgb(var(--v-theme-error-container));
}

.upload-section {
  min-height: 400px;
}

.upload-progress-section {
  min-height: 300px;
}

@media (max-width: 600px) {
  .modern-dropzone {
    padding: 32px 16px;
  }

  .file-card {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
}
</style>
