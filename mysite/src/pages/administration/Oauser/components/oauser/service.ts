// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
// import { TableListItem } from './data';
import token from '@/utils/token';

/** 列表接口 */
export async function rule(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/oauser', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

// 获取用户数据
export async function getValueEnum(data: {}, options?: { [key: string]: any }) {
  return request('/api/oauser/get-value-enum', {
    method: 'GET',
    ...(options || {}),
  });
}
export async function getQualificationList(data: {}, options?: { [key: string]: any }) {
  return request('/api/oauser/get-qualification', {
    method: 'GET',
    params: data,
    ...(options || {}),
  });
}
export async function saveQualificationRule(data: {}, options?: { [key: string]: any }) {
  return request('/api/oauser/save-qualification', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 错误列表接口 */
// export async function errorRule(
//   params: {
//     current?: number;
//     pageSize?: number;
//   },
//   options?: { [key: string]: any },
// ) {
//   return request<{
//     data: [];
//     total?: number;
//     success?: boolean;
//   }>('/api/error-record?type=3', {
//     method: 'GET',
//     params: {
//       ...params,
//     },
//     ...(options || {}),
//   });
// }

/** 导入绩效接口 */
export async function importData(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  console.log(data);
  console.log(options);
  return request<any>('/api/oauser/import', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
/** 修改接口 */
export async function updateRule(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<any>('/api/oauser/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建接口 */
export async function createRule(
  data: { [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<any>('/api/oauser', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除接口 */
export async function removeRule(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/oauser/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}
/** 签发接口 */
// export async function signRule(data: { id: number[] }, options?: { [key: string]: any }) {
//   return request<Record<string, any>>('/api/salary-bonus/sign', {
//     method: 'POST',
//     params:data,
//     ...(options || {}),
//   });
// }
/** 移动数据接口 */
export async function cut(
  data: { fromId: number; toId: number; infoIds: any[] },
  options?: { [key: string]: any },
) {
  return request<Record<string, any>>('/api/oauser/cut', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}
//导出数据
export async function download(params: {}) {
  // params['depId'] =depId;

  fetch('/api/oauser/export', {
    method: 'POST',
    body: window.JSON.stringify(params),
    credentials: 'include',
    headers: new Headers({ 'Content-Type': 'application/json', Authorization: token.get() }),
  })
    .then((response) => {
      response.blob().then((blob) => {
        const aLink = document.createElement('a');
        document.body.appendChild(aLink);
        aLink.style.display = 'none';
        const objectUrl = window.URL.createObjectURL(blob);
        aLink.href = objectUrl;
        aLink.download = '职员信息导出';
        aLink.click();
        document.body.removeChild(aLink);
      });
    })
    .catch((error) => {
      console.log(error);
      // message.error('下载失败，请重试');
    });
}

/** tab访问权限 */
export async function accessTab(data: { tabId: number }, options?: { [key: string]: any }) {
  console.log(data);
  return request('/api/common/access-tab', {
    method: 'POST',
    data,
    ...(options || {}),
  });
}

/** 错误列表接口 */
export async function errorRule(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    total?: number;
    success?: boolean;
  }>('/api/error-record?type=4', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
