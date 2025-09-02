<script lang="ts" setup>
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";
import type { Notification } from '@layouts/types';

const authenticationStore = useAuthenticationStore();
const emit = defineEmits(["reload-task"])
const { toast } = useToast()



const enableInfiniteScroll = ref(false);

const notifications = ref<Notification[]>([])
const nextPageUrl = ref(null); // URL for next page 
const isLoading = ref<boolean>(false)
const initapi = ref<boolean>(false)
const unreadCount = ref<number>(0);

const getNotifications = async (url = '/notification/getNotifications/' + authenticationStore.user.id) => {

  if (url) {
    isLoading.value = true;
    // Si 'url' es proporcionado, es la URL para la siguiente página
    const { response, data } = await useApi(url).get();

    isLoading.value = false;

    if (response.value?.ok && data.value) {
      // Concatenar las nuevas notificaciones al array actual
      notifications.value.push(...data.value.notifications);
      nextPageUrl.value = data.value.nextPageUrl;
      unreadCount.value = data.value.unreadCount;

    }
  }
};

// Función que se llama cuando el usuario hace scroll
async function load({ done }) {

  if (enableInfiniteScroll.value) {
    try {
      isLoading.value = true;
      await getNotifications(nextPageUrl.value);
      done('ok');
    } catch (error) {
      console.error('Error loading notifications:', error);
      done('error');
    } finally {
      isLoading.value = false;
    }
  }

}

getNotifications()

const removeNotification = (notificationId: number) => {
  notifications.value.forEach((item, index) => {
    if (notificationId === item.id)
      notifications.value.splice(index, 1)
  })

  const { response, data } = useApi(`/notifications/remove`).post({
    notification_ids: [notificationId]
  });

}

const markRead = async (notificationId: number[]) => {
  notifications.value.forEach(item => {
    notificationId.forEach(id => {
      if (id === item.id) {
        item.isSeen = true
        unreadCount.value = unreadCount.value - 1
      }

    })
  })


  const { response, data } = await useApi(`/notification/markMultipleAsRead`).post({
    notification_ids: notificationId
  });
}

const markUnRead = async (notificationId: number[]) => {
  notifications.value.forEach(item => {
    notificationId.forEach(id => {
      if (id === item.id) {
        item.isSeen = false
        unreadCount.value = unreadCount.value + 1
      }

    })
  })

  const { response, data } = await useApi(`/notification/markMultipleAsUnread`).post({
    notification_ids: notificationId
  });
}

const handleNotificationClick = (notification: Notification) => {
  if (!notification.isSeen) {
    markRead([notification.id])
  }

  // const targetUrl = notification.action_url;
  // const currentUrl = router.currentRoute.value.fullPath;

  // Si la ruta y el parámetro cambian, forzar el cambio
  // if (currentUrl !== targetUrl) {
  //   router.push(targetUrl).then(() => {

  //     // Puedes manejar otras cosas después de la navegación si es necesario
  //     emit('reload-task', notification.id);
  //   });
  // }
}




window.Echo.channel('user.' + authenticationStore.user.id)
  .listen('.update-notification', (event) => {

    unreadCount.value = event.activeNotificationsCount

    notifications.value.forEach((item, index) => {
      // Buscar el elemento correspondiente en event.notifications
      const notification = event.notifications.find(e => e.id === item.id);

      if (notification) {
        // Si notification tiene is_removed en true, eliminar item
        if (notification.is_removed) {
          notifications.value.splice(index, 1);  // Elimina el item en el índice actual
        } else {
          // Si no está marcado como eliminado, actualizar los valores
          item.isSeen = notification.isSeen;
          item.time = notification.time;
        }
      }
    });
  })
  .listen('.bell-notification', (event) => {
    notifications.value.unshift(event)

    unreadCount.value = event.activeNotificationsCount

    toast("Tienes una nueva notificación", event.title, "info", "bottom-right")
  });

// Modificar la función handleLoadMore
const handleLoadMore = async () => {
  // if (!nextPageUrl.value || isLoading.value) return;

  enableInfiniteScroll.value = true; // Primero activa el scroll
  // isLoading.value = true;
  await getNotifications(nextPageUrl.value);
  // isLoading.value = false;
};
</script>

<template>
  <div>

    <NotificationsList :notifications="notifications" :unreadCount="unreadCount" @remove="removeNotification"
      @read="markRead" @unread="markUnRead" @click:notification="handleNotificationClick" :loading="isLoading"
      :getNotifications="getNotifications" :enable-infinite-scroll="enableInfiniteScroll" :nextPageUrl="nextPageUrl"
      @load-more="handleLoadMore" @load-data-init="getNotifications" />
    <!-- <Notifications :notifications="notifications" :unreadCount="unreadCount" @load="load" @remove="removeNotification"
      @read="markRead" @unread="markUnRead" @click:notification="handleNotificationClick" :loading="isLoading"
      :getNotifications="getNotifications" :enable-infinite-scroll="enableInfiniteScroll" @load-more="handleLoadMore"
      @load-data-init="getNotifications" /> -->
  </div>
</template>
