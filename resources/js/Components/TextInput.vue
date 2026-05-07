<script setup>
import { onMounted, ref, defineEmits, defineProps } from "vue";

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean, Object, null],
    default: null,
  },
  type: {
    type: String,
    default: "text",
  },
});

defineEmits(["update:modelValue"]);

const input = ref(null);

onMounted(() => {
  if (input.value.hasAttribute("autofocus")) {
    input.value.focus();
  }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<style scoped>
.defaultTheme {
  outline: none;
  border: 1px solid rgba(80, 80, 80, 0.8);
  background-color: var(--cor-fundo);
  color: var(--cor-contraste);
}

.defaultTheme:disabled {
  background-color: darken(var(--cor-fundo), 100%);
}
</style>

<template>
  <input
    ref="input"
    :type="type"
    class="defaultTheme w-full rounded-md shadow-sm"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>
