// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
import { TableListItem } from './data';

/** 请销假流程列表接口 */
export async function leaveFlowList(
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
  }>('/api/leave-flow', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 修改请销假流程接口 */
export async function updateLeaveFlow(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<TableListItem>('/api/leave-flow/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建请销假流程接口 */
export async function addLeaveFlow(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request<TableListItem>('/api/leave-flow', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除请销假流程接口 */
export async function removeLeaveFlow(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/leave-flow/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}
