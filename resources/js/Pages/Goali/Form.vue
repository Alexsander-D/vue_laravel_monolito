<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
  solicitations: { type: Array, default: () => [] },
  amounts: { type: Array, default: () => [] },
});

const today = new Date().toLocaleDateString("en-CA");
const form = useForm({ travel_date: today, travel_time: "", status: "", solicitation: "", origin: "", destination: "", passenger: "", amount: "" });
const page = usePage();
const successMessage = computed(() => page.props.flash?.success || "");

const submit = () => {
  form.post(route("goali.store"), {
    preserveScroll: true,
    onSuccess: () => form.reset("travel_time", "status", "solicitation", "origin", "destination", "passenger", "amount"),
  });
};
</script>

<template>
  <BaseLayout title="GT DIONÍNIO BARBOSA">
    <main class="goali-page mx-auto w-full max-w-3xl px-3 py-6 sm:px-6 sm:py-10">
      <header class="goali-banner overflow-hidden rounded-t-xl">
        <div class="banner-mark" aria-hidden="true">GT</div>
        <div class="relative z-10 px-6 py-8 sm:px-10 sm:py-10">
          <p class="text-xs font-bold uppercase text-white/75">Registro de deslocamento</p>
          <h1 class="mt-2 text-3xl font-semibold text-white sm:text-4xl">GT DIONÍNIO BARBOSA</h1>
          <p class="mt-3 max-w-xl text-sm leading-6 text-white/85">“Porque eu sei que o meu Redentor vive, e que por fim se levantará sobre a terra” - Jó 19:25</p>
        </div>
      </header>

      <form class="space-y-3" @submit.prevent="submit">
        <section class="form-section">
          <label for="travel_date" class="field-label">DATA <span>*</span></label>
          <input id="travel_date" v-model="form.travel_date" type="date" class="field-input" required />
          <p v-if="form.errors.travel_date" class="field-error">{{ form.errors.travel_date }}</p>
        </section>
        <section class="form-section">
          <label for="travel_time" class="field-label">HORÁRIO <span>*</span></label>
          <input id="travel_time" v-model="form.travel_time" type="time" class="field-input max-w-56" required />
          <p v-if="form.errors.travel_time" class="field-error">{{ form.errors.travel_time }}</p>
        </section>
        <fieldset class="form-section">
          <legend class="field-label">STATUS <span>*</span></legend>
          <div class="mt-4 flex flex-wrap gap-x-8 gap-y-3">
            <label v-for="status in ['ENTRADA', 'SAÍDA']" :key="status" class="choice-label"><input v-model="form.status" type="radio" name="status" :value="status" required /><span>{{ status }}</span></label>
          </div>
          <p v-if="form.errors.status" class="field-error">{{ form.errors.status }}</p>
        </fieldset>
        <fieldset class="form-section">
          <legend class="field-label">SOLICITAÇÃO <span>*</span></legend>
          <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
            <label v-for="solicitation in props.solicitations" :key="solicitation" class="choice-label"><input v-model="form.solicitation" type="radio" name="solicitation" :value="solicitation" required /><span>{{ solicitation }}</span></label>
          </div>
          <p v-if="form.errors.solicitation" class="field-error">{{ form.errors.solicitation }}</p>
        </fieldset>
        <section v-for="field in [{ key: 'origin', label: 'ORIGEM' }, { key: 'destination', label: 'DESTINO' }, { key: 'passenger', label: 'PASSAGEIRO' }]" :key="field.key" class="form-section">
          <label :for="field.key" class="field-label">{{ field.label }} <span>*</span></label>
          <input :id="field.key" v-model="form[field.key]" type="text" class="field-input" placeholder="Sua resposta" maxlength="255" required />
          <p v-if="form.errors[field.key]" class="field-error">{{ form.errors[field.key] }}</p>
        </section>
        <fieldset class="form-section">
          <legend class="field-label">VALOR <span>*</span></legend>
          <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3">
            <label v-for="amount in props.amounts" :key="amount" class="choice-label"><input v-model="form.amount" type="radio" name="amount" :value="String(amount)" required /><span>R$ {{ amount }}</span></label>
          </div>
          <p v-if="form.errors.amount" class="field-error">{{ form.errors.amount }}</p>
        </fieldset>
        <div class="flex flex-wrap items-center justify-between gap-4 px-1 py-4">
          <button type="submit" class="submit-button" :disabled="form.processing">{{ form.processing ? "Enviando..." : "Enviar registro" }}</button>
          <button type="reset" class="text-sm font-medium text-teal-800 hover:text-teal-950" @click="form.reset()">Limpar formulário</button>
        </div>
        <p v-if="successMessage" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ successMessage }}</p>
      </form>
      <p class="px-1 py-5 text-center text-xs text-gray-500">* Indica uma pergunta obrigatória</p>
    </main>
  </BaseLayout>
</template>

<style scoped>
.goali-page { --goali-teal: #0099a1; font-family: "Roboto", "Trebuchet MS", sans-serif; }
.goali-banner { position: relative; isolation: isolate; background: linear-gradient(115deg, #075b61, #0099a1 58%, #3fdadf); }
.banner-mark { position: absolute; right: -1rem; top: -3.7rem; color: rgb(255 255 255 / 9%); font-size: 15rem; font-weight: 800; line-height: 1; }
.form-section { border: 1px solid #e2e8e8; border-radius: 10px; background: white; padding: 1.5rem; box-shadow: 0 1px 2px rgb(18 55 58 / 5%); }
.field-label { color: #202124; font-size: .95rem; font-weight: 600; }
.field-label span { color: #b42318; }
.field-input { display: block; width: 100%; margin-top: 1.25rem; border: 0; border-bottom: 1px solid #9aa0a6; border-radius: 0; padding: .65rem .15rem; color: #202124; outline: none; }
.field-input:focus { border-bottom: 2px solid var(--goali-teal); }
.choice-label { display: flex; min-height: 2rem; align-items: center; gap: .75rem; color: #303438; font-size: .92rem; cursor: pointer; }
.choice-label input { width: 1.1rem; height: 1.1rem; accent-color: var(--goali-teal); }
.field-error { margin-top: .5rem; color: #b42318; font-size: .8rem; }
.submit-button { min-width: 8rem; border-radius: 5px; background: #007f86; padding: .7rem 1.25rem; color: white; font-size: .9rem; font-weight: 600; }
.submit-button:hover { background: #00676d; }
.submit-button:disabled { cursor: wait; opacity: .65; }
@media (prefers-reduced-motion: no-preference) { .form-section { animation: appear .35s both; } .form-section:nth-child(2) { animation-delay: 40ms; } .form-section:nth-child(3) { animation-delay: 80ms; } @keyframes appear { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } } }
</style>