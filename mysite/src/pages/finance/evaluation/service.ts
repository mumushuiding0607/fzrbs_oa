import { request } from 'umi';
import { downloadAsXlSX } from '../utils';

// 获取评分参数配置
export async function getConfig(params?: { type?: string }) {
  return request<any>('/api/evaluation/getconfig', {
    method: 'GET',
    params,
  });
}

// 获取被考评部门列表
export async function getDepartments() {
  return request<any>('/api/evaluation/getdepartments', {
    method: 'GET',
  });
}

// 获取当前用户的考评任务列表
export async function getTasks(params: {
  year?: number;
  quarter?: number;
  status?: number;
  keyword?: string;
  current?: number;
  pageSize?: number;
}) {
  return request<any>('/api/evaluation/gettasks', {
    method: 'POST',
    params,
  });
}

// 删除考评任务
export async function deleteTask(data: { task_id: number }) {
  return request<any>('/api/evaluation/deletetask', {
    method: 'POST',
    data,
  });
}

// 获取任务详情
export async function getTaskDetail(data: { task_id: number }) {
  return request<any>('/api/evaluation/gettaskdetail', {
    method: 'POST',
    data,
  });
}

// 生成季度考评任务
export async function generateTasks(data: {
  year: number;
  quarter: number;
  start_date: string;
  end_date: string;
}) {
  return request<any>('/api/evaluation/generatetasks', {
    method: 'POST',
    data,
  });
}

// 提交评分
export async function submitScore(data: {
  task_id: number;
  score: number;
  opinion?: string;
  is_personal?: number;
  target_user_id?: string;
  target_opinion?: string;
}) {
  return request<any>('/api/evaluation/submitscore', {
    method: 'POST',
    data,
  });
}

// 超时自动打分
export async function autoScore(data: { year?: number; quarter?: number }) {
  return request<any>('/api/evaluation/autoscore', {
    method: 'POST',
    data,
  });
}

// 获取扣款明细
export async function getPenalties(params: { year?: number; quarter?: number }) {
  return request<any>('/api/evaluation/getpenalties', {
    method: 'GET',
    params,
  });
}

// 获取预警名单
export async function getWarnings(params: { year?: number }) {
  return request<any>('/api/evaluation/getwarnings', {
    method: 'GET',
    params,
  });
}

// 导出扣款明细
export function exportPenalties(params: { year?: number; quarter?: number }) {
  window.open(`/api/evaluation/exportpenalties?year=${params.year || new Date().getFullYear()}&quarter=${params.quarter || Math.ceil((new Date().getMonth() + 1) / 3)}`);
}

// 导出预警名单
export function exportWarnings(params: { year?: number }) {
  window.open(`/api/evaluation/exportwarnings?year=${params.year || new Date().getFullYear()}`);
}

// 导出评分任务汇总
export function exportTasks(params: { year?: number; quarter?: number }) {
  window.open(`/api/evaluation/exporttasks?year=${params.year || new Date().getFullYear()}&quarter=${params.quarter || Math.ceil((new Date().getMonth() + 1) / 3)}`);
}

// 计算扣款
export async function calculateDeduction(data: { year?: number; quarter?: number }) {
  return request<any>('/api/evaluation/calculatededuction', {
    method: 'POST',
    data,
  });
}

// 生成50分预警
export async function generateWarnings(data: { year?: number }) {
  return request<any>('/api/evaluation/generatewarnings', {
    method: 'POST',
    data,
  });
}

// 保存扣罚录入
export async function savePenaltyInput(data: {
  year: number;
  quarter: number;
  items: {
    dept_id: number;
    dept_name: string;
    total_deduct: number;
    dept_person_count: number;
    persons: {
      user_id: string;
      user_name: string;
      deduct_type: number;
      post_performance?: number;
      fixed_deduct?: number;
    }[];
  }[];
}) {
  return request<any>('/api/evaluation/savepenaltyinput', {
    method: 'POST',
    data,
  });
}

// 计算分摊扣款
export async function calculatePenalty(data: { year: number; quarter: number }) {
  return request<any>('/api/evaluation/calculatepenalty', {
    method: 'POST',
    data,
  });
}

// 获取扣款结果
export async function getPenaltyResult(params: {
  year?: number;
  quarter?: number;
  dept_id?: number;
}) {
  return request<any>('/api/evaluation/getpenaltyresult', {
    method: 'GET',
    params,
  });
}

// 导出扣款结果Excel
export function exportPenaltyResult(params: { year?: number; quarter?: number }) {
  window.open(`/api/evaluation/exportpenalty?year=${params.year || new Date().getFullYear()}&quarter=${params.quarter || Math.ceil((new Date().getMonth() + 1) / 3)}`);
}

// 获取打印数据
export async function getPrintData(params: { year: number; quarter: number }) {
  return request<any>('/api/evaluation/getprintdata', {
    method: 'GET',
    params,
  });
}

// 导出打印数据为xlsx
export async function exportPrintDataXlsx(params: { year: number; quarter: number }) {
  const res: any = await getPrintData(params);
  if (res.message) {
    return;
  }
  const printData = res.data;
  if (!printData) return;

  // 构建Excel数据
  const data: any[][] = [];

  // 表头：评分人 + 部门名称 + 部门意见 + 个人意见
  const header = ['评分人'];
  printData.departments?.forEach((dept: any) => {
    header.push(dept.name);
  });
  header.push('对社直部门的批评意见（列出具体事例、提出意见建议）', '对社直员工的批评意见（含对其个人的考评分数）');
  data.push(header);

  // 数据行
  printData.scorers?.forEach((scorer: any) => {
    const row = [scorer.scorer_name || ''];
    printData.departments?.forEach((dept: any) => {
      const scoreInfo = scorer.scores?.[dept.id];
      row.push(scoreInfo?.score != null ? `${scoreInfo.score}分` : '-');
    });
    row.push(scorer.dept_opinion || '', scorer.personal_opinion || '');
    data.push(row);
  });

  // 最后一行：平均分 + 各部门平均分
  const avgRow = ['平均分'];
  printData.departments?.forEach((dept: any) => {
    const avg = printData.avg_scores?.[dept.id];
    avgRow.push(avg != null && avg !== undefined ? `${avg}分` : '-');
  });
  avgRow.push('', '');
  data.push(avgRow);

  downloadAsXlSX(data, `社直行政后勤部门考评表_${params.year}年第${params.quarter}季度`);
}

// 获取考评操作日志
export async function getOperationLogs(params: {
  bizId?: any;
  current?: number;
  pageSize?: number;
}) {
  return request<any>('/api/evaluation/getoperationlogs', {
    method: 'GET',
    params,
  });
}

// 获取评分人排序列表
export async function getEvaluatorSeq() {
  return request<any>('/api/evaluation/getevaluatorseq', {
    method: 'GET',
  });
}

// 保存评分人排序
export async function saveEvaluatorSeq(data: { orders: { userid: string; seq: number }[] }) {
  return request<any>('/api/evaluation/saveevaluatorseq', {
    method: 'POST',
    data,
  });
}
