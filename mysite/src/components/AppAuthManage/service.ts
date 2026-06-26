// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
import { TableListItem } from './data';

/** 应用权限列表接口 */
export async function appAuthList(
  params: {
    agentid?:number;
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },  
) {
  return request<{
    data: TableListItem[];
    total?: number;
    success?: boolean;
  }>('/api/app-auth', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 修改应用权限接口 */
export async function updateAppAuth(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<TableListItem>('/api/app-auth/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建应用权限接口 */
export async function addAppAuth(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request<TableListItem>('/api/app-auth', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除应用权限接口 */
export async function removeAppAuth(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/app-auth/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}
