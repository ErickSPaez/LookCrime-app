import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        '../server/vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        '../server/storage/framework/views/*.php',
        '../server/resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
