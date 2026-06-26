// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
import { TableListItem,TableQyappItem } from './data';

/** 列表接口 */
export async function flowtemplate(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: TableListItem[];
    total?: number;
    success?: boolean;
  }>('/api/financerole/templatelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 修改接口 */
export async function updateFlowtemplate(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<TableListItem>('/api/financerole/updatetemplate', {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建接口 */
export async function addFlowtemplate(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request<TableListItem>('/api/financerole/createtemplate', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除接口 */

export async function removeFlowtemplate(
  data: { id:any }
) {
  return request('/api/financerole/delflowrole', {
    data,
    method: 'DELETE',
  });
}

// 获取应用
export async function getQyapp(params: { }, options?: { [key: string]: any }) {
  return request<{data:TableQyappItem[]}>('/api/financerole/getqyapp', {
    method: 'GET',
    ...(options || {}),
  });
}
// 获取选项数据
export async function getDict(params: { }, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/financerole/getdict', {
    method: 'GET',
    ...(options || {}),
  });
}