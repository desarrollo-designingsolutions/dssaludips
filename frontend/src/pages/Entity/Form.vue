<script lang="ts" setup>
import { useToast } from '@/composables/useToast';
import IErrorsBack from "@/interfaces/Axios/IErrorsBack";
import { router } from '@/plugins/1.router';
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { VForm } from 'vuetify/components/VForm';

const authenticationStore = useAuthenticationStore();

definePage({
  path: "entity-Form/:action/:id?",
  name: "Entity-Form",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "menu.entity",
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

const form = ref({
  id: null as string | null,
  corporate_name: null as string | null,
  nit: null as string | null,
  address: null as string | null,
  phone: null as string | null,
  email: null as string | null,
  type_entity_id: null as string | null,
  insuranceCode: null as string | null,
})

const typeEntities = ref<Array<object>>([])

const currentTab = ref(0)

const tabs = ref([
  {
    title: "Información general",
    show: true,
    errorsValidations: false,
  },
  {
    title: "Documentos",
    show: false,
    errorsValidations: false,
  },
])

const clearForm = () => {
  for (const key in form.value) {
    form.value[key] = null
  }
}

const fetchDataForm = async () => {

  form.value.id = route.params.id || null

  const url = form.value.id ? `/entity/${form.value.id}/edit` : `/entity/create`

  loading.form = true
  const { response, data } = await useAxios(url).get();

  if (response.status == 200 && data) {
    typeEntities.value = data.typeEntities
    //formulario 
    if (data.form) {
      form.value = cloneObject(data.form)

      tabs.value[1].show = true;
    }
  }
  loading.form = false
}

const submitForm = async () => {
  const validation = await formValidation.value?.validate()
  if (validation?.valid) {

    const url = form.value.id ? `/entity/update/${form.value.id}` : `/entity/store`

    form.value.company_id = authenticationStore.company.id;

    loading.form = true;
    const { data, response } = await useAxios(url).post(form.value);

    if (response.status == 200 && data) {

      if (data.code == 200) {
        form.value.id = data.form.id
        tabs.value[1].show = true;
        router.replace({ name: "Entity-Form", params: { action: "edit", id: data.form.id } });
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

const nitRules = [
  // value => (!value || /^[0-9]{9}-[0-9]{1}$/.test(value)) || 'El NIT debe tener el formato 000000000-0',
  value => requiredValidator(value),
];
const phoneRules = [
  value => requiredValidator(value),
  value => integerValidator(value),
  value => (!value || value.length <= 10) || "El número no debe tener mas de 10 caracteres",
  value => positiveNumberValidator(value),
];
</script>


<template>
  <div>
    <VCard :disabled="loading.form" :loading="loading.form">
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Formulario Entidades
        </span>
      </VCardTitle>

      <VCardText>
        <VTabs v-model="currentTab">
          <VTab class="text-none" v-for="(item, index) in tabs" :key="index" v-show="item.show">
            <VIcon start :icon="!item.errorsValidations ? '' : 'tabler-alert-circle-filled'"
              :color="!item.errorsValidations ? '' : 'error'" />
            {{ item.title }}
          </VTab>
        </VTabs>
      </VCardText>

      <VCardText>
        <div v-show="currentTab == 0">
          <VForm ref="formValidation" @submit.prevent="() => { }" :disabled="disabledFiledsView">
            <VRow>
              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" :rules="[requiredValidator]" v-model="form.corporate_name"
                  label="Razón Social" :error-messages="errorsBack.corporate_name"
                  @input="errorsBack.corporate_name = ''" clearable />
              </VCol>

              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" :rules="nitRules" v-model="form.nit" label="Nit"
                  :error-messages="errorsBack.nit" @input="errorsBack.nit = ''" clearable />
              </VCol>

              <VCol cols="12" sm="4">
                <AppAutocomplete :requiredField="true" clearable :items="typeEntities" v-model="form.type_entity_id"
                  label="Tipo" :error-messages="errorsBack.type_entity_id" @input="errorsBack.type_entity_id = ''"
                  :rules="[requiredValidator]">
                </AppAutocomplete>
              </VCol>

              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" clearable v-model="form.address" label="Dirección"
                  :error-messages="errorsBack.address" />
              </VCol>

              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" :rules="phoneRules" v-model="form.phone" label="Teléfono"
                  :error-messages="errorsBack.phone" @input="errorsBack.phone = ''" />
              </VCol>

              <VCol cols="12" sm="4">
                <AppTextField :requiredField="true" clearable :rules="[emailValidator]" v-model="form.email"
                  label="Correo de contacto" :error-messages="errorsBack.email" @input="errorsBack.email = ''" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField clearable v-model="form.insuranceCode" label="Código de la aseguradora"
                  :error-messages="errorsBack.insuranceCode" @input="errorsBack.insuranceCode = ''" />
              </VCol>

            </VRow>
          </VForm>
        </div>

        <div v-show="currentTab == 1">
          <Files v-if="form.id" model="Entity" :id="form.id" :disabled="disabledFiledsView"></Files>
        </div>
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
