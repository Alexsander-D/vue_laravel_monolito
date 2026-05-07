<script setup>
import { computed, ref, defineProps } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import "moment-timezone";
import DateFilter from "@/Components/DateFilter.vue";

const props = defineProps({
    customersInfo: Array,
    widgets: Array,
    selectedStatus: String,
    date: Object,
});

const activeStatus = ref(props.selectedStatus || null);

const form = useForm({
    startDate: props.date?.startDate ?? null,
    endDate: props.date?.endDate ?? null,
    EditProductScreening: "",
});

const tableHeaders = ref([
    { name: "ID", data: "id" },
    { name: "Nome do Cliente", data: "company_name" },
    { name: "Tipo", data: "type_service" },
    { name: "Data criação", data: "created_at" },
    { name: "Status", data: "status" },
    {
        name: "Visualizar",
        data: "button",
    },
]);

const tableId = ref("screening");

const filterByStatus = (status) => {
    const newStatus = activeStatus.value === status ? null : status;
    activeStatus.value = newStatus;


    router.get(route("ViewScreening.index"), { status: newStatus }, { preserveScroll: true, preserveState: true });
};
</script>

<template>
    <FormSection>
        <template #title> Registros de atendimentos </template>

        <template #description>
            <DateFilter v-model:startDate="form.startDate" v-model:endDate="form.endDate" @submit="submitForm" />
        </template>

        <template #form>

            <div class="grid grid-cols-4 gap-4 mb-4">
                <button v-for="widget in widgets" :key="widget.title" @click="filterByStatus(widget.title)"
                    class="p-4 rounded-lg text-center transition font-medium" :class="{
                        'bg-blue-600 text-white': activeStatus === widget.title,
                        'bg-gray-200 text-black': activeStatus !== widget.title
                    }">
                    <h4 class="text-lg">{{ widget.title }}</h4>
                    <p class="text-sm">{{ widget.value }}</p>
                </button>
            </div>

            <Datatable :thead="tableHeaders" :id="tableId" class="text-left" :ajax="route('ViewScreening.datatable', {
                status: activeStatus,
                startDate: form.startDate,
                endDate: form.endDate
            })" :export-url="route('ViewScreening.export', {
                status: activeStatus,
                startDate: form.startDate,
                endDate: form.endDate
            })" />

            <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
                <ActionMessage :on="form.recentlySuccessful"> Salvo. </ActionMessage>
            </div>
        </template>
    </FormSection>
</template>
