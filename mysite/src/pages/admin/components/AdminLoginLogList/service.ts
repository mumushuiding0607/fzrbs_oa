// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 用户登录日志列表接口 */
export async function rule(
  params: {
    current?: number;
    pageSize?: number;
    inserttime: any;
  },
  options?: { [key: string]: any },
) {
  if (params.inserttime) {
    params.inserttime = params.inserttime.join(',');
  }
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/admin-login-log', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
