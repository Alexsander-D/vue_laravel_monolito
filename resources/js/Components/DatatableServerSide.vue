<template>
  <div class="bg-inherit rounded-xl border dark:border-gray-700 shadow p-4 mt-8">
    <div class="flex flex-col">
      <div class="overflow-x-auto min-h-auto">
        <div class="min-w-full inline-block align-middle">
          <div class="overflow-hidden">
            <table
              :id="props.id"
              class="min-w-full divide-y divide-gray-600 text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400"
            >
              <thead>
                <tr>
                  <th
                    v-for="(head, index) in props.thead"
                    :key="index"
                    class="px-3 py-2 font-semibold text-sm dark:text-white"
                  >
                    {{ head.name }}
                  </th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import $ from "jquery";
import { defineProps, onMounted, onBeforeUnmount, nextTick, watch, ref } from "vue";

import "datatables.net";
import "datatables.net-fixedheader";
import "datatables.net-buttons/js/dataTables.buttons";
import "datatables.net-buttons/js/buttons.html5";
import "datatables.net-buttons-dt/css/buttons.dataTables.css";
import JSZip from "jszip";
window.JSZip = JSZip;

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  ajax: {
    type: String,
    required: true,
  },
  exportUrl: {
    type: String,
    default: () => null,
  },
  thead: {
    type: Array,
    required: true,
  },
  orderBy: {
    type: Array,
    default: () => [0, "desc"],
  },
});

let dataTableInstance = null;
const tableKey = ref(0);

const getColumnDefs = () => {
  return props.thead.map((head) => ({
    data: head.data ?? null,
    name: head.data ?? null, // <- CORRIGIDO
    orderable: head.orderable ?? true,
    searchable: head.searchable ?? true,
    render: head.render ?? undefined,
    className: "px-4 py-2 whitespace-nowrap text-black dark:text-white",
  }));
};


const destroyDataTable = () => {
  if (dataTableInstance) {
    dataTableInstance.destroy();
    dataTableInstance = null;
  }
};

const initializeDataTable = () => {
  nextTick(() => {
    const buttonsConfig = [];

    // Se a rota de exportação estiver definida, adiciona o botão customizado
    if (props.exportUrl) {
      buttonsConfig.push({
        text: "Exportar Excel",
        action: function () {
          window.open(props.exportUrl, "_blank");
        },
        className: "btn btn-success",
      });
    } else {
      // Se NÃO houver rota definida, usa o exportador do próprio DataTables
      buttonsConfig.push({
        extend: "excelHtml5",
        text: "Exportar Excel",
        className: "btn btn-primary",
      });
    }

    dataTableInstance = $(`#${props.id}`).DataTable({
      processing: true,
      serverSide: true,
      ajax: props.ajax,
      columns: getColumnDefs(),
      dom:
        '<"d-flex align-items-center"<"col-6"B><"col-6 text-end"f>>t<"d-flex mt-2"<"col-6"i><"col-6 text-end"p>>',
      buttons: buttonsConfig,
      language: {
        lengthMenu: "_MENU_ por página",
        zeroRecords: "Nenhum registro encontrado",
        info: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
        infoEmpty: "Mostrando 0 até 0 de 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros no total)",
        search: "Buscar:",
      },
      fixedHeader: true,
      order: [props.orderBy],
      createdRow: function (row) {
        $(row)
          .find("td")
          .addClass(
            "border border-black text-black dark:border-gray-700 dark:text-white px-4 py-2 whitespace-nowrap"
          )
          .addClass("border border-black text-black dark:border-gray-700");
      },
      headerCallback: function (thead) {
        $(thead)
          .find("th")
          .addClass(
            "border border-black px-3 py-2 text-left font-semibold text-sm dark:border-gray-700 dark:text-white"
          );
      },
    });
  });
};

onMounted(() => {
  initializeDataTable();
});

onBeforeUnmount(() => {
  destroyDataTable();
});

watch(
  () => [props.thead, props.ajax],
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
