// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
import { TableListItem } from './data';

/** 用户账号列表接口 */
export async function rule(
  params: {
    current?: number;
    pageSize?: number;
    lastlogintime?: any;
    inserttime?: any;
  },
  options?: { [key: string]: any },
) {
  if (params.lastlogintime) {
    params.lastlogintime = params.lastlogintime.join(',');
  }
  if (params.inserttime) {
    params.inserttime = params.inserttime.join(',');
  }
  return request<{
    data: TableListItem[];
    total?: number;
    success?: boolean;
  }>('/api/admin', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 修改用户接口 */
export async function updateRule(
  data: { [id: number]: any;[values: string]: any },
  options?: { [key: string]: any },
) {
  return request<TableListItem>('/api/admin/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建用户接口 */
export async function addRule(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request<TableListItem>('/api/admin', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除用户接口 */
export async function removeRule(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/admin/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}

/** 获取用户角色接口 */
export async function role(data: { username: string }, options?: { [key: string]: any }) {
  return request<TableListItem>('/api/admin/role', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
/** 保存用户角色接口 */
export async function saveRole(data: { username: string, [userRoleId: string]: any }, options?: { [key: string]: any }) {
  return request<TableListItem>('/api/admin/save-role', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
