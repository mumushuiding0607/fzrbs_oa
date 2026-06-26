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
  }>('/api/apps/vote', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 获取单条数据 */
export async function viewinfo(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/vote/view-info', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 获取单条数据 */
export async function saveVote(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/vote/save-vote', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
