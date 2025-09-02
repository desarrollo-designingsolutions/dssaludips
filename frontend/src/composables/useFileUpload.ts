import { ref, watch } from "vue";

export function useFileUpload() {
  const imageFile = ref<File | null>(null);
  const imageUrl = ref<string | null | ArrayBuffer>(null);
  const key = ref<number>(1);
  const name = ref<string | null>(null);
  const allowedExtensions = ref<Array<string>>([]);
  const allowedSizeFile = ref<number>(30720); // 30 MB in KB
  const loading = ref<boolean>(false);

  function handleImageSelected(event: Event, swal = false) {

    //--------------------------------------------------------------------------------------------
    // Validacion tamaño del archivo
    //--------------------------------------------------------------------------------------------
    var siezekiloByte = parseInt(event.target.files[0].size / 1024);
    if (siezekiloByte > allowedSizeFile.value) {
      clearData();
      return {
        icon: 'error',
        title: 'Oops...',
        text: 'Debe registrar máximo 30MB',
        success: false
      };
    }

    //--------------------------------------------------------------------------------------------
    // Validacion del tipo de archivo
    //--------------------------------------------------------------------------------------------
    const extensionArchivo = event.target.files[0].name
      .split(".")
      .pop()
      ?.toLowerCase();
    if (allowedExtensions.value.length > 0 && !allowedExtensions.value.includes(extensionArchivo)) {
      clearData();
      return {
        icon: 'error',
        title: 'Error tipo de extensión',
        text: `Solo se admite archivos con extensiónes: ${allowedExtensions.value.join(
          ", ",
        )}`,
        success: false
      };
    }

    if (event.target.files.length === 0) {
      clearData();
      return {
        success: false
      };
    }

    imageFile.value = event.target.files[0];

    return {
      success: true
    };
  }

  watch(imageFile, (imageFile) => {
    if (!(imageFile instanceof File)) return;

    const fileReader = new FileReader();

    fileReader.readAsDataURL(imageFile);

    loading.value = true
    fileReader.addEventListener("load", () => {
      imageUrl.value = fileReader.result;
      loading.value = false

    });
  });

  function clearData() {
    imageFile.value = null;
    imageUrl.value = null;
    name.value = null;
    key.value++;
    loading.value = false;
  }

  return {
    imageFile,
    imageUrl,
    handleImageSelected,
    key,
    allowedExtensions,
    clearData,
    name,
    allowedSizeFile,
    loading,
  };
}
