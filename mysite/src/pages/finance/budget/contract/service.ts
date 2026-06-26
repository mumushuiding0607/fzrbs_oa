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
  }>('/api/budget/getcontractlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}


export async function savecontract(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/savecontract', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function delcontract(
  params:{}
){
  return request('/api/budget/delcontract',{
    method:'DELETE',
    params:{...params}
  })
}



