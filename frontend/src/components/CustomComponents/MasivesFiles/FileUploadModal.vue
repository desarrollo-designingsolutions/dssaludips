<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import axios from 'axios';
import { computed, ref } from 'vue';
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
  modelValue: boolean
  maxFileSizeMB?: number // Ahora en MB
  allowedExtensions?: string[]
  fileable_id: string
  fileable_type: string
  maxFiles: number
}

const props = withDefaults(defineProps<FileUploadProps>(), {
  maxFileSizeMB: 0, // Cambiado de Infinity a 0 para mejor manejo
  allowedExtensions: () => [],
  maxFiles: 100
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

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

// Dialog state
const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const successDialog = ref(false)
const currentStep = ref<'upload' | 'uploading'>('upload')

// File management
const files = ref<FileUpload[]>([])
const isDragOver = ref(false)
const fileInput = ref<HTMLInputElement>()

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
    ? `Límite de tamaño por archivo: ${props.maxFileSizeMB} MB`
    : 'Tamaño de archivo ilimitado'
}

const isValidFile = (file: File): boolean => {
  // Validar tamaño si hay límite
  if (props.maxFileSizeMB !== null) {
    const maxSizeBytes = props.maxFileSizeMB * 1024 * 1024
    if (file.size > maxSizeBytes) {
      return false
    }
  }

  // Validar extensiones solo si se especificaron
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
      return `Extensión no permitida. Use: ${props.allowedExtensions.join(', ')}`
    }
  }

  return 'Archivo no válido'
}

const addFiles = (fileList: FileList) => {
  const filesToAdd = Array.from(fileList)
  let filesAdded = 0
  let filesSkipped = 0

  filesToAdd.forEach(file => {
    // Verificar si ya alcanzamos el límite
    if (files.value.length >= props.maxFiles) {
      filesSkipped++
      return
    }

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
      filesAdded++
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
    filesAdded++
  })

  // Mostrar mensaje si se omitieron archivos por límite
  if (filesSkipped > 0) {
    alert(`Se cargaron ${filesAdded} archivos de ${filesToAdd.length}. 
           Límite máximo: ${props.maxFiles} archivos.`)
  }
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

  // Filtrar solo archivos pendientes
  const filesToUpload = files.value.filter(f => f.status === 'pending')

  // Crear una función para subir un solo archivo
  const uploadSingleFile = async (file: FileUpload) => {
    const formData = new FormData();
    formData.append('files[]', file.file);
    formData.append('company_id', String(authenticationStore.company.id));
    formData.append('user_id', String(authenticationStore.user.id));
    formData.append('fileable_id', props.fileable_id);
    formData.append('fileable_type', props.fileable_type);

    try {
      file.status = 'uploading';
      file.progress = 0;
      file.errorMessage = undefined;

      // Crear una nueva instancia XMLHttpRequest
      const xhr = new XMLHttpRequest();
      file.xhr = xhr; // Guardar referencia para posible cancelación

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

        xhr.open('POST', `${api.defaults.baseURL}/file/massUpload`);

        // Configurar headers
        xhr.setRequestHeader('Authorization', `Bearer ${accessToken}`);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.send(formData);
      });

      file.status = 'completed';
      file.progress = 100;
    } catch (error: any) {
      // No marcar como error si fue cancelación intencional
      if (error.message !== 'Subida cancelada') {
        file.status = 'error';
        file.errorMessage = error.response?.data?.message || 'Error al subir el archivo';
      }
    } finally {
      file.xhr = undefined; // Limpiar referencia
    }
  };

  // Subir archivos en batches de 2, pero cada uno independientemente
  for (let i = 0; i < filesToUpload.length; i += 2) {
    const batch = filesToUpload.slice(i, i + 2)

    // Usamos Promise.allSettled para manejar ambos archivos en paralelo
    // pero mantener su progreso independiente
    await Promise.allSettled(batch.map(file => uploadSingleFile(file)))
  }

  // Mostrar éxito si todos los archivos se subieron correctamente
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
    // 1. Cancelar archivos en progreso (uploading)
    const uploadsToCancel = files.value.filter(f => f.status === 'uploading')
    await Promise.all(uploadsToCancel.map(file => {
      if (file.xhr) {
        file.xhr.abort()
      }
    }))

    // 2. Marcar TODOS los archivos no completados como cancelados
    files.value.forEach(file => {
      if (file.status !== 'completed') {
        file.status = 'error'
        file.errorMessage = 'Subida cancelada por el usuario'
        file.xhr = undefined
      }
    })

    // 3. Opcional: Eliminar archivos ya subidos del servidor
    // await deleteUploadedFiles()

    // 4. Cerrar el diálogo
    closeDialog()
  } finally {
    isCancelling.value = false
  }
}

// Opcional: Función para limpiar archivos subidos
const deleteUploadedFiles = async () => {
  const completedFiles = files.value.filter(f => f.status === 'completed')
  if (completedFiles.length === 0) return

  try {
    await api.delete('/file/batchDelete', {
      data: {
        file_ids: completedFiles.map(f => f.id),
        company_id: authenticationStore.company.id
      }
    })
  } catch (error) {
    console.error('Error al eliminar archivos:', error)
  }
}

const finishUpload = () => {
  closeDialog()
}
const loadNewFiles = () => {
  resetState()
}

const closeDialog = () => {
  dialog.value = false
  resetState()
}

const closeSuccessDialog = () => {
  successDialog.value = false
  // closeDialog()
}

const resetState = () => {
  files.value = []
  currentStep.value = 'upload'
  isDragOver.value = false
}

const acceptString = computed(() => {
  // Si no hay extensiones especificadas, retornar undefined para permitir cualquier archivo
  return props.allowedExtensions.length > 0
    ? props.allowedExtensions.join(',')
    : undefined
})

// Inicialización
onMounted(() => {

})

//ModalQuestion
const refModalQuestionCancelUpload = ref()

const openModalQuestionCancelUpload = () => {
  const completedCount = files.value.filter(f => f.status === 'completed').length

  refModalQuestionCancelUpload.value.openModal()
  refModalQuestionCancelUpload.value.componentData.title =
    completedCount > 0
      ? `¿Cancelar subidas? ${completedCount} archivos ya fueron guardados`
      : "¿Está seguro que desea cancelar todas las subidas?"

  refModalQuestionCancelUpload.value.componentData.actions = [
    {
      text: 'Cancelar solo pendientes',
      color: 'primary',
      action: () => cancelUploads(false)
    },
    completedCount > 0 && {
      text: 'Cancelar y eliminar todo',
      color: 'error',
      action: () => cancelUploads(true)
    },
    {
      text: 'Continuar subiendo',
      color: 'secondary',
      action: () => { }
    }
  ].filter(Boolean)
}

</script>

<template>
  <VDialog v-model="dialog" persistent max-width="800px">
    <DialogCloseBtn @click="closeDialog()" />
    <v-card>
      <div>
        <VToolbar color="primary">
          <VToolbarTitle>Carga de archivos masivos</VToolbarTitle>
        </VToolbar>
      </div>

      <v-card-text>
        <!-- File Upload Section -->
        <div v-if="currentStep === 'upload'" class="upload-section">
          <div ref="dropzone" class="dropzone" :class="{ 'dragover': isDragOver }" @drop="onDrop"
            @dragover.prevent="isDragOver = true" @dragleave.prevent="isDragOver = false" @dragenter.prevent>
            <div class="dropzone-content">
              <v-icon size="48" color="grey-lighten-1">tabler-cloud-upload</v-icon>
              <h3 class="text-h6 mt-4 mb-2">Arrastre y suelte para cargar archivos</h3>
              <p class="text-body-2 mb-4">o</p>
              <v-btn color="primary" @click="openFileDialog">
                Navegar en carpeta
              </v-btn>
            </div>
          </div>

          <div class="file-info mt-4">
            <p class="text-body-2 text-grey-darken-1">
              {{ formatSizeInfo() }}
            </p>
            <p class="text-body-2 text-grey-darken-1">
              {{ props.allowedExtensions.length > 0
                ? `Extensiones permitidas: ${props.allowedExtensions.join(', ')}`
                : 'Se permiten archivos de cualquier tipo' }}
            </p>
            <p class="text-body-2 text-grey-darken-1">
              Máximo {{ props.maxFiles }} archivo{{ props.maxFiles > 1 ? 's' : '' }} permitido{{ props.maxFiles > 1 ?
                's' : '' }}
            </p>
          </div>

          <!-- File List -->
          <div v-if="files.length > 0" class="file-list mt-4">
            <div class="file-grid">
              <div v-for="file in files" :key="file.id" class="file-item">
                <div class="file-info-item">
                  <span class="file-name">{{ file.name }}</span>
                  <span class="file-size">{{ formatFileSize(file.size) }}</span>
                  <v-alert v-if="file.errorMessage" type="error" density="compact" class="mt-2">
                    {{ file.errorMessage }}
                  </v-alert>
                </div>
                <v-btn icon="tabler-x" size="small" variant="text" @click="removeFile(file.id)" />
              </div>
            </div>
          </div>
        </div>

        <!-- Upload Progress Section -->
        <div v-if="currentStep === 'uploading'" class="upload-progress-section">
          <div class="file-grid">
            <div v-for="file in files" :key="file.id" class="file-progress-item">
              <div class="file-info-item">
                <span class="file-name">{{ file.name }}</span>
                <span class="file-size">{{ formatFileSize(file.size) }}</span>
              </div>
              <div class="file-status">
                <v-progress-linear v-if="file.status === 'uploading'" :model-value="file.progress" color="primary"
                  height="6" rounded />
                <v-icon v-if="file.status === 'completed'" color="success" size="24">
                  tabler-circle-check
                </v-icon>
                <v-icon v-if="file.status === 'error'" color="error" size="24">
                  tabler-alert-circle
                </v-icon>
              </div>
            </div>
          </div>
        </div>

      </v-card-text>

      <VDivider />
      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <v-btn @click="openModalQuestionCancelUpload" :disabled="!hasUploadsInProgress" :loading="isCancelling">
          {{ hasUploadsInProgress ? 'Cancelar Subidas' : 'Cancelar' }}
        </v-btn>

        <v-btn v-if="currentStep === 'upload'" color="primary" :disabled="files.length === 0" @click="startUpload">
          Guardar
        </v-btn>

        <v-btn v-if="currentStep === 'uploading'" color="primary" :disabled="!allFilesCompleted" @click="loadNewFiles">
          Cargar nuevos archivos
        </v-btn>

      </VCardText>
    </v-card>

    <input ref="fileInput" type="file" multiple :accept="acceptString" style="display: none" @change="onFileSelect" />
  </VDialog>

  <!-- Success Modal -->
  <VDialog v-model="successDialog" max-width="500px">
    <DialogCloseBtn @click="closeSuccessDialog()" />
    <VCard>
      <v-card-text class="text-h5 text-center">
        <v-icon color="success" size="5rem" class="mb-2">tabler-circle-check</v-icon>
        <h2>¡Subida exitosa!</h2>
        <span>{{ files.length }} archivo{{ files.length > 1 ? 's' : '' }} se han cargado correctamente.</span>
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
.dropzone {
  border: 2px dashed #ccc;
  border-radius: 8px;
  padding: 60px 20px;
  text-align: center;
  transition: all 0.3s ease;
  background-color: #fafafa;
}

.dropzone.dragover {
  border-color: #1976d2;
  background-color: #e3f2fd;
}

.dropzone-content {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.file-list {
  max-height: 300px;
  overflow-y: auto;
}

.file-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.file-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  background-color: #f5f5f5;
}

.file-progress-item {
  display: flex;
  flex-direction: column;
  padding: 16px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  background-color: #fff;
}

.file-info-item {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.file-name {
  font-weight: 500;
  font-size: 14px;
  color: #424242;
}

.file-size {
  font-size: 12px;
  color: #757575;
  margin-top: 2px;
}

.file-status {
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.upload-section {
  min-height: 400px;
}

.upload-progress-section {
  min-height: 300px;
}

.database-section {
  border-top: 1px solid #e0e0e0;
  padding-top: 24px;
}

@media (max-width: 600px) {
  .file-grid {
    grid-template-columns: 1fr;
  }
}
</style>
