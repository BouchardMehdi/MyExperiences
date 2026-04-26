import adapter from '@sveltejs/adapter-static';

const publicBasePath = process.env.PUBLIC_BASE_PATH || '';

const config = {
  kit: {
    adapter: adapter({
      pages: 'build',
      assets: 'build',
      fallback: 'index.html'
    }),
    paths: {
      base: publicBasePath
    },
    prerender: {
      handleHttpError: 'warn'
    }
  }
};

export default config;
