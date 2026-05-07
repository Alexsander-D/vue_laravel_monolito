<script setup>
import $ from "jquery";
import { computed, ref, defineProps, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import { Inertia } from "@inertiajs/inertia";
import ProductModal from "../Partials/ProductModal.vue";
import Select from "@/Components/Select.vue";
import TextInput from "@/Components/TextInput.vue";
import Datatable from "@/Components/DatatableServerSide.vue";

const props = defineProps({
    productsData: {
        type: Object,
        required: true,
        default: () => [],
    },
});

const products = ref([]);
const openModal = ref(false);
const tableId = ref("ManageEntries");

const form = useForm({
    screening_id: props.productsData.screeningId,
    products_id: "",
    quantity: "",
    price: "",
    warranty: false,
    guarantee: "fora de garantia",
});

const warrantyValue = computed(() =>
    form.warranty ? "fora de garantia" : "em garantia"
);
const productIdValue = computed(
    () => form.products_id?.value || form.products_id || null
);
const dataInfo = ref([...props.productsData.entries]);

const entryForm = () => {
    form.guarantee = warrantyValue.value;
    form.products_id = productIdValue.value;

    axios
        .post(route("ProductEntry.create"), form)
        .then(() => {

            $(`#${tableId.value}`).DataTable().ajax.reload(null, false);

            form.reset(
                "products_id",
                "quantity",
                "price",
                "warranty",
                "guarantee"
            );
        })
        .catch((error) => {
            if (error.response?.status === 422) {
                const errors = error.response.data.errors;

                if (errors.price) {
                    Swal.fire({
                        icon: "warning",
                        title: "Atenção",
                        text: errors.price,
                        confirmButtonText: "OK"
                    });
                }

                form.errors = errors;
            } else {
                console.error("Erro inesperado:", error);
                Swal.fire({
                    icon: "error",
                    title: "Erro",
                    text: "Ocorreu um erro inesperado. Tente novamente.",
                });
            }
        });


};

const fetchProducts = async (search = "") => {
    try {
        const response = await axios.post(route("findProducts.show"), {
            sku: search,
        });
        if (response.data) {
            products.value = response.data.map((item) => ({
                label: item.sku,
                value: item.id,
            }));
        }
    } catch (error) {
        console.error(`Erro ao carregar produtos: ${error.message}`);
    }
};

const deleteEntry = (entryId) => {
    axios
        .delete(route("ProductEntry.destroy", entryId))
        .then(() => {
            $(`#${tableId.value}`).DataTable().ajax.reload(null, false);
        })
        .catch((error) => {
            console.error("Erro ao excluir produto:", error);
        });
};

onMounted(() => {
    fetchProducts();
    $(document).off("click", ".delete-btn").on("click", ".delete-btn", function () {
        const entryId = $(this).data("id");
        deleteEntry(entryId);
    });

    $(`#${tableId.value}`).on("xhr.dt", function (e, settings, json) {
        if (json && json.data) {
            dataInfo.value = json.data;
        }
    });
});

const deleteAllEntries = () => {
    axios
        .delete(route("ProductEntry.destroyAll", props.productsData.screeningId))
        .then(() => {
            
            $(`#${tableId.value}`).DataTable().ajax.reload(null, false);


            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: "Todos os produtos foram removidos!",
                showConfirmButton: false,
                timer: 2000,
            });
        })
        .catch(() => {
            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "error",
                title: "Falha ao excluir registros!",
                showConfirmButton: false,
                timer: 2000,
            });
        });
};

const tableHeaders = ref([
    { name: 'Produto', data: 'sku' },
    { name: 'Família', data: 'family' },
    { name: 'Garantia', data: 'guarantee' },
    { name: 'Quantidade', data: 'quantity' },
    { name: 'Valor unitário', data: 'price' },
    { name: 'Excluir', data: 'button', orderable: false, searchable: false }
]);

const handleEntriesUpdate = (entries) => {
    dataInfo.value = entries;
    openModal.value = false;
};

const finalizeScreening = () => {
    if (props.productsData.typeService === "pre-agenda") {
        Swal.fire({
            icon: "success",
            title: "Concluído",
            text: "Os dados foram salvos com sucesso!",
        }).then(() => Inertia.get(route("ViewScreening.index")));
        return;
    }

    axios
        .patch(route("Screening.finalize", props.productsData.screeningId))
        .then(() => {
            Swal.fire({
                icon: "success",
                title: "Concluído",
                text: "Os dados foram salvos com sucesso!",
            }).then(() => Inertia.get(route("ViewScreening.index")));
        })
        .catch(() => {
            Swal.fire("Erro", "Falha ao finalizar triagem.", "error");
        });
};

const hasProducts = computed(() => dataInfo.value.length > 0);
</script>

<template>

    <ProductModal :show="openModal" :screening-id="props.productsData.screeningId" @updateEntries="handleEntriesUpdate"
        @close="openModal = false" />

    <div class="mb-6">

        <div class="text-center mb-2">
            <h2 class="text-lg font-semibold dark:text-gray-100">Clientes</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Adicione os produtos que serão atendidos na triagem.
            </p>
        </div>

        <div class="flex justify-end">
            <button @click="openModal = true" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 
             dark:bg-blue-600 dark:hover:bg-blue-700 transition-colors whitespace-nowrap">
                Importar Lista
            </button>
        </div>
    </div>

    <div
        class="col-span-2 text-center flex flex-col justify-center items-center gap-4 p-4 rounded-lg border border-neutral-300 dark:border-neutral-700 shadow-md bg-white dark:bg-gray-800 transition-colors duration-300 mb-10 sm:mb-12">
        <span class="text-lg font-semibold text-neutral-900 dark:text-neutral-200">
            ID: {{ props.productsData.screeningId }}
            <span class="text-neutral-700 dark:text-white">
                - {{ props.productsData.companyName }}
            </span>
        </span>

        <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
            {{ props.productsData.city }},
            <span class="text-neutral-700 dark:text-white">
                {{ props.productsData.state }}
            </span>
        </span>

        <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
            <span class="text-neutral-700 dark:text-white">
                {{ props.productsData.typeService.toUpperCase() }}
            </span>
        </span>

        <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
            Status triagem:
            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                {{ props.productsData.screeningStatus.toUpperCase() }}
            </span>
        </span>
    </div>

    <form @submit.prevent="entryForm"
        class="space-y-6 sm:space-y-8 mb-10 text-center bg-white dark:bg-gray-900 rounded-xl p-6 shadow-inner transition-colors duration-300">

        <div
            class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-end justify-center text-center w-full max-w-6xl mx-auto">

            <div class="sm:col-span-5 flex flex-col items-center text-center">
                <label class="font-medium text-sm mb-2 dark:text-gray-200" for="productSelect">
                    Produto (SKU)
                </label>

                <Select id="productSelect" class="block w-full text-center border border-gray-300 dark:border-gray-600
             rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
             focus:ring-2 focus:ring-blue-400 dark:focus:ring-blue-500 transition-colors" :options="products"
                    v-model="form.products_id" @search-change="fetchProducts" label="label" :reduce="item => item.value"
                    placeholder="Digite o SKU..." required />

                <InputError :message="form.errors.products_id" class="mt-1" />
            </div>

            <div class="col-span-2">
                <div class="flex flex-col gap-2">
                    <TextInput id="quantityInput" v-model="form.quantity" type="number" min="1" placeholder="Quantidade"
                        required
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                    <InputError :message="form.errors.quantity" />
                </div>
            </div>

            <div class="col-span-2">
                <div class="flex flex-col gap-2">
                    <TextInput id="unitPriceInput" v-model="form.price" type="number" min="0" step="0.01"
                        placeholder="Valor unitário"
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                    <InputError :message="form.errors.price" />
                </div>
            </div>

            <div class="col-span-3 flex items-center gap-2 mt-0">
                <input id="warrantyCheckbox" name="warranty" type="checkbox" v-model="form.warranty"
                    @change="form.guarantee = warrantyValue"
                    class="border-gray-200 rounded text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" />
                <label class="font-medium inline-block colorBase dark:text-neutral-200" for="warrantyCheckbox">
                    Fora de garantia?
                </label>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-4 sm:gap-6 mt-8">
            <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700
             dark:bg-green-700 dark:hover:bg-green-800 transition-colors shadow-sm">
                Salvar
            </button>
            <button type="button" @click="deleteAllEntries" class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700
             dark:bg-red-700 dark:hover:bg-red-800 transition-colors shadow-sm">
                Limpar Lista
            </button>
        </div>
    </form>

    <div class="mt-6">
        <Datatable :thead="tableHeaders" :ajax="route('ProductEntry.datatable', props.productsData.screeningId)"
            :export-url="route('ProductEntry.export', props.productsData.screeningId)" :id="tableId" />

    </div>

    <div class="flex justify-end mt-6">
        <button @click="finalizeScreening" :disabled="!hasProducts" class="px-6 py-2 rounded-md text-white font-medium
         bg-green-600 hover:bg-green-700
         disabled:opacity-50 disabled:cursor-not-allowed
         dark:bg-green-700 dark:hover:bg-green-800
         focus:outline-none focus:ring-2 focus:ring-green-400 dark:focus:ring-green-500
         transition-all duration-200 shadow-sm">
            Finalizar
        </button>
    </div>
</template>
