<script setup>
import BaseLayout from '@/Layouts/BaseLayout.vue';
import { computed } from 'vue';
import { defineProps } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
  records: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id ?? null);

const totalPrice = computed(() => {
  return props.records.reduce((sum, record) => sum + Number(record.price || 0), 0);
});

const formatDateTime = (value) => {
  if (!value) {
    return '-';
  }

  const formatted = new Date(value).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  });

  return formatted.replace(', ', ' - ');
};

const formatCurrency = (value) => {
  const formatted = Number(value || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  return `R$ ${formatted}`;
};

const deleteAttendance = async (attendanceId) => {
  const result = await Swal.fire({
    title: 'Excluir atendimento?',
    text: 'Esta ação removerá o atendimento e seus serviços.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sim, excluir',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc2626',
  });

  if (!result.isConfirmed) {
    return;
  }

  router.delete(route('attendance.destroy', { attendance: attendanceId }), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Atendimento excluído!',
        timer: 3000,
        showConfirmButton: false,
      });
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: 'Não foi possível excluir o atendimento.',
      });
    },
  });
};
</script>

<template>
  <BaseLayout title="Produção Diária">
    <div class="w-full mx-auto pt-1">
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-yellow-500">Relatório Diário</h1>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Data</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Barbeiro</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Serviço</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Pagamento</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Preço (R$)</th>
                <th v-if="currentUserId === 1" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Ações</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="record in props.records" :key="record.attendance_service_id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ formatDateTime(record.created_at) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ record.user_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ record.service_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ record.payment_method || 'Não informado' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-200">{{ formatCurrency(record.price) }}</td>
                <td v-if="currentUserId === 1" class="px-6 py-4 whitespace-nowrap text-right">
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
                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Nenhum registro encontrado.</td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-100 dark:bg-gray-800">
              <tr>
                <td :colspan="currentUserId === 1 ? 5 : 4" class="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-200">Total</td>
                <td class="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-200">{{ formatCurrency(totalPrice) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </BaseLayout>
</template>
