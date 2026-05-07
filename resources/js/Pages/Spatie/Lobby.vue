<script setup>
import { defineProps } from "vue";
import { Link } from "@inertiajs/vue3";
import BaseLayout from "@/Layouts/BaseLayout.vue";

const props = defineProps({
  teams: Array,
});
</script>

<template>
  <BaseLayout title="Lobby">
    <!-- Card Section -->
    <div class="w-full mx-auto pt-1">
      <!-- Card -->
      <div class="bg-white rounded-xl shadow shadow-lg p-0 dark:bg-gray-900">
        <!-- Content -->
        <div class="p-4 mx-auto">
          <!-- Grid -->
          <div class="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- RMA -->
            <div v-for="team in teams">
              <Link
                class="group flex flex-col bg-white border shadow-sm rounded-xl hover:shadow-md focus:outline-none focus:shadow-md transition"
                :href="route('dashboard')"
                aria-current="page"
              >
                <!-- Card -->

                <div class="p-4 md:p-5 border rounded-xl text-center">
                  <div class="flex justify-between items-center gap-x-3">
                    <div class="grow">
                      <h3 class="group-hover:text-blue-600 font-semibold">
                        {{ team.name }}
                      </h3>
                      <p
                        v-if="team.role"
                        class="group-hover:text-blue-600 text-sm text-gray-500 dark:text-neutral-500"
                      >
                        {{ team.role }}
                      </p>
                    </div>
                    <div>
                      <svg
                        class="shrink-0 size-5 text-gray-800 dark:text-neutral-200 group-hover:text-blue-600"
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
                        <path d="m9 18 6-6-6-6" />
                      </svg>
                    </div>
                  </div>
                </div>
              </Link>
            </div>
          </div>

          <!-- End Grid -->
        </div>
        <!-- End Icon Blocks -->
      </div>
      <!-- End Section -->
    </div>
    <!-- End Card Section -->
  </BaseLayout>
</template>
