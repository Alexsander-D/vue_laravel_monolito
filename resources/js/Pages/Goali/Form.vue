<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import Select from "@/Components/Select.vue";
import TextInput from "@/Components/TextInput.vue";
import TextArea from "@/Components/TextArea.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Radio from "@/Components/Radio.vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Swal from "sweetalert2";

const props = defineProps({
  solicitations: { type: Array, default: () => [] },
  responsibleUser: { type: String, default: "" },
  initialTripStatus: { type: String, default: "Criada" },
  currentUserId: { type: Number, default: null },
  drivers: { type: Array, default: () => [] },
  initialDriverId: { type: Number, default: null },
  isDriver: { type: Boolean, default: false },
  hasInProgressTrip: { type: Boolean, default: false },
  isAdmin: { type: Boolean, default: false },
  canAssignDrivers: { type: Boolean, default: false },
  canManageDirectory: { type: Boolean, default: false },
  companies: { type: Array, default: () => [] },
  passengers: { type: Array, default: () => [] },
});

const currentDateTime = () => {
  const now = new Date();
  const pad = (value) => String(value).padStart(2, "0");

  return {
    date: `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`,
    time: `${pad(now.getHours())}:${pad(now.getMinutes())}`,
  };
};
const earliestTravelDate = () => {
  const date = new Date();
  if (props.isDriver) {
    date.setDate(date.getDate() - 1);
  }
  const pad = (value) => String(value).padStart(2, "0");

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};
const initialDateTime = currentDateTime();
const form = useForm({
  travel_start_date: initialDateTime.date,
  travel_end_date: initialDateTime.date,
  travel_time: initialDateTime.time,
  status: "",
  solicitation: "",
  company_id: null,
  passenger_ids: [],
  trip_code: "",
  driver_id: props.initialDriverId,
  origin: "",
  destination: "",
  passenger: "",
  observation: "",
  responsible: props.responsibleUser,
  amount: "",
});
const page = usePage();
const successMessage = computed(() => page.props.flash?.success || "");
const initialTripStatus = computed(() => (
  props.canAssignDrivers && form.driver_id && Number(form.driver_id) !== Number(props.currentUserId)
    ? "Criada"
    : props.canAssignDrivers
      ? "Em Andamento"
      : props.initialTripStatus
));
const selectedDriver = computed({
  get: () => props.drivers.find((driver) => driver.id === Number(form.driver_id)) || null,
  set: (driver) => {
    form.driver_id = driver?.id ?? null;
  },
});
const selectedCompany = computed({
  get: () => props.companies.find((company) => company.id === Number(form.company_id)) || null,
  set: (company) => {
    form.company_id = company?.id ?? null;
    form.solicitation = company?.name ?? "";
  },
});
const availablePassengers = computed(() => props.passengers.filter((passenger) => Number(passenger.company_id) === Number(form.company_id)));
const selectedPassengers = computed({
  get: () => availablePassengers.value.filter((passenger) => form.passenger_ids.includes(Number(passenger.id))),
  set: (passengers) => {
    form.passenger_ids = (Array.isArray(passengers) ? passengers : []).map((passenger) => Number(passenger.id));
  },
});
const hasCompanyPassengers = computed(() => availablePassengers.value.length > 0);
const useRegisteredCompany = ref(props.companies.length > 0);
watch(() => form.company_id, (companyId) => {
  const company = props.companies.find((item) => Number(item.id) === Number(companyId));
  if (company?.departure_address) {
    form.origin = company.departure_address;
  }
  form.passenger_ids = [];
  form.passenger = "";
});
watch(() => form.passenger_ids, (passengerIds) => {
  const selected = props.passengers.filter((passenger) => passengerIds.includes(Number(passenger.id)));
  form.passenger = selected.map((passenger) => passenger.name).join("\n");
});
const createWithoutDriver = ref(false);
const previousDriverId = ref(props.initialDriverId);
const enableTripCode = ref(false);

const toggleCompanyMode = (event = null) => {
  if (event?.target) {
    useRegisteredCompany.value = Boolean(event.target.checked);
  }

  // Ao sair do cadastro, a solicitação e o passageiro passam a ser informados
  // manualmente. Sem empresa selecionada, o seletor de passageiros nunca deve
  // aparecer.
  form.company_id = null;
  form.passenger_ids = [];
  form.passenger = "";
  form.solicitation = "";
};

const toggleWithoutDriver = (event) => {
  if (event.target.checked) {
    previousDriverId.value = form.driver_id ?? props.initialDriverId;
    form.driver_id = null;
    return;
  }

  form.driver_id = previousDriverId.value;
};

const toggleTripCode = () => {
  if (!enableTripCode.value) {
    form.trip_code = "";
  }
};

const formatAmount = () => {
  const amount = Number(String(form.amount).replace(",", "."));
  if (form.amount !== "" && Number.isFinite(amount)) {
    form.amount = amount.toFixed(2);
  }
};

const showInProgressTripAlert = () => Swal.fire({
  title: "Corrida em andamento",
  text: "Finalize ou envie sua corrida atual para aprovação antes de iniciar outra.",
  icon: "warning",
  confirmButtonText: "Entendi",
  confirmButtonColor: "#0891b2",
});

const submit = () => {
  if (props.isDriver && props.hasInProgressTrip) {
    showInProgressTripAlert();
    return;
  }

  if (useRegisteredCompany.value && !form.company_id) {
    form.setError("company_id", "Selecione uma empresa cadastrada.");
    return;
  }

  if (!useRegisteredCompany.value && !form.solicitation) {
    form.setError("solicitation", "Selecione uma empresa.");
    return;
  }

  form.post(route("goali.store"), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset(
        "status",
        "solicitation",
        "company_id",
        "passenger_ids",
        "trip_code",
        "driver_id",
        "origin",
        "destination",
        "passenger",
        "observation",
        "amount"
      );
      createWithoutDriver.value = false;
      enableTripCode.value = false;
      useRegisteredCompany.value = props.companies.length > 0;
      previousDriverId.value = props.initialDriverId;
      form.driver_id = props.initialDriverId;
      const dateTime = currentDateTime();
      form.travel_start_date = dateTime.date;
      form.travel_end_date = dateTime.date;
      form.travel_time = dateTime.time;
    },
    onError: (errors) => {
      if (errors.active_trip) {
        showInProgressTripAlert();
      }
    },
  });
};
</script>

<template>
  <BaseLayout title="CRIAR NOVA CORRIDA">
    <main class="col-span-12 w-full px-3 py-5 sm:px-6 lg:px-8">
      <section
        class="overflow-hidden rounded-2xl border border-cyan-100 bg-white shadow-lg dark:border-cyan-900/50 dark:bg-gray-900">
        <div class="p-5 sm:p-6">
          <header
            class="mb-4 border-b border-cyan-100 bg-cyan-50/70 px-4 py-5 text-center dark:border-cyan-900/50 dark:bg-cyan-950/30 sm:mb-6 sm:pb-5">
            <Link :href="route('goali.index')"
              class="mb-4 flex w-fit min-h-11 items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-700 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-800">
              <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Voltar ao relatório
            </Link>
            <div v-if="props.canManageDirectory" class="mb-4 flex flex-wrap gap-2">
              <Link :href="route('goali.companies.index')"
                class="inline-flex min-h-11 items-center rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                Cadastrar empresa</Link>
              <Link :href="route('goali.passengers.index')"
                class="inline-flex min-h-11 items-center rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                Cadastrar passageiro</Link>
            </div>

            <h1 class="text-xl font-bold uppercase text-gray-900 dark:text-white sm:text-2xl">CRIAR NOVA CORRIDA</h1>
            <div class="mx-auto mt-3 h-1 w-14 rounded-full bg-cyan-600" aria-hidden="true"></div>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
              Registro de deslocamento do {{ props.responsibleUser || "usuário" }}
            </p>
          </header>

          <form class="grid min-w-0 grid-cols-1 gap-x-4 gap-y-4 sm:gap-x-6 sm:gap-y-5 md:grid-cols-2"
            @submit.prevent="submit">

            <div>
              <label for="responsible"
                class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Usuário responsável
                <span>*</span></label>
              <TextInput id="responsible" v-model="form.responsible" type="text" class="mt-1 block w-full"
                maxlength="255" required :readonly="Boolean(props.responsibleUser)" />
              <InputError :message="form.errors.responsible" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-3 md:col-span-2 md:gap-4">
              <div>
                <label for="travel_start_date"
                  class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Data inicial
                  <span>*</span></label>
                <TextInput id="travel_start_date" v-model="form.travel_start_date" type="date" class="mt-1 block w-full"
                  :min="earliestTravelDate()" required />
                <InputError :message="form.errors.travel_start_date" class="mt-2" />
              </div>
              <div>
                <label for="travel_end_date"
                  class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Data final
                  <span>*</span></label>
                <TextInput id="travel_end_date" v-model="form.travel_end_date" type="date" class="mt-1 block w-full"
                  :min="form.travel_start_date > earliestTravelDate() ? form.travel_start_date : earliestTravelDate()"
                  required />
                <InputError :message="form.errors.travel_end_date" class="mt-2" />
              </div>
            </div>
            <div>
              <label for="travel_time"
                class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Horário
                <span>*</span></label>
              <TextInput id="travel_time" v-model="form.travel_time" type="time" class="mt-1 block w-full" required />
              <InputError :message="form.errors.travel_time" class="mt-2" />
            </div>

            <fieldset>
              <legend class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Status
                <span>*</span>
              </legend>
              <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2">
                <Radio v-for="status in ['ENTRADA', 'SAÍDA']" :key="status" v-model="form.status" :value="status"
                  :label="status" name="status" required />
              </div>
              <InputError :message="form.errors.status" class="mt-2" />
            </fieldset>
            <div>
              <label for="initial_trip_status"
                class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Status
                inicial</label>
              <TextInput id="initial_trip_status" :model-value="initialTripStatus" type="text" class="mt-1 block w-full"
                readonly />
            </div>
            <div>
              <label for="solicitation"
                class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Solicitação /
                Empresa
                <span>*</span></label>
              <label class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input v-model="useRegisteredCompany" type="checkbox" class="rounded border-gray-300 text-cyan-700"
                  :disabled="!props.companies.length" @change="toggleCompanyMode" />
                Usar empresa cadastrada
              </label>
              <div v-if="useRegisteredCompany" class="mt-1">
                <Select id="company_id" v-model="selectedCompany" :options="props.companies"
                  label="name" track-by="id" class="block w-full" placeholder="Selecione uma empresa"
                  :allow-empty="false" :select2="true" required />
              </div>
              <div v-else class="mt-1">
                <TextInput id="solicitation" v-model="form.solicitation" type="text" class="block w-full"
                  maxlength="150" placeholder="Informe o nome da empresa" required />
              </div>
              <p v-if="!props.companies.length" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nenhuma empresa
                cadastrada. Informe o nome manualmente.</p>
              <p v-else class="mt-1 text-xs text-gray-500 dark:text-gray-400">Escolha uma empresa cadastrada para usar
                os passageiros vinculados ou desmarque para informar os dados manualmente.</p>
              <InputError :message="form.errors.solicitation" class="mt-2" />
              <InputError :message="form.errors.company_id" class="mt-2" />
            </div>
            <div>
              <label for="driver_id"
                class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Motorista
                <span v-if="!createWithoutDriver">*</span></label>
              <TextInput v-if="props.isDriver" id="driver_id" :model-value="selectedDriver?.name || props.responsibleUser"
                type="text" class="mt-1 block w-full" disabled />
              <Select v-else id="driver_id" v-model="selectedDriver" :options="props.drivers" label="name" track-by="id"
                placeholder="Selecione um motorista" class="mt-1 block w-full" :allow-empty="props.isAdmin"
                :select2="true" :disabled="!props.canAssignDrivers || createWithoutDriver"
                :required="!createWithoutDriver" />
              <InputError :message="form.errors.driver_id" class="mt-2" />
              <label v-if="props.isAdmin" class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input v-model="createWithoutDriver" type="checkbox" class="rounded border-gray-300 text-cyan-700"
                  @change="toggleWithoutDriver" />
                Criar corrida sem motorista
              </label>
            </div>

            <div>
              <label for="trip_code"
                class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Código da
                Viagem</label>
              <label for="enable_trip_code"
                class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input id="enable_trip_code" v-model="enableTripCode" type="checkbox"
                  class="rounded border-gray-300 text-cyan-700" @change="toggleTripCode" />
                Habilitar código da viagem
              </label>
              <TextInput id="trip_code" v-model="form.trip_code" type="text" class="mt-1 block w-full" maxlength="255"
                :disabled="!enableTripCode" />
              <InputError :message="form.errors.trip_code" class="mt-2" />
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
              <TextInput id="destination" v-model="form.destination" type="text" class="mt-1 block w-full"
                maxlength="255" required />
              <InputError :message="form.errors.destination" class="mt-2" />
            </div>
            <div class="md:col-span-2">
              <label for="observation"
                class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Observação</label>
              <TextArea id="observation" v-model="form.observation" class="mt-1 block w-full"
                maxlength="5000" autocomplete="off" placeholder="Informe observações sobre a corrida" />
              <InputError :message="form.errors.observation" class="mt-2" />
            </div>
            <div>
              <label for="passenger"
                class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">Passageiro
                <span>*</span></label>
              <div v-show="hasCompanyPassengers" class="mt-1">
                <Select id="passenger_ids" v-model="selectedPassengers" :options="availablePassengers" label="name"
                  track-by="id" class="block w-full" placeholder="Selecione um ou mais passageiros"
                  :allow-empty="false" :select2="true" :multiple="true" :required="hasCompanyPassengers" />
              </div>
              <div v-show="!hasCompanyPassengers" class="mt-1">
                <TextArea id="passenger" v-model="form.passenger" class="block w-full"
                  placeholder="Informe os passageiros" :required="!hasCompanyPassengers" />
              </div>
              <p v-if="hasCompanyPassengers" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Você pode selecionar
                vários passageiros.</p>
              <InputError :message="form.errors.passenger" class="mt-2" />
              <InputError :message="form.errors.passenger_ids" class="mt-2" />
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
              class="flex flex-col-reverse gap-2 border-t border-gray-200 pt-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-end sm:gap-3 md:col-span-2">
              <PrimaryButton class="min-h-11 w-full justify-center sm:w-auto" :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing">
                {{ form.processing ? "Enviando..." : "Enviar registro" }}
              </PrimaryButton>
              <button type="reset"
                class="min-h-11 w-full rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800 sm:w-auto"
                @click="form.reset(); useRegisteredCompany = props.companies.length > 0; toggleCompanyMode()">
                Limpar formulário
              </button>
              <p v-if="successMessage" class="text-sm text-green-700 dark:text-green-400" role="status">
                {{ successMessage }}
              </p>
            </div>
          </form>
        </div>
      </section>
    </main>
  </BaseLayout>
</template>
