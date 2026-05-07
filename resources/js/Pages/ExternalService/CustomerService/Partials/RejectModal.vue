<template>
    <DialogModal :show="show" @close="$emit('close')">
        <template #title>Reprovar laudo</template>

        <template #content>
            <TextInput v-model="form.reject_report" placeholder="Motivo da Reprovação"
                class="mt-1 block w-full h-16 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-600 focus:border-blue-600 sm:text-sm" />
            <InputError :message="form.errors.reject_report" class="mt-2" />
        </template>

        <template #footer>
            <div class="flex justify-end gap-3">
                <button type="button" @click="$emit('close')"
                    class="px-4 py-2 bg-gray-400 text-white rounded-md hover:bg-gray-500 dark:bg-gray-600 dark:hover:bg-gray-500 transition">
                    Cancelar
                </button>

                <PrimaryButton @click="submitReject">
                    Salvar
                </PrimaryButton>
            </div>
        </template>
    </DialogModal>
</template>


<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    show: Boolean,
    finalReportData: {
        type: Object,
        required: true,
        default: () => ({}),
    },
});

const emit = defineEmits(["close", "success"]);

const screeningId = computed(() => props.finalReportData.screeningId);

const form = useForm({
    screening_id: screeningId,
    reject_report: "",
    status: "laudo reprovado",
});

const submitReject = () => {
    form.post(route("customers.finalReport.reprove"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reject_report = "";
            emit("success");
            emit("close");
        },
        onError: (error) => console.error("Erro ao reprovar laudo:", error),
    });
};
</script>
