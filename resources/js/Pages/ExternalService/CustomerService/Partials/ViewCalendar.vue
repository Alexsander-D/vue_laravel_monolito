<script setup>
import { ref, onMounted, defineProps, watch, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";

import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import Agenda from "@/Components/Agenda.vue";
import Select from "@/Components/Select.vue";

const props = defineProps({
    calendarData: {
        type: Object,
        required: true,
    },
});

// Pegando o papel do usuário logado enviado pelo controller
const userRole = props.calendarData.user_role;

// Verifica se pode visualizar/selecionar técnicos
const podeSelecionarTecnico = computed(() =>
    ["Admin", "Auxiliar Administrativo"].includes(userRole)
);

// Formulário
const form = useForm({
    SelectTechniciansCalendar: "",
});

// Eventos e técnicos
const eventos = ref(props.calendarData?.events || []);
const tecnicos = ref(props.calendarData?.technicians || []);

// Carregar eventos filtrados
async function carregarEventos(tecnicoId = null) {
    try {
        const { data } = await axios.get(route("calendar.events"), {
            params: { tecnico: tecnicoId },
            withCredentials: true,
        });

        eventos.value = (data || []).map(ev => ({
            ...ev,
            start: new Date(ev.start),
            end: ev.end ? new Date(ev.end) : new Date(ev.start),
            ...corEvento(ev.status)
        }));

    } catch (error) {
        console.error("Erro ao carregar eventos:", error);
    }
}

// Cores dos eventos por status
function corEvento(status) {
    const cores = {
        'Laudo aprovado': { background: '#4B5563', color: '#fff' },
        'Confirmada': { background: '#1D4ED8', color: '#fff' },
        'Agendada': { background: '#4F46E5', color: '#fff' },
        'Finalizada': { background: '#16A34A', color: '#fff' },
        'Aguardando agendamento': { background: '#FACC15', color: '#000' },
        'Aguardando produtos': { background: '#38BDF8', color: '#fff' },
        'Cancelada': { background: '#DC2626', color: '#fff' }
    };
    return cores[status] || { background: '#9CA3AF', color: '#fff' };
}

// Carrega todos os eventos no início
onMounted(() => {
    carregarEventos();
});

// Recarrega eventos quando o usuário muda o técnico
watch(() => form.SelectTechniciansCalendar, (novoTecnico) => {
    // Se NÃO tiver permissão:
    if (!podeSelecionarTecnico.value) {
        carregarEventos(null);
        return;
    }

    const tecnicoId = novoTecnico?.id ?? null;
    carregarEventos(tecnicoId);
});
</script>

<template>
    <FormSection>
        <template #title>Agenda</template>
        <template #description>
            Visualize os agendamentos dos técnicos
        </template>

        <template #form>

            <!-- Select aparece somente se usuário for Admin ou Auxiliar Administrativo -->
            <div class="mb-4" v-if="podeSelecionarTecnico">
                <label for="tecnico" class="block text-sm font-medium text-gray-700">
                    Técnico
                </label>

                <Select id="tecnico" class="mt-1 block w-full" :options="tecnicos"
                    v-model="form.SelectTechniciansCalendar" label="name" track-by="id"
                    placeholder="Selecione o técnico" />
            </div>

            <!-- Agenda -->
            <Agenda :calendarData="{ events: eventos }" />

            <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
                <ActionMessage :on="form.recentlySuccessful">
                    Salvo.
                </ActionMessage>
            </div>

        </template>
    </FormSection>
</template>
