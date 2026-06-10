<script setup>
import $ from "jquery";
import "jquery-mask-plugin";
import { defineProps, ref, onMounted, watch, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import TextArea from "@/Components/TextArea.vue";
import Select from "@/Components/Select.vue";
import { Inertia } from "@inertiajs/inertia";
import axios from "axios";

const props = defineProps({
  customersInfo: Object,
});

const isEditMode = computed(() => !!form.customerId);

const isCnpjDisabled = computed(() => {
  return isEditMode.value;
});

const isCompanyNameDisabled = computed(() => {
  if (isEditMode.value) {
    return form.type_person === "juridica";
  } else {
    return form.type_person === "juridica";
  }
});

const type_person = ref("juridica");
const estados = ref([]);
const cidades = ref([]);
const isLoading = ref(false);
var governmentType = ref("");

if (props.customersInfo.government == "governo") {
  governmentType = true;
} else {
  governmentType = false;
}

function documentType(document) {
  let selectedDocument = "";
  if (document) {
    document.replace(/\D/g, "");
    if (document.length === 14) {
      selectedDocument = "fisica";
      props.customersInfo.cpf = props.customersInfo.type_person;
    } else if (document.length === 18) {
      selectedDocument = "juridica";
      props.customersInfo.cnpj = props.customersInfo.type_person;
    } else {
      alert("DOCUMENTO INVÁLIDO!");
    }
  }

  return selectedDocument;
}

props.customersInfo.state = { label: props.customersInfo.state, value: "" };
props.customersInfo.city = { label: props.customersInfo.city, value: "" };

const form = useForm({
  _method: "POST",
  customerId: props.customersInfo?.id || null,
  type_person: documentType(props.customersInfo.type_person) || type_person,
  cpf: props.customersInfo?.cpf || "",
  cnpj: props.customersInfo?.cnpj || "",
  company_name: props.customersInfo?.company_name || "",
  trade_name: props.customersInfo?.trade_name || "",
  cep: props.customersInfo?.cep || "",
  state: props.customersInfo?.state || "",
  city: props.customersInfo?.city || "",
  road: props.customersInfo?.road || "",
  district: props.customersInfo?.district || "",
  number: String(props.customersInfo?.number) || "",
  telephone: props.customersInfo?.telephone || "",
  email: props.customersInfo?.email || "",
  responsible: props.customersInfo?.responsible || "",
  observation: props.customersInfo?.observation || "",
  government: governmentType,
});

const limparFormulario = () => {
  if (!form.customerId) {
    form.cpf = "";
    form.cnpj = "";
    form.company_name = "";
    form.trade_name = "";
    form.cep = "";
    form.state = null;
    form.city = null;
    form.road = "";
    form.district = "";
    form.number = "";
    form.telephone = "";
    form.email = "";
    form.responsible = "";
    form.observation = "";
    form.government = false;
  }
};

const buscarEstados = async () => {
  try {
    const response = await axios.get('/api/estados')
    estados.value = response.data.map((estado) => ({
      label: estado.nome.toUpperCase(),
      value: estado.sigla.toUpperCase(),
    }));
  } catch (error) {
    console.error("Erro ao buscar estados:", error);
  }
};

const buscarCidades = async (estadoSigla) => {
  form.city = "";
  cidades.value = [];
  try {
    const response = await axios.get(`/api/estados/${estadoSigla}/municipios`);
    cidades.value = response.data.map((cidade) => ({
      label: cidade.nome.toUpperCase(),
      value: cidade.nome.toUpperCase(),
    }));
  } catch (error) {
    console.error("Erro ao buscar cidades:", error);
  }
};


const buscarCnpj = async () => {
  form.company_name = "";
  form.trade_name = "";
  form.road = "";
  form.district = "";
  form.number = "";
  form.cep = "";
  form.state = "";
  form.city = "";

  const cnpj = form.cnpj.replace(/\D/g, "");

  if (cnpj.length === 14) {
    isLoading.value = true;
    try {
      const response = await axios.get(`/api/cnpj/${cnpj}`);
      const data = response.data;
      console.log(data);

      if (data.status === "OK") {
        form.company_name = data.nome || "";
        form.trade_name = data.fantasia || "";
        form.road = data.logradouro || "";
        form.district = data.bairro || "";
        form.number = data.numero || "";
        form.cep = data.cep || "";

        await buscarEstados();
        const estadoEncontrado = estados.value.find(
          (estado) => estado.value === data.uf.toUpperCase()
        );
        if (estadoEncontrado) {
          form.state = estadoEncontrado;

          await buscarCidades(estadoEncontrado.value);
          const cidadeEncontrada = cidades.value.find(
            (cidade) => cidade.label === data.municipio.toUpperCase()
          );
          if (cidadeEncontrada) {
            form.city = cidadeEncontrada;
          }
        } else {
          console.warn("Estado retornado pela API CNPJ não encontrado.");
        }
      } else {
        alert("CNPJ não encontrado!");
      }
    } catch (error) {
      console.error(
        "Erro ao buscar dados do CNPJ:",
        error.response?.data || error.message
      );
      alert("Erro ao consultar o CNPJ. Tente novamente.");
    } finally {
      isLoading.value = false;
    }
  } else {
    alert("Digite um CNPJ válido com 14 dígitos.");
  }
};

const buscarCep = async () => {
  const cep = form.cep.replace(/\D/g, "");

  if (cep.length === 8) {
    isLoading.value = true;
    try {
      const response = await axios.get(`/api/cep/${cep}`);
      const data = response.data;

      if (!data.erro) {
        form.road = data.logradouro || "";
        form.district = data.bairro || "";

        const estadoEncontrado = estados.value.find(
          (estado) => estado.value === data.uf.toUpperCase()
        );
        if (estadoEncontrado) {
          form.state = estadoEncontrado;

          await buscarCidades(estadoEncontrado.value);
          const cidadeEncontrada = cidades.value.find(
            (cidade) => cidade.label === data.localidade.toUpperCase()
          );
          if (cidadeEncontrada) {
            form.city = cidadeEncontrada;
          }
        } else {
          console.warn("Estado retornado pela API ViaCEP não encontrado.");
        }
      } else {
        alert("CEP não encontrado!");
      }
    } catch (error) {
      console.error("Erro ao buscar dados do CEP:", error.message);
      alert("Erro ao consultar o CEP. Tente novamente.");
    } finally {
      isLoading.value = false;
    }
  } else {
    alert("Digite um CEP válido com 8 dígitos.");
  }
};


const submitCustomerForm = () => {
  if (form.customerId) {
    form.put(route("customers.update"), {
      errorBag: "submitCustomersForm",
      preserveScroll: true,
      onSuccess: (value) => {
        Inertia.reload({ only: ["props.customersInfo"] });

        form.errors.type_person = value.props.flash.message;
        console.log(form.errors.type_person);

        const target = $("#scrollToSection");

        if (target.length) {
          $("html, body").stop().animate(
            {
              scrollTop: target.offset().top,
            },
            1000
          );
        }
      },
      onError: (error) => {
        console.log(error);
      },
    });
  } else {
    form.post(route("customers.create"), {
      errorBag: "submitCustomersForm",
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
      },
      onError: () => {
        const firstErrorField = Object.keys(form.errors)[0];

        if (firstErrorField) {
          $("#" + firstErrorField).focus();
        }
      },
    });
  }
};

const applyMasks = () => {
  $("#CEP").mask("00000-000");
  $("#cnpj").mask("00.000.000/0000-00");
  $("#cpf").mask("000.000.000-00");
  $("#telefone")
    .mask("(00) 00000-0000", {
      placeholder: "(__) ____-____",
      clearIfNotMatch: true,
      translation: {
        0: { pattern: /[0-9]/, optional: true },
      },
    })
    .focusout(function () {
      $(this).unmask();
      var tel = $(this).val().replace(/\D/g, "");
      if (tel.length == 10) {
        $(this).mask("(00) 0000-0000");
      } else {
        $(this).mask("(00) 00000-0000");
      }
      form.telephone = $(this).val();
    })
    .focusin(function () {
      $(this).mask("(00) 00000-0000");
    });
};

watch(
  () => form.state,
  (newValue) => {
    if (newValue) {
      buscarCidades(newValue.value);
    }
  }
);

watch(
  () => form.cnpj,
  (newValue) => {
    if (newValue && newValue.length === 14) {
      form.state = "";
      form.city = "";
    }
  }
);

onMounted(() => {
  buscarEstados();
  applyMasks();
  if (!form.customerId) {
    limparFormulario();
  }
  hideShowLogic();
});

const hideShowLogic = () => {
  if (!form.customerId) {
    limparFormulario();
  }

  if (form.type_person == "fisica") {
    $("#juridica").hide();
    $("#fisica").show();

    form.cnpj = "";

    $("#cpf").attr("required", true);
    $("#cnpj").attr("required", false);
  } else if (form.type_person == "juridica") {
    $("#juridica").show();
    $("#fisica").hide();

    form.cpf = "";

    $("#cpf").attr("required", false);
    $("#cnpj").attr("required", true);
  }
};
</script>

<style scoped>
.colorBase {
  color: var(--cor-contraste);
}
</style>

<template>
  <FormSection @submitted="submitCustomerForm">
    <template #title> Gerir Cliente </template>

    <template #description> Edite as informações do cliente. </template>

    <template #form>
      <input name="customerId" type="hidden" v-model="form.customerId" />
      <div class="col-span-12" v-if="!isEditMode">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4 justify-center mx-auto">
            <div class="flex items-center gap-2">
              <input
                id="juridicaRadio"
                name="pessoaTipo"
                type="radio"
                value="juridica"
                v-model="form.type_person"
                class="border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
                @change="hideShowLogic"
              />
              <label
                class="font-medium inline-block colorBase dark:text-neutral-200"
                for="juridicaRadio"
              >
                Pessoa jurídica
              </label>
            </div>
            <div class="flex items-center gap-2">
              <input
                id="fisicaRadio"
                name="pessoaTipo"
                type="radio"
                value="fisica"
                v-model="form.type_person"
                class="border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
                @change="hideShowLogic"
              />
              <label
                class="font-medium inline-block colorBase dark:text-neutral-200"
                for="fisicaRadio"
              >
                Pessoa física
              </label>
            </div>
          </div>
        </div>
      </div>
      <InputError
        class="text-center mt-4"
        id="scrollToSection"
        :message="form.errors.type_person"
      />

      <div class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2" id="fisica">
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="cpf"
          >
            CPF
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="cpf"
              v-model="form.cpf"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              maxlength="11"
              required
            />
            <InputError :message="form.errors.cpf" />
          </div>
        </div>
      </div>

      <div class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2" id="juridica">
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="cnpj"
          >
            CNPJ
          </label>
        </div>
        <div class="col-span-7">
          <div class="flex items-center gap-5">
            <TextInput
              id="cnpj"
              v-model="form.cnpj"
              :disabled="isCnpjDisabled"
              required
              @blur="buscarCnpj"
            />
            <div v-if="isLoading" class="flex items-center gap-2 text-sm text-white">
              <div class="loader"></div>
              <span class="loading-text">Carregando informações...</span>
            </div>
            <InputError :message="form.errors.cnpj" />
          </div>
        </div>

        <div class="col-span-2">
          <div class="flex items-center gap-2 mt-3">
            <input
              id="governmentCheckbox"
              name="government"
              type="checkbox"
              v-model="form.government"
              class="border-gray-200 rounded text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
            />
            <label
              class="font-medium inline-block colorBase dark:text-neutral-200"
              for="governmentCheckbox"
            >
              Governo
            </label>
          </div>
        </div>
      </div>

      <div class="grid md:grid-cols-12 gap-2 sm:gap-6 mt-2">
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="razaoSocial"
          >
            Razão social
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex items-center gap-5">
            <TextInput
              id="company_name"
              v-model="form.company_name"
              :disabled="isCompanyNameDisabled"
              required
              @input="form.company_name = form.company_name.toUpperCase()"
            />
            <InputError :message="form.errors.company_name" />
          </div>
        </div>

        <div v-if="form.type_person === 'juridica'" class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="nomeFantasia"
          >
            Nome fantasia
          </label>
        </div>
        <div v-if="form.type_person === 'juridica'" class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="nomeFantasia"
              v-model="form.trade_name"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              required
              @input="form.trade_name = form.trade_name.toUpperCase()"
            />
            <InputError :message="form.errors.trade_name" />
          </div>
        </div>

        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="CEP"
          >
            CEP
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="CEP"
              v-model="form.cep"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              maxlength="9"
              required
              @blur="buscarCep"
            />
            <InputError :message="form.errors.cep" />
          </div>
        </div>

        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="estadoSelect"
          >
            Estado
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <Select
              id="estadoSelect"
              class="mt-1 block w-full"
              :options="estados"
              v-model="form.state"
              label="label"
              required
            />
            <InputError :message="form.errors.state" />
          </div>
        </div>

        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="cidadeSelect"
          >
            Cidade
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <Select
              id="cidadeSelect"
              class="mt-1 block w-full"
              :options="cidades"
              v-model="form.city"
              label="label"
              required
            />
            <InputError :message="form.errors.city" />
          </div>
        </div>

        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="rua"
          >
            Rua
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="rua"
              v-model="form.road"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              required
              @input="form.road = form.road.toUpperCase()"
            />
            <InputError :message="form.errors.road" />
          </div>
        </div>

        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="bairro"
          >
            Bairro
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="bairro"
              v-model="form.district"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              required
              @input="form.district = form.district.toUpperCase()"
            />
            <InputError :message="form.errors.district" />
          </div>
        </div>

        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="numero"
          >
            Número
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="numero"
              v-model="form.number"
              type="number"
              class="mt-1 block w-full"
              autocomplete="off"
              required
            />
            <InputError :message="form.errors.number" />
          </div>
        </div>

        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="telefone"
          >
            Telefone
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="telefone"
              v-model="form.telephone"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              required
            />
            <InputError :message="form.errors.telephone" />
          </div>
        </div>

        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="responsible"
          >
            Responsável
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="responsible"
              v-model="form.responsible"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              required
              @input="form.responsible = form.responsible.toUpperCase()"
            />
            <InputError :message="form.errors.responsible" />
          </div>
        </div>

        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="email"
          >
            E-mail
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="email"
              v-model="form.email"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              required
              @input="form.email = form.email.toUpperCase()"
            />
            <InputError :message="form.errors.email" />
          </div>
        </div>

        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="obs"
          >
            Observação
          </label>
        </div>
        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextArea
              id="obs"
              v-model="form.observation"
              class="mt-1 block w-full"
              rows="3"
              @input="form.observation = form.observation.toUpperCase()"
            />
            <InputError :message="form.errors.observation" />
          </div>
        </div>
      </div>
    </template>

    <template #actions>
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
