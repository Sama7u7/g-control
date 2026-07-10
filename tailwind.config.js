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
            colors: {
                ink: '#0D0D0F',
                muted: '#6B6B78',
                hint: '#A8A8B0',
                surface: '#F7F6F3',
                accent: '#4F3FF0',
                'accent-light': '#EAE8FF',
                green: '#16A34A',
                'green-light': '#DCFCE7',
                amber: '#D97706',
                'amber-light': '#FEF3C7',
                rose: '#E11D48',
                'rose-light': '#FFE4E6',
                border: 'rgba(0,0,0,0.08)',
            },
            fontFamily: {
                display: ['Syne', 'sans-serif'],
                body: ['DM Sans', 'sans-serif'],
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                'sys-tag': '4px',
                'sys-input': '8px',
                'sys-stat': '12px',
                'sys-card': '20px',
                'sys-pill': '100px',
            }
        },
    },
    plugins: [forms],
};
