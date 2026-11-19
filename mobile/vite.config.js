import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  base: '/app/',
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      scope: '/app/',
      base: '/app/',
      includeAssets: ['icons/*.svg', 'icons/*.png'],
      manifest: {
        name: 'Party CRM',
        short_name: 'Party CRM',
        description: 'Party member management app',
        theme_color: '#1e293b',
        background_color: '#f8fafc',
        display: 'standalone',
        orientation: 'portrait',
        scope: '/app/',
        start_url: '/app/',
        icons: [
          {
            src: '/app/icons/icon.svg',
            sizes: 'any',
            type: 'image/svg+xml',
            purpose: 'any maskable',
          },
        ],
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,svg,png,ico,woff2}'],
        navigateFallback: '/app/index.html',
        navigateFallbackDenylist: [/^\/api/, /^\/(?!app)/],
        runtimeCaching: [
          {
            urlPattern: /^\/api\/v1\//,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'api-cache',
              networkTimeoutSeconds: 10,
              expiration: { maxEntries: 100, maxAgeSeconds: 86400 },
            },
          },
        ],
      },
    }),
  ],
  build: {
    outDir: '../public/app',
    emptyOutDir: true,
  },
  resolve: {
    alias: { '@': '/src' },
  },
})
