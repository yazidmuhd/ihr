// resources/js/bootstrap.js
import axios from "axios";

// Always send cookies on same-origin requests
axios.defaults.withCredentials = true;

// Tell Laravel this is an AJAX/XHR request
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Pull CSRF token from the Blade <meta> tag and attach to every request
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
} else {
    console.warn("CSRF token meta tag not found.");
}

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error?.response?.status === 419) {
            // Refresh to get a new token/session and retry manually if needed
            window.location.reload();
        }
        return Promise.reject(error);
    },
);

window.axios = axios;
