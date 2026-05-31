import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  // base: '/gamebench/',
  base: '/cos30043/s105571938/gamebench/',
  optimizeDeps: {
    noDiscovery: true,
    include: [],
  },
})
