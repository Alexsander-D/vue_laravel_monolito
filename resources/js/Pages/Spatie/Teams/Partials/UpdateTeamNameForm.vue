<script setup>
import { defineProps } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
  team: Object,
  permissions: Object,
});

const form = useForm({
  name: props.team.name,
});

const updateTeamName = () => {
  form.put(route("teams.update", props.team), {
    errorBag: "updateTeamName",
    preserveScroll: true,
  });
};
</script>

<template>
  <FormSection @submitted="updateTeamName">
    <template #title> Nome do time </template>

    <template #description> O nome da equipe e informações do proprietário. </template>

    <template #form>
      <!-- Team Owner Information -->
      <div class="grid sm:grid-cols-12 gap-2 sm:gap-6 mt-8">
        <div class="col-span-2">
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
          >
            Responsável pelo time
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex items-center gap-5">
            <img
              class="w-12 h-12 rounded-full object-cover"
              :src="team.owner.profile_photo_url"
              :alt="team.owner.name"
            />

            <div class="ms-4 leading-tight">
              <div class="text-gray-900 dark:text-white">{{ team.owner.name }}</div>
              <div class="text-gray-700 dark:text-gray-300 text-sm">
                {{ team.owner.email }}
              </div>
            </div>
          </div>
        </div>

        <!-- Team Name -->
        <div class="col-span-2">
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="name"
          >
            Nome do time
          </label>
        </div>

        <div class="col-span-9">
          <div class="flex items-center gap-5">
            <TextInput
              id="name"
              v-model="form.name"
              type="text"
              class="mt-1 block w-full"
              :disabled="!permissions.canUpdateTeam"
            />

            <InputError :message="form.errors.name" class="mt-2" />
          </div>
        </div>
      </div>
    </template>

    <template v-if="permissions.canUpdateTeam" #actions>
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
