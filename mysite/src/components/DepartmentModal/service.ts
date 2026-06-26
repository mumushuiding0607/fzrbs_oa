// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 用户搜索 */
export async function searchUser(data: any, options?: { [key: string]: any }) {
  return request('/api/common/search-user', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
