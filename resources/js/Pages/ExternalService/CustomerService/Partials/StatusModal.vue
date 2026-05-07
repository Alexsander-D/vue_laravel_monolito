<template>
    <DialogModal :show="show" @close="$emit('close')">
        <template #title>
            <div class="text-center font-semibold text-lg text-gray-800 dark:text-gray-100">
                Alterar Status
            </div>
        </template>

        <template #content>
            <div class="flex flex-col items-center justify-center text-center mt-4 px-4">
                <div class="w-full max-w-md relative z-[999]">
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">
                        Selecione o novo status
                    </label>
                    <div class="relative w-full overflow-visible">
                        <Select :options="statusOptions" v-model="form.status" label="label" class="w-full" />
                    </div>
                    <InputError :message="form.errors.status" class="mt-2" />
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-center mt-6">
                <PrimaryButton @click="submitStatusChange">Salvar</PrimaryButton>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import DialogModal from "@/Components/DialogModal.vue";
import Select from "@/Components/Select.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { computed } from "vue";

const props = defineProps({
    show: Boolean,
    finalReportData: {
        type: Object,
        required: true,
        default: () => ({}),
    },
    userRole: {
        type: String,
        required: true,
        default: "",
    },
});

const emit = defineEmits(["close", "updated"]);

const screeningId = computed(() => props.finalReportData.screeningId);

const form = useForm({
    screening_id: screeningId,
    status: "",
});

const statusOptions = [
    { label: "Aguardando produtos", value: "aguardando_produtos" },
    { label: "Aguardando agendamento", value: "aguardando_agendamento" },
];

const submitStatusChange = () => {
    form.put(route("customers.finalReport.updateStatusNoMaterialCheck"), {
        preserveScroll: true,
        onSuccess: () => {
            emit("updated");
            emit("close");
        },
        onError: (e) => console.error("Erro ao alterar status:", e),
    });
};
</script>

<style scoped>
/* Faz o modal crescer conforme necessário */
:deep(.modal-container) {
    overflow: visible !important;
}

/* Garante que o dropdown do Select não seja cortado */
:deep(.multiselect),
:deep(.v-select),
:deep(.vs__dropdown-menu),
:deep(.multiselect__content-wrapper) {
    overflow: visible !important;
    z-index: 9999 !important;
    position: relative !important;
    max-height: 240px !important;
}

/* Permite rolagem suave dentro do modal caso haja muitas opções */
:deep(.multiselect__content-wrapper),
:deep(.vs__dropdown-menu) {
    overflow-y: auto !important;
    max-height: 240px !important;
}
</style>
