import axios from 'axios';
window.axios = axios;

// Configura baseURL automaticamente conforme o ambiente
if (import.meta.env.MODE === 'development') {
    // Ambiente local
    window.axios.defaults.baseURL = 'http://127.0.0.1:8000';
} else {
    // Produção
    window.axios.defaults.baseURL = 'https://spm.multilaser.com.br';
}

// Mantém as demais configs
window.axios.defaults.withCredentials = false;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
