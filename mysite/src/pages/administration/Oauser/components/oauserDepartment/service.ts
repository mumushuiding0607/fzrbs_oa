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
    data: any;
    total?: number;
    success?: boolean;
  }>('/api/oauser-department/tree-list', {
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
  return request<any>('/api/oauser-department/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}
/** 显示还是隐藏 接口 */
export async function visiableRule(
  data:{ [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<any>('/api/oauser-department/visiable', {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}
/** 移动部门接口 */
export async function moveRule(
  data:{ [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<any>('/api/oauser-department/move', {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}
/** 新建接口 */
export async function createRule(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request<any>('/api/oauser-department', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

