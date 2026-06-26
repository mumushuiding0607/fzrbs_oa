// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 用户账号列表接口 */
export async function getlist(
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
  }>('/api/budget/getbalancelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}



export async function save(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/savebalance', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function updateattachment(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/updateattachment', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}




export async function getbalancedetails(
  params: {
    id?: any;
    projectid?:any;
    relatedcontractid?:any;
  },
  options?: { [key: string]: any },
) {
  return request<{
    basic: {},
    invoices:[],
    contracts:[]
  }>('/api/budget/getbalancedetails', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function delbalance(
  params:{id:any}
){
  return request('/api/budget/delbalance',{
    method:'DELETE',
    params:{...params}
  })
}

