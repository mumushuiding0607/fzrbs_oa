// @ts-ignore
/* eslint-disable */
import { MenuDataItem } from '@ant-design/pro-components';
import { request } from 'umi';

/** 获取当前登录用户信息接口 */
export async function currentUser(options?: { [key: string]: any }) {
  return request<{
    data: API.CurrentUser;
  }>('/api/account/current', {
    method: 'GET',
    ...(options || {}),
  });
}

/** 退出登录接口 */
export async function LoginOut(options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/account/loginOut', {
    method: 'POST',
    ...(options || {}),
  });
}

/** 获取路由菜单接口 */
export async function currentUserMenus() {
  return request<MenuDataItem[]>('/api/account/menu', {
    method: 'GET',
  });
}

/** 删除上传文件 */
export async function uploadDelete(
  data: { [fileurl: string]: any },
  options?: { [key: string]: any },
) {
  return request<MenuDataItem[]>('/api/common/uploadDelete', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
