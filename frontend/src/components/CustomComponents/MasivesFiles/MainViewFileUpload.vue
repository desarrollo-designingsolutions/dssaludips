<script setup lang="ts">
import FileUploadModal from './FileUploadModal.vue'

const showUploadModal = ref(false)
const fileable_id = "01970ce2-c881-724e-b0cb-b5660696d787"
const fileable_type = "User"

const openUploadModal = () => {
  showUploadModal.value = true
}
</script>

<template>
  <div>
    <VBtn prepend-icon="tabler-upload" @click="openUploadModal" elevation="2">
      Start Upload
    </VBtn>

    <FileUploadModal v-model="showUploadModal" :maxFileSizeMB="30" :allowed-extensions="['.jpg', '.png', '.pdf']"
      :max-files="5" :fileable_id="fileable_id" :fileable_type="fileable_type" />
  </div>
</template>
