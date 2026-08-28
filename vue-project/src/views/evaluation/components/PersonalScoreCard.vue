<template>
  <div class="personal-score-item" @click="toggleExpand">
    <van-image round width="36" height="36" :src="data.target_user_avatar || '/default-avatar.png'" />
    <span class="item-name">{{ data.target_user_name }}</span>
    <van-tag :type="scoreTagType" size="medium">{{ data.score }}分</van-tag>
    <span class="item-opinion" :class="{ collapsed: !expanded && hasLongOpinion }">
      {{ data.opinion || '无意见' }}
    </span>
    <div v-if="!submitted" class="item-actions">
      <span class="action-btn" @click.stop="onEdit">修改</span>
      <span class="action-btn danger" @click.stop="onDelete">删除</span>
    </div>
  </div>
</template>

<script lang="ts">
import { ref, computed } from 'vue'
import { Image as VanImage, Tag } from 'vant'

export default {
  name: 'PersonalScoreCard',
  components: {
    vanImage: VanImage,
    vanTag: Tag,
  },
  props: {
    data: {
      type: Object,
      required: true,
    },
    submitted: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['edit', 'delete'],
  setup(props: any, { emit }: any) {
    const expanded = ref(false)
    const OPINION_MAX_LENGTH = 12
    const hasLongOpinion = computed(() => {
      return props.data.opinion && props.data.opinion.length > OPINION_MAX_LENGTH
    })

    const scoreTagType = computed(() => {
      const score = props.data.score
      if (score >= 85) return 'success'
      if (score >= 70) return 'warning'
      return 'danger'
    })

    const toggleExpand = () => {
      if (hasLongOpinion.value) {
        expanded.value = !expanded.value
      }
    }

    const onEdit = () => {
      emit('edit', props.data)
    }

    const onDelete = () => {
      emit('delete', props.data.id)
    }

    return {
      expanded,
      hasLongOpinion,
      scoreTagType,
      toggleExpand,
      onEdit,
      onDelete,
    }
  },
}
</script>

<style>
@import "@/views/financeCss.css";
@import "@/views/financeCommon.css";
</style>
<style scoped>
.personal-score-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  background: #fff;
  border-radius: 8px;
  margin: 4px 0;
  cursor: pointer;
}

.item-name {
  font-size: 16px;
  font-weight: 500;
  color: #334155;
  white-space: nowrap;
}

.item-opinion {
  flex: 1;
  font-size: 16px;
  color: #94a3b8;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item-opinion.collapsed {
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}

.item-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.action-btn {
  font-size: 16px;
  color: #0ea5e9;
  cursor: pointer;
}

.action-btn.danger {
  color: #ef4444;
}

:deep(.van-tag) {
  font-size: calc(var(--van-cell-font-size)) !important;
}
</style>
