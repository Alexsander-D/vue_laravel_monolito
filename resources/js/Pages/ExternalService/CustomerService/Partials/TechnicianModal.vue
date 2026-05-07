<template>
    <DialogModal :show="show" @close="$emit('close')">
        <template #title>
            <div class="text-center font-semibold text-lg text-gray-800 dark:text-gray-100">
                Alterar Técnico
            </div>
        </template>

        <template #content>
            <div class="flex flex-col items-center justify-center text-center mt-4">
                <!-- Seleção -->
                <div class="mb-4 w-full max-w-md">
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">
                        Selecione o técnico
                    </label>
                    <Select :options="props.finalReportData.technicians" v-model="form.technician" label="name" class="w-full" />
                    <InputError :message="form.errors.technician" />
                </div>

                <!-- Lista -->
                <div class="mt-6 w-full max-w-lg">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                        Técnicos escalados
                    </h3>

                    <div
                        class="flex flex-wrap justify-center gap-2 p-3 border rounded-lg bg-gray-50 dark:bg-gray-700 shadow-inner transition-all duration-300">
                        <template v-if="localScales.length">
                            <span v-for="t in localScales" :key="t.id"
                                class="bg-gray-200 dark:bg-gray-600 px-4 py-2 rounded-lg text-sm text-gray-800 dark:text-gray-100 font-medium flex items-center gap-2">
                                {{ t.technical }}
                                <button @click="removeTechnician(t.id)"
                                    class="text-red-500 hover:text-red-700 transition">
                                    ✖
                                </button>
                            </span>
                        </template>

                        <span v-else class="text-sm text-gray-500 dark:text-gray-400 italic">
                            Nenhum técnico escalado
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-center mt-6">
                <PrimaryButton @click="submitTechnicianChange">Salvar</PrimaryButton>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import DialogModal from "@/Components/DialogModal.vue";
import Select from "@/Components/Select.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Swal from "sweetalert2";

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
const technicians = computed(() => props.finalReportData.technicians || []);
const localScales = ref([...props.finalReportData.technicalScales]);

const form = useForm({
    screening_id: screeningId,
    technician: "",
});

/**
 * Adiciona técnico (sem fechar o modal e mantendo compatibilidade com backend)
 */
const submitTechnicianChange = () => {
    if (!form.technician?.id) return;

    const technicianId = form.technician.id;
    form.technician = technicianId;

    form.put(route("customers.finalReport.updateTechnician"), {
        preserveScroll: true,
        onSuccess: () => {
            const addedTech = technicians.value.find((t) => t.id === technicianId);
            if (addedTech && !localScales.value.some((t) => t.id === technicianId)) {
                const newTech = { id: technicianId, technical: addedTech.name };
                localScales.value.push(newTech);

                // 🔁 Atualiza o objeto pai em tempo real
                props.finalReportData.technicalScales.push(newTech);
            }

            Swal.fire({
                icon: "success",
                title: "Técnico adicionado!",
                text: "O técnico foi adicionado à triagem.",
                confirmButtonColor: "#02B590",
            });

            form.technician = "";
            emit("updated");
        },
        onError: (error) => {
            console.error("Erro ao adicionar técnico:", error);
            Swal.fire({
                icon: "error",
                title: "Erro!",
                text: error?.technician?.[0] || "Não foi possível adicionar o técnico.",
                confirmButtonColor: "#d33",
            });
        },
    });
};

/**
 * Remove técnico (sem fechar o modal)
 */
const removeTechnician = (id) => {
    form.delete(route("customers.finalReport.removeTechnician", id), {
        preserveScroll: true,
        onSuccess: () => {
            // Remove localmente do modal
            localScales.value = localScales.value.filter((t) => t.id !== id);

            // 🔁 Atualiza também no pai (tabela)
            props.finalReportData.technicalScales =
                props.finalReportData.technicalScales.filter((t) => t.id !== id);

            Swal.fire({
                icon: "success",
                title: "Técnico removido!",
                text: "O técnico foi removido da triagem.",
                confirmButtonColor: "#02B590",
            });

            emit("updated");
        },
    });
};

/**
 * Mantém sincronizado com o backend e com o componente pai
 */
watch(
    () => props.finalReportData.technicalScales,
    (newVal) => {
        localScales.value = [...newVal];
    },
    { deep: true }
);

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            localScales.value = [...props.finalReportData.technicalScales];
        }
    }
);
</script>

