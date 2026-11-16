<script setup>
import { ref, defineProps } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
  user: Object,
});

const form = useForm({
  _method: "PUT",
  name: props.user.name,
  email: props.user.email,
  photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const updateProfileInformation = () => {
  if (photoInput.value) {
    form.photo = photoInput.value.files[0];
  }

  form.post(route("user-profile-information.update"), {
    errorBag: "updateProfileInformation",
    preserveScroll: true,
    onSuccess: () => clearPhotoFileInput(),
  });
};

const sendEmailVerification = () => {
  verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
  photoInput.value.click();
};

const updatePhotoPreview = () => {
  const photo = photoInput.value.files[0];

  if (!photo) return;

  const reader = new FileReader();

  reader.onload = (e) => {
    photoPreview.value = e.target.result;
  };

  reader.readAsDataURL(photo);
};

const deletePhoto = () => {
  router.delete(route("current-user-photo.destroy"), {
    preserveScroll: true,
    onSuccess: () => {
      photoPreview.value = null;
      clearPhotoFileInput();
    },
  });
};

const clearPhotoFileInput = () => {
  if (photoInput.value?.value) {
    photoInput.value.value = null;
  }
};
</script>

<style scoped>
/* Classe personalizada */
.colorBase {
  color: var(--cor-secundaria);
}
</style>

<template>
  <FormSection @submitted="updateProfileInformation">
    <template #title> Informações do Perfil </template>

    <template #description> Atualize as informações de perfil da sua conta. </template>

    <template #form>
      <div v-if="$page.props.jetstream.managesProfilePhotos">
        <div class="grid sm:grid-cols-12 gap-2 sm:gap-6 mt-8">
          <div class="col-span-2">
            <label
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
              for="photo"
            >
              Foto de perfil
            </label>
          </div>

          <div class="col-span-9">
            <div class="flex items-center gap-5">
              <!-- Profile Photo File Input -->
              <input
                id="photo"
                ref="photoInput"
                type="file"
                class="hidden"
                @change="updatePhotoPreview"
              />

              <!-- Current Profile Photo -->
              <div v-show="!photoPreview">
                <img
                  :src="user.profile_photo_url"
                  :alt="user.name"
                  class="inline-block size-16 rounded-full ring-2 ring-white dark:ring-neutral-900"
                />
              </div>

              <!-- New Profile Photo Preview -->
              <div v-show="photoPreview">
                <span
                  class="inline-block size-16 rounded-full ring-2 ring-white dark:ring-neutral-900"
                  :style="'background-image: url(\'' + photoPreview + '\');'"
                />
              </div>

              <div class="flex gap-x-2">
                <div>
                  <button
                    type="button"
                    @click.prevent="selectNewPhoto"
                    class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-700"
                  >
                    <svg
                      class="flex-shrink-0 size-4"
                      xmlns="http://www.w3.org/2000/svg"
                      width="24"
                      height="24"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                      <polyline points="17 8 12 3 7 8" />
                      <line x1="12" x2="12" y1="3" y2="15" />
                    </svg>
                    SELECIONAR UMA NOVA FOTO
                  </button>
                </div>
              </div>

              <div class="flex gap-x-2">
                <div>
                  <button
                    type="button"
                    v-if="user.profile_photo_path"
                    @click.prevent="deletePhoto"
                    class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-700"
                  >
                    <svg
                      class="flex-shrink-0 size-4"
                      xmlns="http://www.w3.org/2000/svg"
                      width="24"
                      height="24"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <line x1="18" y1="6" x2="6" y2="18" />
                      <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                    REMOVER FOTO
                  </button>
                </div>
              </div>

              <InputError :message="form.errors.photo" />
            </div>
          </div>

          <div class="col-span-2">
            <label
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
              for="name"
            >
              Nome
            </label>
          </div>
          <div class="col-span-9">
            <div class="flex items-center gap-5">
              <TextInput
                id="name"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full"
                autocomplete="name"
                required
              />
              <InputError :message="form.errors.name" />
            </div>
          </div>

          <div class="col-span-2">
            <label
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
              for="email"
            >
              Email
            </label>
          </div>
          <div class="col-span-9">
            <div class="flex items-center gap-5">
              <span
                id="email"
                class="font-medium inline-block text-gray-800 mt-2.5 dark:text-neutral-200 w-full"
                >{{ form.email }}</span
              >
              <InputError :message="form.errors.email" />
            </div>
          </div>

          <div class="col-span-12 mt-0">
            <div align="center">
              <div
                v-if="
                  $page.props.jetstream.hasEmailVerification &&
                  user.email_verified_at === null
                "
              >
                <p class="text-sm mt-2 dark:text-white">
                  E-mail não verificado.

                  <Link
                    :href="route('verification.send')"
                    method="post"
                    as="button"
                    class="underline text-sm colorBase hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                    @click.prevent="sendEmailVerification"
                  >
                    Clique aqui para re-enviar o e-mail de verificação.
                  </Link>
                </p>

                <div
                  v-show="verificationLinkSent"
                  class="mt-2 font-medium text-sm text-green-600 dark:text-green-400"
                >
                  O link de verificação foi enviado ao seu endereço do e-mail.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #actions>
      <ActionMessage :on="form.recentlySuccessful" class="me-3"> Salvo. </ActionMessage>

      <PrimaryButton
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
      >
        Salvar
      </PrimaryButton>
    </template>
  </FormSection>
</template>
