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
  if (params.inserttime) {
    params.inserttime = params.inserttime.join(',');
  }
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/suggest', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 类型数据接口 */
export async function type(options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/suggest/type', {
    method: 'POST',
    ...(options || {}),
  });
}
