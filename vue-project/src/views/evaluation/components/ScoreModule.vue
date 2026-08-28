<template>
  <van-cell-group inset>
    <van-cell>
      <template #title>
        <div class="dept-title">
          <span class="dept-name">{{ deptName }}</span>
          <van-tag v-if="submitted && selectedScore" :type="scoreTagType" size="medium">
            {{ scoreLabel }}
          </van-tag>
        </div>
      </template>
    </van-cell>

    <van-cell>
      <template #title>
        <ScoreRadio
          v-model="selectedScore"
          :options="displayOptions"
          @change="onScoreChange"
        />
      </template>
    </van-cell>

    <van-cell>
      <template #title>
        <van-field
          v-model="opinion"
          rows="2"
          autosize
          type="textarea"
          maxlength="200"
          placeholder="对部门的批评意见（列出具体事例、提出意见建议）。如果没有批评意见，此栏可不填。 "
          @update:model-value="onOpinionChange"
        />
      </template>
    </van-cell>

    <!-- Personal Scores List -->
    <van-cell v-if="personalScores && personalScores.length > 0">
      <template #title>
        <div class="personal-list">
          <div
            v-for="ps in personalScores"
            :key="ps.id"
            class="personal-item clickable"
            @click.stop="onPersonalScoreClick(ps)"
          >
            <van-image round width="32" height="32" :src="ps.target_user_avatar || '/default-avatar.png'" />
            <span class="personal-name">{{ ps.target_user_name }}</span>
            <van-tag :type="getScoreTagType(ps.score)" size="small">{{ ps.score }}分</van-tag>
            <span class="personal-opinion">{{ ps.opinion || '无意见' }}</span>
          </div>
        </div>
      </template>
    </van-cell>

    <van-cell>
      <template #title>
        <van-button type="primary" size="small" block icon="plus" @click="onPersonalScore" class="add-btn">
          添加个人评分
        </van-button>
      </template>
    </van-cell>
  </van-cell-group>
</template>

<script lang="ts">
import { ref, watch, computed } from 'vue'
import { CellGroup, Cell, Tag, Field, Button, Image as VanImage } from 'vant'
import ScoreRadio from './ScoreRadio.vue'

export default {
  name: 'ScoreModule',
  components: {
    vanCellGroup: CellGroup,
    vanCell: Cell,
    vanTag: Tag,
    vanField: Field,
    vanButton: Button,
    vanImage: VanImage,
    ScoreRadio,
  },
  props: {
    deptId: { type: [Number, String], required: true },
    deptName: { type: String, required: true },
    taskId: { type: [Number, String], required: true },
    scoreOptions: { type: Array, default: () => [] },
    initialScore: { type: Number, default: null },
    initialOpinion: { type: String, default: '' },
    submitted: { type: Boolean, default: false },
    personalScores: { type: Array, default: () => [] },
  },
  emits: ['update', 'personal-score', 'personal-score-click'],
  setup(props: any, { emit }: any) {
    const defaultOptions = [
      { value: 95, label: '非常满意' },
      { value: 85, label: '满意' },
      { value: 70, label: '基本满意' },
      { value: 60, label: '不满意' },
      { value: 50, label: '非常不满意' },
    ]

    const displayOptions = computed(() => {
      return props.scoreOptions && props.scoreOptions.length > 0 ? props.scoreOptions : defaultOptions
    })

    const selectedScore = ref(props.initialScore ?? displayOptions.value[0]?.value ?? null)
    const opinion = ref(props.initialOpinion)

    const scoreLabel = computed(() => {
      if (!selectedScore.value) return ''
      const opt = displayOptions.value.find((o: any) => o.value === selectedScore.value)
      return opt ? opt.label : ''
    })

    const scoreTagType = computed(() => {
      if (!selectedScore.value) return 'default'
      return selectedScore.value >= 85 ? 'success' : selectedScore.value >= 70 ? 'warning' : 'danger'
    })

    const getScoreTagType = (score: number) => {
      if (score >= 85) return 'success'
      if (score >= 70) return 'warning'
      return 'danger'
    }

    const onScoreChange = () => {
      emitUpdate()
    }

    const onOpinionChange = () => {
      emitUpdate()
    }

    const onPersonalScore = () => {
      emit('personal-score', {
        taskId: props.taskId,
        deptId: props.deptId,
        deptName: props.deptName
      })
    }

    const onPersonalScoreClick = (ps: any) => {
      emit('personal-score-click', {
        taskId: props.taskId,
        deptId: props.deptId,
        deptName: props.deptName,
        personalScore: ps
      })
    }

    const emitUpdate = () => {
      emit('update', {
        taskId: props.taskId,
        deptId: props.deptId,
        score: selectedScore.value,
        opinion: opinion.value
      })
    }

    watch(() => props.initialScore, (v) => { selectedScore.value = v })
    watch(() => props.initialOpinion, (v) => { opinion.value = v })

    return {
      selectedScore,
      opinion,
      displayOptions,
      scoreLabel,
      scoreTagType,
      getScoreTagType,
      onScoreChange,
      onOpinionChange,
      onPersonalScore,
      onPersonalScoreClick
    }
  }
}
</script>

<style>
@import "@/views/financeCss.css";
@import "@/views/financeCommon.css";
</style>
<style scoped>
.dept-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dept-name {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
}

.personal-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.personal-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  padding-right: 16px;
  background: #f8fafc;
  border-radius: 8px;
  cursor: default;
}

.personal-item.clickable {
  cursor: pointer;
}

.personal-name {
  font-size: 16px;
  font-weight: 500;
  color: #1a1a1a;
  white-space: nowrap;
}

.personal-opinion {
  flex: 1;
  font-size: 16px;
  color: #6b7280;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 150px;
}

:deep(.add-btn) {
  background: #fff;
  border-color: #e2e8f0;
  color: #334155;
  font-size: 16px;
  border-radius: 8px;
}

:deep(.van-tag) {
  font-size: calc(var(--van-cell-font-size)) !important;
}
</style>
