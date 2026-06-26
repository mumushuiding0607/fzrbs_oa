// @ts-ignore
/* eslint-disable */
import { request } from 'umi';


export async function getrolelist(
  params: {
    current?: number;
    pageSize?: number;
    userid?:any,
    role?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/financerole/getrolelist', {
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
  return request('/api/financerole/delrole',{
    method:'DELETE',
    params:{...params}
  })
}
export async function saveflowrole(data: {}, options?: { [key: string]: any }) {
  return request('/api/financerole/saveflowrole', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function saverole(data: {}, options?: { [key: string]: any }) {
  return request('/api/financerole/saverole', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function getrole(
  params:{
    type?:String;
    agentid:any
  }
){
  var result = request<{
    data:[]
  }>('/api/financerole/getrole',{
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
  }>('/api/financerole/hasrole',{
    method:'GET',
    params
  })

  return  result
}
export async function getpowers(
  params: {
    agentid:number
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/financerole/getpowers', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function getapps() {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/financerole/getapps', {
    method: 'GET',
  });
}
export async function getappoptions() {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/financerole/getappoptions', {
    method: 'GET',
  });
}




