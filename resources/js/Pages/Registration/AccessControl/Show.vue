<script setup>
import { defineProps } from "vue";
import BaseLayout from "@/Layouts/BaseLayout.vue";
import UnassignedTeam from "@/Pages/Registration/AccessControl/Partials/UnassignedTeam.vue";

const props = defineProps({
  currentUser: {
    type: Object,
    default: () => [],
  },
  unassignedUsers: {
    type: Object,
    default: () => [],
  },
  ownTeams: {
    type: Array,
    default: () => [],
  },
  availableRoles: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <BaseLayout title="Unassigned Users">
    <!-- Card Section -->
    <div class="w-full mx-auto pt-1">
      <!-- Card -->
      <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
        <!-- Content -->
        <UnassignedTeam
          :currentUser="props.currentUser"
          :unassignedUsers="props.unassignedUsers"
          :ownTeams="props.ownTeams"
          :availableRoles="props.availableRoles"
        />
      </div>
      <!-- End Section -->
    </div>
    <!-- End Card Section -->
  </BaseLayout>
</template>
