import { defineConfig, loadEnv } from 'vite';
import { sveltekit } from '@sveltejs/kit/vite';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const backendDevProxyTarget = env.BACKEND_DEV_PROXY_TARGET || 'http://127.0.0.1:8000';
  const publicBasePath = env.PUBLIC_BASE_PATH || '';
  const apiProxyPath = `${publicBasePath}/api`.replace('//', '/');

  return {
    plugins: [sveltekit()],
    server: {
      proxy: {
        [apiProxyPath]: {
          target: backendDevProxyTarget,
          changeOrigin: true,
          rewrite: (path) => (publicBasePath ? path.replace(new RegExp(`^${publicBasePath}`), '') : path)
        }
      }
    },
    preview: {
      proxy: {
        [apiProxyPath]: {
          target: backendDevProxyTarget,
          changeOrigin: true,
          rewrite: (path) => (publicBasePath ? path.replace(new RegExp(`^${publicBasePath}`), '') : path)
        }
      }
    }
  };
});
