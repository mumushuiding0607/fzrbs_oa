<template>
  <div class="box1">
    <!-- 用户信息头部 -->
    <div v-if="flowInfo" class="row" :style="{ padding: '8px' }">
      <Image
        width="50"
        radius="10"
        height="50"
        fit="cover"
        :round="false"
        :src="flowData?.applyUserImage || defaultAvatar"
      />
      <span
        class="title"
        :style="{ marginLeft: '5px', marginRight: '5px', fontWeight: 'bold', color: 'black' }"
      >{{ flowInfo.username }}的月度考核申请</span>
      <Tag size="large" plain :type="tagcolor">{{ statusCn }}</Tag>
    </div>

    <CellGroup>
      <!-- 基本信息 -->
      <div class="cell">
        <div class="label">考核周期</div>
        <div class="value">{{ evaluation?.sparation || '-' }}</div>
      </div>
      <div class="cell">
        <div class="label">申请部门</div>
        <div class="value">{{ evaluation?.department || '-' }}</div>
      </div>
      <div class="cell">
        <div class="label">岗位</div>
        <div class="value">{{ evaluation?.position || '-' }}</div>
      </div>
      <div class="cell">
        <div class="label">自评等级</div>
        <div class="value">{{ evaluation?.selfEvaluation || '-' }}</div>
      </div>
      <div class="cell">
        <div class="label">考勤情况</div>
        <div class="value">{{ evaluation?.attendance || '-' }}</div>
      </div>
      <div class="cell">
        <div class="label">考核总分</div>
        <div class="value" style="color: red; font-weight: bold;">
          {{ evaluation?.totalMark || '0' }}分
        </div>
      </div>
      <div class="cell">
        <div class="label">问题与下月计划</div>
        <div class="value">{{ evaluation?.shortComesAndPlan || '-' }}</div>
      </div>
    </CellGroup>

    <!-- 工作项目列表 -->
    <div v-if="projects.length > 0" class="cell title" style="margin-top: 20px;">工作项目:</div>
    <CellGroup v-for="pro in projects" :key="pro.projectId" style="margin-bottom: 10px;">
      <div class="cell" style="background: #f7f8fa;">
        <div class="label">项目内容</div>
        <div class="value">{{ pro.projectContent }}</div>
      </div>
      <div class="cell">
        <div class="label">进度</div>
        <div class="value">{{ pro.progress }}</div>
      </div>
      <!-- 项目加减分 -->
      <div v-if="getMarksByProject(pro.projectId).length > 0" class="cell">
        <div class="label">加减分</div>
        <div class="value">
          <span
            v-for="mark in getMarksByProject(pro.projectId)"
            :key="mark.markId"
            :style="{ color: Number(mark.markNumber) > 0 ? 'green' : 'red', marginRight: '5px' }"
          >
            {{ Number(mark.markNumber) > 0 ? '+' : '' }}{{ mark.markNumber }} ({{ mark.markReason }})
          </span>
        </div>
      </div>
    </CellGroup>

    <!-- 审批流程 -->
    <div v-if="flowData" class="cell title" style="margin-top: 20px;">审批流程:</div>
    <Flow_Steps v-if="flowData" :data="{ viewdata: flowData, statusCn: statusCnArray }" />

    <Divider />

    <div style="height: 50px;"></div>

    <ActionBar>
      <ActionBarButton
        v-if="showEditBtn"
        type="primary"
        text="修改"
        @click="showProjectDialog = true"
      />
    </ActionBar>

    <!-- 项目编辑弹窗 -->
    <Popup v-model:show="showProjectDialog" position="bottom" :style="{ height: '80vh' }">
      <CellGroup title="工作项目" style="height: calc(80vh - 60px); overflow-y: auto;">
        <div
          v-for="(pro, pIndex) in projects"
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
        <CellGroup title="基本信息修改">
          <Field v-model="editForm.selfEvaluation" label="自评等级" placeholder="请输入自评等级" />
          <Field v-model="editForm.attendance" label="考勤情况" placeholder="请输入考勤情况" />
          <Field v-model="editForm.shortComesAndPlan" type="textarea" rows="3" label="问题与下月计划" placeholder="请输入" />
        </CellGroup>
      </CellGroup>
      <div style="padding: 10px; display: flex; gap: 10px; position: sticky; bottom: 0; background: #fff;">
        <Button block @click="showProjectDialog = false">取消</Button>
        <Button block type="primary" @click="saveProjectAndEvaluation">保存</Button>
      </div>
    </Popup>

    <!-- 项目编辑表单弹窗 -->
    <Popup v-model:show="showSingleProjectDialog" position="bottom" :style="{ height: '60vh' }">
      <CellGroup title="项目信息" style="height: calc(60vh - 60px); overflow-y: auto;">
        <Field v-model="projectForm.projectContent" type="textarea" rows="4" label="项目内容" placeholder="请输入项目内容" style="width: 100%;" />
        <Field v-model="projectForm.progress" type="textarea" rows="4" label="完成进度" placeholder="请输入完成进度" style="width: 100%;" />
      </CellGroup>
      <div style="padding: 10px; display: flex; gap: 10px; position: sticky; bottom: 0; background: #fff;">
        <Button block @click="showSingleProjectDialog = false">取消</Button>
        <Button block type="primary" @click="confirmProject">确定</Button>
      </div>
    </Popup>
  </div>
</template>

<script lang="ts">
import {
  Divider,
  Image as VanImage,
  Tag,
  Cell,
  CellGroup,
  Field,
  Button,
  Popup,
  showDialog,
  showToast,
  ActionBar,
  ActionBarButton,
} from 'vant';
import { useUserStore } from '@/stores';
import { storeToRefs } from 'pinia';
import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';
import { viewYxkhFlow, saveYxkhProject, delYxkhProject, saveYxkh } from '../api';
import { StatesEnum } from '../config';

const { userInfo } = storeToRefs(useUserStore());

export default {
  components: {
    Flow_Steps,
    VanImage,
    Image: VanImage,
    Tag,
    Divider,
    Cell,
    CellGroup,
    Field,
    Button,
    Popup,
    ActionBar,
    ActionBarButton,
  },
  props: ['processInstanceId'],
  data() {
    return {
      flowInfo: null as any,
      flowData: null as any,
      evaluation: null as any,
      projects: [] as any[],
      marks: [] as any[],
      showEditBtn: false,
      showProjectDialog: false,
      showSingleProjectDialog: false,
      projectForm: {
        projectId: undefined as number | undefined,
        projectContent: '',
        progress: '',
        editIndex: -1,
      },
      editForm: {
        selfEvaluation: '',
        attendance: '',
        shortComesAndPlan: '',
      },
      statusCn: '',
      tagcolor: 'default' as any,
      statusCnArray: ['', '审批中', '已同意', '已驳回', '已取消'],
      defaultAvatar: 'https://fastly.jsdelivr.net/npm/@vant/assets/cat.jpeg',
    };
  },
  mounted() {
    this.getdata();
  },
  methods: {
    getMarksByProject(projectId: number) {
      return this.marks.filter((m) => m.projectId === projectId);
    },
    async getdata() {
      if (!this.processInstanceId) {
        showDialog({ message: 'processInstanceId 为空' });
        return;
      }

      try {
        const res: any = await viewYxkhFlow(this.processInstanceId);
        if (res?.errorMessage) {
          showDialog({ message: res.errorMessage });
          return;
        }

        const d = res?.data || {};
        this.flowInfo = d.flowInfo;
        this.flowData = d.flowData;
        this.evaluation = d.evaluation;
        this.projects = d.projects || [];
        this.marks = d.marks || [];

        // 回填编辑表单
        if (d.evaluation) {
          this.editForm.selfEvaluation = d.evaluation.selfEvaluation || '';
          this.editForm.attendance = d.evaluation.attendance || '';
          this.editForm.shortComesAndPlan = d.evaluation.shortComesAndPlan || '';
        }

        const completed = this.flowInfo?.completed;
        const userId = userInfo.value?.userId;

        // 状态文字和颜色（completed: 0=审批中, 1=已通过, 2=已驳回...）
        if (completed === StatesEnum.REJECT || completed === 2) {
          this.statusCn = '已驳回';
          this.tagcolor = 'danger';
        } else if (completed === StatesEnum.CANCEL || completed === 4) {
          this.statusCn = '已取消';
          this.tagcolor = 'default';
        } else if (completed === StatesEnum.PASS || completed === 1 || completed === 5) {
          this.statusCn = '已通过';
          this.tagcolor = 'success';
        } else if (completed === 0) {
          this.statusCn = '审批中';
          this.tagcolor = 'warning';
        }

        // 判断按钮显示（只有申请人且审批中才能修改）
        const isApplicant = this.flowInfo?.userId === userId;
        this.showEditBtn = completed === 0 && isApplicant;
      } catch (e) {
        console.error(e);
        showDialog({ message: '加载失败' });
      }
    },
    openProjectDialog() {
      this.projectForm = { projectId: undefined, projectContent: '', progress: '', editIndex: -1 };
      this.showSingleProjectDialog = true;
    },
    editProject(index: number) {
      const pro = this.projects[index];
      this.projectForm = {
        projectId: pro.projectId,
        projectContent: pro.projectContent,
        progress: pro.progress,
        editIndex: index,
      };
      this.showSingleProjectDialog = true;
    },
    deleteProject(index: number) {
      showConfirmDialog({ title: '确定删除该项目吗？' }).then(async () => {
        const pro = this.projects[index];
        if (pro.projectId) {
          await delYxkhProject([pro.projectId]);
        }
        this.projects.splice(index, 1);
      });
    },
    async confirmProject() {
      if (!this.projectForm.projectContent || !this.projectForm.progress) {
        showDialog({ message: '请填写完整信息' });
        return;
      }
      try {
        const res: any = await saveYxkhProject({
          projectId: this.projectForm.projectId,
          projectContent: this.projectForm.projectContent,
          progress: this.projectForm.progress,
          startDate: this.evaluation?.startDate || '',
          endDate: this.evaluation?.endDate || '',
        });
        if (res?.errorMessage) {
          showDialog({ message: res.errorMessage });
          return;
        }
        const projectId = res?.data?.projectId;
        if (this.projectForm.editIndex >= 0) {
          const pro = this.projects[this.projectForm.editIndex];
          pro.projectContent = this.projectForm.projectContent;
          pro.progress = this.projectForm.progress;
          if (projectId) pro.projectId = projectId;
        } else {
          this.projects.push({
            projectId,
            projectContent: this.projectForm.projectContent,
            progress: this.projectForm.progress,
          });
        }
        this.showSingleProjectDialog = false;
      } catch (e) {
        showDialog({ message: '保存项目失败' });
      }
    },
    async saveProjectAndEvaluation() {
      try {
        // 保存基本信息
        await saveYxkh({
          eid: this.evaluation?.eId,
          startDate: this.evaluation?.startDate || '',
          endDate: this.evaluation?.endDate || '',
          sparation: this.evaluation?.sparation || '',
          selfEvaluation: this.editForm.selfEvaluation,
          attendance: this.editForm.attendance,
          shortComesAndPlan: this.editForm.shortComesAndPlan,
        });
        showToast({ message: '保存成功' });
        this.showProjectDialog = false;
        this.getdata();
      } catch (e) {
        showDialog({ message: '保存失败' });
      }
    },
  },
};
</script>

<style lang="css" src="@/views/financeCss.css"></style>

<style scoped></style>
