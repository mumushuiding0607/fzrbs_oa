import { request } from 'umi';

export async function getbalancelog(
  params: {
    projectid: any;
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    total?: number;
    current?: number;
    pageSize?: number;
    errorMessage?: string;
  }>('/api/budget/getbalancelog', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
