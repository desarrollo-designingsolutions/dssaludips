<script setup lang="ts">

import defaultProfileImg from '@images/profile.png';
import user_profile_header_bg from '@images/user_profile_header_bg.png';

definePage({
  name: "User-Profile",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
  },
});

import IImageSelected from '@/interfaces/FileUpload/IImageSelected';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();
const { user } = storeToRefs(authenticationStore);

const isLoading = ref<boolean>(false)

//ModalChangePassword 
const refModalChangePassword = ref()

const openModalChangePassword = () => {
  refModalChangePassword.value.openDialog(user.value.id)
}

const profileImg = ref<File | string>(defaultProfileImg)

const fileType = ref(useFileUpload())
fileType.value.allowedExtensions = ["jpg", "jpeg", "png"];
watch(fileType.value, (newVal, oldVal) => {
  if (newVal.imageFile) profileImg.value = newVal.imageFile
})

const fileInput = ref()

const openModalChangePhotoProfile = () => {
  fileInput.value.click()
}


// Función para manejar el cambio de archivo
const onFileChange = (event: any) => {
  const file = event.target.files[0]; // Obtener el primer archivo seleccionado


  if (file) {
    // Crear un objeto FileReader para leer el archivo
    const reader = new FileReader();

    reader.onloadend = async () => {
      // Actualizar la imagen de perfil con la imagen seleccionada
      profileImg.value = reader.result; // Establecer el resultado de la imagen como la nueva imagen de perfil

      const formData = new FormData();
      formData.append("user_id", user.value.id);
      formData.append("photo", file);

      isLoading.value = true;
      const { response, data } = await useApi(`/user/changePhoto`).post(formData);
      isLoading.value = false;

      if (response.value?.ok && data.value) {
        user.value.photo = data.value.photo
      }

    };

    // Leer el archivo como una URL de datos (base64)
    reader.readAsDataURL(file);
  }
};

onMounted(async () => {
  profileImg.value = user.value.photo ? storageBack(user.value.photo) : defaultProfileImg
})




const changeFile = (event: Event) => {
  // Definir un tipo para la respuesta
  const response: IImageSelected = fileType.value.handleImageSelected(event);

  if (response.success == false && response.icon) {
    openModalQuestion(response)
  } else {
    onFileChange(event)
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
    <VCard v-if="user">
      <VImg :src="user_profile_header_bg" min-height="125" max-height="250" cover />

      <VCardText class="d-flex align-bottom flex-sm-row flex-column justify-center gap-x-5">
        <div class="d-flex h-0" @click="openModalChangePhotoProfile">
          <VAvatar rounded size="120" :image="user.photo ? storageBack(user.photo) : defaultProfileImg"
            class="user-profile-avatar mx-auto" />
        </div>

        <!-- Input file oculto -->
        <input ref="fileInput" :loading="fileType.loading" :key="fileType.key" type="file" accept="image/*"
          style="display: none;" @change="changeFile($event)" />

        <div class="user-profile-info w-100 mt-16 pt-6 pt-sm-0 mt-sm-0">
          <h5 class="text-h5 text-center text-sm-start font-weight-medium mb-3">
            {{ user?.full_name }}
          </h5>


          <div class="d-flex align-center justify-center justify-sm-space-between flex-wrap gap-4">
            <div class="d-flex flex-wrap justify-center justify-sm-start flex-grow-1 gap-4">
              <span class="d-flex">
                <VIcon size="20" icon="tabler-color-swatch" class="me-1" />
                <span class="text-body-1">
                  {{ user?.rol_name }}
                </span>
              </span>
            </div>

            <VBtn prepend-icon="tabler-lock" @click="openModalChangePassword">
              Cambiar Constraseña
            </VBtn>
          </div>
        </div>
      </VCardText>
    </VCard>

    <ModalChangePassword ref="refModalChangePassword" />
    <ModalQuestion ref="refModalQuestion" />

  </div>
</template>

<style lang="scss">
.user-profile-avatar {
  border: 5px solid rgb(var(--v-theme-surface));
  background-color: rgb(var(--v-theme-surface)) !important;
  inset-block-start: -3rem;

  .v-img__img {
    border-radius: 0.125rem;
  }
}
</style>
