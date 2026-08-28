<template>
  <van-popup
    :show="show"
    position="bottom"
    round
    :style="{ height: '85%' }"
    @close="$emit('close')"
  >
    <div class="score-modal">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-btn" @click="$emit('close')">取消</div>
        <div class="header-title">服务评价</div>
        <div class="header-btn header-submit" :class="{ disabled: !selectedScore }" @click="handleSubmit">
          提交
        </div>
      </div>

      <!-- Department Info -->
      <div class="modal-dept">
        <div class="dept-icon">
          <van-icon name="shop-o" />
        </div>
        <div class="dept-name">{{ deptName }}</div>
        <div class="dept-period">服务对象评价</div>
      </div>

      <!-- Score Module -->
      <div class="modal-score">
        <ScoreModule
          :dept-id="deptInfo.dept_id"
          :dept-name="deptName"
          :task-id="taskId"
          :initial-score="deptInfo.score"
          :initial-opinion="deptInfo.opinion"
          :submitted="!!deptInfo.status"
          @submit="handleModuleSubmit"
        />
      </div>

      <!-- Opinion -->
      <div class="modal-opinion">
        <div class="opinion-label">意见建议（选填）</div>
        <van-field
          v-model="opinion"
          type="textarea"
          placeholder="请输入对该部门的意见建议..."
          rows="4"
          autosize
          show-word-limit
          maxlength="500"
        />
      </div>
    </div>
  </van-popup>
</template>

<script lang="ts">
import { Popup, Field, Icon, showToast } from 'vant'
import ScoreSelector from './ScoreSelector.vue'
import { submitScore, getConfig } from '@/views/evaluation/api/evaluation'

export default {
  name: 'ScoreModal',
  components: {
    vanPopup: Popup,
    vanField: Field,
    vanIcon: Icon,
    ScoreSelector,
  },
  props: {
    show: {
      type: Boolean,
      default: false,
    },
    taskId: {
      type: Number,
      default: 0,
    },
    deptInfo: {
      type: Object as () => {
        dept_id: number
        dept_name: string
      },
      default: () => ({}),
    },
  },
  emits: ['close', 'success'],
  data() {
    return {
      selectedScore: 0,
      opinion: '',
      scoreOptions: [
        { label: '非常满意', value: 95 },
        { label: '满意', value: 85 },
        { label: '基本满意', value: 75 },
        { label: '不满意', value: 65 },
        { label: '非常不满意', value: 55 },
      ],
    }
  },
  computed: {
    deptName() {
      return this.deptInfo.dept_name || ''
    },
  },
  watch: {
    show(val) {
      if (val) {
        this.selectedScore = 0
        this.opinion = ''
        this.loadConfig()
      }
    },
  },
  methods: {
    async loadConfig() {
      try {
        const res: any = await getConfig({ type: '评分档次' })
        if (res && res.data && res.data['评分档次']) {
          this.scoreOptions = res.data['评分档次'].map((item: any) => ({
            label: item.label,
            value: Number(item.value),
          }))
        }
      } catch (e) {
        console.error('加载评分配置失败', e)
      }
    },
    async handleSubmit() {
      if (!this.selectedScore) {
        showToast('请选择评分档次')
        return
      }

      try {
        const res: any = await submitScore({
          task_id: this.taskId,
          dept_id: this.deptInfo.dept_id,
          score: this.selectedScore,
          opinion: this.opinion,
        })

        if (res && !res.message) {
          showToast({ message: '提交成功', duration: 1500 })
          this.$emit('success', this.deptInfo.dept_id)
          this.$emit('close')
        } else {
          showToast(res.message || '提交失败')
        }
      } catch (e: any) {
        showToast(e.message || '提交失败')
      }
    },
  },
}
</script>

<style lang="css" scoped>
.score-modal {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #f5f5f5;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 50px;
  padding: 0 16px;
  background: #fff;
  border-bottom: 1px solid #eee;
}

.header-btn {
  font-size: 15px;
  color: #999;
}

.header-submit {
  color: #ff9800;
  font-weight: 500;
}

.header-submit.disabled {
  color: #ccc;
}

.header-title {
  font-size: 17px;
  font-weight: 500;
  color: #333;
}

.modal-dept {
  background: #fff;
  padding: 24px 16px;
  text-align: center;
  margin-bottom: 12px;
}

.dept-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #fff7e6;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
  font-size: 28px;
  color: #ff9800;
}

.dept-name {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  margin-bottom: 6px;
}

.dept-period {
  font-size: 14px;
  color: #999;
}

.modal-score {
  padding: 0 16px;
  margin-bottom: 12px;
}

.modal-opinion {
  flex: 1;
  padding: 0 16px;
}

.modal-opinion .opinion-label {
  font-size: 15px;
  font-weight: 500;
  color: #333;
  margin-bottom: 10px;
}

.modal-opinion :deep(.van-field) {
  padding: 12px;
  background: #fff;
  border-radius: 8px;
}

.modal-opinion :deep(.van-field__control) {
  font-size: 15px;
}
</style>
