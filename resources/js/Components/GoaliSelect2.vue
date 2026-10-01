<script setup>
import $ from "jquery";
import "select2";
import { onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps({
  modelValue: { type: String, default: "" },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: "Selecione uma opção" },
  required: { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue"]);
const selectElement = ref(null);
let select2Instance;

onMounted(() => {
  select2Instance = $(selectElement.value).select2({
    width: "100%",
    placeholder: props.placeholder,
    allowClear: !props.required,
  });
  select2Instance.on("change.goali", () => {
    emit("update:modelValue", String(select2Instance.val() || ""));
  });
  select2Instance.val(props.modelValue || "").trigger("change.select2");
});

watch(() => props.modelValue, (value) => {
  if (select2Instance && select2Instance.val() !== (value || "")) {
    select2Instance.val(value || "").trigger("change.select2");
  }
});

onBeforeUnmount(() => {
  if (select2Instance) {
    select2Instance.off(".goali").select2("destroy");
  }
});
</script>

<template>
  <select ref="selectElement" class="goali-select2" :required="required">
    <option value=""></option>
    <option v-for="option in options" :key="option" :value="option">{{ option }}</option>
  </select>
</template>

<style>
@import "select2/dist/css/select2.min.css";

.goali-select2 + .select2-container { margin-top: .375rem; }
.goali-select2 + .select2-container .select2-selection--single {
  height: 42px;
  border: 1px solid #d1d5db;
  border-radius: .375rem;
  background: #fff;
  box-shadow: 0 1px 2px rgb(0 0 0 / 5%);
}
.goali-select2 + .select2-container .select2-selection__rendered { line-height: 40px; padding-left: .75rem; color: #111827; font-size: .875rem; }
.goali-select2 + .select2-container .select2-selection__arrow { height: 40px; right: .4rem; }
.goali-select2 + .select2-container.select2-container--focus .select2-selection--single { border-color: #3b82f6; box-shadow: 0 0 0 2px rgb(59 130 246 / 25%); }
html.dark .goali-select2 + .select2-container .select2-selection--single { border-color: #4b5563; background: #1f2937; }
html.dark .goali-select2 + .select2-container .select2-selection__rendered { color: #f9fafb; }
</style>