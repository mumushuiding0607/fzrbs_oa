<template>
  <van-popup
    :show="show"
    position="bottom"
    round
    :style="{ height: 'auto', maxHeight: '85%' }"
    @close="$emit('close')"
  >
    <div class="personal-score-modal">
      <!-- Header -->
      <div class="modal-header">
        <span class="modal-title">{{ editMode ? '编辑个人评分' : '添加个人评分' }}</span>
        <van-icon name="cross" @click="$emit('close')" class="close-icon" />
      </div>

      <!-- Evaluator Select -->
      <div class="form-section">

        <div class="user-select" @click="userSelectVisible = true">
          <van-image
            v-if="selectedUser"
            round
            width="40"
            height="40"
            :src="selectedUser.avatar || '/default-avatar.png'"
          />
          <div v-if="selectedUser" class="user-info">
            <span class="user-name">{{ selectedUser.name }}</span>
            <span class="user-hint">点击更换</span>
          </div>
          <div v-else class="user-placeholder">
            <van-icon name="plus" />
            <span>选择评价对象</span>
          </div>
          <van-icon name="arrow" class="arrow-icon" />
        </div>
      </div>

      <!-- Score Selection -->
      <div class="form-section">
  
        <ScoreRadio
          v-model="selectedScore"
          :options="scoreOptions"
        />
      </div>

      <!-- Opinion -->
      <div class="form-section">

        <van-field
          v-model="opinion"
          type="textarea"
          placeholder="对员工的批评意见"
          rows="3"
          autosize
          show-word-limit
          maxlength="150"
          :disabled="submitting"
          class="opinion-field"
        />
      </div>

      <!-- Submit Button -->
      <div class="modal-footer">
        <van-button
          v-if="editMode"
          type="danger"
          size="large"
          round
          :loading="deleting"
          @click.stop="handleDelete"
          class="delete-btn"
        >
          删除
        </van-button>
        <van-button
          type="primary"
          size="large"
          round
          :loading="submitting"
          :disabled="!canSubmit"
          @click.stop="handleSubmit"
          class="submit-btn"
        >
          {{ editMode ? '保存修改' : '提交评分' }}
        </van-button>
      </div>
    </div>

    <!-- User Select Popup -->
    <UserSelect
      :show="userSelectVisible"
      :dept-id="editMode ? undefined : deptId"
      :value="selectedUser"
      @update:show="(val) => userSelectVisible = val"
      @update:value="onUserSelected"
    />
  </van-popup>
</template>

<script lang="ts">
import { ref, computed, watch } from 'vue'
import { Popup, Icon, Image as VanImage, Field, Button, showToast } from 'vant'
import { submitPersonalScore, updatePersonalScore, deletePersonalScore } from '@/views/evaluation/api/evaluation'
import UserSelect from '@/views/invoice/components/UserSelect.vue'
import ScoreRadio from './ScoreRadio.vue'

export default {
  name: 'PersonalScoreModal',
  components: {
    vanPopup: Popup,
    vanIcon: Icon,
    vanImage: VanImage,
    vanField: Field,
    vanButton: Button,
    UserSelect,
    ScoreRadio,
  },
  props: {
    show: { type: Boolean, default: false },
    taskId: { type: [Number, String], default: 0 },
    deptId: { type: [Number, String], default: 0 },
    deptName: { type: String, default: '' },
    scoreOptions: {
      type: Array,
      default: () => [
        { value: 70, label: '基本满意' },
        { value: 60, label: '不满意' },
        { value: 50, label: '非常不满意' },
      ],
    },
    editData: { type: Object, default: null },
  },
  emits: ['close', 'success', 'delete'],
  setup(props: any, { emit }: any) {
    const selectedScore = ref(0)
    const opinion = ref('')
    const submitting = ref(false)
    const deleting = ref(false)
    const selectedUser = ref<{ name: string; userid: string; avatar: string; mobile: string } | null>(null)
    const userSelectVisible = ref(false)

    const editMode = computed(() => !!props.editData)

    const canSubmit = computed(() => selectedScore.value > 0 && selectedUser.value !== null && opinion.value.trim() !== '')

    const onUserSelected = (user: any) => {
      selectedUser.value = user
      userSelectVisible.value = false
    }

    const handleSubmit = async () => {
      if (!selectedScore.value) {
        showToast('请选择评分档次')
        return
      }
      if (!selectedUser.value) {
        showToast('请选择评价对象')
        return
      }
      if (!opinion.value.trim()) {
        showToast('请输入意见建议')
        return
      }

      submitting.value = true
      try {
        let res: any

        if (editMode.value) {
          res = await updatePersonalScore({
            score_id: props.editData.id,
            score: selectedScore.value,
            opinion: opinion.value
          })
        } else {
          res = await submitPersonalScore({
            task_id: props.taskId,
            dept_id: props.deptId,
            target_user_id: selectedUser.value.userid,
            score: selectedScore.value,
            opinion: opinion.value
          })
        }

        if (res && !res.message) {
          showToast({ message: editMode.value ? '修改成功' : '提交成功', duration: 1500 })
          emit('success', {
            deptId: props.deptId,
            targetUser: selectedUser.value,
            score: selectedScore.value,
            opinion: opinion.value,
            editData: props.editData,
          })
          emit('close')
          resetForm()
        } else {
          showToast(res?.message || (editMode.value ? '修改失败' : '提交失败'))
        }
      } catch (e: any) {
        showToast(e?.message || (editMode.value ? '修改失败' : '提交失败'))
      }
      submitting.value = false
    }

    const handleDelete = async () => {
      if (!props.editData?.id) return

      deleting.value = true
      try {
        const res: any = await deletePersonalScore({ id: props.editData.id })
        if (res && !res.message) {
          showToast({ message: '删除成功', duration: 1500 })
          emit('delete', { id: props.editData.id, deptId: props.deptId })
          emit('close')
          resetForm()
        } else {
          showToast(res?.message || '删除失败')
        }
      } catch (e: any) {
        showToast(e?.message || '删除失败')
      }
      deleting.value = false
    }

    const resetForm = () => {
      selectedScore.value = 0
      opinion.value = ''
      selectedUser.value = null
    }

    const loadEditData = () => {
      if (props.editData) {
        selectedScore.value = props.editData.score || 0
        opinion.value = props.editData.opinion || ''
        selectedUser.value = {
          name: props.editData.target_user_name || '',
          userid: props.editData.target_user_id || '',
          avatar: props.editData.target_user_avatar || '',
          mobile: props.editData.target_user_mobile || '',
        }
      }
    }

    watch(() => props.show, (val) => {
      if (val) {
        resetForm()
        if (props.editData) {
          loadEditData()
        }
      }
    })

    watch(() => props.editData, (val) => {
      if (val && props.show) {
        loadEditData()
      }
    })

    return {
      selectedScore,
      opinion,
      submitting,
      selectedUser,
      userSelectVisible,
      canSubmit,
      editMode,
      onUserSelected,
      handleSubmit,
      handleDelete,
    }
  },
}
</script>

<style>
@import "@/views/financeCss.css";
@import "@/views/financeCommon.css";
</style>
<style lang="css" scoped>
.personal-score-modal {
  background: #f7f7f8;
  padding-bottom: 16px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
}

.modal-title {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
}

.close-icon {
  font-size: 20px;
  color: #6b7280;
  cursor: pointer;
}

.form-section {
  padding: 8px;
}

.section-label {
  font-size: 16px;
  font-weight: 500;
  color: #1e293b;
  margin-bottom: 12px;
}

.user-select {
  display: flex;
  align-items: center;
  background: #fff;
  border-radius: 12px;
  padding: 12px;
  cursor: pointer;
  border: 1px solid #e5e5e5;
}

.user-info {
  flex: 1;
  margin-left: 12px;
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 16px;
  font-weight: 500;
  color: #1a1a1a;
}

.user-hint {
  font-size: 14px;
  color: #6b7280;
  margin-top: 2px;
}

.user-placeholder {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #6b7280;
  font-size: 16px;
}

.arrow-icon {
  color: #6b7280;
  font-size: 16px;
}

.opinion-field {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e5e5;
  font-size: 16px;
}

:deep(.van-field__control) {
  font-size: 16px;
}

.modal-footer {
  padding: 16px;
  display: flex;
  gap: 12px;
}

.submit-btn,
.delete-btn {
  flex: 1;
  font-size: 16px;
  height: 48px;
}
</style>
