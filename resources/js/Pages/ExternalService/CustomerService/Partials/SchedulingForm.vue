<script setup>
import { computed, ref, defineProps } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Datatable from "@/Components/Datatable.vue";
import Swal from "sweetalert2";

const props = defineProps({
    schedulingData: {
        type: Object,
        required: true,
        default: () => [],
    },
});

const customersInfo = computed(() => {
    return props.schedulingData.customersInfo || {};
});

const form = useForm({
    screening_id: props.schedulingData.screeningId || "",
    service_start: "",
    completion_date: "",
    air_ticket: false,
    observation: "",
    status: "agendada",
    scheduling_date: new Date().toISOString(),
});

const submitForm = () => {
    form.status = "agendada";
    form.scheduling_date = new Date().toISOString();
    form.post(route("customers.scheduling.save"), {
        errorBag: "submitForm",
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            Inertia.reload({ only: ["components"] });
            Swal.fire({
                title: "Sucesso!",
                text: "Triagem agendada com sucesso!",
                icon: "success",
                confirmButtonText: "OK",
            });
        },
        onError: (errors) => {
            console.error("Erro ao salvar dados:", errors);
            let errorMessage = "Houve um problema ao processar sua solicitação.";
            if (errors.message) {
                errorMessage = errors.message;
            } else if (Object.keys(errors).length > 0) {
                errorMessage = Object.values(errors)[0];
            }
            Swal.fire({
                title: "Erro!",
                text: errorMessage,
                icon: "error",
                confirmButtonText: "OK",
            });
        },
    });
};

const tableHeaders = ref([
    { name: "Identificação (CPF/CNPJ)" },
    { name: "Razão Social" },
    { name: "Nome Fantasia" },
    { name: "CEP" },
    { name: "Estado" },
    { name: "Cidade" },
    { name: "Rua" },
    { name: "Bairro" },
    { name: "Número" },
    { name: "Telefone" },
    { name: "E-mail" },
]);

const tableData = computed(() => {
    if (!customersInfo.value || Object.keys(customersInfo.value).length === 0) {
        return [];
    }
    return [
        {
            type_person: customersInfo.value.type_person,
            company_name: customersInfo.value.company_name,
            trade_name: customersInfo.value.trade_name,
            cep: customersInfo.value.cep,
            state: customersInfo.value.state,
            city: customersInfo.value.city,
            road: customersInfo.value.road,
            district: customersInfo.value.district,
            number: customersInfo.value.number,
            telephone: customersInfo.value.telephone,
            email: customersInfo.value.email,
        },
    ];
});

const productTableHeaders = ref([
    { name: "Família" },
    { name: "Produto" },
    { name: "Total" },
    { name: "Recuperado" },
    { name: "Devolução" },
    { name: "Garantia" },
    { name: "Mau Uso" },
    { name: "Não Encontrado" },
    { name: "Próxima Triagem" },
]);

const productTableData = computed(() => {
    const schedulingInfo = props.schedulingData.schedulingInfo || [];
    if (!schedulingInfo.length) return [];
    return schedulingInfo.map((item) => ({
        family: item.family,
        product: item.product,
        total: item.total,
        recovered: item.status_counts.recuperado,
        return: item.status_counts.devolucao,
        warranty: item.warranty,
        misuse: item.status_counts["mau uso"],
        not_found: item.status_counts["nao encontrado"],
        next_screening: item.status_counts["proxima triagem"],
    }));
});

const tableId = ref("ManageTable");
const productTableId = ref("ProductTable");
</script>

<template>
    <FormSection @submitted="submitForm">
        <!-- 🔹 Cabeçalho -->
        <template #title> Agendar Triagem </template>
        <template #description>
            Preencha as informações necessárias e visualize os dados na tabela.
        </template>

        <!-- 🔹 Formulário -->
        <template #form>
            <!-- Compra de passagem -->
            <div class="col-span-2 flex items-center gap-2 mt-3">
                <input id="passagemCheckbox" name="air_ticket" type="checkbox" v-model="form.air_ticket" class="border-gray-200 rounded text-blue-600 focus:ring-blue-500 
          dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 
          dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" />
                <label class="font-medium inline-block text-gray-900 dark:text-neutral-200" for="passagemCheckbox">
                    Compra de passagem aérea?
                </label>
            </div>

            <!-- Datas -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center w-full max-w-6xl mx-auto mt-8">
                <div class="col-span-6 sm:col-span-5 flex flex-col">
                    <label class="font-medium text-sm mb-2 text-gray-800 dark:text-gray-200" for="inicioInput">
                        Data Inicial
                    </label>
                    <input id="inicioInput" type="date" v-model="form.service_start" class="block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm 
            focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 
            sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    <InputError :message="form.errors.service_start" class="mt-1" />
                </div>

                <div class="col-span-6 sm:col-span-5 flex flex-col">
                    <label class="font-medium text-sm mb-2 text-gray-800 dark:text-gray-200" for="terminoInput">
                        Data Término
                    </label>
                    <input id="terminoInput" type="date" v-model="form.completion_date" class="block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm 
            focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 
            sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    <InputError :message="form.errors.completion_date" class="mt-1" />
                </div>
            </div>

            <!-- Observação -->
            <div class="col-span-12 mt-6">
                <label class="font-medium text-sm text-gray-800 dark:text-neutral-200 mb-2" for="observacaoInput">
                    Observação
                </label>
                <textarea id="observacaoInput" v-model="form.observation" rows="4" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm 
          focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 
          sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                <InputError :message="form.errors.observation" class="mt-1" />
            </div>

            <!-- Botão Finalizar -->
            <div class="col-span-12 flex justify-end mt-8">
                <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                    class="px-8 py-3 rounded-lg font-medium 
         text-white dark:text-white
         bg-green-600 hover:bg-green-700 
         dark:bg-green-700 dark:hover:bg-green-800 
         transition-all duration-200 shadow-sm">
                    Finalizar
                </PrimaryButton>

            </div>

            <!-- Tabelas -->
            <div class="mt-10">
                <h3 class="text-center font-medium text-gray-800 dark:text-neutral-200 mb-4">
                    Cliente
                </h3>
                <Datatable :thead="tableHeaders" :tbody="tableData" :id="tableId" />
            </div>

            <div class="mt-10">
                <h3 class="text-center font-medium text-gray-800 dark:text-neutral-200 mb-4">
                    Lista de Produtos
                </h3>
                <Datatable :thead="productTableHeaders" :tbody="productTableData" :id="productTableId" />
            </div>

            <!-- Mensagem de sucesso -->
            <div class="col-span-12 flex justify-end mt-4">
                <ActionMessage :on="form.recentlySuccessful">
                    Salvo com sucesso!
                </ActionMessage>
            </div>
        </template>
    </FormSection>
</template>
