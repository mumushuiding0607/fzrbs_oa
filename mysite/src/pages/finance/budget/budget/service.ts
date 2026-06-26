// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
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
  }>('/api/budget/approvallist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function getthirdno() {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/budget/getthirdno');
}
export async function getprojectbythirdno(
  params:{
    thirdno:any
  }
){
  return request<{
    errorMessage: string,
    data:{}
  }>('/api/budget/getprojectbythirdno',{
    method:'GET',params:{...params}
  });
}
export async function getsingleprojectbyid(
  params:{
    id:any
  }
){
  return request<{
    errorMessage: string,
    data:{}
  }>('/api/budget/getsingleprojectbyid',{
    method:'GET',params:{...params}
  });
}

export async function getbudgetinfo(
  params: {
    id?: any;
  },
  options?: { [key: string]: any },
) {
  return request<{
    errorMessage: string,
    budget:[]
  }>('/api/budget/getbudgetinfo', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function startflow(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/startflow', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function getflowinfo(
  params:{thirdNo?:any,projectid?:any,state?:any}
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/budget/getflowdata',{
    method: 'GET',
    params:{...params}
  })
}
export async function flowact(params:{thirdNo?:any,speech?:string,act:string}){
  return request<{errorMessage:String,data:{}}>('/api/budget/flowact',{
    method: 'GET',
    params:{...params}
  })
}

// 根据角色查询用户
export async function getuserbyrole(
  params:{
    rolename?:any
    departmentid?:any
  }
){
  return request<{
    errorMessage: string,
    data:{}
  }>('/api/budget/getuserbyrole',{
    method:'GET',params:{...params}
  });
}

export async function viewflow(
  params:{projectid?:any,act?:any}
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/budget/viewflow',{
    method: 'GET',
    params:{...params}
  })
}
export async function getFlowinfodata(
  params:{thirdNo:any}
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/budget/getflowinfodata',{
    method: 'GET',
    params:{...params}
  })
}
export async function getfileurlsbycontractids(
  params:{contractids:any}
){
  return request<any>('/api/budget/getfileurlsbycontractids',{
    method: 'GET',
    params:{...params}
  })
}






