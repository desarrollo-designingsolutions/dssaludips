<script setup lang="ts">
import type { VForm } from "vuetify/components/VForm";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import IImageSelected from "@/interfaces/FileUpload/IImageSelected";

const authenticationStore = useAuthenticationStore();

const emits = defineEmits(["saveData"]);

const { toast } = useToast();

const { model_type, model_id } = defineProps({
  model_type: {
    type: String,
    required: true,
  },
  model_id: {
    type: String,
    required: true,
  },
})

const componentData = reactive({
  isDialogVisible: false,
  isLoading: false,
  form: {
    id: null as string | null,
    company_id: null as string | null,
    filename: null as string | null,
    fileable_id: null as string | null,
    fileable_type: null as string | null,
    file: null as null | File,
  }
});

const handleIsDialogVisible = () => {
  componentData.isDialogVisible = !componentData.isDialogVisible;
};

const handleClearForm = (): void => {
  componentData.form.id = null
  componentData.form.filename = null
  componentData.form.file = null
};

const setFormCreate = async () => {
  handleClearForm();
  componentData.isLoading = true;
  const { response, data } = await useAxios(`file/create`).get();
  componentData.isLoading = false;

  if (response.status == 200 && data && data.code === 200) {
    componentData.form.fileable_id = model_id;
    componentData.form.fileable_type = model_type;
    handleIsDialogVisible();
  }
};

const setFormEdit = async (id: string) => {
  handleClearForm();
  componentData.isLoading = true;
  const { response, data } = await useAxios(`file/${id}/edit`).get();
  componentData.isLoading = false;

  if (response.status == 200 && data && data.code === 200) {
    componentData.form = data.form;
    componentData.form.fileable_id = model_id;
    componentData.form.fileable_type = model_type;

    handleIsDialogVisible();
  }
};

const handleSubmit = async () => {
  const validation = await refForm.value?.validate();
  if (validation?.valid) {

    componentData.form.company_id = authenticationStore.company.id;
    componentData.form.user_id = authenticationStore.user.id;

    if (inputFile.value.imageFile) componentData.form.file = inputFile.value.imageFile

    const formData = new FormData();

    for (const key in componentData.form) {
      if (componentData.form.hasOwnProperty(key)) {
        formData.append(key, componentData.form[key]);
      }
    }

    let responseApi = {} as any

    if (!componentData.form.id) {
      componentData.isLoading = true;
      responseApi = await useAxios(`/file/store`).post(formData);
      componentData.isLoading = false;
    } else {
      componentData.isLoading = true;
      responseApi = await useAxios(`/file/update/${componentData.form.id}`).post(formData);
      componentData.isLoading = false;
    }

    if (responseApi.response.status == 200 && responseApi.data) {
      handleIsDialogVisible();
      emits("saveData")
    }

  } else {
    toast("Faltan campos por diligenciar", "", "danger");
  }
};

//File 
const inputFile = ref(useFileUpload())
inputFile.value.allowedExtensions = ["jpg", "jpeg", "png", "doc", "docx", "xls", "xlsx", "pdf"];
watch(inputFile.value, (newVal, oldVal) => {
  if (newVal.imageFile) componentData.form.file = newVal.imageFile
})

defineExpose({
  setFormCreate,
  setFormEdit,
  handleIsDialogVisible,
  componentData
});

const refForm = ref<VForm>();

const changeFile = (event: Event) => {
  // Definir un tipo para la respuesta
  const response: IImageSelected = inputFile.value.handleImageSelected(event);

  if (response.success == false && response.icon) {
    openModalQuestion(response)
  }
}

//ModalQuestion
const refModalQuestion = ref()

const openModalQuestion = (response: IImageSelected) => {
  refModalQuestion.value.componentData.isDialogVisible = true
  refModalQuestion.value.componentData.dialogMaxWidth = '20rem'
  refModalQuestion.value.componentData.showBtnCancel = false
  refModalQuestion.value.componentData.btnSuccessText = 'Ok'
  refModalQuestion.value.componentData.icon = response.icon
  refModalQuestion.value.componentData.title = response.title
  refModalQuestion.value.componentData.subTitle = response.text
}

</script>
<template>
  <div>
    <VDialog v-model="componentData.isDialogVisible" max-width="40rem" persistent>
      <DialogCloseBtn @click="handleIsDialogVisible()" />
      <VCard :title="`${componentData.form.id ? 'Editar' : 'Subir'} archivo`" :loading="componentData.isLoading">
        <VCardText>
          <VForm @submit.prevent="" ref="refForm">
            <VRow>
              <VCol cols="12">
                <AppFileInput :requiredField="componentData.form.id ? false : true" clearable
                  :label2="componentData.form.id ? '1 archivo agregado' : ''" :loading="inputFile.loading"
                  label="Seleccione un archivo" @change="changeFile($event)"
                  :rules="[componentData.form.id ? true : requiredValidator]"></AppFileInput>

              </VCol>
              <VCol cols="12">
                <AppTextField :requiredField="true" label="Nombre del archivo" v-model="componentData.form.filename"
                  clearable :rules="[requiredValidator]" />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VCardText class="d-flex justify-end">
          <VBtn variant="flat" color="secondary" class="mr-3" @click="handleIsDialogVisible()"
            :loading="componentData.isLoading" :disabled="componentData.isLoading">
            <VIcon start icon="tabler-x" />
            Cancelar
          </VBtn>
          <VBtn variant="flat" @click="handleSubmit()" :loading="componentData.isLoading"
            :disabled="componentData.isLoading">
            <VIcon start icon="tabler-device-floppy" />
            Guardar cambios
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>
