<script setup>
import { ref, defineProps } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import Datatable from "@/Components/DatatableServerSide.vue";

const props = defineProps({
    finalReportData: {
        type: Object,
        required: true,
        default: () => [],
    },
});

const form = useForm({
    screening_id: props.finalReportData.screeningId || '',
    service_start: '',
    completion_date: '',
    air_ticket: false,
    observation: '',
    status: 'agendada',
});

const submitForm = () => {
    form.status = "agendada";

    form.post(route("customers.scheduling.save"), {
        errorBag: "submitForm",
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            Inertia.reload({ only: ["components"] });
        },
        onError: (error) => {
            console.error("Erro ao salvar dados:", error);
        },
    });
};

const tableHeaders = ref([
    { name: "Produto", data: "product" },
    { name: "Família", data: "family" },
    { name: "Valor unitário", data: "price" },
    { name: "Componente", data: "component" },
    { name: "Defeito", data: "defect" },
    { name: "Solução", data: "solution" },
    { name: "Imei 1", data: "imei1" },
    { name: "Imei 2", data: "imei2" },
    { name: "N° de série", data: "serial_number" },
    { name: "Tratativa", data: "status" },
    { name: "Observação", data: "observation" },
]);

const productTableHeaders = ref([
    { name: "Família", data: "family" },
    { name: "Produto", data: "product" },
    { name: "Garantia", data: "warranty" },
    { name: "Total", data: "total" },
    { name: "Recuperado", data: "recovered" },
    { name: "Devolução", data: "return" },
    { name: "Mau Uso", data: "misuse" },
    { name: "Não Encontrado", data: "not_found" },
    { name: "Próxima Triagem", data: "next_screening" },
]);


const tableId = ref("ManageTable");
const productTableId = ref("ProductTable");
</script>

<template>
    <FormSection @submitted="submitForm">
        <template #title> Produtos</template>

        <template #description> Detalhes por SKU e produtividade. </template>

        <template #form>
            <div class="mb-8">
                <h3 class="text-center font-medium text-gray-800 dark:text-neutral-200">
                    Lista de Produtos
                </h3>

                <Datatable v-if="props.finalReportData?.screeningId" :thead="productTableHeaders"
                    :ajax="route('finalReport.products.datatable', props.finalReportData.screeningId)"
                    :export-url="route('finalReport.products.export', props.finalReportData.screeningId)"
                    :id="productTableId" />

                <div class="text-right mt-4 pr-4">
                    <span class="text-red-600 font-bold text-lg">
                        Total Geral: {{ props.finalReportData.totalProducts }}
                    </span>
                </div>
            </div>


            <div class="mb-8">
                <h3 class="text-center font-medium text-gray-800 dark:text-neutral-200">Rastreabilidade</h3>

                <Datatable v-if="props.finalReportData?.screeningId" :thead="tableHeaders"
                    :ajax="route('finalReport.datatable', props.finalReportData.screeningId)"
                    :export-url="route('finalReport.export', props.finalReportData.screeningId)" :id="tableId" />
            </div>

            <div class="col-span-12 flex justify-end mt-2">
                <ActionMessage :on="form.recentlySuccessful"> Salvo com sucesso! </ActionMessage>
            </div>
        </template>
    </FormSection>
</template>
