import type { HorizontalNavItems } from '@/@layouts/types'

import { useAuthenticationStore } from '@/stores/useAuthenticationStore'

const { getMenuData } = storeToRefs(useAuthenticationStore())

export default getMenuData as HorizontalNavItems
