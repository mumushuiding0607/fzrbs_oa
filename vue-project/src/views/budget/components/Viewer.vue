<template>
  <div ref="container">
    <slot></slot>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, onBeforeUnmount } from 'vue';
import Viewer from 'viewerjs';
import 'viewerjs/dist/viewer.css';
import type { ViewerOptions } from 'viewerjs';

export default defineComponent({
  name: 'Viewer',
  props: {
    options: {
      type: Object as () => ViewerOptions,
      default: () => ({})
    }
  },
  setup(props) {
    const container = ref<HTMLElement | null>(null);
    let viewerInstance: Viewer | null = null;

    // 初始化 Viewer.js
    onMounted(() => {
      if (container.value) {
        viewerInstance = new Viewer(container.value, props.options);
      }
    });

    // 销毁实例避免内存泄漏
    onBeforeUnmount(() => {
      viewerInstance?.destroy();
    });

    return { container };
  }
});
</script>
<style>

.viewer-toolbar > ul > li{
  margin: 0 5px!important
}

</style>

<style>
  @media screen and (min-width: 500px) {
    .viewer-toolbar > ul > li{
  margin: 0 5px!important
}

      
  }
</style>

