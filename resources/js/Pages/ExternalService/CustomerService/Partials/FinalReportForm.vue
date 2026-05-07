<script setup>
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import { computed, ref, defineProps, defineAsyncComponent, watch, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { Inertia } from "@inertiajs/inertia";
import Swal from "sweetalert2";
import moment from "moment-timezone";
import InfoCard from "@/Components/InfoCard.vue";

const CancelModal = defineAsyncComponent(() =>
    import("@/Pages/ExternalService/CustomerService/Partials/CancelModal.vue")
);
const StatusModal = defineAsyncComponent(() =>
    import("@/Pages/ExternalService/CustomerService/Partials/StatusModal.vue")
);
const TechnicianModal = defineAsyncComponent(() =>
    import("@/Pages/ExternalService/CustomerService/Partials/TechnicianModal.vue")
);
const ApproveModal = defineAsyncComponent(() =>
    import("@/Pages/ExternalService/CustomerService/Partials/ApproveModal.vue")
);
const RejectModal = defineAsyncComponent(() =>
    import("@/Pages/ExternalService/CustomerService/Partials/RejectModal.vue")
);

const props = defineProps({
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

const openCancelModal = ref(false);
const openTechnicianModal = ref(false);
const openStatusModal = ref(false);
const openApproveModal = ref(false);
const openRejectModal = ref(false);
const finalReportData = ref(props.finalReportData);
const customersInfo = computed(() => props.finalReportData.customersInfo || {});
const technicalScales = computed(() => props.finalReportData.technicalScales || []);

const confirmCancel = () => {
    Swal.fire({
        title: "Tem certeza?",
        text: "Após salvar, a ação não poderá ser desfeita!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sim, cancelar!",
        cancelButtonText: "Não, voltar",
    }).then((result) => {
        if (result.isConfirmed) {
            openCancelModal.value = true;
        }
    });
};

const formattedTechnicians = computed(() => {
    return technicalScales.value.length > 0
        ? technicalScales.value
            .map((t) => t.technical)
            .sort((a, b) => a.localeCompare(b))
            .join(", ")
        : "Nenhum técnico escalado";
});


const redirectToProductivity = () => {
    const screeningId = props.finalReportData.screeningId;
    router.get(route("productivityReport.view", screeningId));
};

const goToProductEntry = () => {
    if (finalReportData.value.screeningData.type_service === "pre-agenda") {
        Inertia.get(route("ProductEntry.index", { screeningId: props.finalReportData.screeningId }));
    } else {
        form.status = "aguardando_produtos";
        form.post(route("customers.finalReport.updateStatus"), {
            preserveScroll: true,
            onSuccess: () => {
                Inertia.get(route("ProductEntry.index", { screeningId: props.finalReportData.screeningId }));
            },
            onError: (error) => console.error("Erro ao alterar status antes de adicionar produtos:", error),
        });
    }
};

const submitApproveScheduling = () => {
    form.screening_id = props.finalReportData.screeningId;
    form.status = "confirmada";

    form.put(route("customers.finalReport.updateStatus"), {
        preserveScroll: true,
        onSuccess: () => {
            props.finalReportData.status = form.status.label;
            Swal.fire({
                icon: "success",
                title: "Sucesso!",
                text: "Status atualizado com sucesso.",
                confirmButtonColor: "#3085d6",
            });
        },
        onError: (errors) => {
            Swal.fire({
                icon: "error",
                title: "Erro ao atualizar status",
                text: errors.status || errors.screening_id || "Não foi possível atualizar o status.",
                confirmButtonColor: "#d33",
            });
        },
    });
};

const form = useForm({
    screening_id: props.finalReportData.screeningId,
    observation: "",
    status: "",
    technician: "",
});

function generateReport() {
    const url = route('finalReport.printreport', props.finalReportData.screeningId);
    window.open(url, '_blank');
}

function formatDate(date) {
    if (!date) return "";
    const m = moment(date, "DD/MM/YYYY");
    return m.isValid() ? m.format("DD/MM/YYYY") : "";
}



</script>

<template>

    <CancelModal :show="openCancelModal" :finalReportData="finalReportData" :userRole="props.userRole"
        @close="openCancelModal = false" @success="openCancelModal = false" />

    <StatusModal :show="openStatusModal" :finalReportData="finalReportData" :userRole="props.userRole"
        @close="openStatusModal = false" @updated="openStatusModal = false" />

    <TechnicianModal :show="openTechnicianModal" :finalReportData="finalReportData" :userRole="props.userRole"
        @close="openTechnicianModal = false" @updated="openTechnicianModal = false" />

    <ApproveModal :show="openApproveModal" :finalReportData="finalReportData" :userRole="props.userRole"
        @close="openApproveModal = false" @success="openApproveModal = false" />

    <RejectModal :show="openRejectModal" :finalReportData="finalReportData" :userRole="props.userRole"
        @close="openRejectModal = false" @success="openRejectModal = false" />

    <FormSection>
        <template #title>
            <div class="flex justify-center items-center gap-1">
                <!-- Botões Admin -->
                <template v-if="['Admin', 'Auxiliar Administrativo'].includes(props.userRole)">

                    <template
                        v-if="!['laudo aprovado', 'laudo reprovado', 'cancelada'].includes(props.finalReportData.screeningStatus)">
                        <button v-if="props.finalReportData.screeningStatus === 'finalizada'"
                            @click="openApproveModal = true"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-[#02B590] text-[#02B590] hover:border-[#029b80] hover:text-[#029b80]">
                            Aprovar laudo
                        </button>

                        <button v-if="props.finalReportData.screeningStatus === 'finalizada'"
                            @click="openRejectModal = true"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-[#BD0480] text-[#BD0480] hover:border-[#a00370] hover:text-[#a00370]">
                            Reprovar laudo
                        </button>

                        <button v-if="props.finalReportData.screeningStatus === 'confirmada'"
                            @click="redirectToProductivity"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-purple-700 text-purple-700 hover:border-purple-500 hover:text-purple-500">
                            Realizar lançamentos
                        </button>

                        <button v-if="props.finalReportData.screeningStatus === 'agendada'"
                            @click="submitApproveScheduling"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-teal-500 text-teal-500 hover:border-teal-400 hover:text-teal-400">
                            Aprovar agendamento
                        </button>

                        <button v-if="props.finalReportData.screeningStatus !== 'finalizada'"
                            @click="openTechnicianModal = true"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-cyan-600 text-cyan-600 hover:border-cyan-500 hover:text-cyan-500">
                            Alterar técnicos
                        </button>

                        <button v-if="props.finalReportData.screeningStatus !== 'finalizada'"
                            @click="openStatusModal = true"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-blue-600 text-blue-600 hover:border-blue-500 hover:text-blue-500">
                            Alterar status
                        </button>

                        <button v-if="
                            props.finalReportData.screeningData.type_service === 'pre-agenda' &&
                            props.finalReportData.screeningStatus === 'agendada'
                        " @click="goToProductEntry"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-orange-600 text-orange-600 hover:border-orange-500 hover:text-orange-500">
                            Adicionar produtos
                        </button>

                        <button v-if="props.finalReportData.screeningStatus !== 'finalizada'" @click="confirmCancel"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-red-500 text-red-500 hover:border-red-400 hover:text-red-400">
                            Cancelar triagem
                        </button>
                    </template>

                    <template v-else>
                        <button @click="generateReport"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-[#AFFF00] text-[#AFFF00] hover:border-[#94d900] hover:text-[#94d900]">
                            Gerar Laudo
                        </button>
                        <button
                            @click="$inertia.get(route('screeningTimeline.index', { screening_id: props.finalReportData.screeningId }))"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-yellow-500 text-yellow-500 hover:border-yellow-400 hover:text-yellow-400">
                            Histórico
                        </button>
                    </template>
                </template>

                <!-- Botões Auxiliar Técnico -->
                <template v-else-if="props.userRole === 'Auxiliar Tecnico'">
                    <template
                        v-if="!['laudo aprovado', 'laudo reprovado', 'cancelada'].includes(props.finalReportData.screeningStatus)">
                        <button @click="generateReport"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-[#AFFF00] text-[#AFFF00] hover:border-[#94d900] hover:text-[#94d900]">
                            Gerar Laudo
                        </button>
                    </template>

                    <template v-else-if="props.finalReportData.screeningStatus === 'confirmada'">
                        <button @click="redirectToProductivity"
                            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-purple-700 text-purple-700 hover:border-purple-500 hover:text-purple-500">
                            Realizar lançamentos
                        </button>
                    </template>
                </template>
            </div>
        </template>

        <!-- Corpo principal -->
        <template #form>
            <div
                class="col-span-2 text-center flex flex-col justify-center items-center gap-4 p-4 rounded-lg border border-neutral-300 dark:border-neutral-700 shadow-md">
                <span class="text-lg font-semibold text-neutral-900 dark:text-neutral-200">
                    ID: {{ props.finalReportData.screeningId }}
                    <span class="text-neutral-700 dark:text-white">
                        - {{ props.finalReportData.customersInfo.company_name }}
                    </span>
                </span>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    {{ props.finalReportData.customersInfo.city }},
                    <span class="text-neutral-700 dark:text-white">
                        {{ props.finalReportData.customersInfo.state }}
                    </span>
                </span>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    <span class="text-neutral-700 dark:text-white">
                        {{ props.finalReportData.screeningData.type_service.toUpperCase() }}
                    </span>
                </span>

                <span class="text-base font-medium text-neutral-900 dark:text-neutral-200">
                    Status triagem:
                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                        {{ props.finalReportData.screeningStatus.toUpperCase() }}
                    </span>
                </span>
            </div>

            <div v-if="props.finalReportData.screeningStatus?.toLowerCase() === 'laudo aprovado'"
                class="col-span-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                <InfoCard :data="props.finalReportData.screeningData.rm" title="RM" class="uppercase" />
                <InfoCard :data="props.finalReportData.screeningData.recovered_value" title="Recuperado" />
                <InfoCard :data="props.finalReportData.screeningData.return_value" title="Devolução" />
                <InfoCard :data="props.finalReportData.screeningData.ndoa_value" title="Fora de garantia" />
            </div>

            <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
                <ActionMessage :on="form.recentlySuccessful"> Salvo. </ActionMessage>
            </div>

            <div class="col-span-12 p-4 rounded-lg shadow-lg border dark:border-gray-700">
                <p class="font-bold text-xl text-center text-gray-800 dark:text-neutral-200">
                    Informações do Atendimento
                </p>

                <div class="mt-4 text-sm text-gray-800 dark:text-neutral-200">

                    <div class="mb-4">
                        <p><strong>Identificação (CPF/CNPJ):</strong>
                            {{ customersInfo.type_person || '' }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <p><strong>Razão social:</strong>
                            {{ customersInfo.company_name || '' }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <p><strong>Nome fantasia:</strong>
                            {{ customersInfo.trade_name || '' }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <p><strong>Data inicial:</strong>
                            {{ formatDate(finalReportData.screeningData.service_start) }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <p><strong>Data final:</strong>
                            {{ formatDate(finalReportData.screeningData.completion_date) }}
                        </p>
                    </div>

                    <div class="mb-4" v-if="finalReportData.screeningData.scheduling_date">
                        <p><strong>Data de agendamento:</strong>
                            {{ formatDate(finalReportData.screeningData.scheduling_date) }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <p><strong>Endereço:</strong></p>

                        <p>
                            {{ customersInfo.road || '' }},
                            {{ customersInfo.number || '' }}
                            - {{ customersInfo.district || '' }}
                        </p>

                        <p>
                            {{ customersInfo.city || '' }} /
                            {{ customersInfo.state || '' }}
                            - CEP: {{ customersInfo.cep || '' }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <p><strong>Telefone:</strong>
                            {{ customersInfo.telephone || '' }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <p><strong>E-mail:</strong>
                            {{ customersInfo.email || '' }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <p><strong>Técnico(s) Escalado(s):</strong>
                            {{ formattedTechnicians }}
                        </p>
                    </div>

                    <div class="mb-4" v-if="finalReportData.screeningData.approval_date">
                        <p><strong>Data de aprovação:</strong>
                            {{ formatDate(finalReportData.screeningData.approval_date) }}
                        </p>
                    </div>

                    <div class="mb-4" v-if="finalReportData.screeningData.reject_report">
                        <p><strong>Motivo da reprovação:</strong>
                            {{ finalReportData.screeningData.reject_report }}
                        </p>
                    </div>

                    <div class="mb-4" v-if="finalReportData.screeningData.observation">
                        <p><strong>Observação:</strong>
                            {{ finalReportData.screeningData.observation }}
                        </p>
                    </div>

                </div>
            </div>


        </template>
    </FormSection>
</template>
