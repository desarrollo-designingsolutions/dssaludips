<script setup lang="ts">
import ModalFileForm from "@/components/CustomComponents/File/ModalFileForm.vue";
import ModalQuestion from "@/components/CustomComponents/ModalQuestion.vue";

const { toast } = useToast();
const { model, id, disabled, maxFiles } = defineProps({
  model: {
    type: String,
    required: true,
  },
  id: {
    type: String,
    required: true,
  },
  disabled: {
    type: Boolean,
    required: false,
  },
  maxFiles: {
    type: Number,
    required: false,
    default: 9999,
  },
})

const componentData = reactive({
  isLoading: false,
  files: [] as Array<{
    pathname: string
    filename: string
    type_file: string
    created_at: string
  }>,
});

const getFiles = async () => {
  componentData.isLoading = true;
  const { data, response } = await useAxios(`/file/list`).get({
    params: {
      fileable_type: model,
      fileable_id: id
    }
  }
  );
  componentData.isLoading = false;

  if (response.status == 200 && data && data.code === 200) {
    componentData.files = data.tableData;
  }
};

const viewFile = (pathname: any) => {
  window.open(
    `${import.meta.env.VITE_API_BASE_BACK}/storage/${pathname}`,
    "_blank"
  );
};

const deleteData = async (id: any) => {
  const { data, response } = await useAxios(`/file/delete/${id}`).delete();
  await getFiles();
}


//ModalFileForm
const refModalFileForm = ref()


//ModalQuestion
const refModalQuestion = ref()

onMounted(() => {
  getFiles();
});
</script>

<template>
  <div>
    <VCard :loading="componentData.isLoading">
      <VCardTitle v-if="!disabled">
        <VBtn v-if="componentData.files.length < maxFiles" :disabled="disabled"
          @click="refModalFileForm?.setFormCreate()">
          Cargar archivo
        </VBtn>
      </VCardTitle>

      <VCardText>

        <VSkeletonLoader type="image, list-item-two-line" :loading="componentData.isLoading" class="mt-5">
          <p v-show="componentData.files.length < 1">
            No se han subido archivos
          </p>

          <VRow>
            <VCol cols="12" md="4" v-for="(file, index) in componentData.files" :key="index">
              <VCard>
                <VCardText>
                  <VRow>
                    <VCol cols="12" md="6">
                      <h4>
                        <a href="#" @click="viewFile(file.pathname)">
                          <!-- <a href="#" @click="downloadFileV2(file.pathname, file.filename)"> -->
                          <u>
                            {{ file.filename }}
                          </u>
                        </a>
                      </h4>
                      <h5>
                        {{ file.type_file }}
                      </h5>
                    </VCol>
                  </VRow>
                </VCardText>
                <VCardText>
                  <VRow cols="12" md="6">
                    <VCol> <b>Fecha de creación:</b> {{ file.created_at }} </VCol>
                    <VCol> <b>Usuario:</b> {{ file.user_name }} </VCol>
                    <VCol class="d-flex justify-end align-center" cols="12" md="6">
                      <VBtn :disabled="disabled" size="small" @click="refModalFileForm?.setFormEdit(file.id)">
                        <VIcon icon="tabler-pencil" />
                      </VBtn>
                      <VBtn :disabled="disabled" size="small" color="error" class="ml-3"
                        @click="refModalQuestion?.openModal(file.id)">
                        <VIcon icon="tabler-trash" />
                      </VBtn>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VSkeletonLoader>
      </VCardText>
    </VCard>


    <ModalFileForm ref="refModalFileForm" @saveData="getFiles" :model_type="model" :model_id="id" />
    <ModalQuestion ref="refModalQuestion" @success="deleteData" />

  </div>
</template>
