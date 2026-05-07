<script setup>
import { onMounted, ref, defineEmits, defineProps } from 'vue';

// Define props and emits
const props = defineProps({
    modelValue: String,
});

const emit = defineEmits(['update:modelValue']);

// Reference to the textarea element
const textarea = ref(null);

// Focus on textarea if autofocus attribute is present
onMounted(() => {
    if (textarea.value && textarea.value.hasAttribute('autofocus')) {
        textarea.value.focus();
    }
});

// Expose focus method
const focus = () => {
    if (textarea.value) {
        textarea.value.focus();
    }
};

defineExpose({ focus });
</script>

<style scoped>
/* Classe personalizada */
.defaultTheme {
    outline: none;
    border-color: rgba(80, 80, 80, 0.8);
    background-color: var(--cor-fundo);
    color: var(--cor-contraste);
    resize: vertical;
    /* Adicione se quiser permitir redimensionamento vertical */
}
</style>

<template>
    <textarea ref="textarea" class="defaultTheme py-3 px-4 w-full rounded-md shadow-sm" :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)" rows="5" />
</template>
