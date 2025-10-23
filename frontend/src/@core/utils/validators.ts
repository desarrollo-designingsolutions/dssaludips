import { isEmpty, isEmptyArray, isNullOrUndefined } from './helpers'

// 👉 Required Validator
export const requiredValidator = (value: unknown) => {
  if (isNullOrUndefined(value) || isEmptyArray(value) || value === false)
    return 'Este campo es obligatorio'

  return !!String(value).trim().length || 'Este campo es obligatorio'
}

// 👉 Email Validator
export const emailValidator = (value: unknown) => {
  if (isEmpty(value))
    return true

  const re = /^(?:[^<>()[\]\\.,;:\s@"]+(?:\.[^<>()[\]\\.,;:\s@"]+)*|".+")@(?:\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\]|(?:[a-z\-\d]+\.)+[a-z]{2,})$/i

  if (Array.isArray(value))
    return value.every(val => re.test(String(val))) || 'El campo de correo electrónico debe ser un correo electrónico válido'

  return re.test(String(value)) || 'El campo de correo electrónico debe ser un correo electrónico válido'
}

// 👉 Password Validator
export const passwordValidator = (password: string) => {
  const regExp = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%&*()]).{8,}/

  const validPassword = regExp.test(password)

  return validPassword || ' El campo debe contener al menos una mayúscula, una minúscula, un carácter especial y un dígito con un mínimo de 8 caracteres.'
}

// 👉 Confirm Password Validator
export const confirmedValidator = (value: string, target: string) =>

  value === target || 'La confirmación del campo Confirmar contraseña no coincide'

// 👉 Between Validator
export const betweenValidator = (value: unknown, min: number, max: number) => {
  const valueAsNumber = Number(value)

  return (Number(min) <= valueAsNumber && Number(max) >= valueAsNumber) || `Introduzca el número entre ${min} y ${max}`
}

// 👉 Integer Validator
export const integerValidator = (value: unknown) => {
  if (isEmpty(value))
    return true

  if (Array.isArray(value))
    return value.every(val => /^-?\d+$/.test(String(val))) || 'Este campo debe ser un número entero'

  return /^-?\d+$/.test(String(value)) || 'Este campo debe ser un número entero'
}

// 👉 Regex Validator
export const regexValidator = (value: unknown, regex: RegExp | string): string | boolean => {
  if (isEmpty(value))
    return true

  let regeX = regex
  if (typeof regeX === 'string')
    regeX = new RegExp(regeX)

  if (Array.isArray(value))
    return value.every(val => regexValidator(val, regeX))

  return regeX.test(String(value)) || 'El formato del campo Regex no es válido'
}

// 👉 Alpha Validator
export const alphaValidator = (value: unknown) => {
  if (isEmpty(value))
    return true

  return /^[A-Z]*$/i.test(String(value)) || 'El campo sólo puede contener caracteres alfabéticos'
}

// 👉 URL Validator
export const urlValidator = (value: unknown) => {
  if (isEmpty(value))
    return true

  const re = /^https?:\/\/[^\s$.?#].\S*$/

  return re.test(String(value)) || 'La URL no es válida'
}

// 👉 Length Validator
export const lengthValidator = (value: unknown, length: number) => {
  if (isEmpty(value))
    return true

  return String(value).length === length || `La longitud del campo debe ser ${length} caracteres.`
}

// 👉 Alpha-dash Validator
export const alphaDashValidator = (value: unknown) => {
  if (isEmpty(value))
    return true

  const valueAsString = String(value)

  return /^[\w-]*$/.test(valueAsString) || 'Todos los caracteres no son válidos'
}


export const positiveNumberValidator = (value: string) => {
  if (value) {
    return /^[0-9]*$/i.test(value) || "El numero no debe ser negativo"
  } else {
    return false
  }
}

export const lengthBetweenValidator = (value: unknown, min: number, max: number) => {
  // Convertimos el valor a string y obtenemos su longitud
  const length = String(value).length;

  // Validamos si la longitud está entre min y max
  return (length >= min && length <= max) || `Introduzca un texto con longitud entre ${min} y ${max} caracteres`;
};

export const customLengthValidator = (value: unknown, allowedLengths: number[]) => {
  if (isEmpty(value)) {
    return allowedLengths.includes(0) || `La longitud del campo debe ser una de: ${allowedLengths.join(', ')} caracteres.`;
  }

  const length = String(value).length;
  return (
    allowedLengths.includes(length) ||
    `La longitud del campo debe ser una de: ${allowedLengths.join(', ')} caracteres.`
  );
};


export const greaterThanZeroValidator = (value: number | string) => {
  // Convertir el valor a un número si es una cadena
  const num = typeof value === 'string' ? parseFloat(value) : value;

  // Verificar si el valor no es un número o si es menor o igual a cero
  if (isNaN(num) || num <= 0) {
    return 'El valor debe ser mayor que cero';
  }

  // Si el valor es válido, retornar true
  return true;
};




// Regla de validación personalizada para fechaInicioAtencion
export const maxDateValidator = (date: string, dateComparation: string) => {
  if (!date) return true; // Si no hay valor, pasa la validación (manejar required por separado)

  const startDate = new Date(date); // fechaInicioAtencion
  const maxDate = new Date(dateComparation);

  if (!maxDate || isNaN(maxDate)) return true; // Si no hay fecha de egreso o invoice_date, no valida

  // Compara las fechas incluyendo horas
  return startDate <= maxDate || `La fecha no puede ser posterior a ${formatToDMYHI(dateComparation)}`;
};


// Regla de validación para fechaEgreso
export const minDateValidator = (date: string, dateComparation: string) => {
  if (!date) return true; // Si no hay valor, pasa la validación (manejar required por separado)

  const endDate = new Date(date); // fechaEgreso
  const minDate = new Date(dateComparation);;

  if (!minDate || isNaN(minDate)) return true; // Si no hay fecha de inicio, no valida

  return endDate >= minDate || `La fecha no puede ser anterior a ${formatToDMYHI(dateComparation)}`;
};

export const uniqueValue = (value: unknown, arrayData: Array<string>, customErrorMessage?: string): string | boolean => {
  const stringValue = String(value); // Convertir el valor a cadena de texto
   
  const occurrences = arrayData.filter(item => item === stringValue).length;
  
  if (occurrences > 1) {
    return customErrorMessage || "El valor ya está en uso.";
  }
  
  return true;
};
