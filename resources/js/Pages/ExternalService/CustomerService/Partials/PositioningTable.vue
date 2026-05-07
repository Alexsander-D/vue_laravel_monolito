<script setup>
import { ref, defineProps } from "vue";
import { useForm } from "@inertiajs/vue3";

import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import DateFilter from "@/Components/DateFilter.vue";

const props = defineProps({
    date: Object,
});

const form = useForm({
    startDate: props.date.startDate,
    endDate: props.date.endDate,
});

const submitForm = async () => {
    form.get(route("positioning.index"), {
        preserveScroll: true,
    });
};

const tableHeaders = ref([
    { name: "ID", data: "id" },
    { name: "Cliente", data: "company_name" },
    { name: "Cidade", data: "city" },
    { name: "ES", data: "state" },
    { name: "Data", data: "service_start" },
    { name: "Técnicos", data: "technicals" },
    { name: "Tipo", data: "type_service" },
    { name: "Status", data: "status" },
    { name: "Prod", data: "prod_total" },
    { name: "Tablet", data: "tablet" },
    { name: "Smart", data: "smart" },
    { name: "FP", data: "fp" },
    { name: "Audio", data: "audio" },
    { name: "PER", data: "per" },
    { name: "PC", data: "pc" },
    { name: "Rec", data: "rec" },
    { name: "FG", data: "fg" },
    { name: "MU", data: "mu" },
    { name: "Dev", data: "dev" },
    { name: "PNE", data: "pne" },
    { name: "PT", data: "pt" },
]);

const tableId = ref("screenings-table");
</script>

<template>
    <FormSection>
        <template #title> Posicionamento </template>
        <template #description>
            <DateFilter v-model:startDate="form.startDate" v-model:endDate="form.endDate" @submit="submitForm" />
        </template>

        <template #form>
            <Datatable :thead="tableHeaders" :id="tableId" :ajax="route('positioning.datatable', {
                startDate: form.startDate,
                endDate: form.endDate
            })" :export-url="route('positioning.export', {
                startDate: form.startDate,
                endDate: form.endDate
            })" />

            <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
                <ActionMessage :on="form.recentlySuccessful">
                    Salvo.
                </ActionMessage>
            </div>
        </template>
    </FormSection>
</template>
