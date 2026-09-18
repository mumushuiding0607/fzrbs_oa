<template>
  <div class="box">
    <!-- 考核信息预览 -->
    <CellGroup title="考核信息">
      <Cell title="姓名" :value="query.name || '-'" />
      <Cell title="部门" :value="query.department || '-'" />
      <Cell title="岗位" :value="query.position || '-'" />
      <Cell title="考核周期" :value="query.sparation || '-'" />
      <Cell title="自评等级" :value="query.selfEvaluation || '-'" />
      <Cell title="考勤情况" :value="query.attendance || '-'" />
    </CellGroup>

    <!-- 问题与计划 -->
    <CellGroup title="问题与下月计划">
      <Cell title="" :value="query.shortComesAndPlan || '-'" />
    </CellGroup>

    <!-- 项目信息 -->
    <CellGroup title="工作项目">
      <div
        v-for="(pro, pIndex) in projects"
        :key="pIndex"
        style="padding: 10px; background: #f7f8fa; margin: 5px;"
      >
        <Cell>
          <template #title>
            <span style="font-weight: bold;">项目{{ pIndex + 1 }}</span>
          </template>
        </Cell>
        <Cell title="内容" :value="pro.projectContent || '-'" />
        <Cell title="进度" :value="pro.progress || '-'" />
      </div>
      <div v-if="projects.length === 0" style="padding: 10px; color: #999;">暂无项目</div>
    </CellGroup>

    <!-- 审批流程 -->
    <CellGroup title="审批流程">
      <div style="padding: 10px;">
        <Flow_Steps v-if="flowData" :data="flowData" />
        <div v-else-if="loadingFlow" style="text-align: center; padding: 20px;">加载中...</div>
        <div v-else style="text-align: center; padding: 20px; color: #999;">暂无审批流程信息</div>
      </div>
    </CellGroup>

    <div style="height: 80px;"></div>
    <ActionBar>
      <ActionBarButton type="default" text="返回" @click="goBack" />
      <ActionBarButton type="primary" text="确认提交" :loading="submitting" @click="doSubmit" />
    </ActionBar>
  </div>
</template>

<script lang="ts">
import {
  Cell,
  CellGroup,
  Button,
  ActionBar,
  ActionBarButton,
  showDialog,
  showLoadingToast,
  closeToast,
} from 'vant';
import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';
import { saveYxkh, startYxkhFlow, getYxkhFlow } from './api';

export default {
  components: {
    Cell,
    CellGroup,
    Button,
    ActionBar,
    ActionBarButton,
    Flow_Steps,
  },
  data() {
    return {
      query: {} as any,
      projects: [] as any[],
      flowData: null as any,
      loadingFlow: false,
      submitting: false,
      eid: undefined as number | undefined,
    };
  },
  mounted() {
    // 从路由获取参数
    const q = this.$route.query;
    this.query = {
      name: q.name || '',
      department: q.department || '',
      position: q.position || '',
      sparation: q.sparation || '',
      selfEvaluation: q.selfEvaluation || '',
      attendance: q.attendance || '',
      shortComesAndPlan: q.shortComesAndPlan || '',
      startDate: q.startDate || '',
      endDate: q.endDate || '',
    };
    this.projects = q.projects ? JSON.parse(q.projects as string) : [];
    this.eid = q.eid ? Number(q.eid) : undefined;

    // 获取流程预览
    this.loadFlowPreview();
  },
  methods: {
    async loadFlowPreview() {
      if (!this.query.startDate || !this.query.endDate) return;

      this.loadingFlow = true;
      try {
        // 先保存获取 eid（如果还没有）
        if (!this.eid) {
          const saveRes: any = await saveYxkh({
            eid: this.eid,
            startDate: this.query.startDate,
            endDate: this.query.endDate,
            sparation: this.query.sparation,
            selfEvaluation: this.query.selfEvaluation,
            attendance: this.query.attendance,
            shortComesAndPlan: this.query.shortComesAndPlan,
          });
          if (saveRes?.errorMessage) {
            showDialog({ message: saveRes.errorMessage });
            return;
          }
          this.eid = saveRes?.data?.eid;
        }

        // 获取流程预览
        const flowRes: any = await getYxkhFlow({
          eid: this.eid,
          templateId: 'default',
        });
        if (flowRes?.errorMessage) {
          showDialog({ message: flowRes.errorMessage });
          return;
        }
        this.flowData = flowRes;
      } catch (e) {
        console.error('加载流程预览失败', e);
        showDialog({ message: '加载流程预览失败' });
      } finally {
        this.loadingFlow = false;
      }
    },
    async doSubmit() {
      if (!this.eid) {
        showDialog({ message: '考核记录不存在' });
        return;
      }

      this.submitting = true;
      try {
        const startRes: any = await startYxkhFlow({
          eid: this.eid,
          templateId: 'default',
          title: `${this.query.name || ''}-${this.query.sparation}`,
        });
        if (startRes?.errorMessage) {
          showDialog({ message: startRes.errorMessage });
          return;
        }
        showDialog({ message: '提交成功' }).then(() => {
          this.$router.push({ name: 'yxkh_index' });
        });
      } catch (e) {
        showDialog({ message: '提交失败' });
      } finally {
        this.submitting = false;
      }
    },
    goBack() {
      this.$router.back();
    },
  },
};
</script>

<style lang="css" src="@/views/financeCss.css"></style>
