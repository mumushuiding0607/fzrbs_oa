<template>
  <div class="pdf-preview-container">
    <canvas ref="pdfCanvas"></canvas>

  </div>
</template>

<script>
import * as pdfjsLib from 'pdfjs-dist';
import 'pdfjs-dist/build/pdf.worker.mjs';


export default {
  name: 'PdfPreview',
  data() {
    return {
      pdfUrl: '/uploaded/contract/20250522/17479017028953.pdf?name=371000_25352000000056514562_罗源县旅游事业发展中心_20250522161329.pdf&time=1747901701223&size=56679', // 替换为你的 PDF 路径或 Base64
    };
  },
  mounted() {
    console.log('----------------read pdf -----------------------')
    this.loadPdfAsImage();
  },
  methods: {
    async loadPdfAsImage() {
      // 设置 worker 路径（Vite 环境）
      pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(
        'pdfjs-dist/build/pdf.worker.min.mjs',
        import.meta.url
      ).toString();

      try {
        const loadingTask = pdfjsLib.getDocument({
  url: this.pdfUrl,
  cMapUrl: 'https://unpkg.com/pdfjs-dist@3.4.120/cmaps/',
  cMapPacked: true,
});
        const pdf = await loadingTask.promise;
        console.log('pdf:',pdf)
        // 获取第一页
        const page = await pdf.getPage(1);

        // 设置视口缩放（调整 scale 提高清晰度）
        const viewport = page.getViewport({ scale: 2.0 });

        // 获取 canvas 上下文
        const canvas = this.$refs.pdfCanvas;
        const context = canvas.getContext('2d');
        canvas.height = viewport.viewBox[3];
        canvas.width = viewport.viewBox[2];

        // 渲染为图片
        const renderContext = {
          canvasContext: context,
          viewport,
        };

        await page.render(renderContext).promise;
      } catch (error) {
        console.error('加载 PDF 失败:', error);
      }
    },
  },
};
</script>

<style scoped>
.pdf-preview-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

canvas {
  border: 1px solid #ccc;
  max-width: 100%;
}
</style>

