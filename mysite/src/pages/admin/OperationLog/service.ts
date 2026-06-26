// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 列表接口 */
export async function rule(
  params: {
    current?: number;
    pageSize?: number;
    inserttime?: any;
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
  }>('/api/operation-log', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 操作参数接口 */
export async function params(
  params: {
    logid?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    success?: boolean;
  }>('/api/operation-log-param', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 操作类型接口 */
export async function operationType(options?: { [key: string]: any }) {
  return request('/api/operation-log/operation-type', {
    method: 'GET',
    ...(options || {}),
  });
}
