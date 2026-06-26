// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 商品分组列表接口 */
export async function groupRule(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/youzan', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 同步商品分组接口 */
export async function asynchronizationGroup(
  data?: any,
  options?: { [key: string]: any },
) {
  return request<Record<string, any>>('/api/youzan/asynchronization-group', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 修改商品分组接口 */
export async function updateGroup(
  data: { [id: number]: any;[values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/youzan/update-group', {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 排序接口 */
export async function sort(data: any, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/youzan/sort', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}