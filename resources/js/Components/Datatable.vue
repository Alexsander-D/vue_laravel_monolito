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
        lengthMenu: "_MENU_",
        zeroRecords: "Nenhum registro encontrado",
        info: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
        infoEmpty: "Mostrando 0 até 0 de 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros no total)",
        search: "Buscar:",
      },
      initComplete: function () { },
      fixedHeader: true,
      pageLength: 10,
      order: [props.orderBy],
    });
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
