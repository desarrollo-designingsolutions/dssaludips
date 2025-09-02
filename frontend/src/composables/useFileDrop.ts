import { ref } from 'vue';

export function useFileDrop(
  maxAllowedFiles: number,
  allowedExtensions: string[] = [],
  forbiddenExtensions: string[] = [] // Nueva variable para extensiones no permitidas
) {
  const dropZoneRef = ref<HTMLDivElement>();
  const fileData = ref<FileData[]>([]);
  const { open, onChange } = useFileDialog({});
  const error = ref<string | null>(null);

  interface FileData {
    file: File;
    url: string;
    progress?: number;
    status?: 'pending' | 'uploading' | 'completed' | 'failed';
  }

  function onDrop(DroppedFiles: File[] | null) {
    if (!DroppedFiles) return;

    if (maxAllowedFiles > 0 && fileData.value.length >= maxAllowedFiles) {
      error.value = `Se ha alcanzado el límite de ${maxAllowedFiles} archivo${maxAllowedFiles !== 1 ? 's' : ''}.`;
      return;
    }

    if (!verifyFileLimit(DroppedFiles.length)) {
      return;
    }

    DroppedFiles.forEach(file => {
      const validationResult = validateFileExtension(file);
      if (validationResult === 'forbidden') {
        error.value = `No se permiten archivos con extensiones: ${forbiddenExtensions.join(', ')}.`;
        return;
      }
      if (validationResult === 'not_allowed') {
        error.value = `Solo se permiten archivos con extensión ${allowedExtensions.join(', ')}.`;
        return;
      }

      fileData.value.push({
        file,
        url: URL.createObjectURL(file),
        progress: undefined,
        status: 'pending'
      });
    });
  }

  onChange(selectedFiles => {
    if (!selectedFiles) return;

    if (maxAllowedFiles > 0 && fileData.value.length >= maxAllowedFiles) {
      error.value = `Se ha alcanzado el límite de ${maxAllowedFiles} archivo${maxAllowedFiles !== 1 ? 's' : ''}.`;
      return;
    }

    if (!verifyFileLimit(selectedFiles.length)) {
      return;
    }

    for (const file of selectedFiles) {
      const validationResult = validateFileExtension(file);
      if (validationResult === 'forbidden') {
        error.value = `No se permiten archivos con extensiones: ${forbiddenExtensions.join(', ')}.`;
        continue;
      }
      if (validationResult === 'not_allowed') {
        error.value = `Solo se permiten archivos con extensión ${allowedExtensions.join(', ')}.`;
        continue;
      }

      fileData.value.push({
        file,
        url: URL.createObjectURL(file),
        progress: undefined,
        status: 'pending'
      });
    }
  });

  const verifyFileLimit = (fileCount: number): boolean => {
    if (maxAllowedFiles > 0 && fileData.value.length + fileCount > maxAllowedFiles) {
      error.value = `Solo puedes seleccionar hasta ${maxAllowedFiles - fileData.value.length} archivo${maxAllowedFiles - fileData.value.length !== 1 ? 's' : ''} más.`;
      return false;
    }
    return true;
  };

  const validateFileExtension = (file: File): 'valid' | 'forbidden' | 'not_allowed' => {
    const fileExtension = file.name.split('.').pop()?.toLowerCase();

    // Si no hay extensión, verificamos según las reglas
    if (!fileExtension) {
      return allowedExtensions.length === 0 ? 'valid' : 'not_allowed';
    }

    // Primero verificamos las extensiones prohibidas
    if (forbiddenExtensions.length > 0 && forbiddenExtensions.includes(fileExtension)) {
      return 'forbidden';
    }

    // Luego verificamos las extensiones permitidas si existen
    if (allowedExtensions.length > 0 && !allowedExtensions.includes(fileExtension)) {
      return 'not_allowed';
    }

    return 'valid';
  };

  const resetValues = () => {
    fileData.value.forEach(data => {
      if (data.url) URL.revokeObjectURL(data.url);
    });
    fileData.value = [];
    error.value = null;
  };

  useDropZone(dropZoneRef, onDrop);

  return {
    dropZoneRef,
    fileData,
    open,
    resetValues,
    error
  };
}
