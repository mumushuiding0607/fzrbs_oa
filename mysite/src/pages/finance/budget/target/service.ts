// @ts-ignore
/* eslint-disable */
import { request } from 'umi';


export async function gettargetlist(
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
  }>('/api/budget/gettargetlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function deltarget(
  params:{id:any}
){
  return request('/api/budget/deltarget',{
    method:'DELETE',
    params:{...params}
  })
}

export async function savetarget(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/savetarget', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function getsettargetdeparts(
  params:{
    year:number;
  }
){
  var result = request<{
    data:{}
  }>('/api/budget/getsettargetdeparts',{
    method:'GET',
    params
  })

  return  result
}

