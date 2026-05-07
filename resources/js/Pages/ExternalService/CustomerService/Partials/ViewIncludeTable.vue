<script setup>
import { computed, ref, defineProps } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import "moment-timezone";


const props = defineProps({
    customersInfo: Array,
});

const activeStatus = ref(props.selectedStatus || null);

const form = useForm({
    EditProductScreening: "",
});

const tableHeaders = ref([
    { name: "Status", data: "status" },
    { name: "ID", data: "id" },
    { name: "Data de início", data: "service_start" },
    { name: "Cliente", data: "company_name" },
    { name: "Cidade", data: "city" },
    { name: "Estado", data: "state" },
    { name: "Visualizar", data: "button"}
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
        <template #title> Triagens cadastradas </template>
        <template #description> Atendimentos em andamento</template>

        <template #form>

            <Datatable :thead="tableHeaders" :ajax="route('include.screeningsDatatable')"
                :export-url="route('include.screeningsExport')" :id="tableId" class="text-left" />

            <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
                <ActionMessage :on="form.recentlySuccessful"> Salvo. </ActionMessage>
            </div>
        </template>

    </FormSection>
</template>
