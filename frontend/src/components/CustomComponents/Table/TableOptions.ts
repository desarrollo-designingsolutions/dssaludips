export interface TableOptions {
  url: string | null;
  headers: { title: string; key: string; sortable?: boolean; width?: string; align?: string; fixed?: boolean }[];
  paramsGlobal?: Record<string, any>;
  showSelect?: boolean;
  multiSort?: boolean;
  sortBy?: any[];
  selected?: any[];
  pagination?: {
    rowsPerPage: number;
    currentPage: number;
    totalPages?: number;
    totalItems?: number;
  };
  actions?: {
    view?: { show: boolean; icon?: string; tooltip?: string };
    edit?: { show: boolean; icon?: string; tooltip?: string };
    delete?: { show: boolean; url?: string; icon?: string; tooltip?: string };
    changeStatus?: {
      show: boolean; // Indica si la acción está habilitada (true por defecto)
      url: string | null; // URL para la petición POST, obligatorio si show es true
      dbField: string; // Campo de la base de datos a modificar (is_active por defecto)
      btnActive: {
        text: string; // Texto del botón activo (Activo por defecto)
        value: number | string; // Valor para el estado activo (1 por defecto)
        newFunction?: (item: object) => void; // Función opcional para personalizar
      };
      btnInactive: {
        text: string; // Texto del botón inactivo (Inactivo por defecto)
        value: number | string; // Valor para el estado inactivo (0 por defecto)
        newFunction?: (item: object) => void; // Función opcional para personalizar
      };
    };
    style?: 'icon' | 'dropdown';
  };
}
