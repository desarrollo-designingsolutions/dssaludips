<script setup lang="ts">
import ModalForm from '@/pages/Patient/components/ModalForm.vue';

import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
const authenticationStore = useAuthenticationStore();

const emit = defineEmits(["execute", "update:modelValue"])

const elementId = computed(() => {
  const attrs = useAttrs();
  const _elementIdToken = attrs.id || attrs.label;
  return _elementIdToken
    ? `app-selectInifinite-field-${_elementIdToken}-${Math.random()
      .toString(36)
      .slice(2, 7)}`
    : undefined;
});

const isLoading = ref<boolean>(false)
const valueAppSelectRemote = ref()

onMounted(() => {
  valueAppSelectRemote.value = useAttrs().modelValue
  emit("update:modelValue", useAttrs().modelValue);
})

const selectKey = ref(0);

const getDataSaved = (data: any) => {
  if (isObject(data)) {
    const title = `${data.document} - ${data.name}`
    valueAppSelectRemote.value = {
      value: data.id,
      title: title,
    }
    emit("update:modelValue", valueAppSelectRemote.value);
    selectKey.value += 1;
  }
}

const params = {
  company_id: authenticationStore.company.id,
}

//refModalForm
const refModalForm = ref();

const openModalFormCreate = async () => {
  refModalForm.value.openModal();
};

const openModalFormEdit = async (id = null) => {
  refModalForm.value.openModal(id);
};

const openModalFormView = async (id = null) => {
  refModalForm.value.openModal(id, true);
};

</script>

<template>
  <div>
    <AppSelectRemote :key="selectKey" closable-chips class="mt-1" url="/selectInfinitePatients" :loading="isLoading"
      returnObject v-model="valueAppSelectRemote" @update:modelValue="emit('update:modelValue', $event)"
      arrayInfo="patients" clearable v-bind="{
        ...$attrs,
        id: elementId,
      }" :params="params">
      <template #append>
        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VBtn icon color="success" @click="openModalFormCreate()">
            <VIcon icon="tabler-plus" />
          </VBtn>
          <VBtn v-if="valueAppSelectRemote" icon color="warning" @click="openModalFormEdit(valueAppSelectRemote.value)">
            <VIcon icon="tabler-edit" />
          </VBtn>
          <VBtn v-if="valueAppSelectRemote" icon color="info" @click="openModalFormView(valueAppSelectRemote.value)">
            <VIcon icon="tabler-eye" />
          </VBtn>
        </div>
      </template>

    </AppSelectRemote>

    <ModalForm ref="refModalForm" @closeModal="getDataSaved" />

  </div>
</template>
