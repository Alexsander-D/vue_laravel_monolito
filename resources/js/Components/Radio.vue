<!-- Radio.vue -->
<script setup>
import { computed, defineEmits, defineProps } from "vue";

const emit = defineEmits(["update:modelValue"]);

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: null,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  name: {
    type: String,
    required: true,
  },
});

const proxyChecked = computed({
  get: () => props.modelValue === props.value,
  set: (val) => {
    if (val) emit("update:modelValue", props.value);
  },
});
</script>

<style scoped>
/* Classe personalizada */
.colorBase {
  color: var(--cor-principal);
  background-color: var(--cor-fundo);
  border-color: var(--cor-contraste);
}

/* Estilo aplicado quando o checkbox está checado */
.colorBase:checked {
  background-color: var(--cor-principal);
}
</style>

<template>
  <div class="flex items-center">
    <input
      type="radio"
      :id="`${name}-${value}`"
      :name="name"
      :value="value"
      v-model="proxyChecked"
      class="colorBase shrink-0 mt-0.5 rounded-full text-blue-600 focus:ring-blue-500 checked:border-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
    />
    <label
      :for="`${name}-${value}`"
      class="text-sm text-gray-500 ms-2 dark:text-neutral-400"
    >
      {{ label }}
    </label>
  </div>
</template>
