<template>
  <van-popup
    :show="show"
    round
    closeable
    :style="{ maxHeight: '70%', width: '90%' }"
    @close="$emit('close')"
  >
    <div class="guide-modal">
      <div class="modal-header">填写说明</div>
      <div class="modal-content" v-html="content"></div>
      <div class="modal-footer">
        <van-button type="primary" block round @click="$emit('close')">我已知晓</van-button>
      </div>
    </div>
  </van-popup>
</template>

<script lang="ts">
import { Popup, Button } from 'vant'

export default {
  name: 'EvaluationGuideModal',
  components: {
    vanPopup: Popup,
    vanButton: Button,
  },
  props: {
    show: { type: Boolean, default: false },
    content: { type: String, default: '' },
  },
  emits: ['close'],
}
</script>

<style scoped>
.guide-modal {
  padding: 16px;
}

.modal-header {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  text-align: center;
  margin-bottom: 16px;
}

.modal-content {
  font-size: 16px;
  color: #333;
  line-height: 1.6;
  max-height: 50vh;
  overflow-y: auto;
  margin-bottom: 16px;
}

.modal-content :deep(strong),
.modal-content strong {
  font-weight: 600 !important;
}

.modal-footer {
  padding-top: 8px;
}
</style>
