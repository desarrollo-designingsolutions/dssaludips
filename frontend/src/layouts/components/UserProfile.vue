<script setup lang="ts">

import { router } from "@/plugins/1.router";
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

const authenticationStore = useAuthenticationStore();
const { company, user } = storeToRefs(authenticationStore);

const logout = () => {
  authenticationStore.logout()
  // router.push({ name: "Login" });

  const route = { name: "Login" };
  const url = router.resolve(route).href;
  window.location.href = url;
};


const avatarData = computed(() => {
  if (user.value) {
    return String(user.value?.name?.split(" ")[0]) + ' ' + String(user.value?.surname?.split(" ")[0]) || "Administrador"
  }
  return ""
})


/* Limpia los datos del storage */
const changeCompany = () => {
  company.value = {};
  authenticationStore.getMenuData;
  router.push({ name: "Company-List" });

};


//ModalChangePassword 
const refModalChangePassword = ref()

onMounted(() => {
  if (authenticationStore.user.first_time) {

    refModalChangePassword.value.openDialog(authenticationStore.user.id, authenticationStore.user.first_time)

  }
})


const passwordSaved = () => {
  authenticationStore.user.first_time = false
}
</script>

<template>
  <VBadge dot location="bottom right" offset-x="3" offset-y="3" bordered color="success">
    <VAvatar class="cursor-pointer" color="primary" variant="tonal">

      <VImg v-if="user.photo" :src="storageBack(user.photo)" />
      <span v-else> {{ avatarText(avatarData) }} </span>

      <!-- SECTION Menu -->
      <VMenu activator="parent" width="230" location="bottom end" offset="14px">
        <VList>
          <!-- 👉 User Avatar & Name -->
          <VListItem>
            <template #prepend>
              <VListItemAction start>
                <VBadge dot location="bottom right" offset-x="3" offset-y="3" color="success">

                  <VAvatar v-if="user.photo">
                    <VImg :src="storageBack(user.photo)" />
                  </VAvatar>
                  <VAvatar v-else color="primary" variant="tonal">
                    {{ avatarText(avatarData) }}
                  </VAvatar>

                </VBadge>
              </VListItemAction>
            </template>

            <VListItemTitle class="font-weight-semibold">
              {{ user?.full_name }}
            </VListItemTitle>
            <VListItemSubtitle>
              {{ user?.rol_name }}
            </VListItemSubtitle>
          </VListItem>



          <VListItem v-if="company.id && !user.company_id" @click="changeCompany">
            <template #prepend>
              <VIcon class="me-2" icon="tabler-building" size="22" />
            </template>

            <VListItemTitle>Cambiar Empresa</VListItemTitle>
          </VListItem>

          <VListItem :to="{ name: 'User-Profile' }">
            <template #prepend>
              <VIcon class="me-2" icon="tabler-user-circle" size="22" />
            </template>

            <VListItemTitle>Perfil</VListItemTitle>
          </VListItem>



          <!-- Divider -->
          <VDivider class="my-2" />

          <!-- 👉 Logout -->
          <VListItem @click="logout()">
            <template #prepend>
              <VIcon class="me-2" icon="tabler-logout" size="22" />
            </template>

            <VListItemTitle>Cerrar Sesión</VListItemTitle>
          </VListItem>

        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>

  </VBadge>

  <ModalChangePassword ref="refModalChangePassword" @execute="passwordSaved" />

</template>
