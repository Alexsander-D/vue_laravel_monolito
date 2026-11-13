<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";

const currentDateTime = ref("");
const page = usePage();
const isGoaliTeam = page.props.auth?.user?.current_team?.name?.trim()?.toLocaleUpperCase() === "GOALI TRANSPORTES";

let interval;

onMounted(() => {
  if (isGoaliTeam) {
    return;
  }

  const updateClock = () => {
    const now = new Date();

    currentDateTime.value =
      now.toLocaleDateString("pt-BR") +
      " " +
      now.toLocaleTimeString("pt-BR");
  };

  updateClock();

  interval = setInterval(updateClock, 1000);
});

onUnmounted(() => {
  clearInterval(interval);
});
</script>
<template>
  <footer class="mt-auto w-full max-w-[85rem] py-1 mx-auto">
    <!-- Grid -->
    <div class="text-center">
      <div class="mt-2">
        <p v-if="isGoaliTeam" class="text-gray-700 dark:text-neutral-400">
          "Porque eu sei que meu redentor vive, e por fim se levantará sobre a terra" -Jó 19:25
        </p>
        <p v-else class="text-gray-700 dark:text-neutral-400">
          {{ currentDateTime }}
        </p>
      </div>
    </div>
    <!-- End Grid -->
  </footer>
</template>
