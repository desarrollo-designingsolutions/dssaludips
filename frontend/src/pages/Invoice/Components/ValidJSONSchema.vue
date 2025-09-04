<script setup lang="ts">
import Ajv from 'ajv';
import Localize from 'ajv-i18n';
import moment from 'moment';
import { computed, ref } from 'vue';
import schema from './validJSONSchema.json';

const emit = defineEmits(["loadFile", "clearFile"]);

const fileInput = ref<HTMLInputElement | null>(null)
const jsonContent = ref(null)
const validationResult = ref(null)
const isLoading = ref(false)

const ajv = new Ajv({
  allErrors: true,
  strict: false,
  allowUnionTypes: true,
  formats: {
    'date-time-custom': {
      validate: (dateTimeString: string) => {
        return moment(dateTimeString, 'YYYY-MM-DD HH:mm', true).isValid();
      }
    },
    'date-time-custom-user': {
      validate: (dateTimeString: string) => {
        return moment(dateTimeString, 'YYYY-MM-DD', true).isValid();
      }
    },
  }
});

const formattedJson = computed(() => {
  return jsonContent.value ? JSON.stringify(jsonContent.value, null, 2) : ''
})

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  isLoading.value = true
  validationResult.value = null

  try {
    const content = await readFile(file)
    jsonContent.value = JSON.parse(content)
    validateJson(jsonContent.value)

    emit("loadFile", file)

  } catch (error: any) {
    validationResult.value = {
      valid: false,
      errors: [{
        path: '',
        message: `Error al procesar el archivo: ${error.message}`,
        params: null
      }]
    }
  } finally {
    isLoading.value = false
  }
}

const readFile = (file: File): Promise<string> => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = (event) => resolve(event.target?.result as string)
    reader.onerror = (error) => reject(error)
    reader.readAsText(file)
  })
}

const validateJson = (jsonData: any) => {
  try {
    const validate = ajv.compile(schema);
    const valid = validate(jsonData);

    if (valid) {
      validationResult.value = { valid: true, errors: [] };
    } else {
      // Asegura que los errores se traduzcan
      Localize.es(validate.errors);

      const errors = validate.errors?.map(error => ({
        path: error.instancePath || '/',
        message: formatErrorMessage(error),
        params: error.params,
        schemaPath: error.schemaPath
      }));

      validationResult.value = {
        valid: false,
        errors: groupErrors(errors || [])
      };
    }
  } catch (error: any) {
    validationResult.value = {
      valid: false,
      errors: [{
        path: '',
        message: `Error en la validación: ${error.message}`,
        params: null
      }]
    };
  }
};

const formatErrorMessage = (error: any) => {
  if (error.keyword === 'required') {
    return `Campo obligatorio faltante: '${error.params.missingProperty}'`;
  }
  if (error.keyword === 'additionalProperties') {
    return `Campo no permitido: '${error.params.additionalProperty}'`;
  }
  if (error.keyword === 'type') {
    const expectedType = Array.isArray(error.params.type)
      ? error.params.type.join(' o ')
      : error.params.type;
    return `Tipo de dato inválido. Se esperaba: ${expectedType}`;
  }
  if (error.keyword === 'format') {
    if (error.params.format === 'date-time-custom') {
      return "Formato de fecha/hora inválido. Debe ser: 'AAAA-MM-DD HH:MM'";
    }
    return `Formato inválido para '${error.params.format}'`;
  }
  if (error.keyword === 'enum') {
    return `Valor no permitido. Opciones válidas: ${error.params.allowedValues.join(', ')}`;
  }
  if (error.keyword === 'minLength') {
    return `El texto es demasiado corto. Mínimo ${error.params.limit} caracteres requeridos`;
  }
  if (error.keyword === 'maxLength') {
    return `El texto es demasiado largo. Máximo ${error.params.limit} caracteres requeridos`;
  }

  if (error.keyword === 'pattern') {
    return getPatternErrorMessage(error.params.pattern);
  }

  // Mensaje genérico (traducido por ajv-i18n)
  return error.message || 'Error de validación';
};

const getPatternErrorMessage = (pattern: string): string => {
  // Expresión regular mejorada para capturar más variantes de patrones
  const patternRegex = /^\^\((?:\|?\.\{(\d+)(?:,(\d+))?\}|\.\{0\}\|\.\{(\d+)(?:,(\d+))?\})\)\$$/;

  const match = pattern.match(patternRegex);

  if (match) {
    // Para patrones como ^(|.{4,25})$ o ^(.{4,25})$
    if (match[1] !== undefined && match[2] !== undefined) {
      const min = match[1];
      const max = match[2];
      return `El campo debe contener entre ${min} y ${max} caracteres`;
    }

    // Para patrones como ^(.{0}|.{4,25})$
    if (match[3] !== undefined) {
      const min = match[3];
      const max = match[4] || min; // Si no hay máximo, usar el mínimo

      if (min === max) {
        return `El campo debe estar vacío o tener exactamente ${min} ${min === '1' ? 'caracter' : 'caracteres'}`;
      }
      return `El campo debe estar vacío o tener entre ${min} y ${max} caracteres`;
    }

    // Para patrones como ^(|.{5})$ (longitud exacta)
    if (match[1] !== undefined && match[2] === undefined) {
      const length = match[1];
      return `El campo debe contener exactamente ${length} ${length === '1' ? 'caracter' : 'caracteres'}`;
    }
  }

  // Caso para patrones no reconocidos
  return `El valor no cumple con el formato requerido (patrón: ${pattern})`;
};

const formatParams = (params: any) => {
  if (!params) return ''
  return Object.entries(params)
    .map(([key, value]) => `${key}: ${value}`)
    .join(', ')
}

const groupErrors = (errors: any[]) => {
  const grouped: { [key: string]: any } = {}
  errors.forEach(error => {
    const key = `${error.path}-${error.message}`
    if (!grouped[key]) {
      grouped[key] = error
    }
  })
  return Object.values(grouped)
}

const resetValidation = () => {
  jsonContent.value = null
  validationResult.value = null
  if (fileInput.value) fileInput.value.value = ''
  emit("clearFile")
}

const copyJsonToClipboard = async () => {
  try {
    await navigator.clipboard.writeText(formattedJson.value)
    alert('JSON copiado al portapapeles!')
  } catch (err) {
    console.error('Error al copiar: ', err)
  }
}


</script>

<template>
  <v-row justify="center">
    <VCol cols="12" class="text-center">
      <h1 class="text-h3 mb-2">Validador de JSON</h1>
      <p class="text-subtitle-1">Verifica que tu archivo cumpla con el esquema requerido</p>
    </VCol>
  </v-row>

  <v-row justify="center" class="mb-6">
    <VCol cols="12" sm="6" md="4">
      <VCard class="pa-4" variant="outlined">
        <input type="file" ref="fileInput" @change="handleFileUpload" accept=".json" class="d-none">
        <v-btn block color="primary" @click="triggerFileInput" :loading="isLoading" :disabled="isLoading"
          prepend-icon="tabler-file-upload">
          Seleccionar archivo
        </v-btn>
        <div class="text-caption text-center mt-2">Formatos soportados: .json</div>
      </VCard>

      <v-btn v-if="jsonContent" block variant="outlined" class="mt-4" @click="resetValidation" :disabled="isLoading">
        Limpiar
      </v-btn>
    </VCol>
  </v-row>

  <v-row v-if="(jsonContent || validationResult) && !isLoading">
    <VCol cols="12" md="6">
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center">
          Resultado de validación
          <v-chip v-if="validationResult" :color="validationResult.valid ? 'success' : 'error'"
            :text="validationResult.valid ? 'Válido' : 'Inválido'" />
        </VCardTitle>

        <VCardText class="validation-content">
          <v-alert v-if="validationResult?.valid" type="success" title="¡Validación exitosa!"
            text="El archivo JSON cumple con el esquema requerido." />

          <template v-else-if="validationResult?.errors">
            <div class="text-subtitle-1 mb-4">
              Se encontraron {{ validationResult.errors.length }} errores
            </div>

            <VCard v-for="(error, index) in validationResult.errors" :key="index" class="mb-3" variant="outlined">
              <VCardText>
                <div class="d-flex align-center mb-2">
                  <v-chip size="small" color="error" variant="outlined" class="mr-2">
                    {{ error.path || '/' }}
                  </v-chip>
                </div>
                <div class="text-body-1">{{ error.message }}</div>
                <div v-if="error.params" class="text-caption mt-2">
                  <strong>Detalles:</strong> {{ formatParams(error.params) }}
                </div>
              </VCardText>
            </VCard>
          </template>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" md="6">
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center">
          Vista previa del JSON
          <v-btn icon="tabler-copy" @click="copyJsonToClipboard" title="Copiar JSON" />
        </VCardTitle>
        <VCardText>
          <pre class="json-content">{{ formattedJson }}</pre>
        </VCardText>
      </VCard>
    </VCol>
  </v-row>

  <v-overlay v-model="isLoading" class="align-center justify-center">
    <v-progress-circular indeterminate size="64" />
  </v-overlay>
</template>

<style scoped>
.json-content {
  font-family: Menlo, Monaco, Consolas, monospace;
  font-size: 0.875rem;
  line-height: 1.5;
  max-block-size: 500px;
  overflow-y: auto;
  white-space: pre-wrap;
  word-break: break-all;
}

.validation-content {
  max-block-size: 500px;
  overflow-y: auto;
}
</style>
