<template>
  <div v-if="data" class="listrow">
    <div class="value" style="flex-grow: 1; box-sizing: border-box;">
      <span
        v-if="statename"
        :style="{ margin: '0 5px', color: statecolor, border: '1px solid #F5F2EF', padding: '2px' }"
      >{{ statename }}</span>
      <span style="color: black;">{{ data.username }} {{ data.sparation }}</span>
    </div>
    <div class="date" style="width: 80px !important; text-align: right; flex-shrink: 0;">
      {{ data.createTime ? data.createTime.substring(0, 10) : '' }}
    </div>
  </div>
</template>

<script lang="ts">
import { Tag } from 'vant';
import { StatesEnum, stateColor } from '../config';

export default {
  components: { Tag },
  props: ['data'],
  data() {
    return {
      statecolor: '',
      statename: '',
    };
  },
  mounted() {
    const record = this.data;
    if (record) {
      // yii2 返回的流程状态 (completed: 0=审批中, 1=已通过, 2=已驳回, 4=已取消, 5=结束)
      const completed = record.completed ?? record.flow_completed;
      if (completed === 2) {
        this.statename = '已驳回';
        this.statecolor = 'gray';
      } else if (completed === 4) {
        this.statename = '已取消';
        this.statecolor = 'gray';
      } else if (completed === 1 || completed === 5) {
        this.statename = '已通过';
        this.statecolor = 'green';
      } else if (completed === 0) {
        this.statename = '审批中';
        this.statecolor = '#FF6D25';
      } else {
        this.statename = '未提交';
        this.statecolor = 'gray';
      }
    }
  },
};
</script>

<style src="@/views/financeCss.css"></style>
