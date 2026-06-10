<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import DialogModal from "@/Components/DialogModal.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import InputError from "@/Components/InputError.vue";
import $ from "jquery";

const emit = defineEmits(["updateEntries", "close"]);

const props = defineProps({
    show: { type: Boolean, default: false },
    screeningId: { type: Number, required: true },
});

const form = useForm({
    screening_id: props.screeningId,
    excel_file: null,
});

const isLoading = ref(false);
const showTooltip = ref(false);

const handleFileChange = (event) => {
    form.excel_file = event.target.files[0];
};

const uploadExcelSubmit = async () => {
    if (!form.excel_file) {
        await Swal.fire({
            icon: "warning",
            title: "Arquivo não selecionado",
            text: "Por favor, selecione um arquivo Excel.",
        });
        return;
    }

    if (isLoading.value) return;
    isLoading.value = true;

    const formData = new FormData();
    formData.append("screening_id", form.screening_id);
    formData.append("excel_file", form.excel_file);

    try {

        const response = await axios.post(route("excel.upload"), formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        const rows = response.data;

        const processResponse = await axios.post(route("ProductEntry.excel"), {
            rows,
            screening_id: form.screening_id,
        });

        if (processResponse.data.success) {
            emit("updateEntries", processResponse.data.entries);
            form.excel_file = null;
            isLoading.value = false;

            $(`#ManageEntries`).DataTable().ajax.reload(null, false);

            if (processResponse.data.invalid_skus?.length) {
                Swal.fire({
                    icon: "warning",
                    title: "Importação parcial",
                    text: `Os seguintes SKUs não foram encontrados: ${processResponse.data.invalid_skus.join(", ")}`,
                });
            } else {
                Swal.fire({
                    toast: true,
                    position: "top-end",
                    icon: "success",
                    title: "Produtos importados com sucesso!",
                    showConfirmButton: false,
                    timer: 2000,
                });
            }

            setTimeout(() => emit("close"), 300);
        }

    } catch (error) {
        console.error("Erro:", error);
        let message =
            error.response?.data?.message ||
            "Falha ao subir lista. Verifique o arquivo ou tente novamente.";

        await Swal.fire({
            icon: "error",
            title: "Erro",
            text: message,
            confirmButtonText: "OK",
        });
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <DialogModal :show="show" @close="emit('close')">
        <template #title>
            <div class="flex justify-between items-center">
                <div class="flex-grow text-center">Adicionar Produto</div>
                <div class="relative inline-block max-w-full">
                    <a href="/storage/excel/importar_lista.xlsx" download="importar_lista.xlsx"
                        class="tooltip-button px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg shadow-md hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-400 transition-all"
                        @mouseover="showTooltip = true" @mouseleave="showTooltip = false">
                        Baixar Modelo
                    </a>
                    <div v-if="showTooltip"
                        class="tooltip-content absolute top-full right-0 mt-2 w-48 p-2 text-sm text-gray-700 bg-white rounded-lg shadow-lg border border-gray-300 z-50">
                        Clique para baixar a planilha em Excel padronizada.
                    </div>
                </div>
            </div>
        </template>

        <template #content>
            <div class="grid grid-cols-12 gap-4 sm:gap-6 mt-2 items-center">
                <div class="col-span-12">
                    <label class="font-medium text-sm text-gray-800 dark:text-neutral-200" for="excel">
                        Excel:
                    </label>
                    <TextInput id="excel" @change="handleFileChange" type="file" class="mt-1 block w-full" />
                    <InputError :message="form.errors.excel_file" />
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2 mt-2">
                <PrimaryButton @click="uploadExcelSubmit" :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                    <span v-if="!isLoading">Salvar</span>
                    <span v-else>Carregando...</span>
                </PrimaryButton>
                <ActionMessage :on="form.recentlySuccessful"> Salvo. </ActionMessage>
            </div>
        </template>
    </DialogModal>
</template>
