import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  base: '/',
  publicDir: 'landing-react/public',
  plugins: [react()],
  build: {
    outDir: 'landing-dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        index: 'landing-build-index.html',
      },
    },
  },
});
