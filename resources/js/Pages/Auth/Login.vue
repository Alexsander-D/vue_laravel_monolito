<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import Button from "@/Components/Button.vue";
import { EyeIcon, EyeSlashIcon } from "@heroicons/vue/24/outline";
import { defineProps, ref } from "vue";

const props = defineProps({
  canResetPassword: Boolean,
  status: String,
});

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      remember: form.remember ? "on" : "",
    }))
    .post(route("login"), {
      onFinish: () => form.reset("password"),
    });
};

const showPassword = ref(false);
</script>

<style scoped>
/* Classe personalizada */
.colorBase {
  color: var(--cor-secundaria);
}
</style>

<template>
  <Head title="Log in" />
  <div
    class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-800"
  >
    <div
      class="bg-white border border-neutral-600 rounded-xl shadow-sm dark:bg-neutral-900 dark:border-neutral-600 w-full sm:w-2/3 lg:w-1/3"
    >
      <div class="p-4 sm:p-7">
        <div class="text-center">
          <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">Entrar</h1>
          <p class="mt-2 text-sm dark:text-neutral-400">
            Não possui uma conta?
            <Link
              :href="route('register')"
              class="text-sm decoration-2 hover:underline font-medium colorBase"
            >
              Cadastre-se aqui
            </Link>
          </p>
        </div>
        <div class="mt-5">
          <div
            class="py-3 flex items-center text-xs text-gray-600 uppercase before:flex-1 before:border-t before:border-gray-400 before:me-6 after:flex-1 after:border-t after:border-gray-400 after:ms-6 dark:text-neutral-500 dark:before:border-neutral-600 dark:after:border-neutral-600"
          >
            Ou
          </div>
          <form @submit.prevent="submit">
            <div class="grid gap-y-4">
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
                    autofocus
                    autocomplete="username"
                  />
                  <InputError class="mt-2" :message="form.errors.email" />
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="email-error">
                  Por favor, inclua um endereço de e-mail válido
                </p>
              </div>
              <div>
                <div class="flex justify-between items-center">
                  <InputLabel
                    for="password"
                    value="Senha"
                    class="block text-sm mb-2 dark:text-white"
                  />
                  <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm decoration-2 hover:underline font-medium colorBase"
                  >
                    Esqueceu a senha?
                  </Link>
                </div>

                <div class="relative">
                  <TextInput
                    id="password"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                  />
                  <button
                    type="button"
                    class="absolute top-1/2 end-2 -translate-y-1/2 bg-transparent border-none p-0 w-auto h-auto flex items-center justify-center"
                    @click="showPassword = !showPassword"
                  >
                    <EyeIcon v-if="!showPassword" class="w-5 h-5" />
                    <EyeSlashIcon v-else class="w-5 h-5" />
                  </button>
                  <InputError class="mt-2" :message="form.errors.password" />
                </div>
              </div>
              <div class="flex items-center">
                <div class="flex">
                  <Checkbox
                    v-model:checked="form.remember"
                    name="remember"
                    id="remember"
                  />
                </div>
                <div class="ms-3">
                  <label for="remember" class="text-sm dark:text-white"
                    >Continuar logado?</label
                  >
                </div>
              </div>
              <Button
                type="submit"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
                class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent disabled:opacity-50 disabled:pointer-events-none"
              >
                Entrar
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
