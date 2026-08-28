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
        @click="handleSubmitAll"
      >
        提交全部评分 ({{ pendingCount }}项)
      </van-button>
    </div>

    <!-- Personal Score Modal -->
    <PersonalScoreModal
      :show="personalScoreModal.show"
      :task-id="personalScoreModal.taskId"
      :dept-id="personalScoreModal.deptId"
      :dept-name="personalScoreModal.deptName"
      :score-options="scoreOptions"
      :edit-data="personalScoreModal.editData"
      @close="personalScoreModal.show = false"
      @success="handlePersonalScoreSuccess"
      @delete="handlePersonalScoreDelete"
    />
  </div>
</template>

<script lang="ts">
import { NavBar, Empty, Button, Cell, CellGroup, Tag, List, showToast, showLoadingToast, closeToast, showConfirmDialog, Dialog, showDialog } from 'vant'
import { getTasks, getTaskDetail, getConfig, batchSubmitScore, submitPersonalScore, deletePersonalScore } from '@/views/evaluation/api/evaluation'
import ScoreModule from './components/ScoreModule.vue'
import PersonalScoreModal from './components/PersonalScoreModal.vue'

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
      // All drafts must have a score selected
      return (Object.values(this.scoreDrafts) as any[]).every((draft: any) => draft.score > 0)
    },
    navTitle() {
      if (this.taskList.length > 0) {
        const task = this.taskList[0]
        const done = task.departments.filter((d: any) => d.status).length
        return `${task.year}年第${task.quarter}季度（${done}/${task.departments.length}）`
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
          const [detailRes, configRes] = await Promise.all([
            getTaskDetail({ task_id: Number(this.taskId) }),
            getConfig({ type: '评分档次' })
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
      if (!this.canSubmitAll) {
        showToast('请为所有部门选择评分')
        return
      }

      this.submitting = true
      showLoadingToast({ message: '提交中...', forbidClick: true })

      try {
        // 收集所有部门的评分
        const scores = Object.values(this.scoreDrafts).map((draft: any) => ({
          dept_id: draft.deptId,
          score: draft.score,
          opinion: draft.opinion || ''
        }))

        // 获取 task_id（从第一个 draft）
        const firstDraft = Object.values(this.scoreDrafts)[0] as any
        const taskId = firstDraft?.taskId

        if (!taskId) {
          closeToast()
          showToast('任务ID无效')
          this.submitting = false
          return
        }

        const res: any = await batchSubmitScore({
          task_id: taskId,
          scores
        })

        closeToast()
       
        if (res && !res.message) {
     
          showToast({ message: `提交成功 (${Object.keys(this.scoreDrafts).length}项)`, duration: 1500 })
          this.scoreDrafts = {}
          this.loadData()
        } else if (res?.message) {

          const msg = String(res.message)
          showDialog({
            title: msg,
          })
    
        } else {
      
          showToast('提交失败')
        }
      } catch (e) {
       
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
</style>
