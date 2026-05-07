<script setup>
import $ from "jquery";
import { computed, ref, defineProps, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import Select from "@/Components/Select.vue";
import TextInput from "@/Components/TextInput.vue";
import "moment-timezone";
import Swal from "sweetalert2";
import { Inertia } from "@inertiajs/inertia";
import InfoCard from "@/Components/InfoCard.vue";
import { router } from '@inertiajs/vue3';

const props = defineProps({
    dataInclude: {
        type: Object,
        default: () => [],
    },
});

const products = ref([]);
const tableId = ref("ManageEntries");

const form = useForm({
    screening_id: props.dataInclude.screeningId,
    products_id: "",
    quantity: "",
    price: "",
    warranty: false,
    guarantee: "fora de garantia",
    include: "sim",
});

const warrantyValue = computed(() =>
    form.warranty ? "fora de garantia" : "em garantia"
);
const productIdValue = computed(() => form.products_id?.value || null);

const dataInfo = ref([...props.dataInclude.entries]);


const entryForm = () => {
    form.screening_id = props.dataInclude.screeningId;
    form.guarantee = warrantyValue.value;
    form.products_id = productIdValue.value;
    form.include = "sim";

    form.post(route("include.store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset("products_id", "quantity", "price", "warranty", "guarantee");

            router.visit(route('include.show', { screening_id: props.dataInclude.screeningId }), {
                preserveScroll: true,
                only: ['dataInclude'],
                onSuccess: (page) => {
                    dataInfo.value = page.props.dataInclude.entries;
                },
            });
        },
        onError: (error) => {
            console.error("Erro no envio:", error);
        },
    });
};

const fetchProducts = (search) =>
    fetchData(
        route("findProducts.show"),
        { sku: search },
        (data) => (products.value = data),
        "products"
    );

const fetchData = async (url, params, setter, key) => {
    try {
        const response = await axios.post(url, params);
        if (response.data) {
            const mappedData = response.data.map((item) => ({
                label: item.sku,
                value: item.id,
            }));
            setter(mappedData);
        }
    } catch (error) {
        console.error(`Erro ao carregar dados: ${error.message}`);
    }
};

const deleteEntry = (entryId) => {
    axios
        .delete(route("IncludeEntry.destroy", { entryId }))
        .then((response) => {
            dataInfo.value = response.data.entries;
            Inertia.reload();
        })
        .catch((error) => {
            console.error("Erro:", error);
        });
};

const deleteButton = (entryId, include) => {
    const isDisabled = include !== "sim";
    const classes = isDisabled
        ? "bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400 cursor-not-allowed"
        : "bg-red-600 text-white hover:bg-red-700";

    const disabledAttr = isDisabled ? "disabled" : "";

    return `
    <button type="button" data-id="${entryId}" class="delete-btn flex shrink-0 justify-center items-center gap-2 size-[38px] text-sm rounded-lg ${classes}" ${disabledAttr}>
      x
    </button>
  `;
};

onMounted(() => {
    fetchProducts();
    $(document).on("click", ".delete-btn", function () {
        const entryId = $(this).data("id");
        deleteEntry(entryId);
    });
});

const deleteAllEntries = () => {
    Swal.fire({
        title: "Tem certeza?",
        text: "Todos os includes dessa triagem serão apagados!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sim, excluir!",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .delete(route("IncludeEntry.destroyAll", { screeningId: props.dataInclude.screeningId }))
                .then(() => {

                    router.visit(route('include.show', { screening_id: props.dataInclude.screeningId }), {
                        preserveScroll: true,
                        only: ['dataInclude'],
                        onSuccess: (page) => {
                            dataInfo.value = page.props.dataInclude.entries;
                            Swal.fire({
                                icon: "success",
                                title: "Limpeza concluída!",
                                text: "Todos os includes foram removidos.",
                            });
                        },
                    });
                })
                .catch((error) => {
                    console.error("Erro ao limpar lista:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Erro",
                        text: "Não foi possível excluir os registros.",
                    });
                });
        }
    });
};

const tableHeaders = ref([
    { name: "Produto", data: "sku" },
    { name: "Família", data: "family" },
    { name: "Garantia", data: "guarantee" },
    { name: "Quantidade", data: "quantity" },
    { name: "Valor Unitário", data: "price" },
    {
        name: "Excluir",
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
            return deleteButton(row.id, row.include);
        }
    }
]);

const finalizeScreening = () => {
    Swal.fire({
        icon: "success",
        title: "Includes realizados com sucesso!",
        text: "Os produtos foram salvos na triagem.",
        showConfirmButton: true,
    }).then(() => {
        Inertia.get(route("ViewScreening.index"));
    });
};

const hasProducts = computed(() => dataInfo.value.length > 0);

const previsto = computed(() => {
    return props.dataInclude.entries
        .filter(e => e.include === null)
        .reduce((total, e) => total + (e.quantity || 0), 0);
});

const include = computed(() => {
    return props.dataInclude.entries
        .filter(e => e.include?.trim().toLowerCase() === 'sim')
        .reduce((total, e) => total + (e.quantity || 0), 0);
});

const total = computed(() => previsto.value + include.value);

</script>

<template>

    <div class="mb-6">
        <div class="text-center mb-2">
            <h2 class="text-lg font-semibold dark:text-gray-100">Includes</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Adicione os produtos solicitados na triagem.
            </p>
        </div>
    </div>

    <div class="col-span-2 text-center flex flex-col justify-center items-center gap-3 sm:gap-4 p-6 rounded-xl
               border border-neutral-300 dark:border-neutral-700 shadow-md
               bg-white dark:bg-gray-800 transition-colors duration-300 mb-10 sm:mb-12">
        <span class="text-lg font-semibold text-neutral-900 dark:text-neutral-200">
            ID: {{ props.dataInclude.screeningId }}
            <span class="text-neutral-700 dark:text-white">
                - {{ props.dataInclude.companyName }}
            </span>
        </span>

        <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
            {{ props.dataInclude.city }},
            <span class="text-neutral-700 dark:text-white">
                {{ props.dataInclude.state }}
            </span>
        </span>

        <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
            <span class="text-neutral-700 dark:text-white">
                {{ props.dataInclude.typeService.toUpperCase() }}
            </span>
        </span>

        <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
            Status triagem:
            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                {{ props.dataInclude.screeningStatus.toUpperCase() }}
            </span>
        </span>
    </div>

    <div class="w-full flex justify-center mt-4 mb-8">
        <div class="flex gap-4 w-full max-w-6xl px-4">
            <div class="flex-1">
                <InfoCard title="Previsto" :data="previsto" />
            </div>
            <div class="flex-1">
                <InfoCard title="Include" :data="include" />
            </div>
            <div class="flex-1">
                <InfoCard title="Total" :data="total" />
            </div>
        </div>
    </div>

    <form @submit.prevent="entryForm"
        class="space-y-6 sm:space-y-8 mb-10 text-center bg-white dark:bg-gray-900 rounded-xl p-6 shadow-inner transition-colors duration-300">

        <div
            class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-end justify-center text-center w-full max-w-6xl mx-auto">

            <div class="sm:col-span-5 flex flex-col items-center text-center">
                <label for="productSelect" class="font-medium text-sm mb-2 dark:text-gray-200">
                    Produto (SKU)
                </label>

                <Select id="productSelect" class="block w-full text-center border border-gray-300 dark:border-gray-600
                           rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                           focus:ring-2 focus:ring-blue-400 dark:focus:ring-blue-500 transition-colors"
                    :options="products" v-model="form.products_id" @search-change="fetchProducts" label="label"
                    required />

                <InputError :message="form.errors.products_id" class="mt-1" />
            </div>

            <div class="col-span-2 flex flex-col items-center">
                <label for="quantityInput" class="font-medium text-sm mb-2 dark:text-gray-200">Quantidade</label>
                <TextInput id="quantityInput" v-model="form.quantity" type="number" min="1" placeholder="Qtd." class="block w-full py-2 px-3 text-center border border-gray-300 rounded-md 
                           focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 
                           sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                <InputError :message="form.errors.quantity" class="mt-1" />
            </div>

            <div class="col-span-2 flex flex-col items-center">
                <label for="priceInput" class="font-medium text-sm mb-2 dark:text-gray-200">Valor Unitário</label>
                <TextInput id="priceInput" v-model="form.price" type="number" min="0" step="0.01" placeholder="R$ 0,00"
                    class="block w-full py-2 px-3 text-center border border-gray-300 rounded-md 
                           focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 
                           sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                <InputError :message="form.errors.price" class="mt-1" />
            </div>

            <div class="col-span-3 flex items-center justify-center gap-2">
                <input id="warrantyCheckbox" name="warranty" type="checkbox" v-model="form.warranty"
                    @change="form.guarantee = warrantyValue" class="border-gray-200 rounded text-blue-600 focus:ring-blue-500 
                           dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 
                           dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" />
                <label for="warrantyCheckbox" class="font-medium text-sm dark:text-gray-200">
                    Fora de garantia?
                </label>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-4 sm:gap-6 mt-8">
            <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                class="px-8 py-3 rounded-lg font-medium">
                Salvar
            </PrimaryButton>

            <button type="button" @click="deleteAllEntries" class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700
                       dark:bg-red-700 dark:hover:bg-red-800 transition-colors shadow-sm">
                Limpar Lista
            </button>
        </div>

        <div class="col-span-12 flex justify-end mt-4">
            <ActionMessage :on="form.recentlySuccessful">
                Salvo.
            </ActionMessage>
        </div>
    </form>

    <div class="mt-8">
        <Datatable :thead="tableHeaders" :ajax="route('include.datatable', props.dataInclude.screeningId)"
            :export-url="route('include.includeExport', props.dataInclude.screeningId)" :id="tableId" />

    </div>

    <div class="flex justify-end mt-8">
        <button type="button" @click="finalizeScreening" :disabled="!hasProducts" class="px-6 py-3 rounded-md text-white font-medium
                   bg-green-600 hover:bg-green-700
                   disabled:opacity-50 disabled:cursor-not-allowed
                   dark:bg-green-700 dark:hover:bg-green-800
                   focus:outline-none focus:ring-2 focus:ring-green-400 dark:focus:ring-green-500
                   transition-all duration-200 shadow-sm">
            Finalizar
        </button>
    </div>
</template>
