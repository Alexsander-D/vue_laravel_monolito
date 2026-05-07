<script setup>
import $ from "jquery";
import { ref, defineProps, computed, watch } from "vue"; // Adicionado 'watch'
import { useForm } from "@inertiajs/vue3";
import FormSection from "@/Components/FormSection.vue";
import Datatable from "@/Components/Datatable.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import DialogModal from "@/Components/DialogModal.vue";
import Select from "@/Components/Select.vue";
import axios from "axios";

const props = defineProps({
  currentUser: {
    type: Object,
  },

  unassignedUsers: {
    type: Object,
  },
  ownTeams: {
    type: Array,
  },
});

const teamSpecificRoles = ref([]);

const addTeamMemberForm = useForm({
  team_id: "",
  email: "",
  role: null,
});

const currentlyManagingRole = ref(false);
const managingRoleFor = ref(null);

const fetchTeamRoles = (team) => {
  const teamId = team.value;
  if (!teamId) {
    console.warn("teamId está indefinido. Abortando requisição.");
    teamSpecificRoles.value = [];
    addTeamMemberForm.role = null;
    return;
  }

  axios
    .get(route("teams.roles", { team: teamId })) // aqui passa o ID
    .then((response) => {
      if (response.status === 200 && response.data && response.data.length > 0) {
        teamSpecificRoles.value = response.data.map((role) => ({
          name: role.name,
          key: role.id,
        }));
        addTeamMemberForm.role = null;
      } else {
        throw new Error(`Resposta inesperada da API. Status: ${response.status}`);
      }
    })
    .catch((error) => {
      console.error("Erro ao buscar roles do time:", error);
      teamSpecificRoles.value = [];
      addTeamMemberForm.role = null;
    });
};

watch(
  () => addTeamMemberForm.team_id,
  (newTeamId) => {
    if (newTeamId) {
      fetchTeamRoles(newTeamId); // passa o ID direto
    } else {
      teamSpecificRoles.value = [];
      addTeamMemberForm.role = null;
    }
  },
  {
    immediate: true,
  }
);

const updateMemberTeam = () => {
  addTeamMemberForm.post(
    route("team-members.store", { team: addTeamMemberForm.team_id.value }),
    {
      errorBag: "addTeamMember",
      preserveScroll: true,
      onSuccess: () => (currentlyManagingRole.value = false),
    }
  );
};

const tableHeaders = ref([
  { name: "ID" },
  { name: "NOME" },
  { name: "E-MAIL" },
  { name: "" },
]);
const tableId = ref("ManageProducts");

const manageUserTeam = (user) => {
  addTeamMemberForm.email = user.email;
  managingRoleFor.value = user;
  currentlyManagingRole.value = true;
};

const modalButton = (user) => {
  return `<button
    type="button"
    data-user='${JSON.stringify(user)}'
    class="modal-btn flex shrink-0 justify-center items-center gap-2 size-[30px] text-sm rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
    ${user.id === props.currentUser.id ? "disabled" : ""}
    ${user.id === 1 ? "disabled" : ""}
  >
    📝
  </button>`;
};

const tableData = computed(() => {
  return props.unassignedUsers.map((user) => ({
    userId: user.id,
    userName: user.name,
    userEmail: user.email,
    button: modalButton(user),
  }));
});

// Este uso de jQuery diretamente no script setup pode ser problemático em alguns cenários.
// Se possível, é melhor usar eventos Vue (@click) diretamente no template.
// Manterei para compatibilidade com seu código existente.
$(document).on("click", ".modal-btn", function () {
  const user = $(this).data("user");
  manageUserTeam(user);
});

const ownTeams = computed(() =>
  props.ownTeams.map((item) => ({ label: item.name, value: item.id }))
);
</script>

<template>
  <DialogModal
    :show="currentlyManagingRole"
    @close="currentlyManagingRole = false"
    max-width="lg"
  >
    <template #title>
      <div class="text-center">Atribuir Time</div>
    </template>

    <template #content>
      <div>
        <div class="form-group">
          <!-- Select para escolher o time -->
          <label
            for="team-select"
            class="block text-sm font-medium text-black dark:text-neutral-200 mb-2"
          >
            Escolha um Time:
          </label>
          <InputError :message="addTeamMemberForm.errors.team_id" class="mt-2" />
          <Select
            id="team-select"
            class="mb-6 block w-full"
            :options="ownTeams"
            v-model="addTeamMemberForm.team_id"
            label="label"
            required
          />
        </div>
        <div class="form-group mt-12">
          <!-- Role -->
          <div class="col-span-6 lg:col-span-4">
            <label
              for="roles"
              class="block text-sm font-medium text-black dark:text-neutral-200 mb-2"
            >
              Escolha uma Função:
            </label>
            <InputError :message="addTeamMemberForm.errors.role" class="mt-2" />

            <div
              class="relative z-0 mt-2 mb-4 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer"
            >
              <button
                v-if="teamSpecificRoles.length == 0"
                type="button"
                class="border-t border-gray-200 dark:border-gray-700 focus:border-none relative px-4 py-3 inline-flex w-full rounded-lg focus:z-10 focus:outline-none focus:border-neutral-500 dark:focus:border-neutral-600 focus:ring-2 focus:ring-neutral-500 dark:focus:ring-neutral-600"
                disabled
              >
                <div class="flex items-center justify-center w-full">
                  <div class="text-sm text-gray-600 dark:text-gray-400 text-center">
                    Aguarde enquanto carregamos as funções do time...
                  </div>
                </div>
              </button>

              <button
                v-if="teamSpecificRoles.length > 0"
                v-for="(role, i) in teamSpecificRoles"
                :key="role.key"
                type="button"
                class="relative px-4 py-3 inline-flex w-full rounded-lg focus:z-10 focus:outline-none focus:border-neutral-500 dark:focus:border-neutral-600 focus:ring-2 focus:ring-neutral-500 dark:focus:ring-neutral-600"
                :class="{
                  'border-t border-gray-200 dark:border-gray-700 focus:border-none rounded-t-none':
                    i > 0,
                  'rounded-b-none': i != Object.keys(teamSpecificRoles).length - 1,
                }"
                @click="addTeamMemberForm.role = role.name"
              >
                <div
                  :class="{
                    'opacity-80':
                      addTeamMemberForm.role && addTeamMemberForm.role != role.name,
                  }"
                >
                  <!-- Role Name -->
                  <div class="flex items-center mt-2">
                    <div
                      class="text-sm text-gray-600 dark:text-gray-400"
                      :class="{ 'font-semibold': addTeamMemberForm.role == role.name }"
                    >
                      {{ role.name }}
                    </div>

                    <svg
                      v-if="addTeamMemberForm.role == role.name"
                      class="ms-2 h-5 w-5 text-green-400"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke-width="1.5"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                  </div>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Rodapé do Modal -->
    <template #footer>
      <PrimaryButton
        :class="{
          'opacity-25': addTeamMemberForm.processing,
          'mb-4': true,
          'mt-4': true,
        }"
        :disabled="!addTeamMemberForm.role"
        @click="updateMemberTeam"
        class="bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Confirmar
      </PrimaryButton>
    </template>
  </DialogModal>

  <FormSection>
    <template #title>
      <div class="flex justify-between items-center">
        <div class="flex-grow text-center">Usuários Aguardando Atribuição</div>
      </div>
    </template>

    <template #description>
      Lista de usuários que ainda não foram atribuidos a um time
    </template>

    <template #form>
      <Datatable :thead="tableHeaders" :tbody="tableData" :id="tableId" />
    </template>
  </FormSection>
</template>
