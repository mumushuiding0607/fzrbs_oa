// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
import { TableListItem } from './data';
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
    data: TableListItem[];
    total?: number;
    success?: boolean;
  }>('/api/housing', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}


/** 修改接口 */
export async function updateRule(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<TableListItem>('/api/housing/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建接口 */
export async function addRule(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request<TableListItem>('/api/housing', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除接口 */
export async function removeRule(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/housing/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}

// 获取项目数据
export async function getProject(data: { id: number[] }, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/housing/get-project', {
    method: 'GET',
    ...(options || {}),
  });
}

//导出数据
export async function download(params:{}) {

  fetch('/api/housing/export', {
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
      aLink.download = '租房信息导出';
      aLink.click();
      document.body.removeChild(aLink);
    });
  }).catch((error) => {
    console.log(error);
    // message.error('下载失败，请重试');

  });
 } 
