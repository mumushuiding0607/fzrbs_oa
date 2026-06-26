// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 食堂账号列表接口 */
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
  }>('/api/canteen', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 删除接口 */
export async function removeRule(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/canteen/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}

/** 用户类型数据接口 */
export async function type(options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/canteen/userType', {
    method: 'POST',
    ...(options || {}),
  });
}

/** 设置用户类型数据接口 */
export async function setType(
  data: { typeid: number; userIds: any[] },
  options?: { [key: string]: any },
) {
  return request<Record<string, any>>('/api/canteen/setUserType', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 同步账号数据接口 */
export async function asynchronization(
  data: { [keys: string]: any },
  options?: { [key: string]: any },
) {
  return request<Record<string, any>>('/api/canteen/asynchronization', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 移动数据接口 */
export async function cut(
  data: { fromId: number; toId: number; infoIds: any[] },
  options?: { [key: string]: any },
) {
  return request<Record<string, any>>('/api/canteen/cut', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 充值数据接口 */
export async function recharge(
  data: { id: number; value: number | string },
  options?: { [key: string]: any },
) {
  return request<Record<string, any>>('/api/canteen/recharge', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 充值日志列表接口 */
export async function rechargeLog(
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
  }>('/api/canteen/rechargeLog', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 订单列表接口 */
export async function orders(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request('/api/canteen/orders', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 退单数据接口 */
export async function chargeBack(data: { id: number }, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/canteen/chargeBack', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 菜单类型数据接口 */
export async function menuType(options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/canteen/menuType', {
    method: 'POST',
    ...(options || {}),
  });
}

/** 每月账号余额变动接口 */
export async function accountChange(
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
  }>('/api/canteen/accountChange', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 菜单列表接口 */
export async function menus(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request('/api/canteen/menus', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 新建菜单接口 */
export async function addMenu(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request('/api/canteen/addMenu', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 修改菜单接口 */
export async function updateMenu(
  data: { [id: number]: any;[values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/canteen/updateMenu', {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 删除菜单接口 */
export async function removeMenu(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/canteen/removeMenu?id=' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}

/** 修改菜单状态接口 */
export async function updateMenuStatus(
  data: { [id: number]: any;[values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/canteen/updateMenuStatus', {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 订单统计接口 */
export async function orderSum(
  params: {
    date?: string;
  },
  options?: { [key: string]: any },
) {
  return request('/api/canteen/orderSum', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 生成每月账号余额变动 */
export async function accountChangeCreate(
  params: {
    userType?: string;
    year?: string;
    month?: string;
  },
  options?: { [key: string]: any },
) {
  return request('/api/canteen/accountChangeCreate', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 本月每个部门充值金额 */
export async function excelRechargeTotal(options?: { [key: string]: any }) {
  return request('/api/canteen/excelRechargeTotal', {
    method: 'GET',
    ...(options || {}),
  });
}

/** tab访问权限 */
export async function accessTab(options?: { [key: string]: any }) {
  return request('/api/canteen/accessTab', {
    method: 'GET',
    ...(options || {}),
  });
}

/** 菜品排行 */
export async function menuRanking(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  if (params.ordertime) {
    params.ordertime = params.ordertime.join(',');
  }
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/canteen/menu-ranking', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 用餐人数统计接口 */
export async function peopleSum(
  params: {
    date?: string;
  },
  options?: { [key: string]: any },
) {
  return request('/api/canteen/people-sum', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 同步账号数据接口 */
export async function setting(
  data: { userid?: any, action?: string, typeid?: any, username?: any, id?: any },
  options?: { [key: string]: any },
) {
  return request<Record<string, any>>('/api/canteen/setting', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}
