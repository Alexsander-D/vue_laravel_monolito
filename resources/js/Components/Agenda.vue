<template>
    <div
        class="p-4 sm:p-6 md:p-8 bg-white dark:bg-gray-900 rounded-2xl shadow-md border border-gray-200 dark:border-gray-700">

        <div class="overflow-auto">
            <vue-cal style="height: 600px" default-view="month" locale="pt-br" :time="false" :events="eventos"
                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 custom-vuecal"
                @event-click="openModal">

                <template #event="props">
                    <div class="google-event group" :style="{ background: getStatusColor(props.event.status) }">
                        <span class="google-event-title">ID: {{ props.event.id }}</span>
                    </div>
                </template>

            </vue-cal>
        </div>

        <!-- MODAL BONITO GOOGLE STYLE -->
        <div v-if="modalOpen" class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-sm z-50"
            @click.self="closeModal">
            <div class="modal-card animate-scale p-6">

                <h2 class="text-xl font-bold mb-6 text-center">
                    ID: {{ selectedEvent.id }}
                </h2>

                <div class="space-y-3">
                    <p><strong>Empresa:</strong> {{ selectedEvent.company_name }}</p>
                    <p><strong>Tipo:</strong> {{ selectedEvent.type_service.toUpperCase() }}</p>
                    <p><strong>Status:</strong> {{ selectedEvent.status.toUpperCase() }}</p>
                    <p><strong>Técnico(s) Escalado(s):</strong> {{ selectedEvent.technicians.join(', ') }}</p>
                    <p><strong>Data Inicial:</strong> {{ formatDate(selectedEvent.start) }}</p>
                    <p><strong>Conclusão:</strong> {{ formatDate(selectedEvent.end) }}</p>
                </div>


                <button class="mt-6 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    @click="closeModal">
                    Fechar
                </button>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, defineProps, watch } from "vue"
import VueCal from "vue-cal"
import "vue-cal/dist/vuecal.css"

const props = defineProps({
    calendarData: {
        type: Object,
        required: true,
    },
})

const eventos = ref([])

// atualiza quando o pai muda os eventos
watch(
    () => props.calendarData.events,
    (novoValor) => {
        eventos.value = (novoValor || []).map(ev => ({
            ...ev,
            start: new Date(ev.start),
            end: ev.end ? new Date(ev.end) : new Date(ev.start),
        }))
    },
    { immediate: true }
)

// ------------------------- MODAL --------------------------
const modalOpen = ref(false)
const selectedEvent = ref({})

function openModal(event) {
    console.log("EVENTO CLICADO:", event);

    selectedEvent.value = {
        id: event.id,
        company_name: event.company_name ?? "",
        type_service: event.type_service ?? "",
        status: event.status ?? "",
        technicians: event.technicians ?? [],
        start: event.start,
        end: event.end,
    };

    modalOpen.value = true;
}



function closeModal() {
    modalOpen.value = false
}

// ------------------------- FORMATAÇÃO ---------------------
function formatDate(date) {
    return new Date(date).toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    })
}

// ---------------------- CORES -----------------------
function getStatusColor(status) {
    switch (status) {
        case "agendada": return "#F59E0B";
        case "confirmada": return "#3B82F6";
        case "finalizada": return "#8B5CF6";
        case "laudo aprovado": return "#10B981";
        default: return "#6B7280";
    }
}
</script>

<style>
/* ---------------- CALENDAR STYLE ----------------- */

/* Número grande nos dias */
.vuecal__cell-date {
    font-size: 1.5rem !important;
    font-weight: 700 !important;
    color: #374151 !important;
    padding: 6px 0;
}

.dark .vuecal__cell-date {
    color: #f1f5f9 !important;
}

/* Células maiores */
.vuecal__cell {
    min-height: 140px !important;
    border: none !important;
}

.vuecal__cell-content {
    border: 1px solid #e5e7eb !important;
}

.dark .vuecal__cell-content {
    border-color: #334155 !important;
}

/* ---------------- CONTADOR DE EVENTOS ---------------- */

.vuecal__cell-events-count {
    width: 100% !important;
    height: auto !important;
    padding: 4px 0 !important;

    border-radius: 0 !important;

    background: #6B7280 !important;
    color: white !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;

    display: block !important;
    text-align: center !important;

    margin-top: 4px !important;
}

/* Dark Mode */
.dark .vuecal__cell-events-count {
    background: #cbd5e1 !important;
    color: #1e293b !important;
}

/* ---------------- EVENTO GOOGLE CALENDAR ---------------- */
.google-event {
    width: 100%;
    padding: 6px 10px;
    border-radius: 6px;
    color: #fff !important;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;

    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.google-event:hover {
    transform: scale(1.03);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Remove estilo padrão */
.vuecal__event {
    background: none !important;
    border: none !important;
    box-shadow: none !important;
}

/* ------------------ TOOLTIP MODERNO --------------------- */
.google-event {
    position: relative;
}

.tooltip {
    pointer-events: none;
    position: absolute;
    left: 0;
    top: 110%;
    background: #111827;
    color: #fff;
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    width: max-content;
    max-width: 200px;
    opacity: 0;
    transform: translateY(5px);
    transition: 0.15s;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    z-index: 99;
}

.google-event:hover .tooltip {
    opacity: 1;
    transform: translateY(0);
}

/* ------------------ MODAL GOOGLE ------------------------ */
.modal-card {
    background: white;
    padding: 25px;
    border-radius: 16px;
    max-width: 420px;
    width: 90%;
    animation: fadeIn 0.2s ease;
}

.dark .modal-card {
    background: #1f2937;
    color: #fff;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.92); }
    to   { opacity: 1; transform: scale(1); }
}

.animate-scale {
    animation: scaleIn 0.2s ease;
}

@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}

</style>
