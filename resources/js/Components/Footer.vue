<script setup>
import { onMounted, onUnmounted, ref } from "vue";

const phrases = [
  "Excelência começa com organização. Juntos, fazemos mais.",
  "Qualidade certificada do começo ao fim.",
  "Estamos comprometidos com qualidade e inovação.",
  "Crescemos com responsabilidade, focados em um futuro sustentável.",
  "Simplicidade e eficiência: a base do nosso sucesso com 5S.",
  "Um ambiente organizado, profissional e leve impulsiona grandes resultados.",
  "Nossa força está na união de processos eficientes e pessoas dedicadas.",
];
const currentYear = new Date().getFullYear();
const currentPhrase = ref(phrases[0]);
let phraseIndex = 0;
let intervalId = null;

const rotatePhrases = () => {
  phraseIndex = (phraseIndex + 1) % phrases.length;
  currentPhrase.value = phrases[phraseIndex];
};

onMounted(() => {
  intervalId = setInterval(rotatePhrases, 30000);
});

onUnmounted(() => {
  clearInterval(intervalId);
});
</script>
<template>
  <footer class="mt-auto w-full max-w-[85rem] py-1 mx-auto">
    <!-- Grid -->
    <div class="text-center">
      <div class="mt-2">
        <p class="text-gray-700 dark:text-neutral-400">
          {{ currentPhrase }}
        </p>
        <p class="text-gray-700 dark:text-neutral-400">
          © {{ currentYear }} Grupo Multi.
        </p>
      </div>
    </div>
    <!-- End Grid -->
  </footer>
</template>
