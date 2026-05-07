<script setup>
import { defineProps } from "vue";
import DeleteTeamForm from "@/Pages/Spatie/Teams/Partials/DeleteTeamForm.vue";
import SectionBorder from "@/Components/SectionBorder.vue";
import TeamMemberManager from "@/Pages/Spatie/Teams/Partials/TeamMemberManager.vue";
import UpdateTeamNameForm from "@/Pages/Spatie/Teams/Partials/UpdateTeamNameForm.vue";
import BaseLayout from "@/Layouts/BaseLayout.vue";

const props = defineProps({
  team: Object,
  availableRoles: Array,
  permissions: Object,
});
</script>

<template>
  <BaseLayout title="UserSettings">
    <!-- Card Section -->
    <div class="w-full mx-auto pt-1">
      <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
        <UpdateTeamNameForm :team="team" :permissions="permissions" />

        <TeamMemberManager
          class="mt-10 sm:mt-0"
          :team="team"
          :available-roles="availableRoles"
          :user-permissions="permissions"
        />

        <SectionBorder />

        <template v-if="permissions.canDeleteTeam && !team.personal_team">
          <DeleteTeamForm class="mt-10 sm:mt-0" :team="team" />
        </template>
      </div>
    </div>
  </BaseLayout>
</template>
