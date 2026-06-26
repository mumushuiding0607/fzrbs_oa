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
  }>('/api/app-interface', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 修改接口 */
export async function updateRule(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/app-interface/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建接口 */
export async function addRule(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request('/api/app-interface', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除接口 */
export async function removeRule(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/app-interface/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}
