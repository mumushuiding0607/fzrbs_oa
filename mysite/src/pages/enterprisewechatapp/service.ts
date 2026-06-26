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
  }>('/api/apps/apps', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 我的应用操作接口 */
export async function Myapps(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/apps/my-apps', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
