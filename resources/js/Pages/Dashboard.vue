<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import { Link } from "@inertiajs/vue3";
import { ref, defineProps, computed } from "vue";

// Recebe as props padrão do Inertia (usuário autenticado)
const props = defineProps({
  auth: Object,
});

// Define os cards dinamicamente
const features = [
  {
    title: "Corte Social",
    string_value: "R$ 35,00",
    value: 35,
    icon: "✂️", // Tesoura para cortes clássicos
    link: "#",
  },
  {
    title: "Corte Degradê",
    string_value: "R$ 40,00",
    value: 40,
    icon: "💈", // Paleta de cores para estilo moderno
    link: "#",
  },
  {
    title: "Corte Navalhado",
    string_value: "R$ 45,00",
    value: 45,
    icon: "🪒", // Navalha para cortes detalhados
    link: "#",
  },
  {
    title: "Barba",
    string_value: "R$ 20,00",
    value: 20,
    icon: "🧔‍♂️", // Rosto com barba
    link: "#",
  },
  {
    title: "Bigode",
    string_value: "R$ 10,00",
    value: 10,
    icon: "👨‍🦰", // Rosto com bigode
    link: "#",
  },
  {
    title: "Pezinho",
    string_value: "R$ 10,00",
    value: 10,
    icon: "📏", // Régua para cortes precisos
    link: "#",
  },
  {
    title: "Sobrancelha",
    string_value: "R$ 10,00",
    value: 10,
    icon: "🔍", // Olho para design de sobrancelhas
    link: "#",
  },
  {
    title: "Pigmentação",
    string_value: "R$ 30,00",
    value: 30,
    icon: "🎨", // Paleta de cores para pigmentação
    link: "#",
  },
  {
    title: "Luzes",
    string_value: "R$ 100,00",
    value: 100,
    icon: "💡", // Lâmpada para iluminar o cabelo
    link: "#",
  },
  {
    title: "Nevou",
    string_value: "R$ 120,00",
    value: 120,
    icon: "❄️", // Floco de neve para o efeito "nevou"
    link: "#",
  },
];

const form = useForm({
  services: [],
  total: 0,
  created_at: "",
});

const submitAttendance = () => {
  if (selectedServices.value.length === 0) {
    Swal.fire({
      icon: "warning",
      title: "Nenhum serviço selecionado",
      text: "Selecione pelo menos um serviço.",
    });

    return;
  }

  form.services = selectedServices.value.map((service) => ({
    name: service.title,
    price: service.value,
  }));

  form.total = totalValue.value;

  form.created_at =
    new Date().toLocaleDateString("pt-BR") + " " + new Date().toLocaleTimeString("pt-BR");

  form.post(route("attendance.store"), {
    preserveScroll: true,

    onSuccess: () => {
      Swal.fire({
        icon: "success",
        title: "Atendimento registrado!",
      });

      selectedServices.value = [];
      form.reset();
    },

    onError: (errors) => {
      Swal.fire({
        icon: "error",
        title: "Erro ao registrar atendimento",
        text: Object.values(errors)[0],
      });
    },
  });
};

const selectedServices = ref([]);

const totalValue = computed(() => {
  return selectedServices.value.reduce((total, service) => total + service.value, 0);
});

const toggleSelection = (service) => {
  const index = selectedServices.value.findIndex((item) => item.title === service.title);

  if (index > -1) {
    selectedServices.value.splice(index, 1);
  } else {
    selectedServices.value.push(service);
  }
};

const isSelected = (service) => {
  return selectedServices.value.some((item) => item.title === service.title);
};
</script>
<template>
  <BaseLayout title="Atendimento">
    <div class="w-full mx-auto pt-1">
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold" style="color: #e6e600">
            Realizar Atendimento
          </h1>

          <p class="text-gray-600 dark:text-gray-400 mt-2">
            Selecione os serviços realizados.
          </p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
          <div
            v-for="service in features"
            :key="service.title"
            @click="toggleSelection(service)"
            class="cursor-pointer rounded-xl border p-6 text-center transition-all duration-300"
            :class="
              isSelected(service)
                ? 'border-yellow-500 bg-yellow-500/10 shadow-lg scale-105'
                : 'border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700'
            "
          >
            <div class="mt-4">
              <input
                type="checkbox"
                :checked="isSelected(service)"
                class="absolute top-4 left-4 form-checkbox h-5 w-5 text-blue-600 pointer-events-none"
              />
            </div>

            <div class="text-5xl mb-4">
              {{ service.icon }}
            </div>

            <h3 class="font-bold text-lg text-gray-800 dark:text-gray-200">
              {{ service.title }}
            </h3>

            <!-- <p
              class="mt-2 text-xl font-bold"
              style="color: var(--cor-principal)"
            >
              R$ {{ service.value.toFixed(2).replace(".", ",") }}
            </p> -->
          </div>
        </div>

        <div
          class="mt-10 rounded-xl border border-yellow-500/20 p-6 bg-gray-50 dark:bg-gray-800"
        >
          <div class="flex justify-center items-center">
            <span class="text-xl font-semibold text-black dark:text-white">
              R${{ totalValue.toFixed(2).replace(".", ",") }}
            </span>
          </div>

          <button
            @click="submitAttendance"
            class="mt-6 w-full rounded-lg py-3 font-semibold dark:text-black text-white transition hover:scale-[1.02] dark:bg-white bg-gray-900"
            :disabled="form.processing"
          >
            Finalizar Atendimento
          </button>
        </div>
      </div>
    </div>
  </BaseLayout>
</template>
