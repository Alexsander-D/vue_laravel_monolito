<script setup>
import { ref, defineProps } from "vue";
import { useForm } from "@inertiajs/vue3";

import Datatable from "@/Components/DatatableServerSide.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Swal from "sweetalert2";
import FormSection from "@/Components/FormSection.vue";

const form = useForm({
    startDate: null,
    endDate: null,
});

const tableHeaders = ref([
    { name: "ID", data: "id" },
    { name: "Status Triagem", data: "triagem_status" },
    { name: "Status Material", data: "material_status" },
    { name: "Início da Triagem", data: "service_start" },
    { name: "Saída do Material", data: "material_output" },
    { name: "Tipo de Transporte", data: "type_transport" },
    { name: "Cliente", data: "company_name" },
    { name: "Cidade", data: "city" },
    { name: "Estado", data: "state" },
    { name: "Visualizar", data: "button" },
    
]);

const tableId = ref("materials-table");

const showAlertIfCanceled = () => {
    Swal.fire({
        title: "Triagem Cancelada",
        text: "Esta triagem foi cancelada e não há necessidade de envio de material.",
        icon: "info",
        confirmButtonText: "Entendi",
    });
};

window.showAlertIfCanceled = showAlertIfCanceled;

const clearFilters = () => {
    form.startDate = null;
    form.endDate = null;
};
</script>

<template>
    <FormSection>
        <template #title>Materiais</template>

        <template #description>
            Triagens processadas e pendentes no fluxo de materiais
        </template>

       <template #form>
            <div class="w-full flex justify-center gap-8 mb-4">

                <div class="flex items-center gap-3">
                    <label class="font-medium text-sm dark:text-neutral-200 whitespace-nowrap">
                        Data inicial:
                    </label>
                    <TextInput v-model="form.startDate" type="date" class="block w-44 py-2 px-3 text-sm" />
                </div>

                <div class="flex items-center gap-3">
                    <label class="font-medium text-sm dark:text-neutral-200 whitespace-nowrap">
                        Data final:
                    </label>
                    <TextInput v-model="form.endDate" type="date" class="block w-44 py-2 px-3 text-sm" />
                </div>

                <div class="flex items-center">
                    <PrimaryButton class="bg-red-600 hover:bg-red-700 px-4 py-2 text-sm" @click="clearFilters">
                        Limpar
                    </PrimaryButton>
                </div>

            </div>

            <Datatable :thead="tableHeaders" :id="tableId" :ajax="route('material.datatable', {
                startDate: form.startDate,
                endDate: form.endDate,
            })" />
        </template>

    </FormSection>
</template>
