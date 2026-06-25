// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

// 项目全生命周期时间轴接口
export async function getprotimeline(
  params: {
    projectid: any;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    project?: any;
    errorMessage?: string;
  }>('/api/budget/getprotimeline', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
