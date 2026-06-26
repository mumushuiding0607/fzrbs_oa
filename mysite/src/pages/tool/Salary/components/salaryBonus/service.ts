// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
import { TableListItem } from './data';
import token from '@/utils/token';

/** 部门树接口 */
export async function depRule(
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
  }>('/api/department', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
/** 列表接口 */
export async function rule(
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
  }>('/api/salary-bonus', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 导入绩效接口 */
export async function importData(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  console.log(data);
  console.log(options);
  return request<any>('/api/salary-bonus/import', {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}
/** 修改接口 */
export async function updateRule(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<TableListItem>('/api/salary-bonus/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建接口 */
// export async function addRule(data: { [values: string]: any }, options?: { [key: string]: any }) {
//   return request<TableListItem>('/api/department', {
//     data,
//     method: 'POST',
//     ...(options || {}),
//   });
// }

/** 删除接口 */
export async function removeRule(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/salary-bonus/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}
/** 签发接口 */
export async function signRule(data: { id: number[] }, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/salary-bonus/sign', {
    method: 'POST',
    params:data,
    ...(options || {}),
  });
}

//导出数据
export async function download(params:{},depId:number) {
  params['depId'] =depId;

  fetch('/api/salary-bonus/export', {
    method: 'POST',
    body: window.JSON.stringify(params),
    credentials: 'include',
    headers: new Headers({'Content-Type': 'application/json','Authorization':token.get()})
  }).then((response) => {
    response.blob().then(blob => {
      const aLink = document.createElement('a');
      document.body.appendChild(aLink);
      aLink.style.display='none';
      const objectUrl = window.URL.createObjectURL(blob);
      aLink.href = objectUrl;
      aLink.download = '职工奖金信息导出';
      aLink.click();
      document.body.removeChild(aLink);
    });
  }).catch((error) => {
    console.log(error);
    // message.error('下载失败，请重试');

  });
 } 

/** 奖金类型数据接口 */
export async function types(
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    success?: boolean;
  }>('/api/salary-bonus/types', {
    method: 'GET',
    ...(options || {}),
  });
}

/** 权限数据接口 */
export async function auth(options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/salary/auth', {
    method: 'GET',
    ...(options || {}),
  });
}