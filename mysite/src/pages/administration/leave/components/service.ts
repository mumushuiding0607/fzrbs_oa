// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
import { TableListItem,DictItem } from './data';

/** 请销假列表接口 */
export async function leaveList(
  params: {
    current?: number;
    pageSize?: number;
    leaveStarttime?: any;
    leaveEndtime?: any;
    inserttime?: any;
    module?: any;
  },
  options?: { [key: string]: any },
) {
  // if (params.leaveStarttime) {
  //   params.leaveStarttime = params.leaveStarttime.join(',');
  // }
  // if (params.leaveEndtime) {
  //   params.leaveEndtime = params.leaveEndtime.join(',');
  // }
  if (params.inserttime) {
    params.inserttime = params.inserttime.join(',');
  }
  return request<{
    data: TableListItem[];
    total?: number;
    success?: boolean;
  }>('/api/leave', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 修改请销假接口 */
export async function updateLeave(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<TableListItem>('/api/leave/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建请销假接口 */
export async function addLeave(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request<TableListItem>('/api/leave', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除请销假接口 */
export async function removeLeave(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/leave/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}


/** 字典数据接口 */
export async function dict(
  options?: { [key: string]: any },
) {
  return request<{
    data: DictItem;
    success?: boolean;
  }>('/api/leave/dict', {
    method: 'GET',
    ...(options || {}),
  });
}

/** 权限数据接口 */
export async function auth(options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/leave/auth', {
    method: 'GET',
    ...(options || {}),
  });
}