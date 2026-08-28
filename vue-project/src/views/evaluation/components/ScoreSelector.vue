<template>
  <div class="score-selector">
    <div class="score-question">请选择评分档次</div>
    <div class="score-buttons">
      <div
        v-for="item in scoreOptions"
        :key="item.value"
        class="score-btn"
        :class="{ active: modelValue === item.value }"
        @click="$emit('update:modelValue', item.value)"
      >
        <div class="score-label">{{ item.label }}</div>
        <div class="score-value">{{ item.value }}分</div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
export default {
  name: 'ScoreSelector',
  props: {
    modelValue: {
      type: Number,
      default: 0,
    },
    scoreOptions: {
      type: Array as () => Array<{ label: string; value: number }>,
      default: () => [
        { label: '非常满意', value: 95 },
        { label: '满意', value: 85 },
        { label: '基本满意', value: 75 },
        { label: '不满意', value: 65 },
        { label: '非常不满意', value: 55 },
      ],
    },
  },
  emits: ['update:modelValue'],
}
</script>

<style lang="css" scoped>
.score-selector {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.score-question {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  text-align: center;
  margin-bottom: 20px;
}

.score-buttons {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.score-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 80px;
  border-radius: 12px;
  background: #f5f5f5;
  border: 2px solid transparent;
  transition: all 0.2s;
}

.score-btn:active {
  transform: scale(0.95);
}

.score-btn.active {
  background: #fff7e6;
  border-color: #ff9800;
}

.score-label {
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 6px;
}

.score-btn.active .score-label {
  color: #ff6b00;
}

.score-value {
  font-size: 12px;
  color: #999;
}

.score-btn.active .score-value {
  color: #ff6b00;
}
</style>
