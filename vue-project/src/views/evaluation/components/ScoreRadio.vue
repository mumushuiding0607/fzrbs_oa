<template>
  <van-radio-group v-model="localValue" @change="onChange">
    <div class="score-grid">
      <van-radio
        v-for="option in options"
        :key="option.value"
        :name="option.value"
        :class="{ selected: localValue === option.value }"
        class="score-item"
      >
        <div class="score-content">
          <div class="score-value">{{ option.value }}</div>
          <div class="score-label" :style="{ fontSize: 'calc(var(--van-cell-font-size) * 1.15)' }">{{ option.label }}</div>
        </div>
      </van-radio>
    </div>
  </van-radio-group>
</template>

<script lang="ts">
import { ref, watch } from 'vue'
import { RadioGroup, Radio } from 'vant'

export default {
  name: 'ScoreRadio',
  components: {
    vanRadioGroup: RadioGroup,
    vanRadio: Radio,
  },
  props: {
    modelValue: { type: [Number, String], default: null },
    options: {
      type: Array,
      default: () => [
        { value: 95, label: '非常满意' },
        { value: 85, label: '满意' },
        { value: 70, label: '基本满意' },
        { value: 60, label: '不满意' },
        { value: 50, label: '非常不满意' },
      ],
    },
  },
  emits: ['update:modelValue', 'change'],
  setup(props: any, { emit }: any) {
    const localValue = ref(props.modelValue)

    watch(() => props.modelValue, (v) => {
      localValue.value = v
    })

    const onChange = (val: any) => {
      emit('update:modelValue', val)
      emit('change', val)
    }

    return {
      localValue,
      onChange,
    }
  },
}
</script>

<style>
@import "@/views/financeCss.css";
@import "@/views/financeCommon.css";
</style>
<style scoped>
.score-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.score-item {
  width: 100%;
}

:deep(.van-radio) {
  width: 100%;
  display: flex;
  align-items: center;
  padding: 12px 8px;
  background: #fff;
  border-radius: 8px;
  transition: all 0.2s;
}

:deep(.van-radio--horizontal) {
  margin-right: 0;
}

:deep(.van-radio--selected) {
  background: #f0f9ff;
  border-color: #0ea5e9;
}

:deep(.van-radio__label) {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 8px;
  font-size: 16px;
  color: #1a1a1a;
  min-width: 0;
}

:deep(.van-radio--selected) .van-radio__label {
  color: #1a365d;
  font-weight: 500;
}

.score-content {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.score-value {
  /* font-size: calc(var(--van-cell-font-size) * var(--Big)); */
  font-weight: 600;
  color: #64748b;
  display: flex;
  align-items: center;
}

.score-label {
  /* font-size: calc(var(--van-cell-font-size) * 1.15) !important; */
  white-space: nowrap;
  flex-shrink: 0;
  color: #64748b;
  display: flex;
  align-items: center;
}

:deep(.van-radio--selected) .score-value,
:deep(.van-radio--selected) .score-label {
  color: #0ea5e9;
}
</style>
