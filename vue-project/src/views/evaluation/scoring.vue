<template>
  <div class="evaluation-page">
    <!-- Header -->
    <van-nav-bar
      :title="taskId ? navTitle : '服务对象评价列表'"
      left-arrow
      @click-left="$router.back()"
    />

    <!-- Content -->
    <van-list
      v-if="!taskId"
      :loading="loading"
      :finished="finished"
      finished-text="没有更多了"
      @load="onLoadMore"
    >
      <div v-for="task in taskList" :key="task.task_id">
        <!-- Task Header -->
      

        <!-- Department Score Cards -->
        <div class="dept-list">
          <div v-for="dept in task.departments" :key="dept.dept_id">
            <ScoreModule
              :dept-id="dept.dept_id"
              :dept-name="dept.dept_name"
              :task-id="task.task_id"
              :score-options="scoreOptions"
              :initial-score="dept.score"
              :initial-opinion="dept.opinion"
              :submitted="!!dept.status"
              :personal-scores="getPersonalScores(dept.dept_id)"
              @update="handleScoreChange(dept, $event)"
              @personal-score="handlePersonalScore"
              @personal-score-click="handlePersonalScoreClick(dept, $event)"
            />
          </div>
        </div>
      </div>
    </van-list>

    <!-- Single Task View (no pagination needed) -->
    <div v-if="taskId && taskList.length > 0">
      <div v-for="task in taskList" :key="task.task_id">
 

        <div class="dept-list">
          <div v-for="dept in task.departments" :key="dept.dept_id">
            <ScoreModule
              :dept-id="dept.dept_id"
              :dept-name="dept.dept_name"
              :task-id="task.task_id"
              :score-options="scoreOptions"
              :initial-score="dept.score"
              :initial-opinion="dept.opinion"
              :submitted="!!dept.status"
              :personal-scores="getPersonalScores(dept.dept_id)"
              @update="handleScoreChange(dept, $event)"
              @personal-score="handlePersonalScore"
              @personal-score-click="handlePersonalScoreClick(dept, $event)"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <van-empty v-if="!loading && taskList.length === 0" description="暂无可评价的任务" />

    <!-- Submit All Button (Fixed Bottom) -->
    <div class="submit-all-bar">
      <van-button
        type="primary"
        block
        round
        :loading="submitting"
        :disabled="!canSubmitAll"
        class="submit-btn-text"
        @click="handleSubmitAll"
      >
        提交全部评分
      </van-button>
    </div>

    <!-- Personal Score Modal -->
    <PersonalScoreModal
      :show="personalScoreModal.show"
      :task-id="personalScoreModal.taskId"
      :dept-id="personalScoreModal.deptId"
      :dept-name="personalScoreModal.deptName"
      :edit-data="personalScoreModal.editData"
      @close="personalScoreModal.show = false"
      @success="handlePersonalScoreSuccess"
      @delete="handlePersonalScoreDelete"
    />

    <!-- Evaluation Guide Modal -->
    <EvaluationGuideModal
      :show="guideModal.show"
      :content="guideContent"
      @close="guideModal.show = false"
    />
  </div>
</template>

<script lang="ts">
import { NavBar, Empty, Button, Cell, CellGroup, Tag, List, showToast, showLoadingToast, closeToast, showConfirmDialog, Dialog, showDialog } from 'vant'
import { getTasks, getTaskDetail, getConfig, batchSubmitScore, submitPersonalScore, deletePersonalScore } from '@/views/evaluation/api/evaluation'
import ScoreModule from './components/ScoreModule.vue'
import PersonalScoreModal from './components/PersonalScoreModal.vue'
import EvaluationGuideModal from './components/EvaluationGuideModal.vue'

export default {
  name: 'EvaluationScoring',
  components: {
    vanNavBar: NavBar,
    vanEmpty: Empty,
    vanButton: Button,
    vanCell: Cell,
    vanCellGroup: CellGroup,
    vanTag: Tag,
    vanList: List,
    ScoreModule,
    PersonalScoreModal,
    EvaluationGuideModal,
  },
  data() {
    return {
      currentYear: new Date().getFullYear(),
      currentQuarter: Math.ceil((new Date().getMonth() + 1) / 3),
      taskList: [] as any[],
      scoreOptions: [
        { value: 95, label: '非常满意' },
        { value: 85, label: '满意' },
        { value: 70, label: '基本满意' },
        { value: 60, label: '不满意' },
        { value: 50, label: '非常不满意' },
      ] as any[],
      // Local state for all scores: Map<deptId, {taskId, deptId, score, opinion}>
      scoreDrafts: {} as Record<string, any>,
      // Personal scores: Map<deptId, [{id, target_user_id, target_user_name, target_user_avatar, score, opinion}]>
      personalScores: {} as Record<string, any[]>,
      loading: false,
      finished: false,
      page: 0,
      pageSize: 20,
      submitting: false,
      // Personal score modal state
      personalScoreModal: {
        show: false,
        taskId: 0,
        deptId: 0,
        deptName: '',
        editData: null as any,
      },
      // Evaluation guide modal state
      guideModal: {
        show: false,
      },
      guideContent: '',
      guideShownKey: '', // 用于记录已显示过的 task_id
    }
  },
  computed: {
    taskId() {
      return this.$route.query.task_id as string | undefined
    },
    hasUnsubmittedScores() {
      return Object.keys(this.scoreDrafts).length > 0
    },
    pendingCount() {
      return Object.keys(this.scoreDrafts).length
    },
    canSubmitAll() {
      // 有任务且有部门需要评分
      return this.taskList.length > 0 && (this.taskList[0]?.departments?.length ?? 0) > 0
    },
    navTitle() {
      if (this.taskList.length > 0) {
        const task = this.taskList[0]
        return `${task.year}年第${task.quarter}季度`
      }
      return '服务对象评价'
    }
  },
  created() {
    this.loadData()
  },
  beforeRouteUpdate() {
    this.loadData()
  },
  methods: {
    getPersonalScores(deptId: number | string) {
      return this.personalScores[String(deptId)] || []
    },
    getScoreTagType(score: number) {
      if (score >= 85) return 'success'
      if (score >= 70) return 'warning'
      return 'danger'
    },
    async loadData() {
      if (this.loading) return
      this.loading = true
      this.finished = false

      try {
        // If task_id is provided via query, load task detail directly
        if (this.taskId) {
          const [detailRes, configRes, guideRes] = await Promise.all([
            getTaskDetail({ task_id: Number(this.taskId) }),
            getConfig({ type: '评分档次' }),
            getConfig({ type: '社直部门考评说明' })
          ])

          if (detailRes && detailRes.data) {
            this.taskList = [detailRes.data]
            // Initialize personalScores from API data
            this.personalScores = {}
            if (detailRes.data.departments) {
              detailRes.data.departments.forEach((dept: any) => {
                if (dept.personal_scores && dept.personal_scores.length > 0) {
                  this.personalScores[dept.dept_id] = dept.personal_scores
                }
              })
            }
            this.finished = true

            // 检查是否未评分，显示考评说明弹窗
            const hasUnscored = detailRes.data.departments?.some((d: any) => !d.status)
            if (hasUnscored && this.guideShownKey !== String(this.taskId)) {
              if (guideRes && guideRes.data && guideRes.data['社直部门考评说明']) {
                const guideData = guideRes.data['社直部门考评说明']
                if (guideData.length > 0) {
                  this.guideContent = guideData[0].label || ''
                  if (this.guideContent) {
                    this.guideModal.show = true
                    this.guideShownKey = String(this.taskId)
                  }
                }
              }
            }
          } else {
            showToast('任务不存在')
            this.finished = true
          }

          if (configRes && configRes.data && configRes.data['评分档次']) {
            const unique = new Map()
            configRes.data['评分档次'].forEach((item: any) => {
              if (!unique.has(item.value)) {
                unique.set(item.value, { label: item.label, value: Number(item.value) })
              }
            })
            this.scoreOptions = Array.from(unique.values())
          }
        } else {
          // Normal paginated load
          const [tasksRes, configRes] = await Promise.all([
            getTasks({ year: this.currentYear, quarter: this.currentQuarter, pageSize: this.pageSize, current: this.page }),
            getConfig({ type: '评分档次' })
          ])

          if (tasksRes && tasksRes.data) {
            if (this.page === 1) {
              this.taskList = tasksRes.data
            } else {
              this.taskList.push(...tasksRes.data)
            }
            if (tasksRes.data.length < this.pageSize) {
              this.finished = true
            }
          } else {
            this.finished = true
          }

          if (configRes && configRes.data && configRes.data['评分档次']) {
            const unique = new Map()
            configRes.data['评分档次'].forEach((item: any) => {
              if (!unique.has(item.value)) {
                unique.set(item.value, { label: item.label, value: Number(item.value) })
              }
            })
            this.scoreOptions = Array.from(unique.values())
          }
        }
      } catch (e) {
        this.finished = true
        showToast('加载失败')
      }
      this.loading = false
    },

    onLoadMore() {
      this.page++
      this.loadData()
    },

    handleScoreChange(dept: any, value: any) {
      if (!value) return
      const key = `${dept.dept_id}`
      if (value.score) {
        this.scoreDrafts[key] = {
          taskId: value.taskId || dept.task_id,
          deptId: value.deptId || dept.dept_id,
          score: value.score,
          opinion: value.opinion || ''
        }
      } else {
        // Score was cleared, remove from drafts
        delete this.scoreDrafts[key]
      }
    },

    handlePersonalScore(data: any) {
      this.personalScoreModal = {
        show: true,
        taskId: data.taskId,
        deptId: data.deptId,
        deptName: data.deptName,
        editData: null,
      }
    },

    handlePersonalScoreClick(dept: any, data: any) {
      this.personalScoreModal = {
        show: true,
        taskId: data.taskId,
        deptId: data.deptId,
        deptName: data.deptName,
        editData: data.personalScore,
      }
    },

    handlePersonalScoreEdit(dept: any, ps: any) {
      this.personalScoreModal = {
        show: true,
        taskId: dept.task_id,
        deptId: dept.dept_id,
        deptName: dept.dept_name,
        editData: ps,
      }
    },

    handlePersonalScoreSuccess(data: any) {
      const deptId = String(data.deptId)
      if (!this.personalScores[deptId]) {
        this.personalScores[deptId] = []
      }

      // If editing, update the existing record
      if (data.editData) {
        const index = this.personalScores[deptId].findIndex((ps: any) => ps.id === data.editData.id)
        if (index !== -1) {
          this.personalScores[deptId][index] = {
            ...this.personalScores[deptId][index],
            score: data.score,
            opinion: data.opinion,
          }
        }
      } else {
        // Add new record - find the max id and add with a temp id
        const newPs = {
          id: Date.now(), // temporary id until refresh
          target_user_id: data.targetUser.userid,
          target_user_name: data.targetUser.name,
          target_user_avatar: data.targetUser.avatar,
          score: data.score,
          opinion: data.opinion,
        }
        this.personalScores[deptId].push(newPs)
      }

      this.personalScoreModal.show = false
    },

    handlePersonalScoreDelete(data: any) {
      const deptId = String(data.deptId)
      showLoadingToast({ message: '删除中...', forbidClick: true })
      deletePersonalScore({ id: data.id })
        .then((res: any) => {
          closeToast()
          if (res && !res.message) {
            showToast({ message: '删除成功', duration: 1500 })
            if (this.personalScores[deptId]) {
              this.personalScores[deptId] = this.personalScores[deptId].filter((ps: any) => ps.id !== data.id)
            }
          } else {
            showToast(res.message || '删除失败')
          }
        })
        .catch(() => {
          closeToast()
          showToast('删除失败')
        })
    },

    async handleSubmitAll() {
      if (this.taskList.length === 0) {
        showToast('暂无可提交的任务')
        return
      }

      this.submitting = true
      showLoadingToast({ message: '提交中...', forbidClick: true })

      try {
        const task = this.taskList[0]
        const taskId = task.task_id

        // 收集所有部门的评分（用户改了就用改后的，否则默认95分）
        const scores = task.departments.map((dept: any) => {
          const key = String(dept.dept_id)
          const draft = this.scoreDrafts[key]
          return {
            dept_id: dept.dept_id,
            score: draft ? draft.score : 95, // 默认非常满意95
            opinion: draft ? draft.opinion || '' : ''
          }
        })

        const res: any = await batchSubmitScore({
          task_id: taskId,
          scores
        })

        closeToast()

        if (res && !res.message) {
          showToast({ message: `提交成功 (${scores.length}项)`, duration: 1500 })
          this.scoreDrafts = {}
          // 跳转到评分历史tab
          setTimeout(() => {
            this.$router.replace({ name: 'evaluation_index', query: { active: '1' } })
          }, 1500)
        } else if (res?.message) {
          const msg = String(res.message)
          showDialog({ title: msg })
        } else {
          showToast('提交失败')
        }
      } catch (e) {
        closeToast()
      }

      this.submitting = false
    }
  },
}
</script>

<style>
@import "@/views/financeCss.css";
@import "@/views/financeCommon.css";
</style>
<style lang="css" scoped>
.evaluation-page {
  min-height: 100vh;
  background: #fff;
  padding-bottom: 80px;
}

.task-title {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}

.dept-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.submit-all-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 12px 16px;
  background: #fff;
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08);
  z-index: 100;
}

:deep(.submit-btn-text .van-button__text) {
  font-size: calc(var(--van-cell-font-size) * 1.15) !important;
}
</style>
