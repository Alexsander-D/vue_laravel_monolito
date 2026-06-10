<script setup>
import BaseLayout from '@/Layouts/BaseLayout.vue';
import { computed } from 'vue';
import { defineProps } from 'vue';

const props = defineProps({
  records: {
    type: Array,
    default: () => [],
  },
});

const totalPrice = computed(() => {
  return props.records.reduce((sum, record) => sum + Number(record.price || 0), 0);
});

const formatDateTime = (value) => {
  if (!value) {
    return '-';
  }

  return new Date(value).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  });
};

const formatCurrency = (value) => {
  return Number(value).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
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
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Preço (R$)</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="record in props.records" :key="record.attendance_service_id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ formatDateTime(record.created_at) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ record.user_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ record.service_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-200">{{ formatCurrency(record.price) }}</td>
              </tr>
              <tr v-if="props.records.length === 0">
                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Nenhum registro encontrado.</td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-100 dark:bg-gray-800">
              <tr>
                <td colspan="3" class="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-200">Total</td>
                <td class="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-200">{{ formatCurrency(totalPrice) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </BaseLayout>
</template>
