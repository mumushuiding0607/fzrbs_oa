import { request } from 'umi';



export async function savedailycheck(data: {}, options?: { [key: string]: any }) {
  return request('/api/dailycheck/savedailycheck', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function saveproblem(data: {}, options?: { [key: string]: any }) {
  return request('/api/dailycheck/saveproblem', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function getproblemstates(){
  return request('/api/dailycheck/getproblemstates', {
    method: 'get'
  });
}
export async function getthirdno(){
  return request('/api/dailycheck/getthirdno', {
    method: 'get'
  });
}
export async function getdailycheck(
  params:{
    thirdNo:any
  }
){
  return request('/api/dailycheck/getdailycheck', {
    method: 'get',
    params
  });
}
export async function getdailychecklist(
  params: {
    current?: number;
    pageSize?: number;
    tabtype?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/dailycheck/getdailychecklist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function getproblems(
  params: {
    current?: number;
    pageSize?: number;
    tabtype?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/dailycheck/getproblems', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function delproblem(
  params:{id:any}
){
  return request('/api/dailycheck/delproblem',{
    method:'DELETE',
    params:{...params}
  })
}
export async function flow(
  params:{
    thirdNo:any,
    speech?:any,
    act:string
  }
){
  return request('/api/dailycheck/flow', {
    method: 'get',
    params
  });
}

export async function getdict(
  params:{
    typeid:any
  }
){
  return request('/api/dailycheck/getdict', {
    method: 'get',
    params
  });
}

export async function getuserbyrolename(
  params:{
    rolename:any
  }
){
  return request('/api/dailycheck/getuserbyrolename', {
    method: 'get',
    params
  });
}


