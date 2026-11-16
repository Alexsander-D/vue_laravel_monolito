import axios from 'axios';
window.axios = axios;

// Configura baseURL automaticamente conforme o ambiente
if (import.meta.env.MODE === 'development') {
    // Ambiente local
    window.axios.defaults.baseURL = window.location.origin;
} else {
    // Produção
    window.axios.defaults.baseURL = window.location.origin;
}

// Mantém as demais configs
window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
