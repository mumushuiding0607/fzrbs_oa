// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 内容提交 */
export async function save(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/suggest/save', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 分类*/
export async function getType(options?: { [key: string]: any }) {
  return request('/api/apps/suggest/type', {
    method: 'GET',
    ...(options || {}),
  });
}
