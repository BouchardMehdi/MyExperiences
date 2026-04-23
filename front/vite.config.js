import { defineConfig, loadEnv } from 'vite';
import { sveltekit } from '@sveltejs/kit/vite';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const backendDevProxyTarget = env.BACKEND_DEV_PROXY_TARGET || 'http://127.0.0.1:8000';

  return {
    plugins: [sveltekit()],
    server: {
      proxy: {
        '/MyExperiences/api': {
          target: backendDevProxyTarget,
          changeOrigin: true,
          rewrite: (path) => path.replace(/^\/MyExperiences/, '')
        }
      }
    },
    preview: {
      proxy: {
        '/MyExperiences/api': {
          target: backendDevProxyTarget,
          changeOrigin: true,
          rewrite: (path) => path.replace(/^\/MyExperiences/, '')
        }
      }
    }
  };
});
