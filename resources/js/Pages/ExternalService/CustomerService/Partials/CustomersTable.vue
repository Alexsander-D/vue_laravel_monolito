<script setup>
import { computed, ref, defineProps } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/vue3";
import FormSection from "@/Components/FormSection.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import "moment-timezone";

const props = defineProps({
    customersInfo: Array,
});

const form = useForm({
    _method: "POST",
    customerId: props.customersInfo[0]?.id || "",
    type_person: props.customersInfo[0]?.type_person || "",
    company_name: props.customersInfo[0]?.company_name || "",
    trade_name: props.customersInfo[0]?.trade_name || "",
    cep: props.customersInfo[0]?.cep || "",
    state: props.customersInfo[0]?.state || "",
    city: props.customersInfo[0]?.city || "",
    road: props.customersInfo[0]?.road || "",
    district: props.customersInfo[0]?.district || "",
    number: props.customersInfo[0]?.number || "",
    telephone: props.customersInfo[0]?.telephone || "",
    email: props.customersInfo[0]?.email || "",
    responsible: props.customersInfo[0]?.responsible || "",
    observation: props.customersInfo[0]?.observation || "",
});

const submitentryForm = () => {
    form.post(route("entry.create"), {
        errorBag: "submitentryForm",
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            Inertia.reload({ only: ["entries"] });
        },
        onError: () => {
            if (form.errors.entryInput) {
                form.reset("entryInput");
                entryInput.value.focus();
            }
        },
    });
};

const tableHeaders = ref([
    { name: "ID", data: "id" },
    { name: "Cliente", data: "company_name" },
    { name: "Identificação (CPF/CNPJ)", data: "type_person" },
    { name: "Nome fantasia", data: "trade_name" },
    { name: "CEP", data: "cep" },
    { name: "Cidade", data: "city" },
    { name: "Estado", data: "state" },
    { name: "Rua", data: "road" },
    { name: "Bairro", data: "district" },
    { name: "Número", data: "number" },
    { name: "Telefone", data: "telephone" },
    { name: "E-mail", data: "email" },
    { name: "Responsável", data: "responsible" },
    { name: "Observação", data: "observation" },
    {
        name: "Editar",
        data: "button",
        orderable: false,
        searchable: false,
    },
]);

const tableId = ref("Queue");
</script>

<template>
    <FormSection @submitted="submitentryForm">
        <template #title>
            <div class="flex justify-between items-center">
                <div class="flex-grow text-center">
                    <span class="block text-lg font-medium">Clientes</span>
                    <p class="text-sm text-gray-500">
                        Abaixo listagem com os clientes cadastrados.
                    </p>
                </div>
                <a href="/technical_assistance/external/customers/show">
                    <button type="button"
                        class="flex items-center justify-center gap-2 size-[38px] text-sm rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                        title="Cadastrar novo cliente">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </button>
                </a>
            </div>
        </template>

        <template #form>
            <Datatable :id="tableId" :thead="tableHeaders" :ajax="route('viewCustomers.datatable')"
                :export-url="route('viewCustomers.export')" />
        </template>

    </FormSection>
</template>
