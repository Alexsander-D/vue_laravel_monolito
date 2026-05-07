<script setup>
import { computed, ref, defineProps, watchEffect, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Swal from "sweetalert2";
import Select from "@/Components/Select.vue";
import moment from "moment";
import TextArea from "@/Components/TextArea.vue";

const props = defineProps({
    materialsData: {
        type: Object,
        required: true,
        default: () => [],
    },
});
console.log("materialsData:", props.materialsData);
const screening = computed(() => {
    return props.materialsData.screenings && props.materialsData.screenings[0];
});

const material = computed(() => {
    return props.materialsData.materials && props.materialsData.materials.find(m => m.screening_id === screening.value?.id);
});

const form = useForm({
    screening_id: screening.value?.id || '',
    deadline_list: material.value?.deadline_list ? moment.utc(material.value.deadline_list).format("YYYY-MM-DD") : '',
    material_output: material.value?.material_output ? moment.utc(material.value.material_output).format("YYYY-MM-DD") : '',
    expected_arrival: material.value?.expected_arrival ? moment.utc(material.value.expected_arrival).format("YYYY-MM-DD") : '',
    type_transport: material.value?.type_transport || '',
    status: material.value?.status || '',
    nf: material.value?.nf || '',
    observation: material.value?.observation || '',
});

const isSaved = ref(false);

const allStatusOptions = ["Aguardando Materiais", "Solicitado", "Cancelado", "Pendente", "Enviado", "Entregue"];

const transportOptions = ref([
    'Aéreo',
    'Carro',
    'Ônibus',
    'Performance',
    'Postagem aérea',
    'Sedex',
    'Sem material',
    'Transportadora'
]);

const submitForm = () => {
    if (form.status === "Entregue" && !form.nf) {
        Swal.fire({
            title: "Erro!",
            text: "O campo NF é obrigatório.",
            icon: "error",
        });
        return;
    }

    if (form.status === "Enviado" && !form.type_transport) {
        Swal.fire({
            title: "Erro!",
            text: "O campo 'Tipo de Transporte' é obrigatório.",
            icon: "error",
        });
        return;
    }

    const requestConfig = {
        errorBag: "submitForm",
        preserveScroll: true,
        onSuccess: () => {
            isSaved.value = true;
            Swal.fire({
                title: "Sucesso!",
                text: material.value && material.value.id
                    ? "Materiais atualizados!"
                    : "Materiais cadastrados!",
                icon: "success",
                confirmButtonText: "OK",
            });
            updateMaterialData();
        },
        onError: (errors) => handleFormError(errors),
    };

    if (material.value && material.value.id) {
        form.put(route("material.update", material.value.id), requestConfig);
    } else {
        form.post(route("material.save"), requestConfig);
    }
};

const updateMaterialData = () => {

    Object.assign(material.value, {
        deadline_list: form.deadline_list,
        material_output: form.material_output,
        expected_arrival: form.expected_arrival,
        type_transport: form.type_transport,
        status: form.status,
        nf: form.nf,
        observation: form.observation,
    });
};

const handleFormError = (errors) => {
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
};

const formatDate = (date) => {
    return date ? moment(date).format('DD/MM/YY') : '';
};

watchEffect

const isNFVisible = computed(() => form.status === "Entregue");

watch(() => form.status, (newStatus) => {
    if (newStatus === "Cancelado" || newStatus === "Entregue") {
        Swal.fire({
            title: "Aviso!",
            text: "O status foi definido como '" + newStatus + "'. Nenhuma alteração será permitida após salvar essa ação.",
            icon: "warning",
        });
    }
});

const isFieldsDisabled = computed(() => {
    return material.value && material.value.is_finalizado;
});

</script>

<template>
    <FormSection @submitted="submitForm">
        <template #title> Agendar envio do material </template>

        <template #description> Preencha com status e datas de envio do material </template>

        <template #form>
            <div
                class="col-span-2 text-center flex flex-col justify-center items-center gap-4 p-4 rounded-lg border border-neutral-300 dark:border-neutral-700 shadow-md">
                <span class="text-lg font-semibold text-neutral-900 dark:text-neutral-200">
                    ID: {{ props.materialsData.materials[0]?.screening_id }}
                    <span class="text-neutral-700 dark:text-white">
                        - {{ props.materialsData.customersInfo[0]?.company_name }}
                    </span>
                </span>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    {{ props.materialsData.customersInfo[0]?.city }},
                    <span class="text-neutral-700 dark:text-white">
                        {{ props.materialsData.customersInfo[0]?.state }}
                    </span>
                </span>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    Tipo triagem:
                    <span class="text-neutral-700 dark:text-white">
                        {{ props.materialsData.screenings[0]?.type_service?.toUpperCase() || '' }}
                    </span>
                </span>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    Status triagem:
                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                        {{ props.materialsData.screenings[0]?.status?.toUpperCase() || '' }}
                    </span>
                </span>
            </div>

            <div class="grid grid-cols-12 gap-4 sm:gap-6 items-start mt-4">
                <div class="col-span-6">
                    <div class="mb-4">
                        <label class="font-medium text-sm text-gray-800 dark:text-neutral-200" for="inicioInput">Prazo
                            lista:</label>
                        <TextInput id="inicioInput" type="date" v-model="form.deadline_list"
                            :value="form.deadline_list || ''" :disabled="isFieldsDisabled" class="mt-1 block w-full" />
                    </div>

                    <div class="mb-4">
                        <label class="font-medium text-sm text-gray-800 dark:text-neutral-200" for="terminoInput">Saída
                            do material:</label>
                        <TextInput id="terminoInput" type="date" v-model="form.material_output"
                            :value="form.material_output || ''" :disabled="isFieldsDisabled"
                            class="mt-1 block w-full" />
                    </div>

                    <div class="mb-4">
                        <label class="font-medium text-sm text-gray-800 dark:text-neutral-200"
                            for="entregaInput">Entrega estimada:</label>
                        <TextInput id="entregaInput" type="date" v-model="form.expected_arrival"
                            :value="form.expected_arrival || ''" :disabled="isFieldsDisabled"
                            class="mt-1 block w-full" />
                    </div>

                    <div class="mb-4">
                        <label class="font-medium text-sm text-gray-800 dark:text-neutral-200"
                            for="statusSelect">Status:</label>
                        <Select id="statusSelect" v-model="form.status" :options="allStatusOptions"
                            :disabled="isFieldsDisabled" class="mt-1 block w-full" />
                    </div>

                    <div class="mb-4">
                        <label class="font-medium text-sm text-gray-800 dark:text-neutral-200"
                            for="transportSelect">Tipo de transporte:</label>
                        <Select id="transportSelect" v-model="form.type_transport" :options="transportOptions"
                            :class="{ 'border-red-500': form.status === 'Enviado' && !form.type_transport }"
                            :disabled="isFieldsDisabled" class="mt-1 block w-full" />
                        <p v-if="form.status === 'Enviado' && !form.type_transport" class="text-red-500 text-sm mt-1">
                            Campo obrigatório
                        </p>
                    </div>

                   <div class="mb-4">
                        <label class="font-medium text-sm text-gray-800 dark:text-neutral-200" for="nfInput">
                            NF:
                        </label>

                        <TextInput id="nfInput" type="text" v-model="form.nf" :disabled="isFieldsDisabled"
                            placeholder="Digite o número da NF..."
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-neutral-800 dark:border-gray-600 dark:text-white" />

                        <p v-if="form.status === 'Entregue' && !form.nf" class="text-red-500 text-sm mt-1">
                            Campo obrigatório
                        </p>
                    </div>


                    <div class="mb-4">
                        <label class="font-medium text-sm text-gray-800 dark:text-neutral-200"
                            for="obsInput">Observações:</label>
                        <TextArea id="obsInput" v-model="form.observation" :disabled="isFieldsDisabled" :rows="3"
                            placeholder="Digite suas observações..."
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-neutral-800 dark:text-white" />

                    </div>

                </div>

                <div class="col-span-6 p-4 rounded-lg shadow-lg border dark:border-gray-700">
                    <p class="font-bold text-xl text-center text-gray-800 dark:text-neutral-200">
                        Atualização materiais
                    </p>
                    <div class="mt-4 text-sm text-gray-800 dark:text-neutral-200">
                        <div class="mb-4">
                            <p><strong>Início triagem:</strong>
                                {{ formatDate(props.materialsData.screenings[0]?.service_start) }}
                            </p>
                        </div>
                        <div class="mb-4">
                            <p><strong>Prazo lista:</strong>
                                {{ formatDate(props.materialsData.materials[0]?.deadline_list) }}
                            </p>
                        </div>
                        <div class="mb-4">
                            <p><strong>Saída do Material:</strong>
                                {{ formatDate(props.materialsData.materials[0]?.material_output) }}
                            </p>
                        </div>
                        <div class="mb-4">
                            <p><strong>Entrega estimada:</strong>
                                {{ formatDate(props.materialsData.materials[0]?.expected_arrival) }}
                            </p>
                        </div>
                        <div class="mb-4">
                            <p><strong>Tipo de Transporte:</strong>
                                {{ props.materialsData.materials[0]?.type_transport || '' }}
                            </p>
                        </div>
                        <div class="mb-4">
                            <p><strong>Status do Material:</strong>
                                {{ props.materialsData.materials[0]?.status || '' }}
                            </p>
                        </div>
                        <div class="mb-4">
                            <p><strong>NF:</strong>
                                {{ props.materialsData.materials[0]?.nf || '' }}
                            </p>
                        </div>
                        <div class="mb-4">
                            <p><strong>Técnicos escalados:</strong>
                                {{ props.materialsData.technicians[0]?.name || '' }}
                            </p>
                        </div>
                        <div class="mb-4">
                            <p><strong>Observações:</strong>
                                {{ props.materialsData.materials[0]?.observation || '' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 flex justify-start mt-8">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Salvar
                </PrimaryButton>
            </div>

            <div class="col-span-12 flex justify-end mt-2">
                <ActionMessage :on="form.recentlySuccessful"> Salvo com sucesso! </ActionMessage>
            </div>
        </template>
    </FormSection>
</template>
