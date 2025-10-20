<script lang="ts" setup>
import type { Notification } from '@layouts/types';

interface Props {
  notifications: Notification[]
  badgeProps?: object
  location?: any
  load?: any
  loading: boolean
  unreadCount?: any
  nextPageUrl?: any
  enableInfiniteScroll?: boolean
}
interface Emit {
  (e: 'read', value: number[]): void
  (e: 'unread', value: number[]): void
  (e: 'remove', value: number): void
  (e: 'click:notification', value: Notification): void
  (e: 'load-data-init'): void
  (e: 'load-more'): void

}

const props = withDefaults(defineProps<Props>(), {
  // location: 'bottom end',
  location: 'bottom start',
  badgeProps: undefined,
})

const emit = defineEmits<Emit>()

const isAllMarkRead = computed(() => {
  return props.notifications.some(item => item.isSeen === false)
})

const markAllReadOrUnread = () => {

  if (!isAllMarkRead.value) {
    const allNotificationsIds = props.notifications.filter(item => item.isSeen === true).map(item => item.id)
    emit('unread', allNotificationsIds)
  }
  else {
    const allNotificationsIds = props.notifications.filter(item => item.isSeen === false).map(item => item.id)
    emit('read', allNotificationsIds)
  }
}

const totalUnseenNotifications = computed(() => {
  return props.unreadCount
  // return props.notifications.filter(item => item.isSeen === false).length
})

const toggleReadUnread = (isSeen: boolean, Id: number) => {
  if (isSeen)
    emit('unread', [Id])
  else
    emit('read', [Id])
}

</script>

<template>
  <IconBtn id="notification-btn">
    <VBadge dot v-bind="props.badgeProps" :content="totalUnseenNotifications" color="error" offset-x="2" offset-y="3">
      <VIcon icon="tabler-bell" />
    </VBadge>

    <VMenu activator="parent" width="380px" :location="props.location" offset="12px" :close-on-content-click="false">
      <VCard class="d-flex flex-column">
        <!-- 👉 Header -->
        <VCardItem class="notification-section">
          <VCardTitle class="text-h6">
            Notificationes
          </VCardTitle>

          <template #append>
            <VChip size="small" color="primary" class="me-2">
              {{ totalUnseenNotifications }} Nueva(s)
            </VChip>
            <IconBtn v-show="props.notifications.length" size="34" @click="markAllReadOrUnread">
              <VIcon size="20" color="high-emphasis" :icon="!isAllMarkRead ? 'tabler-mail' : 'tabler-mail-opened'" />

              <VTooltip activator="parent" location="start">
                {{ !isAllMarkRead ? 'Marcar todo como no leído' : 'Marcar todo como leído' }}
              </VTooltip>
            </IconBtn>
          </template>
        </VCardItem>

        <VDivider />

        <!-- 👉 Notifications list -->
        <VList class="notification-list rounded-0 py-0">
          <v-infinite-scroll :height="400" :items="props.notifications">
            <template v-for="(notification, index) in props.notifications" :key="notification">
              <VDivider v-if="index > 0" />
              <VListItem link lines="one" min-height="66px" class="list-item-hover-class"
                @click="$emit('click:notification', notification)">
                <!-- Slot: Prepend -->
                <!-- Handles Avatar: Image, Icon, Text -->
                <div class="d-flex align-start gap-3">
                  <VAvatar :color="notification.color && !notification.img ? notification.color : undefined"
                    :variant="notification.img ? undefined : 'tonal'">
                    <span v-if="notification.text">{{ avatarText(notification.text) }}</span>
                    <VImg v-if="notification.img" :src="storageBack(notification.img)" />
                    <VIcon v-if="notification.icon" :icon="notification.icon" />
                  </VAvatar>

                  <div>
                    <p class="text-sm font-weight-medium mb-1">
                      {{ notification.title }}
                    </p>
                    <p class="text-body-2 mb-2" style=" letter-spacing: 0.4px !important; line-height: 18px;">
                      {{ notification.subtitle }}
                    </p>
                    <p class="text-sm text-disabled mb-0" style=" letter-spacing: 0.4px !important; line-height: 18px;">
                      {{ notification.time }}
                    </p>
                  </div>
                  <VSpacer />

                  <div class="d-flex flex-column align-end">
                    <VIcon size="10" icon="tabler-circle-filled" :color="!notification.isSeen ? 'primary' : '#a8aaae'"
                      :class="`${notification.isSeen ? 'visible-in-hover' : ''}`" class="mb-2"
                      @click.stop="toggleReadUnread(notification.isSeen, notification.id)" />

                    <VIcon v-if="notification.isSeen" size="20" icon="tabler-x" class="visible-in-hover"
                      @click="$emit('remove', notification.id)" />
                  </div>
                </div>
              </VListItem>
            </template>

            <template v-slot:loading>
              <VProgressCircular v-if="props.loading" indeterminate color="primary" />
            </template>
            <!-- <template v-if="!nextPageUrl">
              <v-alert type="warning" color="primary" variant="tonal">
                ¡No hay mas notificaciones!
              </v-alert>

            </template> -->
          </v-infinite-scroll>
          <VListItem v-show="!props.notifications.length" class="text-center text-medium-emphasis"
            style="block-size: 56px;">
            <VListItemTitle>¡No se encontró ninguna notificación!</VListItemTitle>
          </VListItem>
        </VList>


        <VDivider />

        <!-- 👉 Footer -->
        <VCardText class="pa-4" v-if="props.nextPageUrl">
          <VBtn block size="small" @click="$emit('load-more')">
            <span> Cargar más notificaciones</span>

          </VBtn>
        </VCardText>
      </VCard>
    </VMenu>
  </IconBtn>
</template>

<style lang="scss">
.notification-section {
  padding-block: 0.75rem;
  padding-inline: 1rem;
}

.list-item-hover-class {
  .visible-in-hover {
    display: none;
  }

  &:hover {
    .visible-in-hover {
      display: block;
    }
  }
}

.notification-list.v-list {
  .v-list-item {
    border-radius: 0 !important;
    margin: 0 !important;
    padding-block: 0.75rem !important;
  }
}

// Badge Style Override for Notification Badge
.notification-badge {
  .v-badge__badge {
    /* stylelint-disable-next-line liberty/use-logical-spec */
    min-width: 18px;
    padding: 0;
    block-size: 18px;
  }
}
</style>
