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
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/apps/canteen', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** config data */
export async function getConfigData(options?: { [key: string]: any }) {
  return request('/api/apps/canteen/config-data', {
    method: 'GET',
    ...(options || {}),
  });
}

/** 保存订单 */
export async function saveOrder(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/canteen/save-order', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 食堂账号信息 */
export async function account(options?: { [key: string]: any }) {
  return request('/api/apps/canteen/account', {
    method: 'GET',
    ...(options || {}),
  });
}

/** 我的订单 */
export async function myOrder(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request('/api/apps/canteen/my-order', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 充值日志 */
export async function rechargeLog(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request('/api/apps/canteen/recharge-log', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 订单代领 */
export async function shareOrder(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/canteen/share-order', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 订单转让 */
export async function sellOrder(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/canteen/sell-order', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 订单取消关闭 */
export async function closeOrder(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/canteen/close-order', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
