<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import axios from "axios";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { ref, onMounted } from "vue";
import moment from "moment";
import "moment-timezone";

const props = defineProps({
    screening_id: {
        type: String,
        default: ""
    },
    timeline: {
        type: Array,
        default: () => []
    }
});

const form = ref({
    screening_id: props.screening_id || "",
});

const timeline = ref([]);
const errorMessage = ref(""); 

const searchForm = async () => {
    if (!form.value.screening_id) {
        errorMessage.value = "Por favor, insira um ID.";
        return;
    }

    errorMessage.value = "";

    try {
        const response = await axios.get(route("screeningTimeline.show", { screening_id: form.value.screening_id }));
        timeline.value = response.data.data;
    } catch (error) {
        timeline.value = [];
        if (error.response && error.response.status === 404) {
            errorMessage.value = "Nenhum registro encontrado.";
        } else {
            errorMessage.value = "Erro ao buscar os dados.";
        }
    }
};

onMounted(() => {
    if (form.value.screening_id) {
        searchForm();
    }
});

const formatDate = (date) => {
    return moment.utc(date).format("DD/MM/YYYY HH:mm:ss");
};
</script>

<template>
    <BaseLayout title="Timeline">
        <div class="w-full mx-auto pt-1">
            <div class="bg-white rounded-xl shadow-lg p-2 dark:bg-gray-900 text-center">
                <FormSection @submitted.prevent="searchForm">
                    <template #title> BUSCAR HISTÓRICO </template>
                    <template #description>
                        Digite um ID para exibir o histórico.
                    </template>
                    <template #form>
                        <div class="grid grid-cols-12 gap-2 sm:gap-6 mt-8 items-center">
                            <div class="col-span-2"></div>
                            <div class="col-span-8">
                                <TextInput v-model="form.screening_id" type="text" class="mt-1 block w-full"
                                    autocomplete="off" />
                                <InputError v-if="errorMessage" :message="errorMessage" />
                            </div>
                            <div class="col-span-2 flex justify-center mt-4 sm:mt-2">
                                <PrimaryButton @click="searchForm">Buscar</PrimaryButton>
                            </div>
                        </div>
                    </template>
                </FormSection>

                <div class="mt-6">
                    <h3 v-if="timeline.length"
                        class="text-md font-medium uppercase text-black dark:text-neutral-400 mb-4">
                        Histórico de {{ form.screening_id }}
                    </h3>
                    <h3 v-else-if="errorMessage" class="text-md font-medium uppercase text-red-600 mb-4">
                        {{ errorMessage }}
                    </h3>
                    <div v-for="action in timeline" :key="action.screening_id" class="flex gap-x-3">
                        <div class="grow pt-0.5 pb-4">
                            <h3
                                class="py-3 flex items-center before:flex-1 before:border-t before:border-gray-300 before:me-6 after:flex-1 after:border-t after:border-gray-300 after:ms-6 dark:before:border-neutral-600 dark:after:border-neutral-600 text-xs font-medium uppercase text-gray-800 dark:text-neutral-400">
                                {{ formatDate(action.created_at) }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-800 dark:text-neutral-400">
                                {{ action.description }}
                            </p>
                            <button type="button"
                                class="mt-1 p-1 inline-flex items-center gap-x-2 text-xs rounded-lg border border-transparent text-gray-700 focus:outline-none disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400">
                                {{ action.responsible }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

