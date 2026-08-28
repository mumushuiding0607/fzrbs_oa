<template>
  <div v-if="data" class="listitem">
    <div class="item-left">
      <van-tag :type="statusTagType" size="medium">{{ statusText }}</van-tag>
      <span class="item-title">{{ data.year }}年第{{ data.quarter }}季度</span>
    </div>
    <div class="item-date">{{ data.start_date ? data.start_date.substring(0, 10) : '' }}</div>
  </div>
</template>

<script lang="ts">
import { Tag } from 'vant'

export default {
  components: {
    vanTag: Tag,
  },
  props: ['data'],
  computed: {
    statusText() {
      if (!this.data) return ''
      if (this.data.status === 0) return '待评分'
      if (this.data.status === 1) return '已评分'
      if (this.data.status === 2) return '超时默认'
      return ''
    },
    statusTagType() {
      if (!this.data) return 'default'
      if (this.data.status === 0) return 'warning'
      if (this.data.status === 1) return 'success'
      if (this.data.status === 2) return 'danger'
      return 'default'
    },
  },
}
</script>

<style>
@import "@/views/financeCss.css";
@import "@/views/financeCommon.css";
</style>
<style lang="css" scoped>
.listitem {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  padding: 12px;
  margin: 12px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.item-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.item-title {
  font-size: 16px;
  color: #334155;
}

.item-date {
  font-size: 16px;
  color: #94a3b8;
}

:deep(.van-tag) {
  font-size: calc(var(--van-cell-font-size)) !important;
}
</style>
