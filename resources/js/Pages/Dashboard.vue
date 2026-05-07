<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { Link } from "@inertiajs/vue3";
import { defineProps, computed } from "vue";

// Recebe as props padrão do Inertia (usuário autenticado)
const props = defineProps({
  auth: Object,
});

// Detecta se o usuário é do time de triagem
const isTriagemTeam = computed(() => {
  const teamName = props.auth?.user?.current_team?.name || "";
  return teamName.toLowerCase() === "triagem";
});

// Define os cards dinamicamente
const features = [
  {
    title: "Gerencie seus Dados",
    description:
      "Acompanhe e atualize informações importantes do pós-venda com facilidade e precisão.",
    icon: "📊",
    link: "#",
  },
  {
    title: "Relatórios Personalizados",
    description: "Gere relatórios detalhados para acompanhar seu desempenho.",
    icon: "📈",
    link: "#",
  },
  {
    title: "Acompanhamento de Status",
    description: "Monitore o progresso de cada processo pós-venda em tempo real.",
    icon: "⏱️",
    link: "#",
  },
  // ✅ Card dinâmico: muda conforme o time logado
  {
    title: isTriagemTeam.value ? "Buscar ID" : "Buscar Produto",
    description: isTriagemTeam.value
      ? "Acesse a timeline de triagem."
      : "Busque produtos cadastrados.",
    icon: "🔍",
    link: isTriagemTeam.value
      ? route("screeningTimeline.index")
      : route("timeline.index"),
  },
  {
    title: "Controle de Acessos",
    description:
      "Defina permissões e restrinja acessos a funcionalidades sensíveis do sistema.",
    icon: "🔒",
    link: "/technical_assistance/registration/access-control",
  },
  {
    title: "Notificações",
    description: "Receba alertas e atualizações importantes diretamente no sistema.",
    icon: "🔔",
    link: "#",
  },
];
</script>

<template>
  <BaseLayout title="Dashboard">
    <div class="w-full mx-auto pt-1">
      <div class="bg-white rounded-xl shadow shadow-lg p-2 dark:bg-gray-900">
        <div class="p-4 mx-auto">
          <div class="text-center mb-12">
            <h1 class="text-3xl font-bold text-blue-600 dark:text-blue-500">
              Bem-vindo(a) ao Vex!
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
              Utilize as ferramentas abaixo para começar a gerenciar seus dados.
            </p>
          </div>

          <div class="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="feature in features" :key="feature.title"
              class="p-6 bg-gray-200 dark:bg-gray-800 rounded-lg shadow-md text-center hover:bg-gray-300 dark:hover:bg-gray-700 transition">
              <div class="text-4xl mb-4">{{ feature.icon }}</div>
              <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300">
                {{ feature.title }}
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                {{ feature.description }}
              </p>
              <Link :href="feature.link"
                class="inline-block mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition">
              Acessar
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </BaseLayout>
</template>
