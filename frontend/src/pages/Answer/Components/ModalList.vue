<script setup lang="ts">
import ListAnswers from "@/pages/Answer/Components/List.vue";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();

const emit = defineEmits(["execute"])

const titleModal = ref<string>("Listado de Respuestas")
const isDialogVisible = ref<boolean>(false)
const btnsView = ref<boolean>(false)
const disabledFiledsView = ref<boolean>(false)
const showList = ref<boolean>(false)

const isLoading = ref<boolean>(false)

const form = ref({
  id: null as string | null,
  glosa_value: null as string | null,
})

const handleClearForm = () => {
  for (const key in form.value) {
    form.value[key] = null;
  }
}

const handleDialogVisible = () => {
  isDialogVisible.value = !isDialogVisible.value;
  showList.value = !showList.value;
  handleClearForm()
};

const openModal = async ({ id, code_glosa_description, code_glosa_code, glosa_value, showBtnsView }: any, disabled: boolean = false) => {
  disabledFiledsView.value = disabled
  btnsView.value = showBtnsView

  handleDialogVisible();

  form.value.id = id;
  form.value.glosa_value = glosa_value;
  titleModal.value = `Listado de Respuestas: ${code_glosa_code} - ${code_glosa_description}`
};

defineExpose({
  openModal
})

</script>

<template>
  <div>
    <VDialog v-model="isDialogVisible" max-width="70rem" transition="dialog-transition" persistent>
      <DialogCloseBtn @click="handleDialogVisible" />
      <VCard :loading="isLoading" :disabled="isLoading" class="w-100">
        <!-- Toolbar -->

        <VToolbar color="primary" class="rounded-t">
          <VToolbarTitle class="font-weight-medium">
            {{ titleModal }}
          </VToolbarTitle>
        </VToolbar>

        <VCardText class="px-0">
          <ListAnswers v-if="showList" :glosa_id="form.id" :total_value="form.glosa_value" :showBtnsView="btnsView">
          </ListAnswers>
        </VCardText>
      </VCard>
    </VDialog>

  </div>
</template>
