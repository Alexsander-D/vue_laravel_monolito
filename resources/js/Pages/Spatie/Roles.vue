<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import ManageRoles from "@/Pages/Spatie/Partials/ManageRoles.vue";
import ManagePermissions from "@/Pages/Spatie/Partials/ManagePermissions.vue";
import CreateNewPermission from "@/Pages/Spatie/Partials/CreateNewPermission.vue";
import { defineProps } from "vue";

const props = defineProps({
  rolesWithPermissions: {
    type: Array,
    required: true,
    default: () => [],
  },
  permissions: {
    type: Array,
    required: true,
  },
});
const roles = props.rolesWithPermissions.map((item) => item.role);
</script>

<template>
  <BaseLayout title="UserSettings">
    <!-- Card Section -->
    <div class="w-full mx-auto pt-1">
      <!-- Card -->
      <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
        <!-- Content -->
        <ManageRoles :roles="roles" class="mt-10 md:mt-3" />
        <ManagePermissions
          :permissions="props.permissions"
          :rolesWithPermissions="props.rolesWithPermissions"
          class="mt-10 md:mt-3"
        />
        <div v-if="$page.props.userRole === 'Admin'">
          <CreateNewPermission :permissions="props.permissions" class="mt-10 md:mt-3" />
        </div>
      </div>
      <!-- End Section -->
    </div>
    <!-- End Card Section -->
  </BaseLayout>
</template>
