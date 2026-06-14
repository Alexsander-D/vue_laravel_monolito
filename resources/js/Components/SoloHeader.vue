<script setup>
import { onMounted, onUnmounted, ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Dropdown from "@/Components/Dropdown.vue";

const isDarkMode = ref(false);
const isMenuOpen = ref(true);
const isButtonHidden = ref(true);

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value;
};

const switchTheme = () => {
  isDarkMode.value = !isDarkMode.value;
  if (isDarkMode.value) {
    document.documentElement.classList.add("dark");
    localStorage.setItem("theme", "dark");
  } else {
    document.documentElement.classList.remove("dark");
    localStorage.setItem("theme", "light");
  }
};

const logout = () => {
  router.post(route("logout"));
};

const switchToTeam = (team) => {
  router.put(
    route("current-team.update"),
    {
      team_id: team.id,
    },
    {
      preserveState: false,
    }
  );
};

const mediaQuery = window.matchMedia("(min-width: 900px)");

const updateMenuState = (matches) => {
  isButtonHidden.value = matches;
  isMenuOpen.value = matches;
};

onMounted(() => {
  updateMenuState(mediaQuery.matches);

  if (localStorage.getItem("theme") === "dark") {
    isDarkMode.value = true;
    document.documentElement.classList.add("dark");
  } else {
    isDarkMode.value = false;
    document.documentElement.classList.remove("dark");
  }
});
</script>

<template>
  <!-- ========== HEADER ========== -->
  <header
    class="sticky top-0 inset-x-0 flex z-[48] w-full bg-white shadow shadow-md py-2 sm:py-1 lg:ps-64 dark:bg-gray-900">
    <!-- HAMBURGUER BUTTON -->
    <div class="flex items-center absolute left-12 mx-auto justify-start top-1/2 -translate-y-1/2">
      <button :class="{
        hidden: isButtonHidden,
      }" class="rounded-md focus:outline-none" @click="toggleMenu" aria-label="Toggle menu">
        <svg class="w-6 h-6 text-black dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
        </svg>
      </button>
    </div>
    <!-- HAMBURGUER BUTTON -->

    <!-- LOGOMARCA -->
    <div :class="{
      hidden: !isButtonHidden,
      flex: isButtonHidden,
    }" class="flex items-center absolute left-12 mx-auto justify-start top-1/2 -translate-y-1/2">
      <Link
        class="flex-none rounded-md text-xl inline-block font-semibold focus:outline-none focus:opacity-80 flex items-center"
        :href="route('dashboard')" aria-label="barbearia">
        <img src="/images/logomarca.png" alt="barbearia" class="w-32 h-16" />
      </Link>
    </div>
    <!-- FIM LOGOMARCA -->

    <nav class="flex basis-full w-full mx-auto justify-center items-center space-x-4 p-2">
      <div class="flex flex-col sm:w-full md:flex-row md:w-auto items-center md:ml-auto">
        <!-- CADASTROS DROPDOWN -->
        <div v-if="
          ['cadastrar-falhas', 'cadastrar-produtos'].some((term) =>
            $page.props.userPermissions.includes(term)
          )
        " :class="{
          hidden: !isMenuOpen,
          block: isMenuOpen,
        }" class="ms-3 relative py-1">
          <Dropdown align="bottom" width="60">
            <template #trigger>
              <span class="inline-flex rounded-md whitespace-nowrap">
                <button type="button" class="inline-flex items-center px-3 py-2 stringColor text-sm focus:outline-none">
                  Cadastros

                  <svg class="flex-shrink-0 ms-1 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                  </svg>
                </button>
              </span>
            </template>

            <template #content>
              <div class="w-60">
                <Link v-if="$page.props.userPermissions.includes('cadastrar-falhas')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('failures.index')">
                  Defeitos e Soluções
                </Link>

                <Link v-if="$page.props.userPermissions.includes('cadastrar-produtos')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('products.index')">
                  Produtos
                </Link>
              </div>
            </template>
          </Dropdown>
        </div>
        <!-- FIM CADASTROS DROPDOWN -->

        <!-- CADASTROS DROPDOWN -->
        <div v-if="$page.props.auth.user.current_team.name.includes('SAC')" :class="{
          hidden: !isMenuOpen,
          block: isMenuOpen,
        }" class="ms-3 relative py-1">
          <Dropdown align="bottom" width="60">
            <template #trigger>
              <span class="inline-flex rounded-md whitespace-nowrap">
                <button type="button" class="inline-flex items-center px-3 py-2 stringColor text-sm focus:outline-none">
                  Fila

                  <svg class="flex-shrink-0 ms-1 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                  </svg>
                </button>
              </span>
            </template>

            <template #content>
              <div class="w-60">
                <Link v-if="$page.props.userPermissions.includes('separar-rastreio')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('separated-tracking.index')">
                  Separar rastreio
                </Link>

                <Link v-if="$page.props.userPermissions.includes('coletar-rastreio')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('collect-tracking.index')">
                  Coletar rastreio
                </Link>

                <Link v-if="
                  ['separar-rastreio', 'coletar-rastreio'].some((term) =>
                    $page.props.userPermissions.includes(term)
                  )
                " class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('collect-tracking.show')">
                  Relatório
                </Link>
              </div>
            </template>
          </Dropdown>
        </div>
        <!-- FIM CADASTROS DROPDOWN -->

        <!-- MATERIAIS TRIAGEMDROPDOWN -->
        <div v-if="
          $page.props.auth.user.current_team.name.includes('TRIAGEM') &&
          $page.props.userPermissions.includes('gerir-materiais')
        " :class="{
          hidden: !isMenuOpen,
          block: isMenuOpen,
        }" class="ms-3 relative py-1">
          <Dropdown align="right" width="60">
            <template #trigger>
              <span class="inline-flex rounded-md whitespace-nowrap">
                <button type="button" class="inline-flex items-center px-3 py-2 stringColor text-sm focus:outline-none">
                  Materiais
                  <svg class="flex-shrink-0 ms-1 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                  </svg>
                </button>
              </span>
            </template>

            <template #content>
              <div class="w-60">
                <Link
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('viewMaterial.index')">
                  Visualizar Materiais
                </Link>
              </div>
            </template>
          </Dropdown>
        </div>

        <!-- FIM MATERIAIS TRIAGEM DROPDOWN -->

        <!-- RELATÓRIOS TRIAGEM DROPDOWN -->
        <div v-if="
          $page.props.auth.user.current_team.name.includes('TRIAGEM') &&
          $page.props.userPermissions.includes('relatorios-triagem')
        " :class="{
          hidden: !isMenuOpen,
          block: isMenuOpen,
        }" class="ms-3 relative py-1">
          <Dropdown align="right" width="60">
            <template #trigger>
              <span class="inline-flex rounded-md whitespace-nowrap">
                <button type="button" class="inline-flex items-center px-3 py-2 stringColor text-sm focus:outline-none">
                  Relatórios
                  <svg class="flex-shrink-0 ms-1 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                  </svg>
                </button>
              </span>
            </template>

            <template #content>
              <div class="w-60">
                <Link
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('positioning.index')">
                  Posicionamento
                </Link>
              </div>
            </template>
          </Dropdown>
        </div>
        <!-- FIM RELATÓRIOS TRIAGEM DROPDOWN -->

        <!-- ATENDIMENTO EXTERNO DROPDOWN -->
        <div v-if="$page.props.auth.user.current_team.name.includes('TRIAGEM')" :class="{
          hidden: !isMenuOpen,
          block: isMenuOpen,
        }" class="ms-3 relative py-1">
          <!-- Teams Dropdown -->
          <Dropdown align="right" width="60">
            <template #trigger>
              <span class="inline-flex rounded-md whitespace-nowrap">
                <button type="button" class="inline-flex items-center px-3 py-2 stringColor text-sm focus:outline-none">
                  Atendimento Externo
                  <svg class="flex-shrink-0 ms-1 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                  </svg>
                </button>
              </span>
            </template>

            <template #content>
              <div class="w-60">
                <Link v-if="$page.props.userPermissions.includes('gerir-clientes')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('viewCustomers.show')">
                  Clientes
                </Link>

                <Link v-if="$page.props.userPermissions.includes('cadastrar-atendimento')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('screening.index')">
                  Criar Atendimento
                </Link>

                <Link v-if="$page.props.userPermissions.includes('gerir-triagens')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('ViewScreening.index')">
                  Triagens
                </Link>

                <Link v-if="$page.props.userPermissions.includes('gerir-includes')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('include.index')">
                  Includes
                </Link>

                <Link v-if="$page.props.userPermissions.includes('gerir-laudos')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('techLog.index')">
                  Laudos
                </Link>

                <Link v-if="$page.props.userPermissions.includes('gerir-agenda')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('calendar.index')">
                  Agenda
                </Link>

                <Link v-if="$page.props.userPermissions.includes('gerir-historico')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('screeningTimeline.index')">
                  Histórico
                </Link>
              </div>
            </template>
          </Dropdown>
        </div>
        <!-- FIM ATENDIMENTO EXTERNO DROPDOWN -->

        <!-- BARBERSHOP -->
        <div :class="{
          hidden: !isMenuOpen,
          block: isMenuOpen,
        }" class="ms-3 relative py-1">
          <!-- Teams Dropdown -->
          <Dropdown align="right" width="60">
            <template #trigger>
              <Link
                class="stringColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                :href="route('attendance.report')">
                RELATÓRIO
              </Link>
            </template>
          </Dropdown>
        </div>
        <!-- FIM BARBERSHOP -->
        <!-- PRODUÇÃO DIÁRIA DROPDOWN -->
        <div v-if="
          ['RMA', 'SAC'].some((term) =>
            $page.props.auth.user.current_team.name.includes(term)
          )
        " :class="{
          hidden: !isMenuOpen,
          block: isMenuOpen,
        }" class="ms-3 relative py-1">
          <!-- Teams Dropdown -->
          <Dropdown align="right" width="60">
            <template #trigger>
              <span class="inline-flex rounded-md whitespace-nowrap">
                <button type="button" class="inline-flex items-center px-3 py-2 stringColor text-sm focus:outline-none">
                  Apontamentos
                  <svg class="flex-shrink-0 ms-1 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                  </svg>
                </button>
              </span>
            </template>

            <template #content>
              <div class="w-60">
                <Link v-if="$page.props.userPermissions.includes('realizar-entrada')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('entry.index')">
                  Realizar Entrada
                </Link>

                <Link v-if="$page.props.userPermissions.includes('atribuir-fila')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('set-queue.index')">
                  Aguardando Atribuição
                </Link>

                <Link v-if="$page.props.userPermissions.includes('realizar-laudo')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('queue.index')">
                  Realizar Laudo
                </Link>

                <Link v-if="$page.props.userPermissions.includes('realizar-analise')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('analyzes.index')">
                  Realizar Análise
                </Link>

                <Link v-if="
                  $page.props.userPermissions.includes('realizar-laudo') &&
                  !$page.props.userPermissions.includes('transferir-produto-admin')
                " class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('product_transfer.index')">
                  Transferir Produto
                </Link>

                <Link v-if="$page.props.userPermissions.includes('transferir-produto-admin')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('product_transfer_admin.index')">
                  Transferir Produto
                </Link>

                <Link v-if="$page.props.userPermissions.includes('embalagem')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('product_output.index')">
                  Realizar Embalagem
                </Link>

                <Link v-if="
                  $page.props.userPermissions.includes(
                    'visualizar-relatorio-individual'
                  )
                " class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('individual_report.index')">
                  Relatório Individual
                </Link>

                <Link v-if="$page.props.userPermissions.includes('visualizar-relatorio')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('report.index')">
                  Relatório Geral
                </Link>

                <Link v-if="$page.props.userPermissions.includes('realizar-analise')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('analyzes_report.index')">
                  Relatório de Análises
                </Link>
              </div>
            </template>
          </Dropdown>
        </div>
        <!-- FIM PRODUÇÃO DIÁRIA DROPDOWN -->

        <!-- EQUIPE DROPDOWN -->
        <div :class="{
          hidden: !isMenuOpen,
          block: isMenuOpen,
        }" class="ms-3 relative py-1">
          <Dropdown align="right" width="60">
            <template #trigger>
              <span class="inline-flex rounded-md whitespace-nowrap">
                <button type="button"
                  class="stringColor inline-flex items-center px-3 py-2 text-sm font-medium focus:outline-none">
                  {{ $page.props.auth.user.current_team.name }}
                  <svg class="flex-shrink-0 ms-1 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                  </svg>
                </button>
              </span>
            </template>

            <template #content>
              <div class="w-60">
                <Link v-if="$page.props.auth.user.id === 1 || $page.props.userPermissions.includes('gerir-equipe')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('teams.show', $page.props.auth.user.current_team)">
                  Gerir equipe
                </Link>

                <Link v-if="$page.props.auth.user.id === 1 || $page.props.userPermissions.includes('gerir-permissoes')"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('roles.create', $page.props.jetstream.canCreateTeams)">
                  Gerir Permissões
                </Link>

                <Link v-if="$page.props.auth.user.id === 1 && $page.props.jetstream.canCreateTeams"
                  class="themeColor justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none"
                  :href="route('teams.create')">
                  Criar nova equipe
                </Link>
                <div v-if="
                  $page.props.auth.user.all_teams.length > 1
                " class="group relative">
                  <button type="button"
                    class="themeColor w-full justify-center flex items-center gap-x-3.5 py-2 px-3 rounded text-sm font-medium focus:outline-none">
                    Mudar de equipe
                    <svg class="shrink-0 size-4 transition-transform group-hover:rotate-90"
                      xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                      stroke-width="2">
                      <path d="M6 9l6 6 6-6" />
                    </svg>
                  </button>

                  <!-- Submenu dropdown -->
                  <div class="absolute hidden group-hover:block right-16 top-0 w-60 bg-white shadow-lg rounded z-50">
                    <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                      <form @submit.prevent="switchToTeam(team)">
                        <button v-if="team.id == $page.props.auth.user.current_team_id" type="submit"
                          class="secondaryColor w-full flex items-center justify-center gap-x-3.5 py-2 px-3 rounded-lg text-sm font-medium focus:outline-none">
                          {{ team.name }}
                        </button>
                        <button v-else type="submit"
                          class="themeColor w-full flex items-center justify-center gap-x-3.5 py-2 px-3 rounded-lg text-sm font-medium focus:outline-none">
                          {{ team.name }}
                        </button>
                      </form>
                    </template>
                  </div>
                </div>
              </div>
              <!-- Submenu container -->
            </template>
          </Dropdown>
        </div>
        <!-- FIM EQUIPE DROPDOWN -->

        <!-- PERFIL DROPDOWN -->
        <div :class="{
          hidden: !isMenuOpen,
          block: isMenuOpen,
        }" class="relative mx-auto">
          <Dropdown align="right">
            <template #trigger>
              <button v-if="$page.props.jetstream.managesProfilePhotos"
                class="flex text-sm rounded-full focus:outline-none focus:border-gray-300 transition">
                <img class="h-10 w-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url"
                  :alt="$page.props.auth.user.name" />
              </button>

              <span v-else class="inline-flex rounded-md">
                <button type="button"
                  class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                  {{ $page.props.auth.user.name }}

                  <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                  </svg>
                </button>
              </span>
            </template>

            <template #content>
              <!-- Account Management -->
              <div class="py-3 px-4 bg-gray-800">
                <p class="text-sm text-gray-200">Logado como:</p>
                <p class="text-sm font-medium text-gray-200">
                  AT{{ $page.props.auth.user.id }} - {{ $page.props.auth.user.name }}
                </p>
              </div>

              <Link class="themeColor justify-center flex items-center gap-x-3.5 py-2 text-sm focus:ring-2 w-full"
                :href="route('profile.show')">
                Perfil
              </Link>

              <button type="button"
                class="themeColor justify-center flex items-center gap-x-3.5 py-2 text-sm focus:ring-2 w-full"
                @click="switchTheme">
                Alterar Tema
              </button>

              <!-- Authentication -->
              <button type="button"
                class="themeColor justify-center flex items-center gap-x-3.5 py-2 text-sm focus:ring-2 w-full"
                @click="logout">
                Log Out
              </button>
            </template>
          </Dropdown>
        </div>
        <!-- FIM PERFIL DROPDOWN -->
      </div>
    </nav>
  </header>
  <!-- ========== END HEADER ========== -->
</template>
<style scoped>
:root {
  --stroke-color: white;
}

.themeColor {
  color: black;
}

.themeColor:hover {
  color: white;
  background-color: var(--cor-principal);
}

.stringColor {
  color: var(--cor-contraste);
}

.stringColor:hover {
  color: var(--cor-principal);
}

.secondaryColor {
  color: var(--cor-secundaria);
}
</style>
