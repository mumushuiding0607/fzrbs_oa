// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

// 用印审批单查询
export async function usesealList(
  params: {
    current?: number;
    pageSize?: number;
    start?: string;
    end?: string;
    status?: number;
    userid?: string;
    keyword?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/qyuseseal/list', {
    method: 'POST',
    data: {
      ...params,
    },
    ...(options || {}),
  });
}

// 导出用印审批单
export async function exportUsesealList(
  params: {
    start?: string;
    end?: string;
    status?: number;
    userid?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    errorMessage?: string;
  }>('/api/qyuseseal/export', {
    method: 'POST',
    data: {
      ...params,
    },
    ...(options || {}),
  });
}

// 撤销用印审批单
export async function cancelUseseal(
  params: {
    thirdNo: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    errorMessage?: string;
    data?: any;
  }>('/api/qyuseseal/flowact', {
    method: 'POST',
    data: {
      ...params,
      act: 'cancel'
    },
    ...(options || {}),
  });
}
