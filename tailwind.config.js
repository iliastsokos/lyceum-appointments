import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Copied exactly from the school's own site (lyk-rafin-new.att.sch.gr)
                // so the app's brand matches the official one pixel-for-pixel.
                primary: {
                    DEFAULT: '#1a8399',
                    tint: '#EDF5F7',
                    hover: '#177387',
                    active: '#15697A',
                },
                secondary: {
                    DEFAULT: '#ff9635',
                    hover: '#E0842F',
                    active: '#CC782A',
                },
                ink: '#1F2937',
                body: '#606876',
                surface: '#F8FAFC',
                outline: '#E6E9EF',
                muted: '#6E7787',
            },
        },
    },

    plugins: [forms],
};
