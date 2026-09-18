import { storeToRefs } from 'pinia';
import { request } from '../../utils/request';
import { useUserStore } from '@/stores';

const { userInfo } = storeToRefs(useUserStore());

// ==================== 用户信息 ====================

// 获取当前用户信息
export const getYxkhUserinfo = () => request('yxkh/getuserinfo', { wxuserid: userInfo.value?.userId }, 'post');

// 获取单个考核记录（用于编辑）
export const getYxkhExt = (eid: number) => request('yxkh/getext', { eid, wxuserid: userInfo.value?.userId }, 'post');

// ==================== 考核记录 ====================

// 考核列表
export const getYxkhList = (params: {
  uid?: string;
  sparation?: string;
  selfEvaluation?: string;
  keyword?: string;
  state?: number;
  current?: number;
  pageSize?: number;
}) => request('yxkh/getlist', { ...params, wxuserid: userInfo.value?.userId }, 'post');

// 保存考核记录
export const saveYxkh = (data: {
  eid?: number;
  startDate: string;
  endDate: string;
  sparation: string;
  selfEvaluation?: string;
  attendance?: string;
  shortComesAndPlan?: string;
  totalMark?: string;
  marks?: string;
}) => request('yxkh/save', { ...data, wxuserid: userInfo.value?.userId }, 'post');

// 启动审批流
export const startYxkhFlow = (data: {
  eid: number;
  templateId: string;
  title: string;
}) => request('yxkh/startflow', { ...data, wxuserid: userInfo.value?.userId }, 'post');

// 获取流程预览
export const getYxkhFlow = (data?: {
  eid?: number;
  templateId?: string;
}) => request('yxkh/getflow', { ...data, wxuserid: userInfo.value?.userId }, 'post');

// 审批操作
export const flowActYxkh = (data: {
  thirdNo: string;
  act: 'agree' | 'reject' | 'cancel';
  speech?: string;
}) => request('yxkh/flowact', { ...data, wxuserid: userInfo.value?.userId }, 'post');

// 删除流程
export const delYxkhFlow = (processInstanceId: string) =>
  request('yxkh/delflow', { processInstanceId, wxuserid: userInfo.value?.userId }, 'post');

// 流程详情
export const viewYxkhFlow = (processInstanceId: string) =>
  request('yxkh/viewflow', { processInstanceId, wxuserid: userInfo.value?.userId }, 'post');

// ==================== 项目管理 ====================

// 获取项目列表
export const getYxkhProjects = (params: {
  userId?: string;
  startDate: string;
  endDate: string;
}) => request('yxkh/getprojects', { ...params, wxuserid: userInfo.value?.userId }, 'post');

// 保存项目
export const saveYxkhProject = (data: {
  projectId?: number;
  projectContent: string;
  progress: string;
  startDate: string;
  endDate: string;
  userId?: string;
}) => request('yxkh/savesproject', { ...data, wxuserid: userInfo.value?.userId }, 'post');

// 删除项目
export const delYxkhProject = (ids: number[]) =>
  request('yxkh/delproject', { ids, wxuserid: userInfo.value?.userId }, 'post');

// ==================== 加减分管理 ====================

// 保存加减分
export const saveYxkhMark = (data: {
  markId?: number;
  projectId: number;
  markNumber: string;
  markReason: string;
  accordingly: string;
  startDate: string;
  endDate: string;
}) => request('yxkh/savemark', { ...data, wxuserid: userInfo.value?.userId }, 'post');

// 删除加减分
export const delYxkhMark = (ids: number[]) =>
  request('yxkh/delmark', { ids, wxuserid: userInfo.value?.userId }, 'post');
