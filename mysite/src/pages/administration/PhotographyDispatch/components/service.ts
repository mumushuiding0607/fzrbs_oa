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
  }>('/api/photography-dispatch/index', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
/** 月度统计列表接口 */
export async function statisticsRule(
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
  }>('/api/photography-dispatch/statistics', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
/** 记者出勤列表接口 */
export async function reportRule(
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
  }>('/api/photography-dispatch/report-list', {
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
  return request<TableListItem>('/api/photography-dispatch/update', {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 新建接口 */
export async function addRule(data: { [values: string]: any }, options?: { [key: string]: any }) {
  return request<TableListItem>('/api/department', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除接口 */
export async function removeRule(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/photography-dispatch/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}
// 获取记者数据
export async function getReport(data: { id: number,begin_time:string,end_time: string}, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/photography-dispatch/get-report', {
    method: 'GET',
    params: {
      ...data,
    },
    ...(options || {}),
  });
}

//导出数据
export async function download(params:{}) {

  fetch('/api/photography-dispatch/export', {
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
      aLink.download = '摄影派工信息导出';
      aLink.click();
      document.body.removeChild(aLink);
    });
  }).catch((error) => {
    console.log(error);
    // message.error('下载失败，请重试');

  });
 } 

 //月度统计-导出数据
export async function monthDownload(params:{}) {

  fetch('/api/photography-dispatch/export-month', {
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
      aLink.download = '摄影派工月度汇总';
      aLink.click();
      document.body.removeChild(aLink);
    });
  }).catch((error) => {
    console.log(error);
    // message.error('下载失败，请重试');

  });
 } 
 //月度统计-明细导出数据
export async function monthDetailDownload(params:{}) {

  fetch('/api/photography-dispatch/export-month-detail', {
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
      aLink.download = '摄影派工月度汇总-明细';
      aLink.click();
      document.body.removeChild(aLink);
    });
  }).catch((error) => {
    console.log(error);
    // message.error('下载失败，请重试');

  });
 } 