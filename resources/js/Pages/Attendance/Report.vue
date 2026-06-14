<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import DateFilter from "@/Components/DateFilter.vue";
import { computed } from "vue";
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
    }),
  },
});

const getToday = () => new Date().toISOString().slice(0, 10);

const form = useForm({
  startDate: props.date?.startDate || getToday(),
  endDate: props.date?.endDate || getToday(),
});

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id ?? null);

const submitFilters = () => {
  form.get(route("attendance.report"), {
    preserveScroll: true,
    preserveState: true,
  });
};

const totalPrice = computed(() => {
  return props.records.reduce((sum, record) => sum + Number(record.price || 0), 0);
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
    },
    onError: () => {
      Swal.fire({
        icon: "error",
        title: "Não foi possível excluir o atendimento.",
      });
    },
  });
};
</script>

<template>
  <BaseLayout title="Produção Diária">
    <div class="w-full mx-auto pt-1">
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6">
        <DateFilter
          v-model:startDate="form.startDate"
          v-model:endDate="form.endDate"
          @submit="submitFilters"
        />

        <div class="overflow-x-auto mt-6">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                >
                  Data
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                >
                  Barbeiro
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                >
                  Serviço
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                >
                  Pagamento
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                >
                  Preço (R$)
                </th>
                <th
                  v-if="currentUserId === 1"
                  class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                >
                  Ações
                </th>
              </tr>
            </thead>
            <tbody
              class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700"
            >
              <tr v-for="record in props.records" :key="record.attendance_service_id">
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"
                >
                  {{ formatDateTime(record.created_at) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"
                >
                  {{ record.user_name }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"
                >
                  {{ record.service_name }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"
                >
                  {{ record.payment_method || "Não informado" }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-200"
                >
                  {{ formatCurrency(record.price) }}
                </td>
                <td
                  v-if="currentUserId === 1"
                  class="px-6 py-4 whitespace-nowrap text-right"
                >
                  <button
                    type="button"
                    @click="deleteAttendance(record.attendance_id)"
                    class="rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500"
                  >
                    Excluir
                  </button>
                </td>
              </tr>
              <tr v-if="props.records.length === 0">
                <td
                  :colspan="currentUserId === 1 ? 6 : 5"
                  class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400"
                >
                  Nenhum registro encontrado.
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-100 dark:bg-gray-800">
              <tr>
                <td
                  :colspan="currentUserId === 1 ? 5 : 4"
                  class="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-200"
                >
                  Total
                </td>
                <td
                  class="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-200"
                >
                  {{ formatCurrency(totalPrice) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Relatório de Ociosidade -->
        <div v-if="currentUserId == 1" class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Card de Ociosidade -->
          <div
            class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900 dark:to-red-900 rounded-lg p-6 border-l-4 border-orange-500"
          >
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Relatório de Ociosidade
              </h3>
              <span
                class="bg-orange-500 text-white px-3 py-1 rounded-full text-sm font-bold"
                >{{ idleHoursTotal }}h</span
              >
            </div>

            <div v-if="idleHoursTotal > 0" class="space-y-2">
              <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                Horários sem atendimento (09:00 - 22:00):
              </p>
              <div class="grid grid-cols-2 gap-2">
                <div
                  v-for="hour in idleHours"
                  :key="hour"
                  class="bg-white dark:bg-gray-800 px-3 py-2 rounded text-sm text-gray-700 dark:text-gray-200 text-center"
                >
                  {{ hour }}
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4">
              <p class="text-green-600 dark:text-green-400 font-semibold">
                ✓ Nenhuma hora ociosa!
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Todos os horários tiveram atendimento.
              </p>
            </div>
          </div>

          <!-- Card de Resumo -->
          <div
            class="bg-gradient-to-br from-green-50 to-blue-50 dark:from-green-900 dark:to-blue-900 rounded-lg p-6 border-l-4 border-green-500"
          >
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
                <span class="text-gray-600 dark:text-gray-300"
                  >Turno (09:00 - 18:00):</span
                >
                <span class="font-bold text-lg text-gray-800 dark:text-gray-100"
                  >{{ 18 - 9 }}h</span
                >
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-300">Ocupação:</span>
                <span class="font-bold text-lg text-blue-600 dark:text-blue-400"
                  >{{ Math.round(((9 - idleHoursTotal) / 9) * 100) }}%</span
                >
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </BaseLayout>
</template>
