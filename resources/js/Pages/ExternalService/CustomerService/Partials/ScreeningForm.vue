<script setup>
import { computed, ref, defineProps, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import Select from "@/Components/Select.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import FormSection from "@/Components/FormSection.vue";

const props = defineProps({
    screeningInfo: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const Options = computed(() => {
    return props.screeningInfo
        .sort((a, b) => a.company_name.localeCompare(b.company_name))
        .map((customer) => ({
            label: customer.company_name + " - " + customer.type_person,
            value: customer.id,
        }));
});

const cliente = ref(null);
const loading = ref(false);

const form = useForm({
    customers_id: "",
    type_service: "normal",
    type_person: "",
    company_name: "",
    trade_name: "",
    state: "",
    city: "",
    road: "",
    district: "",
    cep: "",
    number: "",
    telephone: "",
    responsible: "",
    email: "",
    observation: "",
    product_id: "",
    quantity: "",
    warranty: "",
});

const buscarCliente = async (customer) => {
    loading.value = true;
    try {

        const response = await axios.get(route("screening.show", customer.value));

        if (response.data) {
            cliente.value = response.data;
            popularDados(response.data);
        }
    } catch (error) {
        console.error("❌ Erro ao buscar cliente:", error);
        if (error.response) {
            console.error("📋 Detalhes:", {
                status: error.response.status,
                url: error.config?.url,
                data: error.response.data,
            });
        }
    } finally {
        loading.value = false;
    }
};

const popularDados = (cliente) => {
    form.type_person = cliente.type_person;
    form.company_name = cliente.company_name;
    form.trade_name = cliente.trade_name;
    form.state = cliente.state;
    form.city = cliente.city;
    form.road = cliente.road;
    form.district = cliente.district;
    form.cep = cliente.cep;
    form.number = cliente.number;
    form.telephone = cliente.telephone;
    form.responsible = cliente.responsible;
    form.email = cliente.email;
    form.observation = cliente.observation;
};

const screeningForm = () => {
    form.post(route("screening.create"), {
        errorBag: "screeningForm",
        preserveScroll: true,
        onSuccess: () => {
            console.log("Atendimento criado com sucesso!");
        },
        onError: (errors) => {
            console.log(errors);
            if (form.errors.customers_id) {
                form.reset("customers_id");
            }
        },
    });
};

watch(
    () => form.customers_id,
    (newValue) => {
        if (newValue) {
            buscarCliente(newValue);
        } else {
            cliente.value = null;
        }
    }
);
</script>

<template>
    <FormSection @submitted="screeningForm">
        <template #title> Criar atendimento </template>

        <template #description>
            Selecione os dados para realizar o cadastro da triagem.
        </template>

        <template #form>
            <div class="col-span-12">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4 justify-center mx-auto">
                        <div class="flex items-center gap-2">
                            <input id="atendimentoRadio" name="pessoaTipo" type="radio" value="normal"
                                v-model="form.type_service"
                                class="border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" />
                            <label class="font-medium inline-block colorBase dark:text-neutral-200"
                                for="atendimentoRadio">
                                Normal
                            </label>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <input id="retrabalhoRadio" name="pessoaTipo" type="radio" value="retrabalho"
                                v-model="form.type_service"
                                class="border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" />
                            <label class="font-medium inline-block colorBase dark:text-neutral-200"
                                for="retrabalhoRadio">
                                Retrabalho
                            </label>
                        </div>

                        <div class="flex items-center gap-2">
                            <input id="preAgendaRadio" name="pessoaTipo" type="radio" value="pre-agenda"
                                v-model="form.type_service"
                                class="border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" />
                            <label class="font-medium inline-block colorBase dark:text-neutral-200"
                                for="preAgendaRadio">
                                Pré-agenda
                            </label>
                        </div>

                        <div class="flex items-center gap-2">
                            <input id="oppoRadio" name="pessoaTipo" type="radio" value="oppo"
                                v-model="form.type_service"
                                class="border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" />
                            <label class="font-medium inline-block colorBase dark:text-neutral-200" for="oppoRadio">
                                Oppo
                            </label>
                        </div>

                        <div class="flex items-center gap-2">
                            <input id="validacaoRadio" name="pessoaTipo" type="radio" value="validacao"
                                v-model="form.type_service"
                                class="border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" />
                            <label class="font-medium inline-block colorBase dark:text-neutral-200"
                                for="validacaoRadio">
                                Validação
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="serviceInput">
                        Cliente:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <Select id="cidadeSelect" class="mt-1 block w-full" :options="Options"
                            v-model="form.customers_id" ref="serviceInput" label="label" required />

                        <p v-if="form.errors.customers_id" class="text-red-500 text-sm">{{ form.errors.customers_id }}
                        </p>
                    </div>
                </div>
            </div>


            <div v-if="loading" class="flex justify-center my-4">
                <span class="text-gray-500">Carregando...</span>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="type_person">
                        Identificação (CPF/CNPJ):
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="type_person" type="text" class="mt-1 block w-full" disabled
                            v-model="cliente.type_person" />
                    </div>
                </div>
            </div>
            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="company_name">
                        Razão social:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="company_name" type="text" class="mt-1 block w-full" disabled
                            v-model="cliente.company_name" />
                    </div>
                </div>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="trade_name">
                        Nome fantasia:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="trade_name" type="text" class="mt-1 block w-full" disabled
                            v-model="cliente.trade_name" />
                    </div>
                </div>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="state">
                        Estado:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="state" type="text" class="mt-1 block w-full" disabled v-model="cliente.state" />
                    </div>
                </div>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="city">
                        Cidade:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="city" type="text" class="mt-1 block w-full" disabled v-model="cliente.city" />
                    </div>
                </div>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="road">
                        Rua:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="road" type="text" class="mt-1 block w-full" disabled v-model="cliente.road" />
                    </div>
                </div>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="district">
                        Bairro:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="district" type="text" class="mt-1 block w-full" disabled
                            v-model="cliente.district" />
                    </div>
                </div>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="cep">
                        CEP:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="cep" type="text" class="mt-1 block w-full" disabled v-model="cliente.cep" />
                    </div>
                </div>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="number">
                        Número:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="number" type="text" class="mt-1 block w-full" disabled
                            v-model="cliente.number" />
                    </div>
                </div>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="telephone">
                        Telefone:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="telephone" type="text" class="mt-1 block w-full" disabled
                            v-model="cliente.telephone" />
                    </div>
                </div>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="responsible">
                        Responsável:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="responsible" type="text" class="mt-1 block w-full" disabled
                            v-model="cliente.responsible" />
                    </div>
                </div>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="email">
                        E-mail:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="email" type="text" class="mt-1 block w-full" disabled v-model="cliente.email" />
                    </div>
                </div>
            </div>

            <div v-if="cliente" class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
                <div class="col-span-3 sm:col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="observation">
                        Observação:
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="observation" type="text" class="mt-1 block w-full" disabled
                            v-model="cliente.observation" />
                    </div>
                </div>
            </div>
            <div class="mt-4 text-right">
                <PrimaryButton type="submit">Salvar Atendimento</PrimaryButton>
            </div>
        </template>

    </FormSection>
</template>
