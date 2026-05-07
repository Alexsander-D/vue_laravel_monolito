<template>
    <DialogModal :show="show" @close="$emit('close')">
        <template #title>Cancelar atendimento</template>

        <template #content>
            <TextInput id="cancel-observation" v-model="cancelObservation" rows="3" placeholder="Motivo do Cancelamento"
                class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm
               focus:ring-blue-600 focus:border-blue-600 sm:text-sm" />
        </template>

        <template #footer>
            <div class="flex justify-end gap-3">
                <PrimaryButton @click="submitCancel">Salvar</PrimaryButton>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { ref, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";

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

const emit = defineEmits(["close", "success"]);
const cancelObservation = ref("");

const form = useForm({
    screening_id: props.finalReportData.screeningId ?? null,
    observation: "",
});

const submitCancel = () => {
    form.observation = cancelObservation.value;
    form.screening_id = props.finalReportData.screeningId;

    form.post(route("customers.finalReport.cancel"), {
        preserveScroll: true,
        onSuccess: () => {
            cancelObservation.value = "";
            emit("success");
            emit("close");
        },
        onError: (error) => console.error("Erro ao cancelar triagem:", error),
    });
};
</script>
