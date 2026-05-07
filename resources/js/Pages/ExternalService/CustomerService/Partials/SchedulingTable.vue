<script setup>
import $ from "jquery";
import { computed, ref, defineProps, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Datatable from "@/Components/Datatable.vue";
import Select from "@/Components/Select.vue";
import "moment-timezone";
import Swal from "sweetalert2";

const props = defineProps({
    schedulingData: {
        type: Object,
        required: true,
        default: () => [],
    },
});

const form = useForm({
    screening_id: props.schedulingData.screeningId || "",
    user_id: "",
    technical: "",
});

const entryForm = () => {
    const payload = {
        screening_id: form.screening_id,
        user_id: form.user_id,
        technical: form.technical,
    };

    form.post(route("customers.scheduling.show"), {
        data: payload,
        preserveScroll: true,
        onSuccess: (response) => {
            form.reset("technical", "user_id");

            if (response.props && response.props.newTechnical) {
                tableData.value.push({
                    id: response.props.newTechnical.id,
                    technical: response.props.newTechnical.technical,
                    buttonDelete: deleteButton(response.props.newTechnical.id),
                });
            }
        },
    });
};

const deleteEntry = async (scaleId) => {
    try {
        const response = await axios.delete(route("customers.scheduling.delete"), {
            data: { id: scaleId },
        });

        if (response.data.technicalScales) {
            props.schedulingData.technicalScales.length = 0; 
            response.data.technicalScales.forEach(scale => {
                props.schedulingData.technicalScales.push({
                    id: scale.id,
                    technical: scale.technical,
                });
            });
        }

    } catch (error) {
        console.error("Erro ao excluir registro:", error);
    }
};

const deleteButton = (scaleId) => `
  <button type="button" data-id="${scaleId}" class="delete-btn flex shrink-0 justify-center items-center gap-2 size-[38px] text-sm rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:bg-red-700 disabled:opacity-50 disabled:pointer-events-none">
    x
  </button>
`;

const tableHeaders = ref([
    { name: "ID" },
    { name: "Técnico" },
    { name: "Excluir" },
]);

const tableData = computed(() => {
    const technicalScales = props.schedulingData.technicalScales || [];
    return Array.isArray(technicalScales)
        ? technicalScales.map((scale) => {
            return {
                id: scale.id,
                technical: scale.technical,
                buttonDelete: deleteButton(scale.id),
            };
        })
        : [];
});

const tableId = ref("ManageEntries");

onMounted(() => {
    $(document).on("click", ".delete-btn", function () {
        const entryId = $(this).data("id");
        deleteEntry(entryId);
    });
});
</script>

<template>
    <FormSection @submitted="entryForm">
        <template #title>
            <div class="flex justify-between items-center">
                <div class="flex-grow text-center">
                    <span class="block text-lg font-medium">Técnico</span>
                </div>
            </div>
        </template>
        <template #description>
            Adicione um ou mais técnicos responsáveis pelo atendimento à
            triagem.
        </template>
        <template #form>
            <div
                class="col-span-2 text-center flex flex-col justify-center items-center gap-4 p-4 rounded-lg border border-neutral-300 dark:border-neutral-700 shadow-md">
                <span class="text-lg font-semibold text-neutral-900 dark:text-neutral-200">
                    ID: {{ props.schedulingData.screeningId }}
                    <span class="text-neutral-700 dark:text-white">
                        - {{ props.schedulingData.customersInfo.company_name }}
                    </span>
                </span>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    {{ props.schedulingData.customersInfo.city }},
                    <span class="text-neutral-700 dark:text-white">
                        {{ props.schedulingData.customersInfo.state }}
                    </span>
                </span>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    <span class="text-neutral-700 dark:text-white">
                        {{ props.schedulingData.screeningTypeService.toUpperCase() }}
                    </span>
                </span>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    Status:
                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                        {{ props.schedulingData.screeningStatus.toUpperCase() }}
                    </span>
                </span>
            </div>

            <div class="grid grid-cols-12 gap-2 mt-3">
                <div class="col-span-2 text-center">
                    <label class="font-medium inline-block colorBase mt-2 dark:text-neutral-200" for="technicianSelect">
                        Técnico
                    </label>
                </div>
                <div class="col-span-8">
                    <div class="flex flex-col gap-2">
                        <Select id="technicianSelect" :options="props.schedulingData.technicians" v-model="form.user_id"
                            label="name" class="w-full" />
                        <InputError :message="form.errors.user_id" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4 mt-4 sm:mt-2">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Salvar
                </PrimaryButton>
            </div>

            <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
                <ActionMessage :on="form.recentlySuccessful">
                    Salvo.
                </ActionMessage>
            </div>

            <Datatable :thead="tableHeaders" :tbody="tableData" :id="tableId" />
        </template>
        
    </FormSection>
</template>
