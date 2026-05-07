<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AuthenticationCard from "@/Components/AuthenticationCard.vue";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { defineProps } from "vue";

const props = defineProps({
  status: String,
});

const form = useForm({
  email: "",
});

const submit = () => {
  form.post(route("password.email"));
};
</script>

<template>
  <Head title="Log in" />
  <div
    class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-800"
  >
    <div
      class="bg-white border border-green-800 rounded-xl shadow-sm dark:bg-neutral-900 dark:border-neutral-600 w-full sm:w-2/3 lg:w-1/3"
    >
      <div class="p-4 sm:p-7 text-dark dark:text-white">
        <div class="mb-4 text-sm">
          Esqueceu sua senha? Sem problemas. Basta nos informar seu endereço de e-mail e
          enviaremos por e-mail um link de redefinição de senha que permitirá que você
          escolha uma nova.
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
          {{ status }}
        </div>

        <form @submit.prevent="submit">
          <div>
            <InputLabel
              class="block text-sm mb-2 dark:text-white"
              for="email"
              value="E-mail"
            />
            <div class="relative">
              <TextInput
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="username"
              />
              <InputError class="mt-2" :message="form.errors.email" />
            </div>
          </div>

          <div class="flex items-center justify-end mt-4">
            <button
              type="submit"
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
              class="inline-flex items-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
            >
              Link de redefinição de senha de e-mail
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
