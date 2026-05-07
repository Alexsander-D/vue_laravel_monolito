<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { useForm } from "@inertiajs/vue3";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import { defineProps } from "vue";
import moment from "moment";
import "moment-timezone";

const props = defineProps({
  actions: Object,
  protocolo: {
    type: String,
    default: null,
  },
});

const form = useForm({
  protocolo: "",
});

const searchForm = () => {
  form.get(route("timeline.show", { protocolo: form.protocolo }), {
    errorBag: "searchForm",
    preserveScroll: true,
    onError: (error) => {
      console.log(error);
    },
  });
};

const formatDate = (date) => {
  return moment.utc(date).format("DD/MM/YYYY HH:mm:ss");
};

const isEmpty = (obj) => {
  return obj && typeof obj === "object" && Object.keys(obj).length === 0;
};
</script>

<template>
  <BaseLayout title="Timeline">
    <!-- Card Section -->
    <div class="w-full mx-auto pt-1">
      <!-- Card -->
      <div class="bg-white rounded-xl shadow shadow-lg p-2 dark:bg-gray-900 text-center">
        <!-- Content -->
        <FormSection @submitted="searchForm">
          <template #title> BUSCAR PROTOCOLO </template>
          <template #description>
            Digite um protocolo para exibir as informações disponíveis sobre ele.
          </template>
          <template #form>
            <div class="grid grid-cols-12 gap-2 sm:gap-6 mt-8 items-center">
              <div class="col-span-2"></div>
              <div class="col-span-8">
                <div class="flex items-center gap-2">
                  <TextInput
                    v-model="form.protocolo"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="off"
                  />
                </div>
                <InputError :message="form.errors.search" />
              </div>

              <!-- Botão de salvar -->
              <div class="col-span-2 flex justify-center mt-4 sm:mt-2">
                <PrimaryButton
                  :class="{ 'opacity-25': form.processing }"
                  :disabled="form.processing"
                >
                  Buscar
                </PrimaryButton>
              </div>

              <!-- Mensagem de sucesso -->
              <div class="col-span-12 flex justify-center mt-2">
                <ActionMessage :on="form.recentlySuccessful"> Salvo. </ActionMessage>
              </div>
            </div>
          </template>
        </FormSection>

        <!-- Timeline -->
        <div class="mt-6">
          <!-- Heading -->
          <div class="ps-2 my-2 first:mt-0">
            <h3
              v-if="!isEmpty(props.actions)"
              class="text-md font-medium uppercase text-black dark:text-neutral-400 mb-4"
            >
              {{ props.protocolo }}
            </h3>
            <h3 v-else class="text-md font-medium uppercase text-red-600 mb-4">
              NENHUM REGISTRO ENCONTRADO
            </h3>
          </div>
          <!-- End Heading -->
          <!-- Item -->
          <div v-for="action in props.actions" :key="action.id" class="flex gap-x-3">
            <!-- Right Content -->
            <div class="grow pt-0.5 pb-4">
              <h3
                class="py-3 flex items-center before:flex-1 before:border-t before:border-gray-300 before:me-6 after:flex-1 after:border-t after:border-gray-300 after:ms-6 dark:before:border-neutral-600 dark:after:border-neutral-600 text-xs font-medium uppercase text-gray-800 dark:text-neutral-400"
              >
                {{ formatDate(action.created_at) }}
              </h3>
              <p class="mt-1 text-sm text-gray-800 dark:text-neutral-400">
                {{ action.description }}
              </p>
              <button
                type="button"
                class="mt-1 -ms-1 p-1 inline-flex items-center gap-x-2 text-xs rounded-lg border border-transparent text-gray-700 focus:outline-none disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400"
              >
                <img
                  class="shrink-0 size-4 rounded-full"
                  src="https://images.unsplash.com/photo-1659482633369-9fe69af50bfb?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8auto=format&fit=facearea&facepad=3&w=320&h=320&q=80"
                  alt="Avatar"
                />
                {{ action.responsable }}
              </button>
            </div>
            <!-- End Right Content -->
          </div>
          <!-- End Item -->
        </div>
        <!-- End Timeline -->
      </div>
    </div>
  </BaseLayout>
</template>