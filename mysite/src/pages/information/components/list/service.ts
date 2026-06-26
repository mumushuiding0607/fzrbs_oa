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
  }>('/api/information', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 修改接口 */
export async function updateRule(
  data: { [id: number]: any;[values: string]: any },
  options?: { [key: string]: any },
) {
  return request('/api/information/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建接口 */
export async function addRule(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request('/api/information', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除接口 */
export async function removeRule(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/information/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}

/** 单条数据接口 */
export async function one(
  params: {
    id?: number;
    flag?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    success?: boolean;
  }>('/api/information/' + params.id + (params.flag ? '?flag=' + params.flag : ''), {
    method: 'GET',
    ...(options || {}),
  });
}

/** 移动数据接口 */
export async function cut(
  data: { fromChannelId: number; toChannelId: number; infoIds: string },
  options?: { [key: string]: any },
) {
  return request<Record<string, any>>('/api/information/cut', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 撤销数据接口 */
export async function revoke(data: { ids: string }, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/information/revoke', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 签发数据接口 */
export async function issued(data: { ids: string }, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/information/issued', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 排序接口 */
export async function sort(data: any, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/information/sort', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 置顶/取消接口 */
export async function top(data: any, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/information/top', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 回收站删除数据接口 */
export async function recycleBinDelete(data: any, options?: { [key: string]: any }) {
  const id = data.id.join(',');
  return request<Record<string, any>>('/api/information/recycle-bin-delete', {
    method: 'POST',
    data: { id },
    ...(options || {}),
  });
}

/** 回收站还原数据接口 */
export async function recycleBinReduction(data: any, options?: { [key: string]: any }) {
  const id = data.id.join(',');
  return request<Record<string, any>>('/api/information/recycle-bin-reduction', {
    method: 'POST',
    data: { id },
    ...(options || {}),
  });
}

/** 扩展工具按钮数据接口 */
export async function extendToolBar(data?: any, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/information/extend-tool-bar', {
    method: 'POST',
    ...(options || {}),
  });
}

/** 向微信企业应用发送新信息提醒数据接口 */
export async function sendNotice(data: any, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/information/send-notice', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}
