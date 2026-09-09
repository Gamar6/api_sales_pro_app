import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            colors: {
                primary: "#0F274A",
                "primary-container": "#1E3A8A",
                "on-primary": "#FFFFFF",
                "on-primary-container": "#DBEFEF",

                secondary: "#536070",

                tertiary: "#9A3412",
                "tertiary-container": "#EA580C",
                "on-tertiary-container": "#FFEDD5",

                "surface-bright": "#F8FAFC",
                "surface-container-lowest": "#FFFFFF",
                "surface-container-low": "#F1F5F9",
                "surface-container": "#E2E8F0",
                "surface-container-high": "#CBD5E1",
                "surface-container-highest": "#94A3B8",

                "on-surface": "#0F172A",
            },

            spacing: {
                "space-xs": "0.25rem",
                "space-sm": "0.5rem",
                "space-base": "0.75rem",
                "space-md": "1rem",
                "space-lg": "1.5rem",
                "space-xl": "2rem",
            },

            fontFamily: {
                sans: ["Plus Jakarta Sans", "sans-serif"],
                mono: ["Roboto Mono", "monospace"],
            },
        },
    },

    plugins: [forms],
};
