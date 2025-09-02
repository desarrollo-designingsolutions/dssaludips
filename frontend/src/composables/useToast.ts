// composables/useToast.ts
import type { Position, ToastType } from 'mosha-vue-toastify'
import { createToast } from 'mosha-vue-toastify'
import 'mosha-vue-toastify/dist/style.css'

export function useToast() {
  const toast = (title: string, description: string, type: ToastType, position: Position = 'bottom-center') => {
    createToast({
      title,
      description, 
    }, {
      type, // 'info', 'danger', 'warning', 'success', 'default'
      timeout: 5000,
      showCloseButton: true,
      position,
      transition: 'slide',
      hideProgressBar: false,
      showIcon: true,
      swipeClose: true,
    })
  }

  const toastValidation = (validation: any) => {
    for (const key in validation.errors) {
      toast(
        "El campo " + validation.errors[key].id + " es obligatorio",
        "",
        "danger"
      )
    }
  }

  return {
    toast,
    toastValidation,
  }
}
