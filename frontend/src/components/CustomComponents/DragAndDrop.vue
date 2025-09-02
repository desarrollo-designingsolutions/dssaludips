<script setup lang="ts">
import { useDropZone, useFileDialog, useObjectUrl } from '@vueuse/core';
import { ref } from 'vue';

import type { VForm } from 'vuetify/components/VForm';
const formValidation = ref<VForm>()

const dropZoneRef = ref<HTMLDivElement>()
interface FileData {
  file: File
  url: string
  progress?: number
  status?: 'pending' | 'uploading' | 'completed' | 'failed' // Agregamos el estado del archivo
}

const fileData = ref<FileData[]>([])
const { open, onChange } = useFileDialog({})

function onDrop(DroppedFiles: File[] | null) {
  DroppedFiles?.forEach(file => {

    if (file.type.slice(0, 6) !== 'image/') {
      // eslint-disable-next-line no-alert
      alert('Only image files are allowed')

      return
    }

    fileData.value.push({
      file,
      url: useObjectUrl(file).value ?? '',
      progress: undefined,
      status: 'pending'
    })
  },
  )
}

onChange(selectedFiles => {
  if (!selectedFiles)
    return

  for (const file of selectedFiles) {
    fileData.value.push({
      file,
      url: useObjectUrl(file).value ?? '',
      progress: undefined,
      status: 'pending'
    })
  }
})

useDropZone(dropZoneRef, onDrop)

const savefiles = async () => {
  const validation = await formValidation.value?.validate()
  if (validation?.valid) {


    if (fileData.value.length === 0) {
      alert('No hay archivos para guardar');
      return;
    }

    isUploading.value = true  // Deshabilitar el botón de carga

    for (let i = 0; i < fileData.value.length; i++) {
      const fileItem = fileData.value[i]
      fileItem.status = 'uploading' // Cambiamos el estado a 'uploading' al comenzar la carga
      try {
        const formData = new FormData()
        formData.append('file', fileItem.file)
        formData.append('field', field.value)

        const { data, response } = await useApi('/savefiles').post(formData)
        if (response.value?.ok && data.value) {
          fileItem.status = 'completed' // Cambiamos el estado a 'completed' si la carga fue exitosa
          fileItem.progress = 100 // Progreso al 100%
        }
      } catch (error) {
        fileItem.status = 'failed' // Cambiamos el estado a 'failed' si hubo un error
        fileItem.progress = undefined // Restablecemos el progreso
        console.error('Error al guardar el archivo', fileItem.file.name, error)
        alert(`Error al guardar el archivo ${fileItem.file.name}`)
      }
    }

    isUploading.value = false  // Rehabilitar el botón después de que todos los archivos se hayan procesado
  }
}

const isUploading = ref(false)  // Control de si estamos subiendo archivos
const field = ref()

const fields = [
  {
    title: "Foto",
    value: "photo",
  },
  {
    title: "Boletín",
    value: "boletin",
  },
]
</script>

<template>

  <div class="flex">
    <div class="w-full h-auto relative">
      <div ref="dropZoneRef" class="cursor-pointer" @click="() => open()">
        <div v-if="fileData.length === 0"
          class="d-flex flex-column justify-center align-center gap-y-3 px-6 py-10 border-dashed drop-zone">
          <IconBtn variant="tonal" class="rounded-sm">
            <VIcon icon="tabler-upload" />
          </IconBtn>
          <div class="text-base text-high-emphasis font-weight-medium">
            Arrastra y suelta tu archivo aquí.
          </div>
          <span class="text-disabled">o</span>
          <VBtn variant="tonal">Explorar archivos</VBtn>
        </div>

        <div v-else class="d-flex justify-center align-center gap-3 pa-8 border-dashed drop-zone flex-wrap">
          <VRow class="match-height w-100">
            <template v-for="(item, index) in fileData" :key="index">
              <VCol cols="12" sm="6">
                <VCard :ripple="false" border class="d-flex flex-column">
                  <VCardText @click.stop>
                    <VImg :src="item.url" />
                    <div class="mt-2">
                      <span class="clamp-text text-wrap">
                        {{ item.file.name }}
                      </span>
                      <span>
                        {{ item.file.size / 1000 }} KB
                      </span>
                    </div>

                    <div v-if="item.status === 'uploading'" class="mt-2">
                      <VProgressCircular :size="24" :value="item.progress" color="primary" />
                      <span>Cargando...</span>
                    </div>
                    <div v-if="item.status === 'completed'" class="mt-2 text-success">
                      <span>¡Archivo cargado exitosamente!</span>
                    </div>
                    <div v-if="item.status === 'failed'" class="mt-2 text-danger">
                      <span>Error al cargar el archivo</span>
                    </div>

                  </VCardText>
                  <VSpacer />
                  <VCardActions>
                    <VBtn variant="outlined" block @click.stop="fileData.splice(index, 1)">
                      Eliminar archivo
                    </VBtn>
                  </VCardActions>
                </VCard>
              </VCol>
            </template>
          </VRow>
        </div>
      </div>
    </div>
  </div>
</template>
