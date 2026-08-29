<template>
  <!-- Tabela -->
  <div class="bg-inherit rounded-xl border dark:border-gray-700 shadow p-4 mt-8">
    <div class="flex flex-col">
      <div class="overflow-x-auto min-h-auto">
        <div class="min-w-full inline-block align-middle">
          <div class="overflow-hidden">
            <table class="min-w-full" :id="id" :key="tableKey">
              <thead>
                <tr class="dark:text-white">
                  <th v-for="head in thead" :key="head.name">
                    {{ head.name }}
                  </th>
                </tr>
              </thead>

              <tbody class="divide-y divide-gray-600">
                <tr v-for="(body, index) in tbody" :key="index">
                  <td class="dark:text-white" v-for="(value, key) in body" :key="key">
                    <template v-if="key.startsWith('button')">
                      <div v-html="value"></div>
                    </template>
                    <template v-else-if="typeof value === 'string' && value.includes('<')">
                      <span v-html="value"></span>
                    </template>
                    <template v-else>
                      {{ value }}
                    </template>
                  </td>
                </tr>
              </tbody>


              <tfoot>
                <tr v-for="(footer, index) in tfooter" :key="index">
                  <td class="dark:text-white" v-for="(value, key) in footer" :key="key">
                    {{ value }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Fim da Tabela -->
</template>

<script setup>
import $ from "jquery";
import { defineProps, onMounted, nextTick, watch, ref } from "vue";

import "datatables.net";
import "datatables.net-fixedheader";
import "datatables.net-buttons/js/dataTables.buttons";
import "datatables.net-buttons/js/buttons.html5";
import "datatables.net-buttons-dt/css/buttons.dataTables.css";
import JSZip from "jszip";
window.JSZip = JSZip;

const props = defineProps({
  thead: Array,
  tbody: Array,
  tfooter: Array,
  id: String,
  orderBy: {
    type: Array,
    default: () => [0, "desc"],
  },
});

const tableKey = ref(0);

let dataTableInstance = null;

const normalizeDateColumns = () => {
  if (!dataTableInstance) {
    return;
  }

  $(`#${props.id} tbody td`).each(function () {
    const rawText = $(this).text().trim();
    if (!rawText) {
      return;
    }

    const match = rawText.match(/(\d{1,2})\/(\d{1,2})\/(\d{2,4})(?:\s*[- ]\s*(\d{1,2}):(\d{2})(?::(\d{2}))?)?/);

    if (!match) {
      return;
    }

    const [, day, month, year, hour = "0", minute = "0", second = "0"] = match;
    const parsedYear = Number(year) < 100 ? 2000 + Number(year) : Number(year);
    const date = new Date(
      parsedYear,
      Number(month) - 1,
      Number(day),
      Number(hour),
      Number(minute),
      Number(second)
    );

    if (!Number.isNaN(date.getTime())) {
      $(this).attr("data-order", date.toISOString());
    }
  });
};

const initializeDataTable = () => {
  nextTick(() => {
    dataTableInstance = $(`#${props.id}`).DataTable({
      dom:
        '<"d-flex align-items-center"<"col-6"B><"col-6 text-end"f>>t<"d-flex mt-2"<"col-6"i><"col-6 text-end"p>>',
      buttons: [
        {
          extend: "excelHtml5",
          text: "Baixar Excel",
        },
      ],
      language: {
        decimal: ",",
        thousands: ".",
        lengthMenu: "Exibir _MENU_ registros",
        zeroRecords: "Nenhum registro encontrado",
        info: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
        infoEmpty: "Mostrando 0 até 0 de 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros no total)",
        search: "Buscar:",
        paginate: {
          first: "Primeiro",
          last: "Último",
          next: "Próximo",
          previous: "Anterior",
        },
      },
      initComplete: function () {
        normalizeDateColumns();
      },
      fixedHeader: true,
      pageLength: 10,
      order: [props.orderBy],
    });

    dataTableInstance.on("draw.dt", normalizeDateColumns);
  });
};

const destroyDataTable = () => {
  if (dataTableInstance) {
    dataTableInstance.destroy();
    dataTableInstance = null;
  }
};

onMounted(() => {
  initializeDataTable();
});

watch(
  () => [props.thead, props.tbody, props.tfooter],
  () => {
    destroyDataTable();
    tableKey.value++;
    nextTick(() => {
      initializeDataTable();
    });
  },
  { deep: true }
);
</script>

<style>
@import "/resources/css/Components/Datatable.css";
@import "/resources/css/Components/datatableButtons.css";
</style>
