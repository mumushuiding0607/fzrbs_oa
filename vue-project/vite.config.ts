import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import pxtoViewPort from "postcss-px-to-viewport"

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue(), vueJsx()],
  build: {
    target: 'es2015',
    chunkSizeWarningLimit: 1800,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('crypto-js')) {
              return 'crypto-js';
            } else if (id.includes('jssdk')) {
              return 'jssdk';
            } else if (id.includes('dayjs')) {
              return 'dayjs';
            } else if (id.includes('html2canvas')) {
              return 'html2canvas';
            } else if (id.includes('video.js')) {
              return 'video.js';
            } else if (id.includes('vant')) {
              return 'vant';
            } else if (id.includes('vue3-pdf-app')) {
              return 'vue3-pdf-app';
            } else if (id.includes('weixin-sdk-js')) {
              return 'weixin-sdk-js';
            } else if (id.includes('xgplayer') || id.includes('xgplayer-hls')) {
              return 'xgplayer';
            } else {
              return 'vendor';
            }
          }
        }
      }
    }
  },
  css: {
    postcss: {
      plugins: [pxtoViewPort({ unitToConvert: 'px', viewportWidth: 375, mediaQuery: false, exclude: [/node_modules/] })]
    }
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  server: {
    open: true, //自动打开
    base: "./ ", //生产环境路径
    proxy: { // 本地开发环境通过代理实现跨域，生产环境使用 nginx 转发
      // 正则表达式写法
      '^/weixin': {
        // target: 'http://129.0.99.30:8030/weixin', // 后端服务实际地址
        target: 'http://127.0.0.1:8888/weixin',
        changeOrigin: true, //开启代理
        rewrite: (path) => path.replace(/^\/weixin/, '')
      },
      '^/index':{
        target: 'http://fzrb.fznews.com.cn/index.php',
        changeOrigin: true, //开启代理
        rewrite: (path) => path.replace(/^\/index/, '')
      }
    }
  },
  base: '/v2'
})
