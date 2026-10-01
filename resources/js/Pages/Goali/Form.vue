<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import Select from "@/Components/Select.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, watch } from "vue";

const props = defineProps({
  solicitations: { type: Array, default: () => [] },
  responsibleUser: { type: String, default: "" },
});

const today = new Date().toLocaleDateString("en-CA");
const form = useForm({
  travel_date: today,
  travel_time: "",
  status: "",
  solicitation: "",
  origin: "",
  destination: "",
  passenger: "",
  responsible: props.responsibleUser,
  amount: "",
});
const page = usePage();
const successMessage = computed(() => page.props.flash?.success || "");

watch(() => form.solicitation, (company) => {
  form.origin = company || "";
  if (company) form.clearErrors("solicitation");
});

const formatAmount = () => {
  const amount = Number(String(form.amount).replace(",", "."));
  if (form.amount !== "" && Number.isFinite(amount)) {
    form.amount = amount.toFixed(2);
  }
};

const submit = () => {
  if (!form.solicitation) {
    form.setError("solicitation", "Selecione uma empresa.");
    return;
  }

  form.post(route("goali.store"), {
    preserveScroll: true,
    onSuccess: () => form.reset("travel_time", "status", "solicitation", "origin", "destination", "passenger", "amount"),
  });
};
</script>

<template>
  <BaseLayout title="GT DIONÍNIO BARBOSA">
    <div class="w-full mx-auto pt-2">
      <div class="rounded-xl bg-white p-4 shadow-lg dark:bg-gray-900 sm:p-6">
        <header class="mb-6 border-b border-gray-200 pb-4 dark:border-gray-700">
          <h1 class="text-xl font-semibold text-gray-900 dark:text-white">GT DIONÍNIO BARBOSA</h1>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Registro de deslocamento</p>
        </header>

        <form class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2" @submit.prevent="submit">
          <div>
            <label for="travel_date" class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Data <span>*</span></label>
            <TextInput id="travel_date" v-model="form.travel_date" type="date" class="mt-1 block w-full" required />
            <InputError :message="form.errors.travel_date" class="mt-2" />
          </div>
          <div>
            <label for="travel_time" class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Horário <span>*</span></label>
            <TextInput id="travel_time" v-model="form.travel_time" type="time" class="mt-1 block w-full" required />
            <InputError :message="form.errors.travel_time" class="mt-2" />
          </div>

          <fieldset>
            <legend class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Status <span>*</span></legend>
            <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2">
              <label v-for="status in ['ENTRADA', 'SAÍDA']" :key="status" class="choice-label">
                <input
                  v-model="form.status"
                  type="radio"
                  name="status"
                  :value="status"
                  required
                  class="border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
                />
                <span class="font-medium text-sm text-gray-800 dark:text-neutral-200">{{ status }}</span>
              </label>
            </div>
            <InputError :message="form.errors.status" class="mt-2" />
          </fieldset>
          <div>
            <label for="solicitation" class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Solicitação / empresa <span>*</span></label>
            <Select
              id="solicitation"
              v-model="form.solicitation"
              :options="props.solicitations"
              class="mt-1 block w-full"
              placeholder="Selecione uma empresa"
              :allow-empty="false"
              required
            />
            <InputError :message="form.errors.solicitation" class="mt-2" />
          </div>

          <div>
            <label for="origin" class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Origem <span>*</span></label>
            <TextInput id="origin" v-model="form.origin" type="text" class="mt-1 block w-full" readonly required />
            <p class="mt-1 text-xs text-gray-500">Preenchida automaticamente pela empresa selecionada.</p>
            <InputError :message="form.errors.origin" class="mt-2" />
          </div>
          <div>
            <label for="destination" class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Destino <span>*</span></label>
            <TextInput id="destination" v-model="form.destination" type="text" class="mt-1 block w-full" maxlength="255" required />
            <InputError :message="form.errors.destination" class="mt-2" />
          </div>
          <div>
            <label for="passenger" class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Passageiro <span>*</span></label>
            <TextInput id="passenger" v-model="form.passenger" type="text" class="mt-1 block w-full" maxlength="255" required />
            <InputError :message="form.errors.passenger" class="mt-2" />
          </div>
          <div>
            <label for="responsible" class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Usuário responsável <span>*</span></label>
            <TextInput
              id="responsible"
              v-model="form.responsible"
              type="text"
              class="mt-1 block w-full"
              maxlength="255"
              required
              :readonly="Boolean(props.responsibleUser)"
            />
            <InputError :message="form.errors.responsible" class="mt-2" />
          </div>
          <div>
            <label for="amount" class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Valor (R$) <span>*</span></label>
            <TextInput
              id="amount"
              v-model="form.amount"
              type="number"
              class="mt-1 block w-full"
              min="0"
              step="0.01"
              inputmode="decimal"
              placeholder="0,00"
              required
              @blur="formatAmount"
            />
            <InputError :message="form.errors.amount" class="mt-2" />
          </div>

          <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 md:col-span-2">
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
              {{ form.processing ? "Enviando..." : "Enviar registro" }}
            </PrimaryButton>
            <button
              type="reset"
              class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
              @click="form.reset()"
            >
              Limpar formulário
            </button>
            <p v-if="successMessage" class="text-sm text-green-700 dark:text-green-400" role="status">
              {{ successMessage }}
            </p>
          </div>
        </form>
      </div>
    </div>
  </BaseLayout>
</template>

