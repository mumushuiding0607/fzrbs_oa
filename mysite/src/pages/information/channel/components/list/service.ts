// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 列表接口 */
export async function rule(
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
  }>('/api/information-channel', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 修改接口 */
export async function updateRule(
  data: { [id: number]: any;[values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/information-channel/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建接口 */
export async function addRule(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request('/api/information-channel', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除接口 */
export async function removeRule(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/information-channel/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}

/** 移动数据接口 */
export async function cut(
  data: { fromChannelId: number; toChannelId: string; infoIds: string },
  options?: { [key: string]: any },
) {
  return request<Record<string, any>>('/api/information-channel/cut', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 复制数据接口 */
export async function copy(
  data: { fromChannelId: number; toChannelId: string; infoIds: string },
  options?: { [key: string]: any },
) {
  return request<Record<string, any>>('/api/information-channel/copy', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}
