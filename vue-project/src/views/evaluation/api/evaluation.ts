import { request } from '@/utils/request';
import { useUserStore } from '@/stores';
import { storeToRefs } from 'pinia';

const { userInfo } = storeToRefs(useUserStore());
const wxuserid = userInfo.value?.userId || ''

export const getConfig = (params?: { type?: string }) => request('evaluation/getconfig', {...params, wxuserid}, 'post')
export const getDepartments = () => request('evaluation/getdepartments', {wxuserid}, 'post')
export const getTasks = (params: { year?: number, quarter?: number, status?: number, pageSize?: number, current?: number }) => request('evaluation/gettasks', {...params, wxuserid}, 'post')
export const getTaskDetail = (params: { task_id: number }) => request('evaluation/gettaskdetail', {...params, wxuserid}, 'post')

// 批量提交主评分
export const batchSubmitScore = (data: {
  task_id: number
  scores: Array<{ dept_id: number; score: number; opinion?: string }>
}) => request('evaluation/batchsubmitscore', {...data, wxuserid}, 'post')

// 提交个人评分
export const submitPersonalScore = (data: {
  task_id: number
  dept_id: number
  target_user_id: string
  score: number
  opinion: string
}) => request('evaluation/personalsubmitscore', {...data, wxuserid}, 'post')

// 删除个人评分
export const deletePersonalScore = (data: { id: number }) => request('evaluation/deletepersonalscore', {...data, wxuserid}, 'post')

// 更新个人评分
export const updatePersonalScore = (data: { score_id: number; score: number; opinion?: string }) => request('evaluation/updatepersonalscore', {...data, wxuserid}, 'post')

// 向后兼容的主评分提交（单个）
export const submitScore = (data: { task_id: number, dept_id: number, score: number, opinion?: string }) => request('evaluation/submitscore', {...data, wxuserid}, 'post')

// 向后兼容的删除（主评分）
export const deleteScore = (data: { score_id: number }) => request('evaluation/deletescore', {...data, wxuserid}, 'post')

// 向后兼容的更新（已废弃，使用 updatePersonalScore）
export const updateScore = (data: { score_id: number, score: number, opinion?: string }) => request('evaluation/updatescore', {...data, wxuserid}, 'post')

export const generateTasks = (data: { year: number, quarter: number, start_date: string, end_date: string }) => request('evaluation/generatetasks', {...data, wxuserid}, 'post')
export const calculateDeduction = (data: { year: number, quarter: number }) => request('evaluation/calculatededuction', {...data, wxuserid}, 'post')
export const generateWarnings = (data: { year: number }) => request('evaluation/generatewarnings', {...data, wxuserid}, 'post')
export const getWarnings = (params: { year: number }) => request('evaluation/getwarnings', {...params, wxuserid}, 'get')
