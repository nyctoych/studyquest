/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Primary dark navy
        navy: {
          950: '#0a0e1a',
          900: '#0d1117',
          800: '#111827',
          700: '#1a2236',
          600: '#1e2a42',
          500: '#243352',
        },
        // Purple accent
        quest: {
          50:  '#f5f0ff',
          100: '#ede0ff',
          200: '#d4b4fe',
          300: '#b980fd',
          400: '#9b4bf8',
          500: '#7c20f0',
          600: '#6b10d8',
          700: '#570fb5',
          800: '#471095',
          900: '#3a0f78',
        },
        // Gold/XP color
        gold: {
          300: '#fde68a',
          400: '#fbbf24',
          500: '#f59e0b',
          600: '#d97706',
        },
        // Streak orange
        streak: {
          400: '#fb923c',
          500: '#f97316',
          600: '#ea580c',
        },
        // Success green
        success: {
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
        },
        // Danger red
        danger: {
          400: '#f87171',
          500: '#ef4444',
          600: '#dc2626',
        },
      },
      fontFamily: {
        outfit: ['Outfit', 'sans-serif'],
        inter:  ['Inter', 'sans-serif'],
      },
      backgroundImage: {
        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
        'gradient-quest': 'linear-gradient(135deg, #6b10d8 0%, #9b4bf8 50%, #b980fd 100%)',
        'gradient-gold':  'linear-gradient(135deg, #d97706 0%, #f59e0b 50%, #fbbf24 100%)',
        'gradient-dark':  'linear-gradient(180deg, #111827 0%, #0a0e1a 100%)',
      },
      boxShadow: {
        'quest': '0 0 20px rgba(124, 32, 240, 0.4)',
        'gold':  '0 0 20px rgba(245, 158, 11, 0.4)',
        'card':  '0 4px 24px rgba(0, 0, 0, 0.4)',
        'glow':  '0 0 40px rgba(124, 32, 240, 0.6)',
      },
      animation: {
        'float':      'float 3s ease-in-out infinite',
        'pulse-glow': 'pulseGlow 2s ease-in-out infinite',
        'slide-up':   'slideUp 0.4s ease-out',
        'slide-in':   'slideIn 0.3s ease-out',
        'bounce-in':  'bounceIn 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97)',
        'shimmer':    'shimmer 2s linear infinite',
      },
      keyframes: {
        float: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%':      { transform: 'translateY(-8px)' },
        },
        pulseGlow: {
          '0%, 100%': { boxShadow: '0 0 10px rgba(124, 32, 240, 0.3)' },
          '50%':      { boxShadow: '0 0 30px rgba(124, 32, 240, 0.7)' },
        },
        slideUp: {
          from: { opacity: '0', transform: 'translateY(20px)' },
          to:   { opacity: '1', transform: 'translateY(0)' },
        },
        slideIn: {
          from: { opacity: '0', transform: 'translateX(-20px)' },
          to:   { opacity: '1', transform: 'translateX(0)' },
        },
        bounceIn: {
          '0%':   { opacity: '0', transform: 'scale(0.3)' },
          '50%':  { opacity: '1', transform: 'scale(1.05)' },
          '70%':  { transform: 'scale(0.9)' },
          '100%': { transform: 'scale(1)' },
        },
        shimmer: {
          '0%':   { backgroundPosition: '-200% 0' },
          '100%': { backgroundPosition: '200% 0' },
        },
      },
    },
  },
  plugins: [],
}
