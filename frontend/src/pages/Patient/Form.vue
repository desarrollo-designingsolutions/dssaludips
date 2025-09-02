<script lang="ts" setup>
import { useToast } from '@/composables/useToast';
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from 'vuetify/components/VForm';

const authenticationStore = useAuthenticationStore();

definePage({
  path: "patient-Form/:action/:id?",
  name: "Patient-Form",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "menu.patient",
  },
});

const { toast } = useToast()
const errorsBack = ref<IErrorsBack>({});
const disabledFiledsView = ref<boolean>(false);
const route = useRoute()
const formValidation = ref<VForm>()
const loading = reactive({
  form: false,
})

const tipoIdPisis_arrayInfo = ref([])
const ripsTipoUsuarioVersion2s_arrayInfo = ref([])
const sexos_arrayInfo = ref([])
const municipios_arrayInfo = ref([])
const paises_arrayInfo = ref([])
const zonaVersion2s_arrayInfo = ref([])

const form = ref({
  id: null as string | null,
  tipo_id_pisi_id: null as string | null,
  document: null as string | null,
  rips_tipo_usuario_version2_id: null as string | null,
  birth_date: null as string | null,
  sexo_id: null as string | null,
  pais_residency_id: null as string | null,
  municipio_residency_id: null as string | null,
  zona_version2_id: null as string | null,
  incapacity: null as string | null,
  pais_origin_id: null as string | null,
  first_name: null as string | null,
  second_name: null as string | null,
  first_surname: null as string | null,
  second_surname: null as string | null,
})

const clearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}

const fetchDataForm = async () => {

  form.value.id = route.params.id || null

  const url = form.value.id ? `/patient/${form.value.id}/edit` : `/patient/create`

  loading.form = true
  const { response, data } = await useAxios(url).get();

  if (response.status == 200 && data) {
    tipoIdPisis_arrayInfo.value = data.tipoIdPisis_arrayInfo
    ripsTipoUsuarioVersion2s_arrayInfo.value = data.ripsTipoUsuarioVersion2s_arrayInfo
    sexos_arrayInfo.value = data.sexos_arrayInfo
    municipios_arrayInfo.value = data.municipios_arrayInfo
    paises_arrayInfo.value = data.paises_arrayInfo
    zonaVersion2s_arrayInfo.value = data.zonaVersion2s_arrayInfo

    //formulario 
    if (data.form) {
      form.value = cloneObject(data.form)
    }
  }
  loading.form = false
}

const submitForm = async () => {
  const validation = await formValidation.value?.validate()
  if (validation?.valid) {

    const url = form.value.id ? `/patient/update/${form.value.id}` : `/patient/store`

    form.value.company_id = authenticationStore.company.id;

    loading.form = true;
    const { data, response } = await useAxios(url).post(form.value);

    if (response.status == 200 && data) {

      if (data.code == 200) {
        router.push({ name: 'Patient-List' })
      }
    }
    if (data.code === 422) errorsBack.value = data.errors ?? {};

    loading.form = false;
  }
  else {
    toast('Faltan Campos Por Diligenciar', '', 'danger')
  }
}

if (route.params.action == 'view') disabledFiledsView.value = true

onMounted(async () => {
  clearForm()

  await fetchDataForm()
})

// Computed que verifica si al menos uno de los valores es true
const isLoading = computed(() => {
  return Object.values(loading).some(value => value);
});

const paramsSelect = computed(() => {
  return {
    company: authenticationStore.company.id,
  }
});

const nacionalities = {
  '170': {
    name: 'Colombia',
    code: 'CO',
  },
  '862': {
    name: 'VENEZUELA',
    code: 'VE',
  },
};

// Validar edad mínima según el tipo de documento
const birthDateRule = [
  (value: string) => requiredValidator(value) || 'La fecha de nacimiento es requerida',
  (value: string) => {
    if (!value || !form.value.tipo_id_pisi_id) return true;

    const tipoId = form.value.tipo_id_pisi_id?.codigo;

    if (!tipoId) return true;

    const birthDate = new Date(value);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--; // Ajuste para edades exactas (considera meses y días)
    }


    // Caso 1: Personas con edad >= 18 años y nacionalidad colombiana deben usar CC
    const paisOriginId = form.value?.pais_origin_id?.codigo as string | null;
    const isColombian = paisOriginId !== null && nacionalities[paisOriginId]?.code === 'CO';
    if (age >= 18 && isColombian && tipoId !== 'CC') {
      return 'Las personas mayores de 18 años con nacionalidad colombiana deben usar la Cédula de Ciudadanía (CC)';
    }

    // Reglas específicas de edad mínima:

    // Validación para TI: Entre 7 y 17 años
    if (tipoId === 'TI' && age < 7) {
      return 'La edad mínima para la tarjeta de identidad (TI) es 7 años';
    }
    if (tipoId === 'TI' && age > 17) {
      return 'La tarjeta de identidad (TI) es para personas menores de 18 años';
    }

    // Validación para RC y CN: Máximo 6 años
    if (['RC', 'CN'].includes(tipoId) && age > 6) {
      return 'El registro civil (RC) o certificado de nacido vivo (CN) es para menores de 7 años';
    }

    // Validación opcional para CN: Máximo 3 años (si tu sistema lo requiere)
    if (tipoId === 'CN' && age > 3) {
      return 'El certificado de nacido vivo (CN) es para menores de hasta 3 años';
    }

    return true;
  },
];


const allowedForeignTransientDocs = ['CE', 'CD', 'PA', 'SC'];

const paisOrigenRule = [
  (value: string) => requiredValidator(value) || 'El país de origen es requerido',
  (value: string) => {
    if (value && form.value.birth_date && form.value.tipo_id_pisi_id && form.value.pais_residency_id) {
      const birthDate = new Date(form.value.birth_date);
      const today = new Date();
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      const tipoId = form.value.tipo_id_pisi_id?.codigo;


      // Caso 1: Personas con edad >= 18 años y nacionalidad colombiana deben usar CC
      if (age >= 18 && value.extra_II === 'CO' && tipoId !== 'CC') {
        return 'Las personas mayores de 18 años con nacionalidad colombiana deben usar la Cédula de Ciudadanía (CC)';
      }

      // Determinar si es extranjero (país de origen no es Colombia)
      const isForeigner = nacionalities[value.codigo]?.code !== 'CO';

      // Determinar si está de paso (reside en Colombia, pero no es colombiano)
      const isTransient = nacionalities[form.value.pais_residency_id.codigo]?.code === 'CO';

      // Caso 2: Extranjeros de paso deben usar CE, CD, PA o SC
      if (isForeigner && isTransient && !allowedForeignTransientDocs.includes(tipoId)) {
        return 'Los extranjeros de paso deben identificarse con Cédula de Extranjería (CE), Carnet Diplomático (CD), Pasaporte (PA) o Salvoconducto (SC)';
      }

      // Caso 3: Migrantes venezolanos deben usar PE
      const isVenezuelan = nacionalities[value.codigo]?.code === 'VE';

      if (isVenezuelan && tipoId !== 'PE') {
        return 'Los migrantes venezolanos deben identificarse con el Permiso Especial de Permanencia (PE)';
      }
    }
    return true;
  },
];


const paisResidenciaRule = [
  (value: string) => requiredValidator(value) || 'El país de residencia es requerido',
  (value: string) => {
    if (!value || !form.value.pais_origin_id || !form.value.tipo_id_pisi_id) return true;

    const tipoId = form.value.tipo_id_pisi_id?.codigo;
    if (!tipoId) return true;


    // Determinar si es extranjero (país de origen no es Colombia)
    const isForeigner = nacionalities[form.value.pais_origin_id.codigo]?.code !== 'CO';

    // Determinar si está de paso (reside en Colombia, pero no es colombiano)
    const isTransient = nacionalities[value.codigo]?.code === 'CO';

    // Caso 2: Extranjeros de paso deben usar CE, CD, PA o SC
    if (isForeigner && isTransient && !allowedForeignTransientDocs.includes(tipoId)) {
      return 'Los extranjeros de paso deben identificarse con Cédula de Extranjería (CE), Carnet Diplomático (CD), Pasaporte (PA) o Salvoconducto (SC)';
    }

    return true;
  },
];

const tipoIdRule = [
  (value: string) => requiredValidator(value) || 'El tipo de documento es requerido',
  (value: string) => {
    if (!value || !form.value.pais_origin_id || !form.value.pais_residency_id) return true;

    const tipoId = value?.codigo;
    if (!tipoId) return true;

    // Determinar si es extranjero y está de paso
    const isForeigner = nacionalities[form.value.pais_origin_id.codigo]?.code !== 'CO';
    const isTransient = nacionalities[form.value.pais_residency_id.codigo]?.code === 'CO';

    // Caso 2: Extranjeros de paso deben usar CE, CD, PA o SC
    if (isForeigner && isTransient && !allowedForeignTransientDocs.includes(tipoId)) {
      return 'Los extranjeros de paso deben identificarse con Cédula de Extranjería (CE), Carnet Diplomático (CD), Pasaporte (PA) o Salvoconducto (SC)';
    }

    return true;
  },
];

const dynamicDocumentLengthRule = computed(() => (value: string) => {
  const tipoId = form.value.tipo_id_pisi_id?.codigo;

  if (!tipoId || !value) return true;
  const maxLength = documentLengthByType[tipoId] || 20;

  return (
    value.length <= maxLength ||
    `El documento ${tipoId} debe tener máximo ${maxLength} caracteres`
  );
});

const documentRules = [
  (value: string) => requiredValidator(value) || 'El documento es requerido',
  (value: string) => lengthBetweenValidator(value, 4, 20) || 'El documento debe tener entre 4 y 20 caracteres',
  dynamicDocumentLengthRule.value, // Agregar la regla dinámica
];

</script>


<template>
  <div>
    <VCard :disabled="loading.form" :loading="loading.form">
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Formulario Pacientes
        </span>
      </VCardTitle>

      <VCardText>
        <VForm ref="formValidation" @submit.prevent="() => { }" :disabled="disabledFiledsView">
          <VRow>
            <VCol cols="12" sm="4">
              <AppSelectRemote label="Tipo de Documento" v-model="form.tipo_id_pisi_id" url="/selectInfiniteTipoIdPisis"
                arrayInfo="tipoIdPisis" :requiredField="true" :rules="tipoIdRule" clearable :params="paramsSelect"
                :itemsData="tipoIdPisis_arrayInfo" :firstFetch="false">
              </AppSelectRemote>
            </VCol>

            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="documentRules" clearable v-model="form.document"
                label="Documento" :error-messages="errorsBack.document" counter maxlength="20" minlength="4" />
            </VCol>

            <VCol cols="12" sm="4">
              <AppSelectRemote label="Tipo de Usuario" v-model="form.rips_tipo_usuario_version2_id"
                url="/selectInfiniteTipoUsuario" arrayInfo="ripsTipoUsuarioVersion2s" :requiredField="true"
                :rules="[requiredValidator]" clearable :params="paramsSelect"
                :itemsData="ripsTipoUsuarioVersion2s_arrayInfo" :firstFetch="false">
              </AppSelectRemote>
            </VCol>

            <VCol cols="12" sm="4">
              <AppDateTimePicker clearable :requiredField="true" label="Fecha de Nacimiento" v-model="form.birth_date"
                :error-messages="errorsBack.birth_date" :rules="birthDateRule" :config="{ dateFormat: 'Y-m-d' }" />
            </VCol>

            <VCol cols="12" sm="4">
              <AppSelectRemote label="Sexo" v-model="form.sexo_id" url="/selectInfiniteSexo" arrayInfo="sexos"
                :requiredField="true" :rules="[requiredValidator]" clearable :params="paramsSelect"
                :itemsData="sexos_arrayInfo" :firstFetch="false">
              </AppSelectRemote>
            </VCol>

            <VCol cols="12" sm="4">
              <AppSelectRemote label="Pais de Residencia" v-model="form.pais_residency_id" url="/selectInfinitePais"
                arrayInfo="paises" :requiredField="true" :rules="paisResidenciaRule" clearable :params="paramsSelect"
                :itemsData="paises_arrayInfo" :firstFetch="false">
              </AppSelectRemote>
            </VCol>

            <VCol cols="12" sm="4">
              <AppSelectRemote label="Municipio de Residencia" v-model="form.municipio_residency_id"
                url="/selectInfiniteMunicipio" arrayInfo="municipios" clearable :params="paramsSelect"
                :itemsData="municipios_arrayInfo" :firstFetch="false">
              </AppSelectRemote>
            </VCol>

            <VCol cols="12" sm="4">
              <AppSelectRemote label="Zona Territorial de Residencia" v-model="form.zona_version2_id"
                url="/selectInfiniteZonaVersion2" arrayInfo="zonaVersion2s" clearable :params="paramsSelect"
                :itemsData="zonaVersion2s_arrayInfo" :firstFetch="false">
              </AppSelectRemote>
            </VCol>

            <VCol cols="12" sm="4">
              <AppSelect v-model="form.incapacity" label="Incapacidad" :items="['SÍ', 'NO']" />
            </VCol>

            <VCol cols="12" sm="4">
              <AppSelectRemote label="Pais de Origen" v-model="form.pais_origin_id" url="/selectInfinitePais"
                arrayInfo="paises" :requiredField="true" :rules="paisOrigenRule" clearable :params="paramsSelect"
                :itemsData="paises_arrayInfo" :firstFetch="false">
              </AppSelectRemote>
            </VCol>

            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]" clearable v-model="form.first_name"
                label="Primer Nombre" :error-messages="errorsBack.first_name" />
            </VCol>

            <VCol cols="12" sm="4">
              <AppTextField clearable v-model="form.second_name" label="Segundo Nombre"
                :error-messages="errorsBack.second_name" />
            </VCol>

            <VCol cols="12" sm="4">
              <AppTextField :requiredField="true" :rules="[requiredValidator]" clearable v-model="form.first_surname"
                label="Primer Apellido" :error-messages="errorsBack.first_surname" />
            </VCol>

            <VCol cols="12" sm="4">
              <AppTextField clearable v-model="form.second_surname" label="Segundo Apellido"
                :error-messages="errorsBack.second_surname" />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap mt-5">
        <BtnBack :disabled="isLoading" :loading="isLoading" />
        <VBtn v-if="!disabledFiledsView" :disabled="isLoading" :loading="isLoading" @click="submitForm()"
          color="primary">
          Guardar
        </VBtn>
      </VCardText>
    </VCard>

    <ModalQuestion ref="refModalQuestion" />
  </div>
</template>
