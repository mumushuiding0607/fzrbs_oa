// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
import { TableListItem,TableRoleItem } from './data';

/** 列表接口 */
export async function flowrole(
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
  }>('/api/flow-role', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 修改接口 */
export async function updateFlowrole(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<TableListItem>('/api/flow-role/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建接口 */
export async function addFlowrole(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request<TableListItem>('/api/flow-role', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除接口 */
export async function removeFlowrole(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/flow-role/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}

// 获取流程角色
export async function getRole(params: { }, options?: { [key: string]: any }) {
  return request<{data:TableRoleItem[]}>('/api/flow-role/getRole', {
    method: 'GET',
    ...(options || {}),
  });
}
// 获取选项数据
export async function getDict(params: { }, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/flow-role/getDict', {
    method: 'GET',
    ...(options || {}),
  });
}