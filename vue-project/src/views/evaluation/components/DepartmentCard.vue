<template>
  <div class="department-card" @click="$emit('click', department)">
    <div class="dept-check">
      <van-icon :name="department.status ? 'passed' : 'circle'" :class="{ 'status-done': department.status }" />
    </div>
    <div class="dept-info">
      <div class="dept-name">{{ department.dept_name }}</div>
      <div class="dept-meta">
        <van-tag v-if="department.status" type="success" size="medium">{{ department.score }}分</van-tag>
        <van-tag v-if="department.status" type="success" size="medium">已评价</van-tag>
        <van-tag v-else type="warning" size="medium">待评价</van-tag>
      </div>
    </div>
    <div class="dept-arrow">
      <van-icon name="arrow" />
    </div>
  </div>
</template>

<script lang="ts">
import { Icon, Tag } from 'vant'

export default {
  name: 'DepartmentCard',
  components: {
    vanIcon: Icon,
    vanTag: Tag,
  },
  props: {
    department: {
      type: Object as () => {
        dept_id: number
        dept_name: string
        status: number
        score?: number
        opinion?: string
        submitted_at?: string
      },
      required: true,
    },
  },
  emits: ['click'],
}
</script>

<style lang="css" scoped>
.department-card {
  display: flex;
  align-items: center;
  background: #fff;
  border-radius: 12px;
  padding: 8px;
  margin-bottom: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.dept-check {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 8px;
  font-size: 18px;
  color: #ccc;
}

.dept-check .status-done {
  color: #16a34a;
}

.dept-info {
  flex: 1;
}

.dept-name {
  font-size: 16px;
  font-weight: 600;
  color: #1a365d;
  margin-bottom: 4px;
}

.dept-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.dept-arrow {
  font-size: 14px;
  color: #ccc;
}

:deep(.van-tag) {
  font-size: calc(var(--van-cell-font-size)) !important;
}
</style>
