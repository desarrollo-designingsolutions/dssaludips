import { ref, watch } from 'vue'

export function useSelect(fun, arrayData = [], countLinks = 0, filterDta = {}) {
  const searchQueryInfinite = ref<string>("")
  const dataNueva = ref<Array<object>>([])
  const page = ref<number>(1)
  const totalLinks = ref<number>(1)
  const filterInterno = ref<object>({})

  const prev = async () => {
    page.value--
    if (page.value <= 1) page.value = 1
    await search()
  }

  const next = async () => {
    page.value++


    if (page.value > totalLinks.value) {
      page.value = totalLinks.value
    }
    await search()
  }

  const search = async () => {
    let filter = {
      page: page.value,
      searchQueryInfinite: searchQueryInfinite.value
    }
    for (const key in filterDta) {
      filter[key] = filterDta[key]
    }
    for (const key in filterInterno.value) {
      filter[key] = filterInterno[key]
    }

    await fun(filter)

    dataNueva.value = [];
    dataNueva.value.push(...arrayData.value)

  }

  let timeoutId = null;

  watch(searchQueryInfinite, async (newQuestion, oldQuestion) => {
    if (timeoutId) {
      clearTimeout(timeoutId);
    }


    page.value = 1
    let filter = {
      page: page.value,
      searchQueryInfinite: newQuestion
    }
    for (const key in filterDta) {
      filter[key] = filterDta[key]
    }
    for (const key in filterInterno.value) {
      filter[key] = filterInterno[key]
    }



    timeoutId = setTimeout(async () => {
      // Lógica para llamar a tu API aquí 
      await fun(filter)

      dataNueva.value = [];
      if (arrayData && arrayData.value !== undefined) {
        dataNueva.value.push(...arrayData.value)
      }
      if (countLinks && countLinks.value !== undefined) {
        totalLinks.value = countLinks.value
      }
    }, 300); // Puedes ajustar el tiempo de espera según tus necesidades



  })

  return {
    searchQueryInfinite,
    dataNueva,
    page,
    filterInterno,
    next, prev, search, totalLinks
  }
}
