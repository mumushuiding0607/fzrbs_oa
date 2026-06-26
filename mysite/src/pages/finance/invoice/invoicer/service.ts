// @ts-ignore
/* eslint-disable */
import { request } from 'umi';


export async function getinvoicerlist(
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
  }>('/api/invoicing/getinvoicerlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function delrole(
  params:{id:any,agentid:any}
){
  return request('/api/invoicing/delrole',{
    method:'DELETE',
    params:{...params}
  })
}
export async function saveinvoicer(data: {}, options?: { [key: string]: any }) {
  return request('/api/invoicing/saveinvoicer', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function delinvoicer(data: {}, options?: { [key: string]: any }) {
  return request('/api/invoicing/delinvoicer', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
