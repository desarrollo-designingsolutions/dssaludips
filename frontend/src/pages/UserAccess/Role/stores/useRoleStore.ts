import { defineStore } from "pinia";

export const useRoleStore = defineStore("useRoleStore", {
  state: () => ({
    arrayFather: ref<Array<object>>([]),
    selectedFather: ref<Array<number | string>>([]),
    selectedElements: ref<Array<number | string>>([]),
    keyIdComparation: ref<string>("id"),
    keyArray: ref<string>("permissions"),
  }),
  getters: {},
  actions: {
    clearPermissionsSelected() {
      this.selectedElements = [1]
    },

    handleCardCheckboxChange(cardId: number | string) {
      if (this.selectedFather.includes(cardId)) {
        this.selectAllPermissions(cardId);
      } else {
        this.deselectAllPermissions(cardId)
      }
    },

    selectAllPermissions(cardId: number | string) {

      const dataIndex = this.arrayFather.findIndex((ele) => ele[this.keyIdComparation] == cardId);
      if (dataIndex !== -1) {
        const data = this.arrayFather[dataIndex];
        data[this.keyArray].forEach((element: { id: number | string }) => {
          if (!this.selectedElements.includes(element[this.keyIdComparation])) {
            this.selectedElements.push(element.id);
          }
        });
      } else {
        // Si no se encuentra en arrayFather, buscar recursivamente en los children
        this.arrayFather.forEach(parent => {
          this.searchInChildrenToAdd(parent, cardId);
        });
      }
    },

    // Función recursiva para buscar en los children de un elemento y agregarlo
    searchInChildrenToAdd(parent: any, cardId: number | string) {
      parent["children"].forEach(child => {
        if (child[this.keyIdComparation] === cardId && !this.selectedElements.includes(child.id)) {
          this.selectedElements.push(child.id);
        }
        // Llamar recursivamente para buscar en los hijos de este child si existen
        if (child["children"] && child["children"].length > 0) {
          this.searchInChildrenToAdd(child, cardId);
        }
      });
    },

    deselectAllPermissions(cardId: number | string) {
      const findAndDeselect = (data: any): boolean => {
        // Buscar en el array actual
        const dataIndex = data.findIndex(ele => ele[this.keyIdComparation] == cardId);
        if (dataIndex !== -1) {
          const elementsToRemove = data[dataIndex][this.keyArray].map(ele => ele.id);
          this.selectedElements = this.selectedElements.filter(element => !elementsToRemove.includes(element));
          return true; // Elemento encontrado y eliminado
        }

        // Buscar en los children de cada elemento
        for (const item of data) {
          if (item.children && item.children.length > 0) {
            if (findAndDeselect(item.children)) {
              return true; // Elemento encontrado y eliminado
            }
          }
        }
        return false; // Elemento no encontrado
      };

      // Iniciar la búsqueda recursiva en this.arrayFather
      const found = findAndDeselect(this.arrayFather);
    },
  },
});
