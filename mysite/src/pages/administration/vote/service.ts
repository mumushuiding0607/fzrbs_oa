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
  }>('/api/vote', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 单条数据接口 */
export async function one(
  params: {
    id?: number;
    preview?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    success?: boolean;
  }>('/api/vote/' + params.id + (params?.preview ? '?preview=' + params?.preview : ''), {
    method: 'GET',
    ...(options || {}),
  });
}

/** 修改接口 */
export async function updateRule(
  data: { [id: number]: any;[values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/vote/save', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 排序保存接口 */
export async function sortRule(
  data: any,
  options?: { [key: string]: any },
) {
  return request('/api/vote/sort', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 统计接口 */
export async function countRule(
  data: { [id: number]: any;[state: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/vote/count', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 统计接口 */
export async function voteUserRule(
  data: { [id: number]: any;[state: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/vote/vote-user', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}


/** 删除接口 */
export async function removeRule(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/vote/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}

/** 删除评议项目接口 */
export async function removeSubRule(data: { [id: number]: any;[values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/vote/del-sub', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 发布撤销接口 */
export async function pushRule(data: any, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/vote/push', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 管理员列表接口 */
export async function ruleAdmin(
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
  }>('/api/vote/admin-index', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 管理员修改接口 */
export async function updateAdminRule(
  data: { [id: number]: any;[values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/vote/admin-save', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 管理员删除接口 */
export async function removeAdminRule(
  data: { [id: number]: any;[values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/vote/del-admin', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** tab访问权限 */
export async function accessTab(options?: { [key: string]: any }) {
  return request('/api/vote/access-tab', {
    method: 'GET',
    ...(options || {}),
  });
}

/** 管理员信息接口 */
export async function managerRule(options?: { [key: string]: any }) {
  return request('/api/vote/manager', {
    method: 'GET',
    ...(options || {}),
  });
}

/** 部门用户接口 */
export async function departmentRule(
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
  }>('/api/common/department', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}