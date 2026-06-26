/**
 * 在生产环境 代理是无法生效的，所以这里没有生产环境的配置
 * -------------------------------
 * The agent cannot take effect in the production environment
 * so there is no configuration of the production environment
 * For details, please see
 * https://pro.ant.design/docs/deploy
 */
export default {

  dev: {
    // localhost:8000/api/** -> https://preview.pro.ant.design/api/**
    '/api/': {
      // 要代理的地址
      // target: 'http://129.0.98.30:8023',
      target: 'http://127.0.0.1:8888',
      // 配置了这个可以从 http 代理到 https
      // 依赖 origin 的功能可能需要这个，比如 cookie
      changeOrigin: true,
    },
    '/uploaded/': {
      // 要代理的地址
      target: 'http://129.0.99.30:8030',
      // target: 'http://127.0.0.1:8888',
      // 配置了这个可以从 http 代理到 https
      // 依赖 origin 的功能可能需要这个，比如 cookie
      changeOrigin: true,
      pathRewrite: (path:any) => path.replace(/^\/uploaded/, '/uploaded/'),

    },
    '/assets/': {
      // 要代理的地址
      // target: 'http://129.0.98.30:8023',
      target: 'https://fzrb.fznews.com.cn',
      // 配置了这个可以从 http 代理到 https
      // 依赖 origin 的功能可能需要这个，比如 cookie
      changeOrigin: true,
    },
  },
  test: {
    '/api/': {
      target: 'http://129.0.98.30:8023',
      changeOrigin: true,
      pathRewrite: { '^': '' },
    },
    '/uploaded': {
      // 要代理的地址
      target: 'http://129.0.99.30:8030',
      // target: 'http://127.0.0.1:8888',
      // 配置了这个可以从 http 代理到 https
      // 依赖 origin 的功能可能需要这个，比如 cookie
      changeOrigin: true,
      rewrite: (path:any) => path.replace(/^\/uploaded/, '/uploaded')
    },
  },
  pre: {
    '/api/': {
      target: 'your pre url',
      changeOrigin: true,
      pathRewrite: { '^': '' },
    },
    '/uploaded': {
      // 要代理的地址
      target: 'http://129.0.99.30:8030',
      // target: 'http://127.0.0.1:8888',
      // 配置了这个可以从 http 代理到 https
      // 依赖 origin 的功能可能需要这个，比如 cookie
      changeOrigin: true,
      rewrite: (path:any) => path.replace(/^\/uploaded/, '/uploaded')
    },
  },
};
