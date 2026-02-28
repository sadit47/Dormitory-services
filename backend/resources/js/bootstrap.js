import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// -------------------------------------------------------------------
// REST API client for Blade pages (Bearer token from <meta name="api-token">)
// -------------------------------------------------------------------
const apiTokenMeta = document.querySelector('meta[name="api-token"]');
const apiToken = apiTokenMeta?.getAttribute('content');

// expose a dedicated instance (recommended)
window.api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// CSRF for same-origin POST (useful if you also call web routes)
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrf) {
    window.api.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
}

// Bearer token for API
if (apiToken) {
    window.api.defaults.headers.common['Authorization'] = `Bearer ${apiToken}`;
}

// handy: auto handle 401 -> redirect to login
window.api.interceptors.response.use(
    (res) => res,
    (err) => {
        if (err?.response?.status === 401) {
            // If token expired / not found, send back to login chooser
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(err);
    }
);
