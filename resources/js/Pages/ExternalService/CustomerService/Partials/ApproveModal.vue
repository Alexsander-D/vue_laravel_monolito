<template>
    <DialogModal :show="show" @close="$emit('close')">
        <template #title>
            <div class="flex justify-center items-center text-lg font-semibold text-gray-800 dark:text-gray-100">
                Aprovar laudo
            </div>
        </template>

        <template #content>
            <form @submit.prevent="submitApproveReport" class="space-y-6 mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <label class="w-28 text-right font-medium text-gray-700 dark:text-gray-300">RM:</label>
                            <TextInput v-model="approveForm.rm" class="w-full uppercase" required />
                        </div>
                        <div class="flex items-center gap-2">
                            <label
                                class="w-28 text-right font-medium text-gray-700 dark:text-gray-300">Recuperado:</label>
                            <TextInput v-model="formattedRecovered" readonly
                                class="w-full bg-gray-100 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <label
                                class="w-28 text-right font-medium text-gray-700 dark:text-gray-300">Devolução:</label>
                            <TextInput v-model="formattedReturn" readonly class="w-full bg-gray-100 dark:bg-gray-700" />
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="w-28 text-right font-medium text-gray-700 dark:text-gray-300">NDOA:</label>
                            <TextInput v-model="formattedNdoa" readonly class="w-full bg-gray-100 dark:bg-gray-700" />
                        </div>
                    </div>
                </div>

                <div class="border rounded-xl p-4 bg-gray-50 dark:bg-gray-700">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-100">Importar preços por Excel</h4>
                        <a href="/pos-venda/storage/excel/valor_unitario.xlsx" download="valor_unitário.xlsx"
                            class="text-sm text-blue-600 hover:underline">Baixar modelo</a>
                    </div>
                    <div class="flex items-center gap-4">
                        <TextInput ref="excelInputRef" @change="uploadExcelAuto" type="file" :disabled="isLoading"
                            accept=".xlsx, .xls" class="block w-full disabled:opacity-50 cursor-pointer" />
                        <span v-if="isLoading" class="text-sm text-gray-600 dark:text-gray-300">Carregando...</span>
                    </div>
                </div>

                <h3 class="text-center font-semibold text-gray-800 dark:text-gray-100 text-lg border-t pt-4 mt-2">
                    Valor unitário por SKU
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="(skuItem, index) in approveForm.skus" :key="index"
                        class="border rounded-xl p-4 shadow-sm bg-white dark:bg-gray-800">
                        <div class="mb-1">
                            <strong class="text-base text-gray-900 dark:text-white">{{ skuItem.sku }}</strong>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ skuItem.family }}</p>
                        </div>
                        <div class="mb-2 text-sm text-gray-700 dark:text-gray-300">
                            Quantidade: <span class="font-medium">{{ skuItem.quantity }}</span>
                        </div>
                        <TextInput v-model="skuItem.price" type="number" step="0.01" min="0"
                            class="w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed" readonly />
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <button type="submit"
                        class="px-4 py-2 bg-[#02B590] text-white rounded-md hover:bg-[#029b80] transition">Aprovar</button>
                    <button type="button" @click="$emit('close')"
                        class="px-4 py-2 bg-gray-400 text-white rounded-md hover:bg-gray-500 dark:bg-gray-600 dark:hover:bg-gray-500 transition">Cancelar</button>
                </div>
            </form>
        </template>
    </DialogModal>
</template>

<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import TextInput from "@/Components/TextInput.vue";
import Swal from "sweetalert2";
import { ref, watch, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
    show: Boolean,
    finalReportData: { type: Object, required: true, default: () => ({}) },
    userRole: { type: String, required: true, default: "" },
});
const emit = defineEmits(["close", "success"]);
const excelInputRef = ref(null);
const isLoading = ref(false);
const formattedRecovered = ref("");
const formattedReturn = ref("");
const formattedNdoa = ref("");

const approveForm = useForm({
    screening_id: props.finalReportData.screeningId,
    rm: "",
    recovered: "",
    return: "",
    ndoa: "",
    status: "laudo aprovado",
    approval_date: "",
    skus: [],
});

const formatCurrency = (val) =>
    Number(val || 0).toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
        minimumFractionDigits: 2,
    });
const unmaskMoney = (value) =>
    !value
        ? "0"
        : value.toString().replace(/\s/g, "").replace("R$", "").replace(/\./g, "").replace(",", ".");

const submitApproveReport = () => {
    if (!approveForm.rm || !formattedRecovered.value || !formattedReturn.value || !formattedNdoa.value) {
        Swal.fire({
            icon: "warning",
            title: "Campos obrigatórios",
            text: "Preencha todos os campos antes de aprovar.",
            confirmButtonColor: "#f59e0b",
        });
        return;
    }

    const hasInvalidSku = approveForm.skus.some((sku) => !sku.price || sku.price <= 0);
    if (hasInvalidSku) {
        // 👉 Fecha o modal antes de exibir o alerta
        emit("close");

        // Pequeno delay para garantir que o modal feche visualmente antes do alerta
        setTimeout(() => {
            Swal.fire({
                title: "Preços inválidos",
                text: "Todos os SKUs devem conter um valor maior que 0.",
                confirmButtonColor: "#f59e0b",
            });
        }, 150);

        return;
    }

    approveForm.status = "laudo aprovado";
    approveForm.approval_date = new Date().toISOString().split("T")[0];
    approveForm.recovered = unmaskMoney(formattedRecovered.value);
    approveForm.return = unmaskMoney(formattedReturn.value);
    approveForm.ndoa = unmaskMoney(formattedNdoa.value);

    approveForm.post(route("customers.finalReport.approve"), {
        preserveScroll: true,

        onSuccess: async () => {
            if (document.activeElement instanceof HTMLElement) document.activeElement.blur();
            emit("success");
            emit("close");

            await new Promise((r) => setTimeout(r, 150));
            Swal.fire({
                icon: "success",
                title: "Sucesso!",
                text: "Laudo aprovado com sucesso.",
                confirmButtonColor: "#3085d6",
            });
        },

        onError: async (errors) => {
            const msgDoBackend =
                errors?.status ||
                errors?.rm ||
                errors?.recovered ||
                errors?.return ||
                errors?.ndoa ||
                errors?.skus ||
                Object.values(errors || {})[0] ||
                "Não foi possível aprovar o laudo.";

            if (document.activeElement instanceof HTMLElement) document.activeElement.blur();
            emit("close");

            await new Promise((r) => setTimeout(r, 150));
            Swal.fire({
                icon: "error",
                title: "Erro",
                text: msgDoBackend,
                confirmButtonColor: "#d33",
            });
        },
    });
};

const uploadExcelAuto = async (event) => {
    const file = event.target.files[0];
    if (!file || isLoading.value) return;
    isLoading.value = true;

    const formData = new FormData();
    formData.append("screening_id", Number(props.finalReportData.screeningId));
    formData.append("excel_file", file);

    try {
        const response = await axios.post(route("excel.upload"), formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        const rows = response.data[0];
        if (!rows || !Array.isArray(rows) || rows.length < 2)
            throw new Error("Arquivo Excel inválido ou sem dados.");

        const processResponse = await axios.post(route("customers.finalReport.excel"), {
            rows,
            screening_id: Number(props.finalReportData.screeningId),
        });

        if (processResponse.data.success) {
            approveForm.skus = processResponse.data.entries.map((item) => ({
                sku: item.sku,
                family: item.family,
                quantity: item.quantity,
                price: item.price,
                editable: false,
            }));

            localStorage.setItem("reopenApproveModal", "true");
            if (document.activeElement instanceof HTMLElement) document.activeElement.blur();
            setTimeout(() => window.location.reload(), 100);
        } else {
            throw new Error(processResponse.data.message || "Erro ao processar os dados no servidor.");
        }
    } catch (error) {
        console.error("Erro ao importar Excel:", error);
        if (document.activeElement instanceof HTMLElement) document.activeElement.blur();
        emit("close");

        const message =
            error.response?.data?.message ||
            error.message ||
            "Erro ao importar planilha.";

        await new Promise((r) => setTimeout(r, 150));

        Swal.fire({
            icon: "error",
            title: "Erro ao importar Excel",
            text: message,
            confirmButtonColor: "#d33",
        });
    } finally {
        isLoading.value = false;
    }
};

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            const grouped = {};
            let recoveredTotal = 0,
                returnTotal = 0,
                ndoaTotal = 0;
            props.finalReportData.groupedReport?.forEach((item) => {
                const sku = item.product;
                const price = parseFloat(item.price) || 0;
                const qtyRecuperado = item.status_counts?.["Recuperado"] ?? 0;
                const qtyDevolucao = item.status_counts?.["Devolução"] ?? 0;
                const qtyMauUso = item.status_counts?.["Mau uso"] ?? 0;
                const qtyForaGarantia = item.status_counts?.["Fora de garantia"] ?? 0;
                recoveredTotal += price * qtyRecuperado;
                returnTotal += price * qtyDevolucao;
                ndoaTotal += price * (qtyMauUso + qtyForaGarantia);
                if (!grouped[sku])
                    grouped[sku] = {
                        sku,
                        family: item.family,
                        quantity: item.quantity,
                        price: item.price || "",
                        editable: !item.price,
                    };
                else grouped[sku].quantity += item.quantity;
            });
            approveForm.skus = Object.values(grouped);
            formattedRecovered.value = formatCurrency(recoveredTotal);
            formattedReturn.value = formatCurrency(returnTotal);
            formattedNdoa.value = formatCurrency(ndoaTotal);
        }
    }
);

onMounted(() => {
    if (localStorage.getItem("reopenApproveModal") === "true") {
        localStorage.removeItem("reopenApproveModal");
        setTimeout(() => window.dispatchEvent(new CustomEvent("open-approve-modal")), 300);
    }
    window.addEventListener("open-approve-modal", () => emit("success"));
});
</script>
