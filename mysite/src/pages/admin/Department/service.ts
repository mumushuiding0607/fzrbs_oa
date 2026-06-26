// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
import { TableListItem } from './data';

/** 列表接口 */
export async function rule(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: TableListItem[];
    total?: number;
    success?: boolean;
  }>('/api/department', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 同步部门数据接口 */
export async function asynchronization(options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/department/asynchronization', {
    method: 'POST',
    ...(options || {}),
  });
}
