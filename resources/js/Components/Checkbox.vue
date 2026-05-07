<script setup>
import { computed, defineEmits, defineProps } from "vue";

const emit = defineEmits(["update:checked"]);

const props = defineProps({
  checked: {
    type: [Array, Boolean],
    default: false,
  },
  value: {
    type: String,
    default: null,
  },
});

const proxyChecked = computed({
  get() {
    return props.checked;
  },

  set(val) {
    emit("update:checked", val);
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
  <input
    v-model="proxyChecked"
    type="checkbox"
    :value="value"
    class="colorBase rounded shadow-sm"
  />
</template>
