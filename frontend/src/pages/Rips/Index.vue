<script setup lang="ts">
import { useAuthenticationStore } from "@/stores/useAuthenticationStore";

definePage({
  name: "Rips-Index",
  meta: {
    redirectIfLoggedIn: true,
    requiresAuth: true,
    requiredPermission: "rips.index",
  },
});

const authenticationStore = useAuthenticationStore();
 

//TABLE
const refTableFull = ref()

const optionsTable = {
  url: "/rips/paginate",
  paramsGlobal: {
    company_id: authenticationStore.company.id,
  },
  headers: [ 
    { key: 'type_description', title: 'Tipo' },
    { key: 'numInvoices', title: 'Facturas' },
    { key: 'successfulInvoices', title: 'Data Completa' },
    { key: 'failedInvoices', title: 'Data Incompleta' }, 
    { key: 'created_at', title: 'Fecha de creación' }, 
    { key: 'actions', title: 'Acciones' },
  ],
}


//FILTER
const optionsFilter = ref({
  filterLabels: { inputGeneral: 'Buscar en todo', filing_invoice_pre_radicated_count: 'Facturas preradicadas' }
})
 

const tableLoading = ref(false); // Estado de carga de la tabla

// Método para refrescar los datos
const refreshTable = () => {
  if (refTableFull.value) {
    refTableFull.value.fetchTableData(null, false, true); // Forzamos la búsqueda
  }
};
</script>

<template>
  <div>

    <VCard>
      <VCardTitle class="d-flex justify-space-between">
        <span>
          Lista de rips
        </span>
        <div class="d-flex justify-end gap-3 flex-wrap ">
          <VMenu location="bottom">
            <template #activator="{ props }">
              <VBtn v-bind="props" append-icon="tabler-circle-chevrons-down">
                Agregar rips
              </VBtn>
            </template>

            <VList>
              <VListItem>
                Añadir ZIP
              </VListItem> 
            </VList>
          </VMenu>
        </div>
      </VCardTitle>

      <VCardText>
        <FilterDialog :options-filter="optionsFilter" @force-search="refreshTable" :table-loading="tableLoading">
        </FilterDialog>
      </VCardText>

      <VCardText>
        <TableFull ref="refTableFull" :options="optionsTable" @update:loading="tableLoading = $event">
          <template #item.status="{ item }">
            <div>
              <VChip :color="item.status_backgroundColor">{{ item.status_description }}</VChip>
            </div>
          </template>

          <template #item.actions="{ item }">
            <div>
              <VBtn icon color="primary">
                <VIcon icon="tabler-square-rounded-chevron-down"></VIcon>
                <VMenu activator="parent">
                  <VList>
                    <VListItem @click="goView(item)">
                      Ingresar
                    </VListItem>
                  </VList>
                </VMenu>
              </VBtn>
            </div>
          </template>
        </TableFull>
      </VCardText>
    </VCard>  
  </div>
</template>
