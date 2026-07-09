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
            fontSize: {
                'k16-body': ['1.125rem', { lineHeight: '1.55' }],
                'k16-lead': ['1.25rem', { lineHeight: '1.45' }],
                'k16-title': ['1.5rem', { lineHeight: '1.3', fontWeight: '700' }],
                'k16-display': ['2rem', { lineHeight: '1.2', fontWeight: '700' }],
            },
            colors: {
                k16: {
                    bg: '#F7F8FA',
                    surface: '#FFFFFF',
                    text: '#1A1A2E',
                    'text-muted': '#64748B',
                    accent: '#1E40AF',
                    'accent-hover': '#1D4ED8',
                    success: '#15803D',
                    'success-soft': '#DCFCE7',
                    danger: '#B91C1C',
                    'danger-soft': '#FEE2E2',
                    warning: '#B45309',
                    'warning-soft': '#FEF3C7',
                    border: '#E2E8F0',
                },
            },
            minHeight: {
                touch: '3.25rem',
                'touch-lg': '3.5rem',
            },
        },
    },

    plugins: [forms],
};
