// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 用户账号列表接口 */
export async function debtlist(
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
  }>('/api/contract/debtlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function paycollectionlist(
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
  }>('/api/contract/paycollectionlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function getvalidpaycollections(
  params: any,
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
  }>('/api/contract/getvalidpaycollections', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function geturgelogs(
  params: {
    contractid?: number;
    debturgeid?:any;
    type?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/contract/geturgelogs', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

// 催收日志分页列表
export async function urgelogslist(
  params: {
    current?: number;
    pageSize?: number;
    contractid?: number;
    debturgeid?: any;
    type?: any;
    creator?: string;
    urgetype?: any;
    urgeresult?: any;
    datestart?: string;
    dateend?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/contract/urgelogslist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function geturges(
  params: {
    debturgeid?:any;

  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
  }>('/api/contract/geturges', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}


export async function debturge(
  params: {
    id:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/contract/debturge', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
// 设置账销案存
export async function setrecoverable(
  params: {
    contractid:any
  },
) {
  return request('/api/contract/setrecoverable', {
    method: 'GET',
    params: {
      ...params,
    }
  });
}
export async function exportdebtlist(
  params: {
    current?: number;
    pageSize?: number;
    contractids?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/contract/exportdebtlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function debtlistbyfield(
  params: {
    current?: number;
    pageSize?: number;
    field?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/contract/debtlistbyfield', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function viewdebt(
  params: {
    id:any,
    debturgeid?:any,
    thirdNo?:any,
    urgeserial?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/contract/viewdebt', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function debtstat(
  params: {
    current?: number;
    pageSize?: number;
    field?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/contract/debtstat', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function startdebturge(data: {}, options?: { [key: string]: any }) {
  return request('/api/contract/startdebturge', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function startdeal(data: {}, options?: { [key: string]: any }) {
  return request('/api/contract/startdeal', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function saveurgelog(data: {}, options?: { [key: string]: any }) {
  return request('/api/contract/saveurgelog', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function updateurge(data: {}, options?: { [key: string]: any }) {
  return request('/api/contract/updateurge', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function debtflowact(params:{thirdNo?:any,speech?:string,act:string}){
  return request<{errorMessage:String,data:{}}>('/api/contract/debtflowact',{
    method: 'GET',
    params:{...params}
  })
}
export async function delurgelog(
  params:{id:any}
){
  return request('/api/contract/delurgelog',{
    method:'DELETE',
    params:{...params}
  })
}

// 删除欠款催收记录
export async function delurge(
  params:{id:any}
){
  return request('/api/contract/delurge',{
    method:'DELETE',
    params:{...params}
  })
}
