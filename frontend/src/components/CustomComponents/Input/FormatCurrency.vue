<script setup lang="ts">
const emit = defineEmits(["update:modelValue", "realValue"]);

const positionCare = ref();
const countPoints = ref(0);

const formatNumber = async (e: any) => {
    let caretPosition = e.target.selectionEnd;
    positionCare.value = caretPosition;

    let value = formatCurrency(e.target.value);

    let points = value.split(".").length - 1;

    if (points > countPoints.value) {
        caretPosition++;
    }

    if (countPoints.value > points) {
        caretPosition--;
    }

    countPoints.value = points;
    emit("update:modelValue", formatCurrency(value));
    emit("realValue", setRealValue(formatCurrency(value)));

    setTimeout(() => {
        e.target.setSelectionRange(caretPosition, caretPosition);
    }, 0);
};

const formatInit = (value: any) => {
    let val = String(value).replaceAll(".", ",");

    // Obtener los puntos
    const parts = val.split(",");
    let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    let points = integerPart.split(".").length - 1;

    countPoints.value = points;

    return val;
};

const formatCurrency = (value: any) => {
    let val = String(value).replaceAll(/[^0-9,-]/g, "");
    const parts = val.split(",");
    let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    let decimalPart = parts[1] ? parts[1].slice(0, 2) : "00";
    let valueFormatted = decimalPart
        ? `${integerPart},${decimalPart}`
        : integerPart;
    return valueFormatted;
};

onMounted(() => {
    emit("update:modelValue", formatCurrency(formatInit(useAttrs().modelValue)));
    emit(
        "realValue",
        setRealValue(formatCurrency(formatInit(useAttrs().modelValue)))
    );
});

const setRealValue = (value: any) => {
    return value.replaceAll(".", "").replaceAll(",", ".");
};

const handleKeypress = (event: any) => {
    const controlKeys = ["Backspace", "ArrowLeft", "ArrowRight", "Delete", ",", "-"];

    // Permitir tabulación
    if (event.key === "Tab") {
        return; // No hacer nada para la tecla Tab, lo que permite que siga tabulando
    }

    // Prevenir la inserción de más de una coma
    if (event.key === "," && event.target.value.includes(",")) {
        event.preventDefault();
    }

    // Prevenir la inserción de más de un guion
    if (event.key === "-" && event.target.value.includes("-")) {
        event.preventDefault();
    }

    // Prevenir teclas no permitidas (ni números ni control keys)
    if (!controlKeys.includes(event.key) && !/[0-9,-]/.test(event.key)) {
        event.preventDefault();
    }
};

const handleKeyup = (event: any) => {
    const controlKeys = ["Backspace", "ArrowLeft", "ArrowRight", "Delete", ",", "-"];

    if (event.key === "," && event.target.value.includes(",")) {
        event.preventDefault();
    }
    if (event.key === "-" && event.target.value.includes("-")) {
        event.preventDefault();
    }

    const parts = event.target.value.split(',')

    if ((!controlKeys.includes(event.key) && !/[0-9,-]/.test(event.key)) || parts[1].length > 2) {
        event.preventDefault();
    }

    if (parts[1]?.length > 2) {
        const newValue = `${parts[0]},${parts[1].slice(0, 2)}`;
        event.target.value = newValue;
        emit("update:modelValue", newValue);
        emit("realValue", setRealValue(newValue));
    }
};

const handleFocus = (e: any) => {
    // Posicionar el cursor al inicio del campo
    e.target.setSelectionRange(0, 0);
};
</script>

<template>
    <div>
        <AppTextField prefix="$" v-bind="{ ...$attrs }" @input="formatNumber($event)" @keydown="handleKeypress"
            @keyup="handleKeyup" @focus="handleFocus">
            <template v-for="(_, name) in $slots" #[name]="slotProps">
                <slot :name="name" v-bind="slotProps || {}" />
            </template>
        </AppTextField>
    </div>
</template>
