<script setup lang="ts">
// Esto es para que se sincronize con la variable de afuera
// Define props pero no destructurado
const props = defineProps({
  modelValue: {
    type: [String, Number, Object, Array, Boolean, Function] as PropType<any>, // Incluye todos los tipos posibles
    default: null
  },
  maxDecimal: {
    type: [String, Number],
    default: 2
  }
});

// Define emits for the component
const emit = defineEmits(['update:modelValue', "realValue"]);

//variable interna
const internalValue = ref(props.modelValue);

//el valor del padre lo proporciona al interno
watch(() => props.modelValue, (newValue) => {
  internalValue.value = newValue;
});

//mi valor interno lo emite al padre
watch(internalValue, (newValue) => {
  emit('update:modelValue', newValue);
});
//////////////////////////////////////////////////////


const maxDecimal = ref(props.maxDecimal);

const setRealValue = (value: any) => {
  value = String(value)
  return value.replaceAll(",", ".");
};
const setFicticieValue = (value: any) => {
  value = String(value)
  return value.replaceAll(".", ",");
};

// Compute the regular expression based on maxDecimal
const regex = computed(() => {
  const decimalPart = `{0,${maxDecimal.value}}`; // Dynamic decimal places
  return new RegExp(`^\\d*(,\\d${decimalPart})?$`);
});

const updateValue = (event: KeyboardEvent) => {
  const input = event.target as HTMLInputElement;

  //no permite agregar los valores del array 
  if (['-', "."].includes(event.key)) {
    input.value = input.value.slice(0, -1);
  }

  // Si el valor tiene más de una coma, eliminamos la última
  const commas = (input.value.match(/,/g) || []).length;
  if (commas > 1) {
    input.value = input.value.slice(0, -1);
  }

  // Si el valor es mayor a 100 se resetea el valor a 100
  if (parseFloat(input.value) > 100) {
    input.value = "100";
  }

  // Si el valor es menos de 0 se resetea el valor a 0
  if (parseFloat(input.value) < 0) {
    input.value = "0";
  }

  //el regex valida que solo sean numeros, los decimales con coma y el tamaño de decimales segun lo configurado
  if (!regex.value.test(input.value)) {
    input.value = input.value.slice(0, -1);
  }

  internalValue.value = input.value; // ya con este cambio tanto el interno como el externo
  emit("realValue", setRealValue(input.value));
}

onMounted(() => {
  //esto lo pongo para que me inicialicen los valores internos con los formatos correctos
  const cal = setFicticieValue(internalValue.value)
  emit("update:modelValue", cal); 
  emit("realValue", internalValue.value);
});

</script>
<template>
  <div>
    <AppTextField v-model="internalValue" @input="updateValue" v-bind="{ ...$attrs }"
      append-inner-icon="tabler-percentage" >

      
      <!-- Codigo que itera sobre las ranuras disponibles en el componente padre e individualmente rinde cada ranura con sus propias propiedades -->
      <template v-for="(_, name) in $slots" #[name]="slotProps">
            <slot :name="name" v-bind="slotProps || {}" />
          </template>
          <!-- Codigo que itera sobre las ranuras disponibles en el componente padre e individualmente rinde cada ranura con sus propias propiedades -->
      </AppTextField>
  </div>
</template>
