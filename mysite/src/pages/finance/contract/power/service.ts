// @ts-ignore
/* eslint-disable */
import { request } from 'umi';


export async function getrolelist(
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
  }>('/api/budget/getrolelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function delrole(
  params:{id:any}
){
  return request('/api/budget/delrole',{
    method:'DELETE',
    params:{...params}
  })
}
export async function saveflowrole(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/saveflowrole', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function saverole(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/saverole', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function getrole(
  params:{
    keyword?:String;
  }
){
  var result = request<{
    data:[]
  }>('/api/budget/getrole',{
    method:'GET',
    params
  })

  return  result
}
export async function hasrole(
  params:{
    rolename:String;
    dept?:String
  }
){
  var result = request<{
    data:boolean,
    errorMessage:string
  }>('/api/budget/hasrole',{
    method:'GET',
    params
  })

  return  result
}