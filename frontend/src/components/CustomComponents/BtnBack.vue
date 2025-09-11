<script setup lang="ts">
const router = useRouter()

const goBack = () => {
  router.back()
}

//ModalQuestion
const refModalQuestion = ref()

const openModalQuestion = ()=>{
  refModalQuestion.value.openModal()
  refModalQuestion.value.componentData.subTitle ="Tenga en cuenta que se perderan los cambios realizados"
}
 
</script>
<template>
  <div>
    <VBtn v-bind="{ ...$attrs }" variant="outlined" @click="openModalQuestion" color="primary">
      Volver
    </VBtn>
  
    <ModalQuestion ref="refModalQuestion" @success="goBack" />
  </div> 
</template>
