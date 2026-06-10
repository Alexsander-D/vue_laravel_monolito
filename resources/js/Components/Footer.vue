<script setup>
import { ref, onMounted, onUnmounted } from "vue";

const currentDateTime = ref("");

let interval;

onMounted(() => {
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
        <!-- <p class="text-gray-700 dark:text-neutral-400">
          {{ currentPhrase }}
        </p> -->
        <p class="text-gray-700 dark:text-neutral-400">
          {{ currentDateTime }}
        </p>
      </div>
    </div>
    <!-- End Grid -->
  </footer>
</template>
