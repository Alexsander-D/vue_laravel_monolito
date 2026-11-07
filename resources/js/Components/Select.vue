<!-- MultiselectComponent.vue -->
<template>
  <select
    v-if="select2"
    ref="selectElement"
    v-bind="attrs"
    :value="selectedOptionValue"
    :required="required"
    :disabled="disabled"
    class="select2-control"
  >
    <option v-if="allowEmpty" value="">{{ placeholder }}</option>
    <option v-for="option in options" :key="String(optionValue(option))" :value="optionValue(option)">
      {{ optionLabel(option) }}
    </option>
  </select>
  <multiselect
    v-else
    v-bind="attrs"
    v-model="localValue"
    :options="options"
    :label="label"
    :track-by="trackBy"
    :placeholder="placeholder"
    :allow-empty="allowEmpty"
    :required="required"
    :disabled="disabled"
    @update:modelValue="updateModelValue"
  />
</template>

<script setup>
import Multiselect from "vue-multiselect";
import $ from "jquery";
import { computed, defineEmits, defineProps, onBeforeUnmount, onMounted, ref, useAttrs, watch } from "vue";

import select2Factory from "select2";
import "select2/dist/css/select2.css";

defineOptions({ inheritAttrs: false });
const props = defineProps({
  modelValue: {
    type: [Array, String, Object],
    default: () => [],
  },
  options: {
    type: Array,
    default: () => [],
  },
  label: {
    type: String,
    default: "label",
  },
  trackBy: {
    type: String,
    default: "",
  },
  placeholder: {
    type: String,
    default: "Selecione uma opção",
  },
  allowEmpty: {
    type: Boolean,
    default: true,
  },
  select2: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue", "selected"]);
const attrs = useAttrs();
const localValue = ref(props.modelValue);
const selectElement = ref(null);
let select2Instance = null;

const optionValue = (option) => (
  option && typeof option === "object"
    ? option[props.trackBy] ?? option.value ?? option[props.label]
    : option
);
const optionLabel = (option) => (
  option && typeof option === "object" ? option[props.label] ?? "" : option
);
const selectedOptionValue = computed(() => {
  const value = props.modelValue && typeof props.modelValue === "object"
    ? optionValue(props.modelValue)
    : props.modelValue;

  return value == null ? "" : String(value);
});

function syncSelect2Value(value) {
  if (!select2Instance) {
    return;
  }

  const selectedValue = value && typeof value === "object" ? optionValue(value) : value;
  $(selectElement.value).val(selectedValue == null ? "" : String(selectedValue)).trigger("change.select2");
}

function initializeSelect2() {
  if (!props.select2 || !selectElement.value || select2Instance) {
    return;
  }

  if (typeof $.fn.select2 !== "function") {
    select2Factory(window, $);
  }

  const element = $(selectElement.value);
  const dropdownParent = element.closest("[data-select2-dropdown-parent]");
  const modal = element.closest(".fixed");
  element.select2({
    placeholder: props.placeholder,
    allowClear: props.allowEmpty,
    width: "100%",
    ...(dropdownParent.length ? { dropdownParent } : modal.length ? { dropdownParent: modal } : {}),
  });
  element.on("change", () => {
    const selectedValue = element.val();
    const option = props.options.find((item) => String(optionValue(item)) === String(selectedValue));
    const value = selectedValue === "" || selectedValue == null
      ? null
      : option ?? selectedValue;

    emit("update:modelValue", value);
    emit("selected", value);
  });
  select2Instance = element;
  syncSelect2Value(props.modelValue);
}

function destroySelect2() {
  if (!select2Instance) {
    return;
  }

  select2Instance.off("change").select2("destroy");
  select2Instance = null;
}

watch(
  () => props.modelValue,
  (newValue) => {
    if (!newValue || (Array.isArray(newValue) && newValue.length === 0)) {
      localValue.value = null;
    } else {
      localValue.value = newValue;
    }
  },
  { deep: true }
);

watch(
  () => [props.options, props.select2],
  () => {
    if (props.select2) {
      destroySelect2();
      initializeSelect2();
    } else {
      destroySelect2();
    }
  },
  { deep: true }
);

watch(() => props.modelValue, syncSelect2Value, { deep: true });

function updateModelValue(value) {
  emit("update:modelValue", value);
  emit("selected", value);
}

onMounted(initializeSelect2);
onBeforeUnmount(destroySelect2);
</script>

<style>
@import "/resources/css/Components/Select.css";

.select2-control {
  width: 100%;
}

.select2-container {
  width: 100% !important;
}
</style>
