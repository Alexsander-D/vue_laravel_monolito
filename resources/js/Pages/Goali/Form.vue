<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
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
            <label for="travel_date" class="field-label">Data <span>*</span></label>
            <input id="travel_date" v-model="form.travel_date" type="date" class="field-input" required />
            <p v-if="form.errors.travel_date" class="field-error">{{ form.errors.travel_date }}</p>
          </div>
          <div>
            <label for="travel_time" class="field-label">Horário <span>*</span></label>
            <input id="travel_time" v-model="form.travel_time" type="time" class="field-input" required />
            <p v-if="form.errors.travel_time" class="field-error">{{ form.errors.travel_time }}</p>
          </div>

          <fieldset>
            <legend class="field-label">Status <span>*</span></legend>
            <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2">
              <label v-for="status in ['ENTRADA', 'SAÍDA']" :key="status" class="choice-label">
                <input v-model="form.status" type="radio" name="status" :value="status" required />
                <span>{{ status }}</span>
              </label>
            </div>
            <p v-if="form.errors.status" class="field-error">{{ form.errors.status }}</p>
          </fieldset>
          <div>
            <label for="solicitation" class="field-label">Solicitação / empresa <span>*</span></label>
            <select id="solicitation" v-model="form.solicitation" class="field-input" required>
              <option disabled value="">Selecione uma empresa</option>
              <option v-for="company in props.solicitations" :key="company" :value="company">{{ company }}</option>
            </select>
            <p v-if="form.errors.solicitation" class="field-error">{{ form.errors.solicitation }}</p>
          </div>

          <div>
            <label for="origin" class="field-label">Origem <span>*</span></label>
            <input id="origin" v-model="form.origin" type="text" class="field-input" readonly required />
            <p class="mt-1 text-xs text-gray-500">Preenchida automaticamente pela empresa selecionada.</p>
            <p v-if="form.errors.origin" class="field-error">{{ form.errors.origin }}</p>
          </div>
          <div>
            <label for="destination" class="field-label">Destino <span>*</span></label>
            <input id="destination" v-model="form.destination" type="text" class="field-input" maxlength="255" required />
            <p v-if="form.errors.destination" class="field-error">{{ form.errors.destination }}</p>
          </div>
          <div>
            <label for="passenger" class="field-label">Passageiro <span>*</span></label>
            <input id="passenger" v-model="form.passenger" type="text" class="field-input" maxlength="255" required />
            <p v-if="form.errors.passenger" class="field-error">{{ form.errors.passenger }}</p>
          </div>
          <div>
            <label for="responsible" class="field-label">Usuário responsável <span>*</span></label>
            <input
              id="responsible"
              v-model="form.responsible"
              type="text"
              class="field-input"
              maxlength="255"
              required
              :readonly="Boolean(props.responsibleUser)"
            />
            <p v-if="form.errors.responsible" class="field-error">{{ form.errors.responsible }}</p>
          </div>
          <div>
            <label for="amount" class="field-label">Valor (R$) <span>*</span></label>
            <input
              id="amount"
              v-model="form.amount"
              type="number"
              class="field-input"
              min="0"
              step="0.01"
              inputmode="decimal"
              placeholder="0,00"
              required
              @blur="formatAmount"
            />
            <p v-if="form.errors.amount" class="field-error">{{ form.errors.amount }}</p>
          </div>

          <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 md:col-span-2">
            <button
              type="submit"
              class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
              :disabled="form.processing"
            >
              {{ form.processing ? "Enviando..." : "Enviar registro" }}
            </button>
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

<style scoped>
.field-label { display: block; color: #1f2937; font-size: .875rem; font-weight: 500; }
.field-label span { color: #dc2626; }
.field-input { display: block; width: 100%; margin-top: .375rem; border: 1px solid #d1d5db; border-radius: .375rem; background: white; padding: .5rem .75rem; color: #111827; font-size: .875rem; box-shadow: 0 1px 2px rgb(0 0 0 / 5%); }
.field-input:focus { border-color: #3b82f6; outline: 2px solid transparent; box-shadow: 0 0 0 2px rgb(59 130 246 / 25%); }
.choice-label { display: inline-flex; align-items: center; gap: .5rem; color: #374151; font-size: .875rem; cursor: pointer; }
.choice-label input { width: 1rem; height: 1rem; accent-color: #2563eb; }
.field-error { margin-top: .375rem; color: #dc2626; font-size: .8rem; }
</style>
