<script setup>
import { ref, defineProps, watch, onMounted, watchEffect, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import TextArea from "@/Components/TextArea.vue";
import Select from "@/Components/Select.vue";
import axios from "axios";
import { Inertia } from "@inertiajs/inertia";
import Swal from "sweetalert2";

const props = defineProps({
    productivityData: {
        type: Object,
        required: true,
        default: () => [],
    },
});

const errors = ref({});
const statusOptions = ref(["Recuperado", "Devolução", "Mau uso", "Próxima triagem", "Produto não encontrado", "Fora de garantia"]);
const products = ref([]);
const defects = ref([]);
const components = ref([]);
let defectsSolutionOption = ref([]);

const addNewFailure = () => {
    const newItem = {
        selectComponent: {
            label: "",
            value: "",
        },
        selectFailure: {
            label: "",
            value: "",
        },
    };
    defectsSolutionOption.value.push(newItem);
};

const removeFailure = (index) => {
    defectsSolutionOption.value.splice(index, 1);
};

if (props.productivityData.defectsSolutions && props.productivityData.defectsSolutions.length > 0) {
    // Mantém os valores originais ao montar
    defectsSolutionOption.value = props.productivityData.defectsSolutions.map(item => ({
        selectComponent: {
            label: item.component_label || "",
            value: item.component_id || ""
        },
        selectFailure: {
            label: item.defect_label || "",
            value: item.defect_id || ""
        },
    }));
} else {
    addNewFailure();
}

const onSelectChange = (selectedOption, type, index = null) => {
    const value = selectedOption?.value;

    if (type === "component") {
        defectsSolutionOption.value[index].selectFailure = {};
        defects.value[index] = [];
        if (value) {
            fetchDefects(value, index);
        }
    }
};

const form = useForm({
    screening_report_id: props.productivityData.screeningReportId,
    product: '',
    family: '',
    status: '',
    defeitoEncontrado: 'sim',
    failureSolution: {},
    serial_number: '',
    imei1: '',
    imei2: '',
    hardware_version: '',
    qr_code: '',
    gemco: '',
    seal: '',
    UniqueID: '',
    fm: '',
    patrimony: '',
    observation: '',
})

watchEffect(() => {
    if (props.productivityData) {
        form.screening_report_id = props.productivityData.screeningReportId || '';
        form.status = props.productivityData.status || '';
        form.serial_number = props.productivityData.serial_number || '';
        form.imei1 = props.productivityData.imei1 || '';
        form.imei2 = props.productivityData.imei2 || '';
        form.hardware_version = props.productivityData.hardware_version || '';
        form.qr_code = props.productivityData.qr_code || '';
        form.gemco = props.productivityData.gemco || '';
        form.seal = props.productivityData.seal || '';
        form.UniqueID = props.productivityData.UniqueID || '';
        form.fm = props.productivityData.fm || '';
        form.patrimony = props.productivityData.patrimony || '';
        form.observation = props.productivityData.observation || '';
        form.failureSolution = props.productivityData.defectsSolutions || {};
    }
});

const isFormValid = computed(() => {

    if (!Array.isArray(defectsSolutionOption.value)) return false;

    // Se NÃO → basta validar status
    if (form.defeitoEncontrado === "nao") {
        return form.status && form.status !== "pendente";
    }

    // Se SIM → validar defeitos
    if (form.defeitoEncontrado === "sim") {

        const list = defectsSolutionOption.value;

        const filledItems = list.filter(item =>
            item?.selectComponent?.value || item?.selectFailure?.value
        );

        if (filledItems.length === 0) return false;

        const validFilled = filledItems.every(item =>
            item?.selectComponent?.value && item?.selectFailure?.value
        );

        return validFilled && form.status && form.status !== "pendente";
    }

    return false;
});


const submitReportForm = async () => {
    if (!isFormValid.value) return;
    form.processing = true;
    errors.value = {}; // Limpa os erros anteriores
    form.fm = form.fm ? "sim" : "não";

    try {
        await form.put(
            route("productivityReport.updateDefectsSolutions", { screeningReportId: form.screening_report_id }),
            {
                errorBag: "submitReportForm",
                preserveScroll: true,
                onSuccess: async () => {
                    form.reset();
                    Inertia.reload({ only: ["entries"] });
                },
                onError: (validationErrors) => {
                    console.error("Erro ao enviar o formulário:", validationErrors);
                    errors.value = validationErrors; // Armazena os erros para exibição no template

                    // 🔹 Exibir alerta apenas informando que há campos incorretos
                    if (Object.keys(validationErrors).length > 0) {
                        Swal.fire({
                            icon: "error",
                            title: "Erro no preenchimento!",
                            text: "Existem campos preenchidos incorretamente. Verifique e tente novamente.",
                            confirmButtonText: "OK",
                        });
                    }
                },
            }
        );

    } catch (error) {
        console.error("Erro inesperado ao enviar o formulário:", error);
    }
};

const fetchData = async (url, params, setter, key) => {
    try {
        const response = await axios.post(url, params);

        if (response.data) {
            const mappedData = response.data.map((item) => {
                let result = {};

                if (key === "products") {
                    result = {
                        label: item.sku,
                        value: item.family,
                    };
                } else if (key === "components") {
                    result = {
                        label: item.component,
                        value: item.id,
                    };
                } else if (key === "defects") {
                    result = {
                        label: item.defect + " => " + item.solution,
                        value: item.id,
                    };
                }

                return result;
            });

            setter(mappedData);
        }
    } catch (error) {
        console.error(`Erro ao carregar dados: ${error.message}`);
    }
};

const fetchComponents = (family) =>
    fetchData(
        route("productivityReport.findComponentsByFamily"),
        { family: family },
        (data) => (components.value = data),
        "components"
    );
const fetchDefects = (component, index) =>
    fetchData(
        route("productivityReport.findDefects"),
        { component_id: component },
        (data) => (defects.value[index] = data),
        "defects"
    );

watch(
    defectsSolutionOption,
    (newValue) => {
        form.failureSolution = JSON.parse(JSON.stringify(newValue));
    },
    { deep: true }
);

watch(() => form.fm, (newValue) => {
    if (newValue === true) {
        Swal.fire({
            icon: "info",
            title: "Falta de Material",
            text: "Informe na Observação qual material faltou para efetuar o reparo do produto.",
            confirmButtonText: "OK",
        }).then(() => {
            const obsInput = document.getElementById("obs");
            if (obsInput) obsInput.focus();
        });
    }
});
onMounted(() => {
    if (props.productivityData.products) {
        const productFound = props.productivityData.products.find(
            (product) => product.id == props.productivityData.screeningReportId
        );

        if (productFound) {
            form.product = {
                label: productFound.sku,
                value: productFound.id,
            };
            form.family = productFound.family;

            if (form.family) {
                fetchComponents(form.family);
            }
        }

        products.value = props.productivityData.products.map((product) => ({
            label: product.sku,
            value: product.id,
        }));

        if (props.productivityData.guarantee === "fora de garantia") {
            Swal.fire({
                icon: "info",
                title: "Produto fora de garantia",
                text: "Este produto está fora de garantia. Atenção ao realizar lançamento.",
                confirmButtonText: "OK",
            });
        }
    }
});

const familyQrCode = [
    'VIDEO',
    'PEN DRIVE',
    'OEM',
    'INFORMATICA',
    'SEGURANCA',
    'BABY',
    'HEALTH CARE',
    'FERRAMENTAS',
    'GYM',
    'PAPEL E ESCRITORIO',
    'ESCRITORIO',
    'AUTOMOTIVO',
    'ESPORTES',
    'MOBILIDADE ELETRICA',
    'MEMORY CARD',
    'SSD',
    'ELETROPORTATEIS',
    'AC MOBILE'
];

</script>

<style scoped>
.colorBase {
    color: var(--cor-contraste);
}
</style>

<template>
    <FormSection @submitted="submitReportForm">
        <template #title> Produtividade triagem</template>
        <template #description> Atualize as informações do produto. </template>
        <template #form>
            <div class="grid grid-cols-12 gap-2 mt-3 text-center">
                <div class="col-span-12">
                    <span id="uniqueId" class="font-medium inline-block colorBase mt-2 dark:text-neutral-200 w-full">
                        ID produto: {{ props.productivityData.screeningReportId }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-2 mt-3">
                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="productSelect">
                        Produto
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <Select id="productSelect" class="mt-1 block w-full" :options="products" v-model="form.product"
                            label="label" disabled />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-2 mt-3">
                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="familyInput">
                        Família
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="familyInput" type="text" v-model="form.family" class="mt-1 block w-full"
                            autocomplete="off" placeholder="Selecione o produto" readonly disabled />
                        <InputError :message="form.errors.family" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-2 mt-3" v-if="props.productivityData">

                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="status">
                        Status
                    </label>
                </div>

                <template v-if="form.status === 'Devolução'">
                    <div class="col-span-7">
                        <div class="flex flex-col gap-2">
                            <Select id="statusSelect" class="mt-1 block w-full" :options="statusOptions"
                                v-model="form.status" required />
                            <InputError :message="errors.status" />
                        </div>
                    </div>

                    <div class="col-span-3">
                        <div class="flex items-center h-full">
                            <input id="fmCheckbox" name="fm" type="checkbox" v-model="form.fm"
                                class="border-gray-200 rounded text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800" />
                            <label for="fmCheckbox"
                                class="font-medium inline-block ml-2 colorBase dark:text-neutral-200">
                                Falta de Material?
                            </label>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="col-span-9">
                        <div class="flex flex-col gap-2">
                            <Select id="statusSelect" class="mt-1 block w-full" :options="statusOptions"
                                v-model="form.status" required />
                            <InputError :message="errors.status" />
                        </div>
                    </div>
                </template>
            </div>

            <div class="grid grid-cols-12 gap-2 mt-3">
                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200">
                        Foi encontrado defeito?
                    </label>
                </div>

                <div class="col-span-9 flex items-center gap-4">
                    <label>
                        <input type="radio" value="sim" v-model="form.defeitoEncontrado" />
                        Sim
                    </label>
                    <label>
                        <input type="radio" value="nao" v-model="form.defeitoEncontrado" />
                        Não
                    </label>
                </div>
            </div>

            <div id="failure-container" v-for="(defectSolution, index) in defectsSolutionOption" :key="index"
                v-if="form.defeitoEncontrado === 'sim'">
                <div class="mt-5">
                    <div class="grid grid-cols-12 gap-2">

                        <div class="col-span-2 text-center">
                            <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
                                :for="'componentSelect' + index">
                                Componente
                            </label>
                        </div>

                        <div class="col-span-8" id="componentSelectLenght">
                            <div class="flex flex-col gap-2">
                                <Select :id="'defectSelect' + index" class="mt-1 block w-full" :options="components"
                                    v-model="defectsSolutionOption[index].selectComponent"
                                    @selected="(option) => onSelectChange(option, 'component', index)" label="label"
                                    :required="form.defeitoEncontrado === 'sim'" />

                                <InputError v-if="form.errors.failureSolution" :message="form.errors.failureSolution" />
                            </div>
                        </div>

                        <div class="col-span-2 flex justify-start items-center space-x-2" id="addButton">

                            <button type="button" @click="addNewFailure()"
                                class="flex items-center justify-center mt-2 size-8 text-sm rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </button>

                            <button v-if="index > 0" type="button" @click="removeFailure(index)"
                                class="flex items-center justify-center mt-2 size-8 text-sm rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:bg-red-700 disabled:opacity-50 disabled:pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-2 mt-3">

                        <div class="col-span-2 text-center">
                            <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
                                :for="'defectSelect' + index">
                                Defeito e Solução
                            </label>
                        </div>

                        <div class="col-span-9">
                            <div class="flex flex-col gap-2">
                                <Select :id="'defectSelect' + index" class="mt-1 block w-full" :options="defects[index]"
                                    v-model="defectsSolutionOption[index].selectFailure" label="label"
                                    :required="form.defeitoEncontrado === 'sim'" />

                                <InputError v-if="form.errors.failureSolution" :message="form.errors.failureSolution" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-2 mt-3">
                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="serialNumber">
                        Serial Number
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="serialNumber" v-model="form.serial_number" type="text" class="mt-1 block w-full"
                            autocomplete="off" required />
                        <InputError :message="form.errors.serial_number" />

                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-2 mt-3"
                v-if="['FEATURE PHONE', 'SMARTPHONE', 'TABLET', 'WEARABLE'].includes(form.family)">
                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="imei1">
                        IMEI 1
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="imei1" v-model="form.imei1" type="text" class="mt-1 block w-full"
                            autocomplete="off" required />
                        <InputError :message="form.errors.imei1" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-2 mt-3"
                v-if="['FEATURE PHONE', 'SMARTPHONE', 'TABLET', 'WEARABLE'].includes(form.family)">
                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="imei2">
                        IMEI 2
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="imei2" v-model="form.imei2" type="text" class="mt-1 block w-full"
                            autocomplete="off" required />
                        <InputError :message="form.errors.imei2" />
                    </div>
                </div>
            </div>



            <div class="grid grid-cols-12 gap-2 mt-3" v-if="form.products?.[0]?.family === 'TABLET'">
                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="hardware_version">
                        Versão hardware
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="hardware_version" v-model="form.hardware_version" type="text"
                            class="mt-1 block w-full" autocomplete="off" />
                        <InputError :message="form.errors.hardware_version" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-2 mt-3" v-if="familyQrCode.includes(form.products?.[0]?.family)">
                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="qr_code">
                        QR code
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="qr_code" v-model="form.qr_code" type="text" class="mt-1 block w-full"
                            autocomplete="off" />
                        <InputError :message="form.errors.qr_code" />
                    </div>
                </div>
            </div>


            <template v-if="form.company_name === 'MAGAZINE LUIZA S/A'">
                <div class="grid grid-cols-12 gap-2 mt-3">
                    <div class="col-span-2 text-center">
                        <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="gemco">
                            Gemco
                        </label>
                    </div>

                    <div class="col-span-9">
                        <div class="flex flex-col gap-2">
                            <TextInput id="gemco" v-model="form.gemco" type="text" class="mt-1 block w-full"
                                autocomplete="off" />
                            <InputError :message="form.errors.gemco" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-2 mt-3">
                    <div class="col-span-2 text-center">
                        <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="seal">
                            Lacre
                        </label>
                    </div>

                    <div class="col-span-9">
                        <div class="flex flex-col gap-2">
                            <TextInput id="seal" v-model="form.seal" type="text" class="mt-1 block w-full"
                                autocomplete="off" />
                            <InputError :message="form.errors.seal" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-2 mt-3">
                    <div class="col-span-2 text-center">
                        <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="UniqueID">
                            ID único
                        </label>
                    </div>

                    <div class="col-span-9">
                        <div class="flex flex-col gap-2">
                            <TextInput id="UniqueID" v-model="form.UniqueID" type="text" class="mt-1 block w-full"
                                autocomplete="off" />
                            <InputError :message="form.errors.UniqueID" />
                        </div>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-12 gap-2 mt-3" v-if="productivityData.government === 'GOVERNO'">
                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="patrimony">
                        Patrimônio
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextInput id="patrimony" v-model="form.patrimony" type="text" class="mt-1 block w-full"
                            autocomplete="off" required />
                        <InputError :message="form.errors.patrimony" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-2 mt-3">
                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="obs">
                        Observação
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex flex-col gap-2">
                        <TextArea id="obs" v-model="form.observation" class="mt-1 block w-full" autocomplete="off" />
                        <InputError :message="form.errors.observation" />
                    </div>
                </div>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Salvo.
            </ActionMessage>
            <PrimaryButton :class="{ 'opacity-25': form.processing || !isFormValid }"
                :disabled="form.processing || !isFormValid" @click="submitReportForm">
                Salvar
            </PrimaryButton>
        </template>

    </FormSection>
</template>
