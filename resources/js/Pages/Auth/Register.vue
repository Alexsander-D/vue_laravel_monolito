<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import Button from "@/Components/Button.vue";

const form = useForm({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
  terms: false,
});

const submit = () => {
  form.post(route("register"), {
    onFinish: () => form.reset("password", "password_confirmation"),
  });
};
</script>

<style scoped>
/* Classe personalizada */
.colorBase {
  color: var(--cor-secundaria);
}
</style>

<template>
  <Head title="Register" />
  <div
    class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-800"
  >
    <div
      class="bg-white border border-red-800 rounded-xl shadow-sm dark:bg-neutral-900 dark:border-neutral-600 w-full sm:w-2/3 lg:w-1/3"
    >
      <div class="p-4 sm:p-8">
        <div class="text-center">
          <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">
            Crie uma conta
          </h1>
          <p class="mt-2 text-sm text-black dark:text-neutral-400">
            Já possui uma conta?
            <Link
              :href="route('login')"
              class="text-sm decoration-2 hover:underline font-medium colorBase"
            >
              Entrar
            </Link>
          </p>
        </div>
        <div class="mt-2">
          <div
            class="py-3 flex items-center text-xs text-gray-600 uppercase before:flex-1 before:border-t before:border-gray-400 before:me-6 after:flex-1 after:border-t after:border-gray-400 after:ms-6 dark:text-neutral-500 dark:before:border-neutral-600 dark:after:border-neutral-600"
          >
            Ou
          </div>
          <form @submit.prevent="submit">
            <div class="grid gap-y-2">
              <div>
                <InputLabel
                  class="block text-sm mb-2 dark:text-white"
                  for="name"
                  value="Nome"
                />
                <div class="relative">
                  <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                  />
                  <InputError class="mt-2" :message="form.errors.name" />
                </div>
              </div>

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

              <div>
                <InputLabel
                  class="block text-sm mb-2 dark:text-white"
                  for="password"
                  value="Senha"
                />
                <div class="relative">
                  <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="username"
                  />
                  <InputError class="mt-2" :message="form.errors.password" />
                </div>
              </div>

              <div>
                <InputLabel
                  class="block text-sm mb-2 dark:text-white"
                  for="password_confirmation"
                  value="Confirme a senha"
                />
                <div class="relative">
                  <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    autocomplete="username"
                  />
                  <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
              </div>

              <div
                v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature"
                class="flex items-center mt-2 mb-2"
              >
                <InputLabel for="terms">
                  <div class="flex items-center">
                    <Checkbox
                      id="terms"
                      v-model:checked="form.terms"
                      name="terms"
                      required
                    />

                    <div class="ms-2 text-sm dark:text-white">
                      Concordo com os
                      <Link
                        :href="route('terms.show')"
                        target="_blank"
                        class="text-sm decoration-2 hover:underline font-medium colorBase"
                      >
                        Termos de Serviço
                      </Link>
                      e
                      <Link
                        :href="route('policy.show')"
                        target="_blank"
                        class="text-sm decoration-2 hover:underline font-medium colorBase"
                      >
                        Política de Privacidade
                      </Link>
                    </div>
                  </div>
                  <InputError class="mt-2" :message="form.errors.terms" />
                </InputLabel>
              </div>

              <Button
                type="submit"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
                class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent disabled:opacity-50 disabled:pointer-events-none"
              >
                Criar conta
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
