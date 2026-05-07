<script setup>
import { computed, ref, defineProps } from "vue";
import { useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import Datatable from "@/Components/DatatableServerSide.vue";

const props = defineProps({
    productivityData: {
        type: Object,
        required: true,
        default: () => ({
            screeningId: '',
            screeningStatus: '',
            reports: [],
        }),
    },
});

const form = useForm({
    screening_id: props.productivityData.screeningId,
    status: "finalizada",
});

const submitUpdateScreening = () => {
    form.post(route("productivityReport.finalizeScreening"), {
        preserveScroll: true,
        onSuccess: () => {
            props.productivityData.screeningInfo.status = form.status;
            Swal.fire({
                icon: "success",
                title: "Sucesso!",
                text: "Triagem finalizada com sucesso.",
                confirmButtonColor: "#3085d6",
                confirmButtonText: "OK",
            });
        },
        onError: (errors) => {
            Swal.fire({
                icon: "error",
                title: "Erro ao finalizar triagem",
                text: errors.status || "Ocorreu um erro inesperado.",
                confirmButtonColor: "#d33",
                confirmButtonText: "Fechar",
            });
        },
    });
};

const tableHeaders = ref([
    { name: "Status", data: "status" },
    { name: "Produto", data: "product" },
    { name: "Componente", data: "component" },
    { name: "Defeito", data: "defect" },
    { name: "Solução", data: "solution" },
    { name: "Imei 1", data: "imei1" },
    { name: "Imei 2", data: "imei2" },
    { name: "N° de série", data: "serial_number" },
    { name: "Observação", data: "observation" },
    { name: "Ação", data: "action" },
]);

const hasPendingProducts = computed(() => {
    return props.productivityData.reports.some(report => report.status.toLowerCase() === 'pendente');
});

const tableId = ref("productivityData"); 
</script>

<template>
    <div>
        <div>
            <div
                class="relative col-span-2 text-center flex flex-col justify-center items-center gap-4 p-4 rounded-lg border border-neutral-300 dark:border-neutral-700 shadow-md">

                <div class="w-full flex items-center justify-center relative">
                    <span class="text-lg font-semibold text-neutral-900 dark:text-neutral-200">
                        ID: {{ props.productivityData.screeningId }}
                    </span>

                    <button v-if="!hasPendingProducts" type="button"
                        class="absolute right-0 py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-full border border-transparent bg-green-600 text-white hover:bg-green-700 focus:outline-hidden focus:bg-green-700 disabled:opacity-50 disabled:pointer-events-none"
                        @click="submitUpdateScreening">
                        Finalizar triagem
                    </button>
                </div>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    {{ props.productivityData.screeningInfo.city }},
                    <span class="text-neutral-700 dark:text-white">
                        {{ props.productivityData.screeningInfo.state }}
                    </span>
                </span>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    <span class="text-neutral-700 dark:text-white">
                        {{ props.productivityData.screeningInfo.type_service.toUpperCase() }}
                    </span>
                </span>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    Status triagem:
                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                        {{ props.productivityData.screeningInfo.status.toUpperCase() }}
                    </span>
                </span>
            </div>

            <div class="mt-6">
                <div class="text-center">
                    <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                        Produtos para análise
                    </span>
                </div>

                <div class="mt-2 text-center">
                    <p class="text-gray-600 dark:text-gray-300">
                        Os produtos mostrados abaixo estão sob sua responsabilidade.
                    </p>
                </div>
                
                <Datatable :thead="tableHeaders"
                    :ajax="route('productivityReport.datatable', props.productivityData.screeningId)"
                    :export-url="route('productivityReport.export', props.productivityData.screeningId)"
                    :id="tableId" />

            </div>
        </div>
    </div>
</template>
