<script setup>
import { ref, defineProps, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Datatable from "@/Components/Datatable.vue";
import Select from "@/Components/Select.vue";

const props = defineProps({
  components: Array,
});

const form = useForm({
  component: "",
  family: "",
});

const submitComponentForm = () => {
  form.post(route("components.store"), {
    errorBag: "submitComponentForm",
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
    onError: (error) => {
      form.reset();
    },
  });
};

const families = ref([]);

const fetchData = async (url, params, setter) => {
  try {
    const response = await axios.post(url, params);

    if (response.data) {
      const mappedData = response.data.map((item) => {
        let result = {};

        result = {
          label: item.family,
        };

        return result;
      });

      setter(mappedData);
    }
  } catch (error) {
    console.error(`Erro ao carregar dados: ${error.message}`);
  }
};

const fetchProducts = (search = "") =>
  fetchData(
    route("findFamily.show"),
    { family: search },
    (data) => (families.value = data)
  );

const tableHeaders = ref([{ name: "ID" }, { name: "Componente" }, { name: "Família" }]);
const tableId = ref("ManageComponents");

onMounted(() => {
  fetchProducts();
});
</script>

<template>
  <FormSection @submitted="submitComponentForm">
    <template #title> Gerenciar Componentes </template>

    <template #description>
      Crie componentes para que seja possível atribuir defeitos e soluções a ele.
    </template>

    <template #form>
      <!-- Formulário -->
      <div class="grid grid-cols-12 gap-2 sm:gap-6 mt-8 items-center">
        <div class="col-span-12">
          <div class="col-span-3 sm:col-span-2">
            <label
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
              for="familySelect"
            >
              Família:
            </label>
          </div>

          <div class="col-span-9 sm:col-span-8">
            <div class="flex flex-col gap-2">
              <Select
                id="familySelect"
                ref="familySelect"
                class="mt-1 block w-full"
                :options="families"
                v-model="form.family"
                @search-change="fetchProducts"
                label="label"
                required
              />
              <InputError :message="form.errors.family" />
            </div>
          </div>
        </div>

        <div class="col-span-12">
          <div class="col-span-3 sm:col-span-2">
            <label
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
              for="roleInput"
            >
              Componente:
            </label>
          </div>

          <div class="col-span-9 sm:col-span-8">
            <div class="flex flex-col gap-2">
              <TextInput
                id="roleInput"
                ref="roleInput"
                rules="required|max:10|regex:/^[A-Z][a-zA-Z]*$/"
                @input="form.component = $event.target.value.toUpperCase().trim()"
                v-model="form.component"
                type="text"
                class="mt-1 block w-full"
                placeholder="Após criar um componente não será possível voltar atrás"
                autocomplete="off"
              />
              <InputError :message="form.errors.component" />
            </div>
          </div>
        </div>

        <!-- Botão de salvar -->
        <div class="col-span-12 sm:col-span-12 flex justify-end mt-4 sm:mt-2">
          <PrimaryButton
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
          >
            Salvar
          </PrimaryButton>
        </div>

        <!-- Mensagem de sucesso -->
        <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
          <ActionMessage :on="form.recentlySuccessful"> Salvo. </ActionMessage>
        </div>
      </div>
      <!-- Fim do Formulário -->
      <Datatable :thead="tableHeaders" :tbody="props.components" :id="tableId" />
    </template>
  </FormSection>
</template>
