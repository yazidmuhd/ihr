// resources/js/app.js
import { createInertiaApp } from "@inertiajs/vue3";
import { createApp, h } from "vue";

// If any template still calls route('...') from old Ziggy code,
// keep this harmless stub so it doesn't crash while you refactor.
if (typeof window.route !== "function") window.route = () => "#";

// Build an async map of all page components under ./Pages/**
// (This is the most reliable with Vite + Inertia)
const pages = import.meta.glob("./Pages/**/*.vue");

createInertiaApp({
    // name is something like 'Auth/Login' or 'HR/Vacancies/Index'
    resolve: (name) => {
        const path = `./Pages/${name}.vue`;
        const loader = pages[path];

        if (!loader) {
            // Helpful dev error so you see exactly what Inertia tried to load.
            console.error(`[Inertia] Page component not found: ${path}`);
            // Optional: fall back to a tiny error component so the app still renders
            return {
                default: {
                    name: "MissingPage",
                    template: `<div style="padding:16px;color:#b91c1c">
            <h2 style="font-weight:600">Missing page</h2>
            <p>Could not load: <code>${path}</code></p>
          </div>`,
                },
            };
        }

        // Return the dynamic import (Inertia handles the promise)
        return loader();
    },

    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);

        // Also expose the stub as a Vue global just in case some components do this.$root.route(...)
        app.config.globalProperties.route = (...a) => window.route(...a);

        app.mount(el);
    },

    progress: { color: "#10b981" },
});
