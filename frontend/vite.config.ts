import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    // Only use dev tools in development
    ...(process.env.NODE_ENV === 'development' ? [vueDevTools()] : []),
    tailwindcss(),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'Timetable Manager',
        short_name: 'Timetable',
        description: 'Gestion des horaires de la station-service',
        theme_color: '#0a0a0a',
        background_color: '#ffffff',
        display: 'standalone',
        orientation: 'portrait',
        start_url: '/',
        icons: [
          {
            src: '/icons/icon-192.png',
            sizes: '192x192',
            type: 'image/png',
          },
          {
            src: '/icons/icon-512.png',
            sizes: '512x512',
            type: 'image/png',
          },
          {
            src: '/icons/icon-512-maskable.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable',
          },
        ],
      },
      workbox: {
        cleanupOutdatedCaches: true,
        clientsClaim: true,
        skipWaiting: true,
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        maximumFileSizeToCacheInBytes: 5 * 1024 * 1024, // 5MB
        runtimeCaching: [
          {
            // Shifts: StaleWhileRevalidate for instant display + background update
            // Cache is invalidated programmatically when manager makes changes
            urlPattern: ({ url }) => url.pathname.startsWith('/api/shifts'),
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'shifts-cache',
              expiration: { maxEntries: 100 }, // No time limit, invalidated on changes
              backgroundSync: {
                name: 'shifts-sync',
                options: {
                  maxRetentionTime: 24 * 60, // 24 hours
                },
              },
            },
          },
          {
            // Shift templates: CacheFirst but with short expiration (rarely change)
            urlPattern: ({ url }) => url.pathname.startsWith('/api/shift-templates'),
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'templates-cache',
              expiration: { maxEntries: 10 },
            },
          },
          {
            // Swap requests: NetworkFirst (time-sensitive)
            urlPattern: ({ url }) => url.pathname.startsWith('/api/swap-requests'),
            handler: 'NetworkFirst',
            options: {
              cacheName: 'swaps-cache',
              expiration: { maxEntries: 50, maxAgeSeconds: 300 }, // 5 minutes
              networkTimeoutSeconds: 10,
            },
          },
          {
            // Holidays: CacheFirst (changes yearly)
            urlPattern: ({ url }) => url.pathname.startsWith('/api/holidays'),
            handler: 'CacheFirst',
            options: {
              cacheName: 'holidays-cache',
              expiration: { maxEntries: 10, maxAgeSeconds: 86400 }, // 24 hours
            },
          },
          {
            // Leave requests: NetworkFirst (frequently changes)
            urlPattern: ({ url }) => url.pathname.startsWith('/api/leave-requests'),
            handler: 'NetworkFirst',
            options: {
              cacheName: 'leaves-cache',
              expiration: { maxEntries: 50, maxAgeSeconds: 300 }, // 5 minutes
              networkTimeoutSeconds: 10,
            },
          },
          {
            urlPattern: /\.(png|jpg|jpeg|svg|gif|woff2?)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'assets-cache',
              expiration: { maxEntries: 100, maxAgeSeconds: 2592000 }, // 30 days
            },
          },
        ],
      },
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  build: {
    // Optimize chunk size
    chunkSizeWarningLimit: 500,
    rollupOptions: {
      output: {
        manualChunks: (id) => {
          // Separate vendor chunks for better caching
          if (id.includes('node_modules')) {
            if (id.includes('vue') || id.includes('vue-router') || id.includes('pinia')) {
              return 'vendor-vue'
            }
            if (id.includes('reka-ui') || id.includes('tailwindcss') || id.includes('radix-vue')) {
              return 'vendor-ui'
            }
            return 'vendor'
          }
        },
      },
    },
    // Minify for production
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
  },
  // Optimize dev server
  server: {
    hmr: {
      overlay: false, // Disable error overlay in dev
    },
  },
})
