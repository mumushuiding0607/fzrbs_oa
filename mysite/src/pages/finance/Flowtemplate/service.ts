import { request } from 'umi';





export async function save(data: {type:any,label:any,userid:any}, options?: { [key: string]: any }) {
  var url = '/api/budget/saveflow'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function financetemplatelist(
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
  }>('/api/financerole/financetemplatelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function yxkhtemplatelist(
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
  }>('/api/financerole/yxkhtemplatelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function attendancetemplatelist(
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
  }>('/api/financerole/attendancetemplatelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function usesealtemplatelist(
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
  }>('/api/financerole/usesealtemplatelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}


export async function delflow(
  params:{id:any}
){
  return request('/api/budget/delflow',{
    method:'DELETE',
    params:{...params}
  })
}
export async function delpayer(
  params:{id:any}
){
  return request('/api/financerole/delpayer',{
    method:'DELETE',
    params:{...params}
  })
}
export async function delfinanceflow(
  params:{id:any}
){
  return request('/api/financerole/delfinanceflow',{
    method:'DELETE',
    params:{...params}
  })
}
export async function delyxkhflow(
  params:{id:any}
){
  return request('/api/financerole/delyxkhflow',{
    method:'DELETE',
    params:{...params}
  })
}
export async function delprintposition(
  params:{id:any}
){
  return request('/api/financerole/delprintposition',{
    method:'DELETE',
    params:{...params}
  })
}
export async function delattendanceflow(
  params:{id:any}
){
  return request('/api/financerole/delattendanceflow',{
    method:'DELETE',
    params:{...params}
  })
}
export async function delusesealflow(
  params:{id:any}
){
  return request('/api/financerole/delusesealflow',{
    method:'DELETE',
    params:{...params}
  })
}








export async function savefinanceflow(data: {type:any,label:any,userid:any}, options?: { [key: string]: any }) {
  var url = '/api/financerole/savefinanceflow'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function saveyxkhflow(data: {type:any,label:any,userid:any}, options?: { [key: string]: any }) {
  var url = '/api/financerole/saveyxkhflow'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function saveprintposition(data: {type:any,label:any,userid:any}, options?: { [key: string]: any }) {
  var url = '/api/financerole/saveprintposition'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}


export async function savepayer(data: {}, options?: { [key: string]: any }) {
  var url = '/api/financerole/savepayer'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function saveattendanceflow(data: {}, options?: { [key: string]: any }) {
  var url = '/api/financerole/saveattendanceflow'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function saveusesealflow(data: {}, options?: { [key: string]: any }) {
  var url = '/api/financerole/saveusesealflow'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function ordertemplatelist(
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
  }>('/api/financerole/ordertemplatelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function delorderflow(
  params:{id:any}
){
  return request('/api/financerole/delorderflow',{
    method:'DELETE',
    params:{...params}
  })
}
export async function saveorderflow(data: {}, options?: { [key: string]: any }) {
  var url = '/api/financerole/saveorderflow'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function getflowdata(
  params:{thirdNo?:any,agentid?:any}
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/financerole/getflowdata',{
    method: 'GET',
    params:{...params}
  })
}

export async function flowback(
  params:{thirdNo?:any,agentid?:any}
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/financerole/flowback',{
    method: 'GET',
    params:{...params}
  })
}
export async function alterspeech(
  params:{thirdNo?:any,agentid?:any,step:any,speech:any,userid?:any}
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/financerole/alterspeech',{
    method: 'GET',
    params:{...params}
  })
}

export async function delflownode(
  params:{thirdNo?:any,step?:any}
){
  return request<{errorMessage:string}>('/api/financerole/delflownode',{
    method: 'GET',
    params:{...params}
  })
}

export async function flowalter(
  params:any
){
  return request<{errorMessage:string}>('/api/financerole/flowalter',{
    method: 'GET',
    params:{...params}
  })
}
export async function flowalteritem(
  params:any
){
  return request<{errorMessage:string}>('/api/financerole/flowalteritem',{
    method: 'GET',
    params:{...params}
  })
}
export async function gettag(
  params:{}
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/financerole/gettag',{
    method: 'GET',
    params:{...params}
  })
}
export async function payerlist(
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
  }>('/api/financerole/payerlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function printpositionlist(
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
  }>('/api/financerole/printpositionlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function getcommonflow(
  params:any
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/financerole/getcommonflow',{
    method: 'GET',
    params:{...params}
  })
}