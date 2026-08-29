<script setup>
import $ from "jquery";
import BaseLayout from "@/Layouts/BaseLayout.vue";
import DateFilter from "@/Components/DateFilter.vue";
import Datatable from "@/Components/Datatable.vue";
import { computed, onMounted } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { router, useForm, usePage } from "@inertiajs/vue3";
import Swal from "sweetalert2";

const props = defineProps({
  records: {
    type: Array,
    default: () => [],
  },
  date: {
    type: Object,
    default: () => ({
      startDate: "",
      endDate: "",
      paymentMethod: "",
    }),
  },
});

const paymentMethods = ["Dinheiro", "Cartão", "Pix"];

const serviceCatalog = [
  { name: "Corte Social", price: 35 },
  { name: "Corte Degradê", price: 40 },
  { name: "Corte Navalhado", price: 45 },
  { name: "Barba", price: 20 },
  { name: "Bigode", price: 10 },
  { name: "Pezinho", price: 10 },
  { name: "Sobrancelha", price: 10 },
  { name: "Pigmentação", price: 30 },
  { name: "Luzes", price: 100 },
  { name: "Nevou", price: 120 },
];

const getToday = () => new Date().toISOString().slice(0, 10);

const form = useForm({
  startDate: props.date?.startDate || getToday(),
  endDate: props.date?.endDate || getToday(),
  paymentMethod: props.date?.paymentMethod || "",
});

const page = usePage();
const currentUserRole = computed(() => page.props.userRole || "");
const isAdmin = computed(() => currentUserRole.value === "Admin");
console.log("Current User Role:", currentUserRole.value , "Is Admin:", isAdmin.value);

const submitFilters = () => {
  form.get(route("attendance.report"), {
    preserveScroll: true,
    preserveState: true,
  });
};

const sendProductivityEmail = async () => {
  const result = await Swal.fire({
    title: "Enviar produtividade por e-mail?",
    text: "O relatório atual será enviado por e-mail aos responsáveis.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Enviar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#2563eb",
  });

  if (!result.isConfirmed) {
    return;
  }

  router.post(
    route("attendance.sendProductivityReport"),
    {
      startDate: form.startDate,
      endDate: form.endDate,
      paymentMethod: form.paymentMethod,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        Swal.fire({
          icon: "success",
          title: "Produtividade enviada!",
          timer: 3000,
          showConfirmButton: false,
        });
      },
      onError: () => {
        Swal.fire({
          icon: "error",
          title: "Erro ao enviar o e-mail.",
        });
      },
    }
  );
};

const totalPrice = computed(() => {
  return props.records.reduce((sum, record) => sum + Number(record.price || 0), 0);
});

const tableId = "AttendanceReport";

const tableHeaders = computed(() => {
  const headers = [
    { name: "Data" },
    { name: "Barbeiro" },
    { name: "Serviço" },
    { name: "Pagamento" },
    { name: "Preço (R$)" },
  ];

  if (isAdmin.value) {
    headers.push({ name: "Ações" });
  }

  return headers;
});

const tableData = computed(() => {
  return props.records.map((record) => {
    const row = {
      date: formatDateTime(record.created_at),
      barber: record.user_name,
      service: record.service_name,
      payment: record.payment_method || "Não informado",
      price: formatCurrency(record.price),
    };

    if (isAdmin.value) {
      const currentPaymentMethod = record.payment_method || "Dinheiro";
      const currentServiceName = typeof record.service_name === "string" ? record.service_name.split(",")[0].trim() : "";
      const currentServicePrice = Number(record.price || 0);

      row.button1 = `
        <div class="flex justify-end gap-2">
          <button
            type="button"
            data-id="${record.attendance_id}"
            data-payment="${currentPaymentMethod}"
            data-service="${currentServiceName}"
            data-price="${currentServicePrice}"
            class="edit-btn rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            Editar
          </button>
          <button type="button" data-id="${record.attendance_id}" class="delete-btn rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500">
            Excluir
          </button>
        </div>
      `;
    }

    return row;
  });
});

const tableFooter = computed(() => {
  const row = {
    date: "",
    barber: "",
    service: "",
    payment: "Total",
    price: formatCurrency(totalPrice.value),
  };

  if (isAdmin.value) {
    row.button1 = "";
  }

  return [row];
});

const idleHours = computed(() => {
  const hoursWithAttendance = new Set();

  props.records.forEach((record) => {
    const date = new Date(record.created_at);
    const hour = date.getHours();
    hoursWithAttendance.add(hour);
  });

  const emptyHours = [];
  for (let hour = 9; hour < 18; hour++) {
    if (!hoursWithAttendance.has(hour)) {
      emptyHours.push(hour);
    }
  }

  return emptyHours.map((hour) => {
    const hourStr = String(hour).padStart(2, "0");
    return `${hourStr}:00 - ${String(hour + 1).padStart(2, "0")}:00`;
  });
});

const idleHoursTotal = computed(() => {
  return idleHours.value.length;
});

const formatDateTime = (value) => {
  if (!value) {
    return "-";
  }

  const formatted = new Date(value).toLocaleString("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
  });

  return formatted.replace(", ", " - ");
};

const formatCurrency = (value) => {
  const formatted = Number(value || 0).toLocaleString("pt-BR", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  return `R$ ${formatted}`;
};

const deleteAttendance = async (attendanceId) => {
  const result = await Swal.fire({
    title: "Excluir atendimento?",
    text: "Esta ação removerá o atendimento e seus serviços.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sim, excluir",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#dc2626",
  });

  if (!result.isConfirmed) {
    return;
  }

  router.delete(route("attendance.destroy", { attendance: attendanceId }), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: "success",
        title: "Atendimento excluído!",
        timer: 3000,
        showConfirmButton: false,
      });
      Inertia.reload({ only: ["records"] });
    },
    onError: () => {
      Swal.fire({
        icon: "error",
        title: "Não foi possível excluir o atendimento.",
      });
    },
  });
};

const editAttendance = async (attendanceId, currentPaymentMethod, currentServiceName = "", currentServicePrice = 0) => {
  const paymentOptions = {
    Dinheiro: "Dinheiro",
    Cartão: "Cartão",
    Pix: "Pix",
  };

  const serviceOptions = serviceCatalog.reduce((options, service) => {
    options[service.name] = `${service.name} - ${formatCurrency(service.price)}`;
    return options;
  }, {});

  const defaultServiceName = serviceCatalog.some((service) => service.name === currentServiceName)
    ? currentServiceName
    : serviceCatalog[0]?.name || "";

  const selectedService = serviceCatalog.find((service) => service.name === defaultServiceName) || serviceCatalog[0] || { name: "", price: 0 };

  const { isConfirmed, value } = await Swal.fire({
    title: "Editar atendimento",
    html: `
      <div class="text-left">
        <div class="mb-4">
          <label class="mb-2 block text-sm font-medium text-gray-700">Serviço</label>
          <select id="attendance-service-edit" class="swal2-input text-left">
            ${Object.entries(serviceOptions)
              .map(([name, label]) => `
                <option value="${name}" ${name === defaultServiceName ? "selected" : ""}>
                  ${label}
                </option>
              `)
              .join("")}
          </select>
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Pagamento</label>
          <select id="attendance-payment-edit" class="swal2-input text-left">
            ${Object.entries(paymentOptions)
              .map(([key, label]) => `
                <option value="${key}" ${key === currentPaymentMethod ? "selected" : ""}>
                  ${label}
                </option>
              `)
              .join("")}
          </select>
        </div>
      </div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: "Salvar",
    cancelButtonText: "Cancelar",
    preConfirm: () => {
      const selectedServiceName = document.getElementById("attendance-service-edit")?.value || "";
      const selectedPaymentMethod = document.getElementById("attendance-payment-edit")?.value || "";

      if (!selectedServiceName || !selectedPaymentMethod) {
        Swal.showValidationMessage("Selecione o serviço e a forma de pagamento.");
        return false;
      }

      const selectedService = serviceCatalog.find((service) => service.name === selectedServiceName) || { name: selectedServiceName, price: Number(currentServicePrice || 0) };

      return {
        payment_method: selectedPaymentMethod,
        service_name: selectedService.name,
        service_price: Number(selectedService.price || 0),
      };
    },
  });

  if (!isConfirmed || !value) {
    return;
  }

  router.put(
    route("attendance.update", { attendance: attendanceId }),
    value,
    {
      preserveScroll: true,
      onSuccess: () => {
        Swal.fire({
          icon: "success",
          title: "Atendimento atualizado!",
          timer: 3000,
          showConfirmButton: false,
        });
        Inertia.reload({ only: ["records"] });
      },
      onError: () => {
        Swal.fire({
          icon: "error",
          title: "Não foi possível atualizar o atendimento.",
        });
      },
    }
  );
};

onMounted(() => {
  $(document).off("click", ".delete-btn, .edit-btn");

  $(document).on("click", ".delete-btn", function () {
    const attendanceId = $(this).data("id");
    deleteAttendance(attendanceId);
  });

  $(document).on("click", ".edit-btn", function () {
    const attendanceId = $(this).data("id");
    const currentPaymentMethod = $(this).data("payment");
    const currentServiceName = $(this).data("service") || "";
    const currentServicePrice = Number($(this).data("price") || 0);
    editAttendance(attendanceId, currentPaymentMethod, currentServiceName, currentServicePrice);
  });
});
</script>

<template>
  <BaseLayout :title="'producao_diaria_' + form.startDate + '_' + form.endDate">
    <div class="w-full mx-auto pt-1">
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="flex-1">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
              <DateFilter v-if="isAdmin" v-model:startDate="form.startDate" v-model:endDate="form.endDate" @submit="submitFilters" />
              <div v-if="isAdmin" class="mt-8 w-full lg:mt-0 lg:ml-4 lg:max-w-xs">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                  Método de pagamento
                </label>
                <select
                  v-model="form.paymentMethod"
                  @change="submitFilters"
                  class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                >
                  <option value="">Todos</option>
                  <option v-for="method in paymentMethods" :key="method" :value="method">
                    {{ method }}
                  </option>
                </select>
              </div>
            </div>
          </div>
          <button
            v-if="isAdmin"
            type="button"
            @click="sendProductivityEmail"
            class="self-start rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            Enviar produtividade por e-mail
          </button>
        </div>

        <Datatable :thead="tableHeaders" :tbody="tableData" :tfooter="tableFooter" :id="tableId" />

        <!-- Relatório de Ociosidade -->
        <div v-if="isAdmin" class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Card de Ociosidade -->
          <div
            class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900 dark:to-red-900 rounded-lg p-6 border-l-4 border-orange-500">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Relatório de Ociosidade
              </h3>
              <span class="bg-orange-500 text-white px-3 py-1 rounded-full text-sm font-bold">{{ idleHoursTotal
              }}h</span>
            </div>

            <div v-if="idleHoursTotal > 0" class="space-y-2">
              <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                Horários sem atendimento (09:00 - 22:00):
              </p>
              <div class="grid grid-cols-2 gap-2">
                <div v-for="hour in idleHours" :key="hour"
                  class="bg-white dark:bg-gray-800 px-3 py-2 rounded text-sm text-gray-700 dark:text-gray-200 text-center">
                  {{ hour }}
                </div>
              </div>
            </div>
          </div>

          <!-- Card de Resumo -->
          <div
            class="bg-gradient-to-br from-green-50 to-blue-50 dark:from-green-900 dark:to-blue-900 rounded-lg p-6 border-l-4 border-green-500">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
              Resumo do Período
            </h3>
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-300">Atendimentos:</span>
                <span class="font-bold text-lg text-gray-800 dark:text-gray-100">{{
                  props.records.length
                }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-300">Faturamento:</span>
                <span class="font-bold text-lg text-green-600 dark:text-green-400">{{
                  formatCurrency(totalPrice)
                }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-300">Turno (09:00 - 18:00):</span>
                <span class="font-bold text-lg text-gray-800 dark:text-gray-100">{{ 18 - 9 }}h</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-300">Ocupação:</span>
                <span class="font-bold text-lg text-blue-600 dark:text-blue-400">{{ Math.round(((9 - idleHoursTotal) /
                  9) * 100) }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </BaseLayout>
</template>
