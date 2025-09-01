import * as XLSX from 'xlsx';

// 👉 IsEmpty
export const isEmpty = (value: unknown): boolean => {
  if (value === null || value === undefined || value === '')
    return true

  return !!(Array.isArray(value) && value.length === 0)
}

// 👉 IsNullOrUndefined
export const isNullOrUndefined = (value: unknown): value is undefined | null => {
  return value === null || value === undefined
}

// 👉 IsEmptyArray
export const isEmptyArray = (arr: unknown): boolean => {
  return Array.isArray(arr) && arr.length === 0
}

// 👉 IsObject
export const isObject = (obj: unknown): obj is Record<string, unknown> =>
  obj !== null && !!obj && typeof obj === 'object' && !Array.isArray(obj)

// 👉 IsToday
export const isToday = (date: Date) => {
  const today = new Date()

  return (
    date.getDate() === today.getDate()
    && date.getMonth() === today.getMonth()
    && date.getFullYear() === today.getFullYear()
  )
}




export const descargarArchivo = (path: string, name: string | null = null) => {

  // Divide la URL por las barras diagonales '/' para obtener las partes
  const partesURL = path.split('/');

  // Toma la última parte que debería ser el nombre del archivo
  const nombreArchivo = partesURL[partesURL.length - 1];

  // Dividir el nombre del archivo por el punto '.' para obtener la extensión
  const partesNombreArchivo = nombreArchivo.split('.');
  const extension = partesNombreArchivo[partesNombreArchivo.length - 1];

  let nameComplete = name + '.' + extension
  if (name == null) {
    nameComplete = nombreArchivo
  }

  // Código para descargar el archivo
  const link = document.createElement('a');
  link.href = path;
  link.target = "_blank"
  link.setAttribute('download', nameComplete);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}



export const openPdfBase64 = (pdfBase64: string) => {
  // Crear una nueva ventana
  const newWindow = window.open('', '_blank');

  // Verificar si la ventana emergente fue bloqueada por el navegador
  if (newWindow) {
    // Crear un documento HTML en la ventana emergente
    const html = `
      <html>
        <head>
          <title>PDF Viewer</title>
        </head>
        <body>
          <embed width="100%" height="100%" src="data:application/pdf;base64,${pdfBase64}" type="application/pdf">
        </body>
      </html>
    `;

    // Escribir el contenido HTML en la ventana emergente
    newWindow.document.write(html);
  } else {
    // Mostrar un mensaje de error si la ventana emergente fue bloqueada
    alert('La ventana emergente fue bloqueada por el navegador. Asegúrate de permitir ventanas emergentes.');
  }
}

export const downloadExcelBase64 = (
  base64: string,
  fileName: string = "excel"
) => {
  if (base64) {
    const link = document.createElement("a");
    link.href = `data:application/vnd.ms-excel;base64,${base64}`;
    link.download = fileName + ".xlsx";
    link.click();
  }
};


export function onlyNumbersKeyPress(event: any) {
  if ((!/[0-9,-]/.test(event.key))) {
    event.preventDefault();
  }
}
export function storageBack(path: string) {
  return BASE_BACK_STORAGE + path
}

export function cloneObject(data: any) {
  if (data === undefined) {
    return null; // O podrías devolver {} si prefieres un objeto vacío
  }
  return JSON.parse(JSON.stringify(data));
}

export const formatoMoneda = (numero: any) => {
  if (numero) {
    let numeroFormateado = numero.replace(/\D/g, "");
    if (!isNaN(numeroFormateado)) {
      // Aplicar el formato de miles utilizando expresiones regulares
      numeroFormateado = numeroFormateado.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    } else {
      // Si no es un número, eliminar el último caracter ingresado
      numeroFormateado = numeroFormateado.slice(0, -1);
    }

    return numeroFormateado;
  }
};

export const exportArrayToExcel = (dataArray: Array<any> = [], nameExcel: string = 'Array') => {

  const worksheet = XLSX.utils.json_to_sheet(dataArray);
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, nameExcel);
  const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });


  const data = new Blob([excelBuffer], { type: 'application/octet-stream' });
  const link = document.createElement('a');
  link.href = window.URL.createObjectURL(data);
  link.download = `${nameExcel}.xlsx`;
  link.click();
};

// Función para formatear fechas al formato datetime-local
export const formatToDateTimeLocal = (dateString: string | null): string => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// Función para formatear fechas al formato datetime-local
export const formatToDateYMD = (dateString: string | null): string => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}




// Función para formatear fechas al formato d-m-Y h:i AM/PM
export const formatToDMYHI = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (isNaN(date)) return '';
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  let hours = date.getHours();
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const ampm = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12 || 12; // Convierte 0 a 12 para medianoche
  return `${day}-${month}-${year} ${hours}:${minutes} ${ampm}`;
};

export const parseCurrencyToFloat = (value: string) => {
  if (!value) return 0;
  return parseFloat(
    value.replace(/\s|\$/g, '').replace(',', '.')
  );
}

export const downloadBlob = async (api: string, nameFile: string, format_ext: string) => {
  try {

    // Hacer la solicitud GET al endpoint
    const response = await useAxios(api).get({
      responseType: 'blob', // Indicar que esperamos un archivo binario
    });

    // Crear un Blob a partir de la respuesta
    const blob = new Blob([response.data], { type: `application/${format_ext}` });

    // Crear un enlace temporal para la descarga
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `${nameFile}.${format_ext}`); // Nombre del archivo
    document.body.appendChild(link);
    link.click();

    // Limpiar
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

  } catch (error) {
    console.error('Error al descargar el archivo:', error);
  } finally {
  }
};
