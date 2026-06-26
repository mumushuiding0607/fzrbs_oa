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
  }>('/api/budget/getprolist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function getlist2(
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
  }>('/api/budget/getprolist2', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function getprohistory(
  params: {
    projectid:any;
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/budget/getprohistory', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function saveproject(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/saveproject', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function altersubmitdate(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/altersubmitdate', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function alterproreport(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/alterproject', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}


export async function delproject(
  params:{id:any}
){
  return request('/api/budget/delproject',{
    method:'DELETE',
    params:{...params}
  })
}

export async function getprojectbyid(
  params:{
    id:any;
}) {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/budget/getprojectbyid',{
    params,
  });
}
export async function getonlyproject(
  params:{
    id:any;
    field:any
}) {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/budget/getonlyproject',{
    params,
  });
}

export async function getinvoicelist(
  params: {
    current?: number;
    pageSize?: number;
    projectid?:any,contractid?:any,type?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/contract/getinvoicelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function getbalancelist(
  params: {
    current?: number;
    pageSize?: number;
    projectid?:any,type?:any,orderby?:any
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
export async function savebalance(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/savebalance', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function delbalance(
  params:{id:any,projectid:any}
){
  return request('/api/budget/delbalance',{
    method:'DELETE',
    params:{...params}
  })
}
export async function getinvoicecheck(
  params:{
    invoiceno:any;
}) {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/budget/getinvoicecheck',{
    params,
  });
}

export async function getcontractswithprojects(
  params:{
    contractids:any;
}) {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/budget/getcontractswithprojects',{
    params,
  });
}


// 入账

export async function saveenteraccount(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/saveenteraccount', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function delenteraccount(
  params:{id:any}
){
  return request('/api/budget/delenteraccount',{
    method:'DELETE',
    params:{...params}
  })
}
export async function getenteraccount(
  params:{
    bid:any;
    projectid:any
    showAll:any
    type:any
}) {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/budget/getenteraccount',{
    params,
  });
}

// 提交计量
export async function submitmeasure(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/submitmeasure', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function getbalancefileurls(
  params:{
    id:any;
}) {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/budget/getbalancefileurls',{
    params,
  });
}
export async function getallfileurs(
  params:{
    projectid:any;
}) {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/budget/getallfileurs',{
    params,
  });
}

export async function getreportbyprojectid(
  params:{
    id:any;
    field:any
}) {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/budget/getreportbyprojectid',{
    params,
  });
}
export async function startpayment(
  params:{
    bid:any;
}) {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/budget/startpayment',{
    params,
  });
}

export async function lockpro(
  params:{
    id:any;
}) {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/budget/lockpro',{
    params,
  });
}
export async function altercreator(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/altercreator', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}




