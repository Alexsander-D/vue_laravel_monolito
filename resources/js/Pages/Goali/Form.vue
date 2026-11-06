<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import Select from "@/Components/Select.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Radio from "@/Components/Radio.vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
  solicitations: { type: Array, default: () => [] },
  responsibleUser: { type: String, default: "" },
  initialTripStatus: { type: String, default: "Criada" },
  drivers: { type: Array, default: () => [] },
  initialDriverId: { type: Number, default: null },
  isAdmin: { type: Boolean, default: false },
});

const currentDateTime = () => {
  const now = new Date();
  const pad = (value) => String(value).padStart(2, "0");

  return {
    date: `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`,
    time: `${pad(now.getHours())}:${pad(now.getMinutes())}`,
  };
};
const initialDateTime = currentDateTime();
const form = useForm({
  travel_start_date: initialDateTime.date,
  travel_end_date: initialDateTime.date,
  travel_time: initialDateTime.time,
  status: "",
  solicitation: "",
  driver_id: props.initialDriverId,
  origin: "",
  destination: "",
  passenger: "",
  responsible: props.responsibleUser,
  amount: "",
});
const page = usePage();
const successMessage = computed(() => page.props.flash?.success || "");
const selectedDriver = computed({
  get: () => props.drivers.find((driver) => driver.id === Number(form.driver_id)) || null,
  set: (driver) => {
    form.driver_id = driver?.id ?? null;
  },
});
const createWithoutDriver = ref(false);
const previousDriverId = ref(props.initialDriverId);

const toggleWithoutDriver = (event) => {
  if (event.target.checked) {
    previousDriverId.value = form.driver_id ?? props.initialDriverId;
    form.driver_id = null;
    return;
  }

  form.driver_id = previousDriverId.value;
};

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
    onSuccess: () => {
      form.reset(
        "status",
        "solicitation",
        "driver_id",
        "origin",
        "destination",
        "passenger",
        "amount"
      );
      createWithoutDriver.value = false;
      previousDriverId.value = props.initialDriverId;
      form.driver_id = props.initialDriverId;
      const dateTime = currentDateTime();
      form.travel_start_date = dateTime.date;
      form.travel_end_date = dateTime.date;
      form.travel_time = dateTime.time;
    },
  });
};
</script>

<template>
  <BaseLayout title="CORRIDAS">
    <div class="w-full mx-auto pt-2">
      <div class="rounded-xl bg-white p-4 shadow-lg dark:bg-gray-900 sm:p-6">
        <header class="mb-6 border-b border-gray-200 pb-5 text-center dark:border-gray-700">
          <h1 class="text-2xl font-bold uppercase text-gray-900 dark:text-white">CORRIDAS</h1>
          <div class="mx-auto mt-3 h-1 w-14 rounded-full bg-blue-600" aria-hidden="true"></div>
          <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
            Registro de deslocamento do {{ props.responsibleUser || "usuário" }}
          </p>
        </header>

        <form class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2" @submit.prevent="submit">

          <div>
            <label for="responsible"
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Usuário responsável
              <span>*</span></label>
            <TextInput id="responsible" v-model="form.responsible" type="text" class="mt-1 block w-full" maxlength="255"
              required :readonly="Boolean(props.responsibleUser)" />
            <InputError :message="form.errors.responsible" class="mt-2" />
          </div>

          <div class="grid grid-cols-1 gap-4 md:col-span-2 md:grid-cols-2">
            <div>
              <label for="travel_start_date"
                class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Data inicial
                <span>*</span></label>
              <TextInput id="travel_start_date" v-model="form.travel_start_date" type="date"
                class="mt-1 block w-full" required />
              <InputError :message="form.errors.travel_start_date" class="mt-2" />
            </div>
            <div>
              <label for="travel_end_date"
                class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Data final
                <span>*</span></label>
              <TextInput id="travel_end_date" v-model="form.travel_end_date" type="date"
                class="mt-1 block w-full" :min="form.travel_start_date" required />
              <InputError :message="form.errors.travel_end_date" class="mt-2" />
            </div>
          </div>
          <div>
            <label for="travel_time"
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Horário
              <span>*</span></label>
            <TextInput id="travel_time" v-model="form.travel_time" type="time"
              class="mt-1 block w-full" required />
            <InputError :message="form.errors.travel_time" class="mt-2" />
          </div>

          <fieldset>
            <legend class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Status
              <span>*</span></legend>
            <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2">
              <Radio v-for="status in ['ENTRADA', 'SAÍDA']" :key="status" v-model="form.status" :value="status"
                :label="status" name="status" required />
            </div>
            <InputError :message="form.errors.status" class="mt-2" />
          </fieldset>
          <div>
            <label for="initial_trip_status"
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Status inicial</label>
            <TextInput id="initial_trip_status" :model-value="props.initialTripStatus" type="text"
              class="mt-1 block w-full" readonly />
          </div>
          <div>
            <label for="solicitation"
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Solicitação / Empresa
              <span>*</span></label>
            <Select id="solicitation" v-model="form.solicitation" :options="props.solicitations"
              class="mt-1 block w-full" placeholder="Selecione uma empresa" :allow-empty="false" :select2="true" required />
            <InputError :message="form.errors.solicitation" class="mt-2" />
          </div>
          <div>
            <label for="driver_id"
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Motorista
              <span v-if="!createWithoutDriver">*</span></label>
            <Select id="driver_id" v-model="selectedDriver" :options="props.drivers" label="name" track-by="id"
              placeholder="Selecione um motorista" class="mt-1 block w-full" :allow-empty="props.isAdmin"
              :select2="true" :disabled="!props.isAdmin || createWithoutDriver" :required="!createWithoutDriver" />
            <InputError :message="form.errors.driver_id" class="mt-2" />
            <label v-if="props.isAdmin" class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <input v-model="createWithoutDriver" type="checkbox" class="rounded border-gray-300 text-teal-700" @change="toggleWithoutDriver" />
              Criar corrida sem motorista
            </label>
          </div>

          <div>
            <label for="origin"
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Origem
              <span>*</span></label>
            <TextInput id="origin" v-model="form.origin" type="text" class="mt-1 block w-full" maxlength="255"
              required />
            <InputError :message="form.errors.origin" class="mt-2" />
          </div>
          <div>
            <label for="destination"
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Destino
              <span>*</span></label>
            <TextInput id="destination" v-model="form.destination" type="text" class="mt-1 block w-full" maxlength="255"
              required />
            <InputError :message="form.errors.destination" class="mt-2" />
          </div>
          <div>
            <label for="passenger"
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Passageiro
              <span>*</span></label>
            <TextInput id="passenger" v-model="form.passenger" type="text" class="mt-1 block w-full" maxlength="255"
              required />
            <InputError :message="form.errors.passenger" class="mt-2" />
          </div>
          <div>
            <label for="amount"
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Valor (R$)
              <span>*</span></label>
            <TextInput id="amount" v-model="form.amount" type="number" class="mt-1 block w-full" min="0" step="0.01"
              inputmode="decimal" placeholder="0,00" required @blur="formatAmount" />
            <InputError :message="form.errors.amount" class="mt-2" />
          </div>

          <div
            class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 md:col-span-2">
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
              {{ form.processing ? "Enviando..." : "Enviar registro" }}
            </PrimaryButton>
            <button type="reset"
              class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
              @click="form.reset()">
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
