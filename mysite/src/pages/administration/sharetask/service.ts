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
  }>('/api/sharetask/index', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 查看阅读量接口 */
export async function getItems(
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
  }>('/api/sharetask/share-user', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 新增、修改接口 */
export async function updateRule(
  data: { [values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/sharetask/save', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 状态更新接口 */
export async function updateState(
  data: { [values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/sharetask/update-state', {
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
  }>('/api/sharetask/admin-index', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 获取已有的管理员 */
export async function getAdminRule(options?: { [key: string]: any }) {
  return request('/api/sharetask/get-admin-info', {
    method: 'POST',
    ...(options || {}),
  });
}

/** 管理员修改接口 */
export async function updateAdminRule(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/sharetask/admin-save', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 管理员删除接口 */
export async function removeAdminRule(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/sharetask/del-admin', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** tab访问权限 */
export async function accessTab(options?: { [key: string]: any }) {
  return request('/api/sharetask/access-tab', {
    method: 'GET',
    ...(options || {}),
  });
}

/** 按人员统计 */
export async function uTotall(
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
  }>('/api/sharetask/utotal-list', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 按任务统计 */
export async function totall(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    clickTotal: any;
    shareTotal: any;
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/sharetask/total-list', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 相关配置数据接口 */
export async function configRule(options?: { [key: string]: any }) {
  return request('/api/sharetask/config', {
    method: 'GET',
    ...(options || {}),
  });
}
