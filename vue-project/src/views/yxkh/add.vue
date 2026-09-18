<template>
  <div class="box">
    <!-- 周期选择阶段 -->
    <div v-if="step === 'select'">
      <CellGroup title="请选择考核周期">
        <Cell
          v-for="period in periodOptions"
          :key="period.value"
          :title="period.text"
          is-link
          @click="selectPeriod(period)"
        />
      </CellGroup>
    </div>

    <!-- 主表单阶段 -->
    <div v-if="step === 'form'">
      <Form @submit="onSubmit">
      <!-- 用户信息头部 -->
      <CellGroup title="考核人信息">
        <Cell title="姓名" :value="userInfoData?.name || '-'" />
        <Cell title="部门" :value="userInfoData?.departmentname || '-'" />
        <Cell title="岗位" :value="userInfoData?.position || '-'" />
      </CellGroup>

      <!-- 自评 -->
      <CellGroup title="自评">
        <Field
          label="自评等级"
          placeholder="请选择自评等级"
          is-link
          readonly
          :model-value="form.selfEvaluation"
          @click="showSelfEval = true"
        />
        <Field
          v-model="form.attendance"
          type="textarea"
          rows="2"
          label="考勤情况"
          placeholder="如：旷工(0)天，请假(0)天"
        />
      </CellGroup>

      <!-- 工作项目 -->
      <CellGroup title="工作项目">
        <div
          v-for="(pro, pIndex) in form.projects"
          :key="pIndex"
          style="padding: 10px; background: #f7f8fa; margin: 5px;"
        >
          <Cell>
            <template #title>
              <span style="font-weight: bold;">项目{{ pIndex + 1 }}</span>
            </template>
            <template #right-icon>
              <span @click.stop="editProject(pIndex)" style="margin-right: 10px; color: #1989fa;">编辑</span>
              <span @click.stop="deleteProject(pIndex)" style="color: #ee0a24;">删除</span>
            </template>
          </Cell>
          <Cell title="内容" :value="pro.projectContent || '-'" />
          <Cell title="进度" :value="pro.progress || '-'" />
        </div>
        <Button block plain type="primary" @click="openProjectDialog" style="margin: 10px;">
          + 添加项目
        </Button>
      </CellGroup>

      <!-- 问题与计划 -->
      <CellGroup title="问题与下月计划">
        <Field
          v-model="form.shortComesAndPlan"
          type="textarea"
          rows="3"
          placeholder="请输入本月存在问题及下月工作计划"
        />
      </CellGroup>

      <div style="margin: 16px; display: flex; gap: 10px;">
        <Button type="default" text="返回" @click="goBack" style="flex: 1;" />
        <Button v-if="!form.processInstanceId" type="primary" native-type="submit" text="提交" style="flex: 1;" />
        <Button v-else type="primary" text="修改" @click="onModify" style="flex: 1;" />
      </div>
      </Form>
    </div>

    <!-- 自评选择弹窗 -->
    <ActionSheet
      v-model:show="showSelfEval"
      :actions="selfEvaluationOptions"
      cancel-text="取消"
      @select="onSelfEvalSelect"
    />

    <!-- 项目弹窗 -->
    <Popup v-model:show="showProjectDialog" position="bottom" :style="{ height: '80vh' }">
      <CellGroup title="项目信息" style="height: calc(80vh - 60px); overflow-y: auto;">
        <Field v-model="projectForm.projectContent" type="textarea" rows="8" label="项目内容" placeholder="请输入项目内容" style="width: 100%;" />
        <Field v-model="projectForm.progress" type="textarea" rows="8" label="完成进度" placeholder="请输入完成进度" style="width: 100%;" />
      </CellGroup>
      <div style="padding: 10px; display: flex; gap: 10px; position: sticky; bottom: 0; background: #fff;">
        <Button block @click="showProjectDialog = false">取消</Button>
        <Button block type="primary" @click="confirmProject">确定</Button>
      </div>
    </Popup>
  </div>
</template>

<script lang="ts">
import {
  Cell,
  CellGroup,
  Button,
  Field,
  Form,
  ActionSheet,
  Popup,
  showDialog,
  showConfirmDialog,
  showToast,
} from 'vant';
import { h } from 'vue';
import {
  getYxkhProjects,
  saveYxkhProject,
  delYxkhProject,
  getYxkhUserinfo,
  getYxkhFlow,
  startYxkhFlow,
  getYxkhExt,
  saveYxkh,
} from './api';
import { SelfEvaluationOptions } from './config';
import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';

export default {
  components: {
    Cell,
    CellGroup,
    Button,
    Field,
    Form,
    ActionSheet,
    Popup,
    Flow_Steps,
  },
  data() {
    return {
      step: 'select' as 'select' | 'form',
      periodOptions: [] as { text: string; value: string; startDate: string; endDate: string }[],
      selfEvaluationOptions: SelfEvaluationOptions,
      showSelfEval: false,
      showProjectDialog: false,
      form: {
        eid: undefined as number | undefined,
        selfEvaluation: '合格',
        attendance: '',
        shortComesAndPlan: '',
        projects: [] as any[],
        processInstanceId: '',
        isSubmmited: false, // 是否已提交（审批中）
      },
      projectForm: {
        projectContent: '',
        progress: '',
        editIndex: -1,
      },
      selectedPeriod: null as any,
      userInfoData: null as any,
      loading: false,
    };
  },
  computed: {
  },
  mounted() {
    this.generatePeriodOptions();
    const eid = this.$route.query.eid;
    if (eid) {
      this.loadEvaluation(Number(eid));
    }
  },
  methods: {
    generatePeriodOptions() {
      const now = new Date();
      const options = [];
      for (let i = 0; i < 6; i++) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const year = d.getFullYear();
        const month = d.getMonth() + 1;
        const startDate = `${year}-${String(month).padStart(2, '0')}-01`;
        const lastDay = new Date(year, month, 0).getDate();
        const endDate = `${year}-${String(month).padStart(2, '0')}-${lastDay}`;
        const text = `${year}年${month}月份-月度考核`;
        options.push({ text, value: text, startDate, endDate });
      }
      this.periodOptions = options;
    },
    async loadEvaluation(eid: number) {
      try {
        const res: any = await getYxkhExt(eid);
        if (res?.errorMessage) {
          showDialog({ message: res.errorMessage });
          return;
        }
        const evalData = res?.data;
        if (!evalData) return;

        // 找到匹配的 period
        const period = this.periodOptions.find(
          (p: any) => p.startDate === evalData.startDate && p.endDate === evalData.endDate
        );
        if (!period) {
          showDialog({ message: '未找到对应的考核周期' });
          return;
        }

        this.form.eid = eid;
        this.form.selfEvaluation = evalData.selfEvaluation || '合格';
        this.form.attendance = evalData.attendance || '';
        this.form.shortComesAndPlan = evalData.shortComesAndPlan || '';
        this.form.processInstanceId = evalData.processInstanceId || '';
        this.form.isSubmmited = !!evalData.processInstanceId; // 有processInstanceId表示已提交
        this.selectedPeriod = period;

        // 加载用户信息和项目
        await this.loadUserInfo();
        await this.loadProjects();

        this.step = 'form';
      } catch (e: any) {
        showDialog({ message: '加载考核记录失败：' + (e.message || e) });
      }
    },
    async selectPeriod(period: any) {
      this.selectedPeriod = period;
      this.step = 'form';
      await this.loadUserInfo();
      this.loadProjects();
    },
    async loadUserInfo() {
      try {
        const res: any = await getYxkhUserinfo();
        if (res?.data) {
          this.userInfoData = res.data;
        }
      } catch (e) {
        console.error('获取用户信息失败', e);
      }
    },
    async loadProjects() {
      if (!this.selectedPeriod) return;
      try {
        const res: any = await getYxkhProjects({
          userId: this.userInfoData?.userid,
          startDate: this.selectedPeriod.startDate,
          endDate: this.selectedPeriod.endDate,
        });
        if (res?.data) {
          this.form.projects = res.data;
        }
      } catch (e) {
        console.error(e);
      }
    },
    openProjectDialog() {
      this.projectForm = { projectContent: '', progress: '', editIndex: -1 };
      this.showProjectDialog = true;
    },
    editProject(index: number) {
      const pro = this.form.projects[index];
      this.projectForm = {
        projectContent: pro.projectContent,
        progress: pro.progress,
        editIndex: index,
      };
      this.showProjectDialog = true;
    },
    deleteProject(index: number) {
      showConfirmDialog({ title: '确定删除该项目吗？' }).then(async () => {
        const pro = this.form.projects[index];
        if (pro.projectId) {
          await delYxkhProject([pro.projectId]);
        }
        this.form.projects.splice(index, 1);
      });
    },
    async confirmProject() {
      if (!this.projectForm.projectContent || !this.projectForm.progress) {
        showDialog({ message: '请填写完整信息' });
        return;
      }
      try {
        const res: any = await saveYxkhProject({
          projectId: this.projectForm.editIndex >= 0 ? this.form.projects[this.projectForm.editIndex].projectId : undefined,
          projectContent: this.projectForm.projectContent,
          progress: this.projectForm.progress,
          startDate: this.selectedPeriod.startDate,
          endDate: this.selectedPeriod.endDate,
        });
        if (res?.errorMessage) {
          showDialog({ message: res.errorMessage });
          return;
        }
        const projectId = res?.data?.projectId;
        if (this.projectForm.editIndex >= 0) {
          const pro = this.form.projects[this.projectForm.editIndex];
          pro.projectContent = this.projectForm.projectContent;
          pro.progress = this.projectForm.progress;
          if (projectId) pro.projectId = projectId;
        } else {
          this.form.projects.push({
            projectId,
            projectContent: this.projectForm.projectContent,
            progress: this.projectForm.progress,
          });
        }
        this.showProjectDialog = false;
      } catch (e) {
        showDialog({ message: '保存项目失败' });
      }
    },
    onSelfEvalSelect(action: any) {
      this.form.selfEvaluation = action.name;
      this.showSelfEval = false;
    },
    onSubmit() {
      if (!this.selectedPeriod) {
        showDialog({ message: '请先选择考核周期' });
        return;
      }

      // 准备数据
      const data = {
        startDate: this.selectedPeriod.startDate,
        endDate: this.selectedPeriod.endDate,
        sparation: this.selectedPeriod.text,
        selfEvaluation: this.form.selfEvaluation,
        attendance: this.form.attendance,
        shortComesAndPlan: this.form.shortComesAndPlan,
      };

      // 先获取流程预览
      getYxkhFlow(data).then((res: any) => {
        if (res.errorMessage) {
          showDialog({ message: res.errorMessage });
        } else {
          showConfirmDialog({
            title: '请确认流程是否正确',
            message: () => h(Flow_Steps, { data: res || {} }),
          })
            .then(() => {
              if (this.loading) {
                showToast({ message: '提交中...' });
                return;
              }
              this.loading = true;
              // 直接启动审批流（actionStartflow 会创建考核记录）
              startYxkhFlow({
                eid: this.form.eid,
                templateId: 'default',
                title: `${this.userInfoData?.name || ''}-${this.selectedPeriod.text}`,
                ...data,
              }).then((startRes: any) => {
                console.log('startYxkhFlow response:', startRes);
                this.loading = false;
                if (startRes.errorMessage) {
                  showDialog({ message: startRes.errorMessage });
                } else {
                  showDialog({ message: '提交成功' }).then(() => {
                    this.$router.push({ name: 'yxkh_index' });
                  });
                }
              }).catch((err: any) => {
                console.error('startYxkhFlow error:', err);
                this.loading = false;
                showDialog({ message: '提交失败：' + (err.message || err) });
              });
            })
            .catch(() => {
              // 取消
            });
        }
      });
    },
    goBack() {
      this.$router.push({ name: 'yxkh_index' });
    },
    onModify() {
      // 已提交的考核，仅保存数据，不重新发起流程
      if (this.loading) return;
      this.loading = true;
      saveYxkh({
        eid: this.form.eid,
        startDate: this.selectedPeriod.startDate,
        endDate: this.selectedPeriod.endDate,
        sparation: this.selectedPeriod.text,
        selfEvaluation: this.form.selfEvaluation,
        attendance: this.form.attendance,
        shortComesAndPlan: this.form.shortComesAndPlan,
      }).then((res: any) => {
        this.loading = false;
        if (res?.errorMessage) {
          showDialog({ message: res.errorMessage });
        } else {
          showDialog({ message: '修改成功' });
        }
      }).catch((e: any) => {
        this.loading = false;
        showDialog({ message: '修改失败：' + (e.message || e) });
      });
    },
  },
};
</script>

<style lang="css" src="@/views/financeCss.css"></style>
