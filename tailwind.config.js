import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#e9c46a',
                    dark: '#d4a84a',
                    light: '#fcf7d6',
                    accent: '#9C27B0',
                },
                // Playa Alta beach-club scheme: sunset → sea.
                sea: { DEFAULT: '#16404D', soft: '#3B5A66', deep: '#0F2B34' },
                teal: { DEFAULT: '#2A9D8F', dark: '#1F7A70' },
                coral: { DEFAULT: '#E76F51', dark: '#C7502F' },
                sand: { DEFAULT: '#F6EDDD', deep: '#EFE2CB', line: '#E4D4B8' },
                grape: { DEFAULT: '#9C27B0', dark: '#6A1B7A' },
            },
            fontFamily: {
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [forms, typography],
};
