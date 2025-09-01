/**
 * Tipo que define los posibles tipos de campos de filtro soportados por el componente FilterDialogNew.
 * Cada valor representa un control de entrada específico que se renderiza en el diálogo de filtros avanzados o en filtros adicionales.
 * Se utiliza para determinar cómo se procesa y muestra cada campo en el componente.
 *
 * @example
 * ```typescript
 * const fieldType: FilterType = 'text'; // Campo de texto simple
 * ```
 */
export type FilterType =
  | 'text'         // Campo de texto simple (ej: filter[name]=texto)
  | 'number'       // Campo numérico (ej: filter[age]=25)
  | 'select'       // Selector con opciones predefinidas (ej: filter[status]=active,inactive)
  | 'booleanActive' // Selector con opciones booleanActive (ej: filter[status]=0,1)
  | 'selectApi'    // Selector remoto con datos desde una API (ej: filter[country_id]=1|USA,2|Canada)
  | 'selectChipCount' // Selector múltiple con chips (ej: filter[tags]=1,2)
  | 'date'         // Selector de fecha simple (ej: filter[created_at]=2023-10-15)
  | 'dateRange';   // Selector de rango de fechas (futuro soporte, ej: filter[created_at]=2023-10-01 to 2023-10-15)

/**
 * Interfaz que define la estructura de un campo de filtro individual en el componente FilterDialogNew.
 * Representa un filtro configurable que se renderiza en el diálogo de filtros avanzados.
 * Se usa dentro de `OptionsFilter.dialog.inputs` para especificar cada campo.
 *
 * @example
 * ```typescript
 * const field: FilterField = {
 *   name: 'country_id',
 *   label: 'Países',
 *   type: 'selectApi',
 *   value: [],
 *   url: 'selectInfiniteCountries',
 *   multiple: true
 * };
 * ```
 */
export interface FilterField {
  /**
   * Identificador único del filtro, usado como clave en los parámetros de la URL (ej: `filter[${name}]`).
   * Debe ser único dentro del conjunto de filtros.
   */
  name: string;

  /**
   * Etiqueta visible en la interfaz para el campo de filtro.
   * Se muestra como título del input en el diálogo y como nombre del filtro en los chips activos.
   */
  label: string;

  /**
   * Tipo del campo, define cómo se renderiza y procesa el filtro.
   * Ver `FilterType` para los valores soportados.
   */
  type: FilterType;

  /**
   * Valor inicial del filtro.
   * - Para `text`, `number`, `date`: string o number.
   * - Para `select`, `selectApi`, `selectChipCount`: array de valores u objetos.
   * - Puede ser `null` si no hay valor inicial.
   */
  value: string | number | any[] | null;

  /**
   * Texto de marcador que aparece cuando el campo está vacío.
   * Aplicable a `text` y `number`.
   * @optional
   */
  placeholder?: string;

  /**
   * Lista de opciones predefinidas para campos `select` y `selectChipCount`.
   * Cada opción tiene un `value` (identificador) y un `title` (texto visible).
   * @optional
   * @example [{ value: '1', title: 'Activo' }, { value: '2', title: 'Inactivo' }]
   */
  options?: { value: string | number; title: string }[];

  /**
   * Indica si el campo permite selección múltiple.
   * Aplicable a `select`, `selectApi`, y `selectChipCount`.
   * @optional
   * @default false
   */
  multiple?: boolean;

  /**
   * URL de la API para cargar datos dinámicos.
   * Solo aplicable a `selectApi`.
   * @optional
   * @example 'selectInfiniteCountries'
   */
  url?: string;

  /**
   * Clave del array en la respuesta de la API que contiene los datos.
   * Solo aplicable a `selectApi`.
   * @optional
   * @example 'countries'
   */
  arrayInfo?: string;

  /**
   * Propiedad del título en los ítems retornados por la API.
   * Solo aplicable a `selectApi`.
   * @optional
   * @example 'title'
   */
  itemTitle?: string;

  /**
   * Propiedad del valor en los ítems retornados por la API.
   * Solo aplicable a `selectApi`.
   * @optional
   * @example 'value'
   */
  itemValue?: string;

  /**
   * Parámetro de búsqueda enviado a la API para filtrar resultados.
   * Solo aplicable a `selectApi`.
   * @optional
   * @example 'q'
   */
  searchParam?: string;
}

/**
 * Interfaz que define la configuración completa del componente FilterDialogNew.
 * Se pasa como prop `optionsFilter` para especificar el comportamiento y los filtros disponibles.
 *
 * @example
 * ```typescript
 * const options: OptionsFilter = {
 *   showBtnSearch: true,
 *   extraFilters: { fechaAdicional: { value: '' } },
 *   dialog: {
 *     cols: '6',
 *     width: 800,
 *     inputs: [{ name: 'name', label: 'Nombre', type: 'text', value: '' }]
 *   },
 *   filterLabels: { inputGeneral: 'Buscar Todo', fechaAdicional: 'Fecha de Creación' }
 * };
 * ```
 */
export interface OptionsFilter {
  /**
   * Controla si se muestra un botón "Buscar" para aplicar los filtros manualmente.
   * - `true`: Requiere clic en "Buscar" para actualizar la URL.
   * - `false`: Aplica filtros automáticamente con un debounce de 500ms.
   * @optional
   * @default false
   */
  showBtnSearch?: boolean;
  /**
   * Controla si se plasma en la url el query de busqueda.
   * - `true`: Agrega el query de busqueda en la url.
   * - `false`: No agrega el query de busqueda en la url.
   * @optional
   * @default true
   */
  queryUrl?: boolean;

  /**
   * Objeto que define filtros adicionales renderizados a través del slot.
   * Las claves son nombres de filtros, y los valores son objetos con una propiedad `value`.
   * @example { fechaAdicional: { value: '' }, category: { value: '' } }
   */
  extraFilters: Record<string, { value: string | any[] }>;

  /**
   * Configuración del diálogo de filtros avanzados.
   */
  dialog: {
    /**
     * Titulo del dialog. 
     */
    title: string;
    /**
     * Número de columnas por campo en el diseño del diálogo (basado en el grid de Vuetify).
     * @example '6' (mitad del ancho), '12' (ancho completo)
     */
    cols: string;

    /**
     * Ancho máximo del diálogo en píxeles.
     * @example 800
     */
    width: number;

    /**
     * Lista de campos de filtro renderizados en el diálogo.
     * Ver `FilterField` para la estructura de cada campo.
     */
    inputs: FilterField[];
  };

  /**
   * Diccionario opcional de etiquetas personalizadas para los filtros activos mostrados como chips.
   * Permite sobrescribir las etiquetas por defecto (label de FilterField o nombre del filtro).
   * Útil para personalizar `inputGeneral` o filtros en `extraFilters`.
   * @optional
   * @example { inputGeneral: 'Buscar Todo', fechaAdicional: 'Fecha de Creación' }
   */
  filterLabels?: Record<string, string>;
}

/**
 * Interfaz que define los parámetros de la URL manejados por FilterDialogNew.
 * Incluye filtros, ordenamiento y paginación para sincronizar con el backend.
 */
export interface Queries {
  /**
   * Ordenamiento aplicado a los datos.
   * @example 'name' o '-created_at'
   */
  sort: string;

  /**
   * Filtro de búsqueda general.
   * @example 'test'
   */
  'filter[inputGeneral]': string;

  /**
   * Número de página actual.
   */
  page: number;

  /**
   * Cantidad de elementos por página.
   */
  perPage: number;

  /**
   * Filtros dinámicos adicionales en formato `filter[${string}]`.
   * @example 'filter[name]': 'John', 'filter[country_id]': '1|USA'
   */
  [key: `filter[${string}]`]: string | number;
}
