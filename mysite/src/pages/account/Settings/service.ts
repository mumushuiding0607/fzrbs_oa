// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 用户密码修改接口 */
export async function changePasswordRule(data: any, options?: { [key: string]: any }) {
  return request('/api/account/changePassword', {
    data,
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    ...(options || {}),
  });
}

/** 解除绑定接口 */
export async function unbind(data: any, options?: { [key: string]: any }) {
  return request('/api/account/unbind', {
    data,
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    ...(options || {}),
  });
}
