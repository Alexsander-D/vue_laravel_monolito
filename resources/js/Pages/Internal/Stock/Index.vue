<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { ref, computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { Inertia } from "@inertiajs/inertia";
import FormSection from "@/Components/FormSection.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DialogModal from "@/Components/DialogModal.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import Swal from "sweetalert2";

const props = defineProps({
    stocks: {
        type: Array,
        default: () => [],
    },
    movements: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const showEditModal = ref(false);

const form = useForm({
    product_name: "",
    quantity: 1,
    cost_price: "",
    price: "",
});

const editForm = useForm({
    id: null,
    product_name: "",
    quantity: 0,
    cost_price: "",
    price: "",
});

const stockRows = computed(() => props.stocks || []);
const logRows = computed(() => props.movements || []);
const currentUserRole = computed(() => page.props.userRole || "");
const isAdmin = computed(() => currentUserRole.value === "Admin");

const submitStock = () => {
    form.post(route("stock.create"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset("product_name", "quantity", "cost_price", "price");
            Inertia.reload({ only: ["stocks", "movements"] });
            Swal.fire({
                icon: "success",
                title: "Produto adicionado!",
                timer: 1800,
                showConfirmButton: false,
            });
        },
    });
};

const confirmSubmitStock = () => {
    Swal.fire({
        title: "Confirmar adição",
        text: `Deseja adicionar ${form.product_name || "o produto"} ao estoque?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sim, adicionar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
            submitStock();
        }
    });
};

const openEditModal = (stock) => {
    editForm.id = stock.id;
    editForm.product_name = stock.product_name;
    editForm.quantity = stock.quantity;
    editForm.cost_price = stock.cost_price ?? "";
    editForm.price = stock.price ?? "";

    showEditModal.value = true;
};

const confirmDeleteStock = (stock) => {
    Swal.fire({
        title: "Excluir produto",
        text: `Tem certeza que deseja excluir ${stock.product_name}?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sim, excluir",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
            deleteStock(stock);
        }
    });
};

const deleteStock = (stock) => {
    Inertia.delete(route("stock.destroy", { stock: stock.id }), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: "success",
                title: "Produto excluído!",
                timer: 1800,
                showConfirmButton: false,
            });
        },
    });
};

const submitEditStock = () => {
    editForm.put(route("stock.update", { stock: editForm.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            if (editForm.quantity === 0) {
                Swal.fire({
                    icon: "success",
                    title: "Produto removido do estoque",
                    timer: 1800,
                    showConfirmButton: false,
                });
            } else {
                Swal.fire({
                    icon: "success",
                    title: "Estoque atualizado!",
                    timer: 1800,
                    showConfirmButton: false,
                });
            }
            Inertia.reload({ only: ["stocks", "movements"] });
        },
    });
};

const sellStock = async (stock) => {
    const result = await Swal.fire({
        title: `Vender ${stock.product_name}`,
        html: `
            <div class="grid gap-3 text-left">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Quantidade a vender</label>
                    <input id="sellQuantity" type="number" min="1" max="${stock.quantity}" value="1" class="swal2-input" />
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300">Preço de venda unitário: ${formatCurrency(stock.price)}</div>
                <div class="text-sm text-gray-600 dark:text-gray-300">Total: <strong id="sellTotal">${formatCurrency(stock.price)}</strong></div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "Vender",
        cancelButtonText: "Cancelar",
        focusConfirm: false,
        preConfirm: () => {
            const quantityInput = Swal.getPopup().querySelector('#sellQuantity');
            const quantity = parseInt(quantityInput.value, 10);
            if (!quantity || quantity < 1) {
                Swal.showValidationMessage('Informe uma quantidade válida.');
                return false;
            }
            if (quantity > stock.quantity) {
                Swal.showValidationMessage(`A quantidade máxima disponível é ${stock.quantity}.`);
                return false;
            }
            return quantity;
        },
        didOpen: () => {
            const quantityInput = Swal.getPopup().querySelector('#sellQuantity');
            const totalElement = Swal.getPopup().querySelector('#sellTotal');
            quantityInput.addEventListener('input', () => {
                const quantity = parseInt(quantityInput.value, 10) || 0;
                totalElement.textContent = formatCurrency(quantity * Number(stock.price || 0));
            });
        },
    });

    if (!result.isConfirmed) {
        return;
    }

    const quantity = result.value;

    router.put(
        route("stock.sell", { stock: stock.id }),
        { quantity },
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    icon: "success",
                    title: "Venda registrada!",
                    timer: 1800,
                    showConfirmButton: false,
                });
                Inertia.reload({ only: ["stocks", "movements"] });
            },
            onError: () => {
                Swal.fire({
                    icon: "error",
                    title: "Erro ao registrar a venda.",
                });
            },
        }
    );
};

const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
    });
};

const formatDateTime = (value) => {
    if (!value) return "-";
    return new Date(value).toLocaleString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <BaseLayout title="Estoque">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6">
            <div class="w-full mx-auto pt-1">

                <FormSection @submitted="confirmSubmitStock">
                    <template #title>
                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">Controle de Estoque
                            </div>
                            <ActionMessage :on="form.recentlySuccessful">Salvo.</ActionMessage>
                        </div>
                    </template>

                    <template #description>
                        Adicione produtos ao estoque, defina quantidade e preço, e edite os registros sempre que
                        necessário.
                    </template>

                    <template #form>
                        <div class="grid grid-cols-12 gap-4 mt-4">
                            <div class="col-span-12 md:col-span-4">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-200">Produto</label>
                                <TextInput id="product_name" v-model="form.product_name" type="text"
                                    class="mt-1 block w-full" autocomplete="off" />
                                <InputError :message="form.errors.product_name" class="mt-1" />
                            </div>

                            <div class="col-span-12 md:col-span-2">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-200">Quantidade</label>
                                <TextInput id="quantity" v-model="form.quantity" type="number" min="1"
                                    class="mt-1 block w-full" />
                                <InputError :message="form.errors.quantity" class="mt-1" />
                            </div>

                            <div class="col-span-12 md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Preço de custo</label>
                                <TextInput id="cost_price" v-model="form.cost_price" type="number" step="0.01" min="0"
                                    class="mt-1 block w-full" />
                                <InputError :message="form.errors.cost_price" class="mt-1" />
                            </div>

                            <div class="col-span-12 md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Preço de venda</label>
                                <TextInput id="price" v-model="form.price" type="number" step="0.01" min="0"
                                    class="mt-1 block w-full" />
                                <InputError :message="form.errors.price" class="mt-1" />
                            </div>

                            <div class="col-span-12 md:col-span-2 flex items-end">
                                <PrimaryButton type="button" :disabled="form.processing" class="w-full" @click="confirmSubmitStock">Adicionar</PrimaryButton>
                            </div>
                        </div>
                    </template>
                </FormSection>

                <div class="grid gap-6 mt-8 lg:grid-cols-2">
                    <section class="bg-white dark:bg-gray-900 rounded-xl shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Produtos em Estoque</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Produto</th>
                                        <th
                                            class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Quantidade</th>
                                        <th v-if="isAdmin"
                                            class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Preço de custo</th>
                                        <th
                                            class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Preço de venda</th>
                                        <th
                                            class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="stock in stockRows" :key="stock.id">
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{
                                            stock.product_name }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-200">{{
                                            stock.quantity }}
                                        </td>
                                        <td v-if="isAdmin" class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-200">{{
                                            formatCurrency(stock.cost_price) }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-200">{{
                                            formatCurrency(stock.price) }}</td>
                                        <td class="px-4 py-3 text-right space-x-2">
                                            <button type="button" @click="sellStock(stock)"
                                                class="rounded-md bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500">Vender</button>
                                            <template v-if="isAdmin">
                                              <button type="button" @click="openEditModal(stock)"
                                                  class="rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">Editar</button>
                                              <button type="button" @click="confirmDeleteStock(stock)"
                                                  class="rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500">Excluir</button>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr v-if="stockRows.length === 0">
                                        <td colspan="4"
                                            class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Nenhum
                                            produto em estoque.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="bg-white dark:bg-gray-900 rounded-xl shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Log de Entradas e Baixas
                            </h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Produto</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Tipo</th>
                                        <th
                                            class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Quantidade</th>
                                        <th
                                            class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Preço</th>
                                        <th
                                            class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Data</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="movement in logRows" :key="movement.id">
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{
                                            movement.stock_product?.product_name || 'Produto removido' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ movement.type
                                            }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-200">{{
                                            movement.quantity
                                            }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-200">{{
                                            formatCurrency(movement.price) }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-200">{{
                                            formatDateTime(movement.created_at) }}</td>
                                    </tr>
                                    <tr v-if="logRows.length === 0">
                                        <td colspan="5"
                                            class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Nenhum
                                            movimento registrado.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <DialogModal :show="showEditModal" @close="showEditModal = false">
                    <template #title>Editar Estoque</template>
                    <template #content>
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-200">Produto</label>
                                <TextInput id="edit_product_name" v-model="editForm.product_name" type="text"
                                    class="mt-1 block w-full" />
                                <InputError :message="editForm.errors.product_name" class="mt-1" />
                            </div>
                            <div class="col-span-12 md:col-span-6">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-200">Quantidade</label>
                                <TextInput id="edit_quantity" v-model="editForm.quantity" type="number" min="0"
                                    class="mt-1 block w-full" />
                                <InputError :message="editForm.errors.quantity" class="mt-1" />
                            </div>
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Preço de custo</label>
                                <TextInput id="edit_cost_price" v-model="editForm.cost_price" type="number" step="0.01" min="0"
                                    class="mt-1 block w-full" />
                                <InputError :message="editForm.errors.cost_price" class="mt-1" />
                            </div>
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Preço de venda</label>
                                <TextInput id="edit_price" v-model="editForm.price" type="number" step="0.01" min="0"
                                    class="mt-1 block w-full" />
                                <InputError :message="editForm.errors.price" class="mt-1" />
                            </div>
                        </div>
                    </template>
                    <template #footer>
                        <div class="flex justify-end gap-2">
                            <PrimaryButton type="button" class="bg-gray-600 hover:bg-gray-700"
                                @click="showEditModal = false">Cancelar</PrimaryButton>
                            <PrimaryButton :disabled="editForm.processing" @click="submitEditStock">Salvar
                            </PrimaryButton>
                        </div>
                    </template>
                </DialogModal>
            </div>
        </div>

    </BaseLayout>
</template>
