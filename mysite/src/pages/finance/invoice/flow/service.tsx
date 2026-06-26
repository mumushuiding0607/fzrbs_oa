import { request } from 'umi';
export async function gettmplatelist(
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
  }>('/api/invoicing/gettmplatelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function viewflow(
  params:{infoid?:any,act?:any}
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/invoicing/viewflow',{
    method: 'GET',
    params:{...params}
  })
}
export async function getthirdno() {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/invoicing/getthirdno');
}
export async function startflow(data: {}, options?: { [key: string]: any }) {
  return request('/api/invoicing/startflow', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function flowact(params:{thirdNo?:any,speech?:string,act:string}){
  return request<{errorMessage:String,data:{}}>('/api/invoicing/flowact',{
    method: 'GET',
    params:{...params}
  })
}
export async function getflowdata(
  params:{thirdNo?:any,projectid?:any,state?:any,infoid?:any}
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/invoicing/getflowdata',{
    method: 'GET',
    params:{...params}
  })
}
export async function deltemplate(
  params:{id:any}
){
  return request('/api/invoicing/deltemplate',{
    method:'DELETE',
    params:{...params}
  })
}
export async function save(data: {type:any,label:any,userid:any}, options?: { [key: string]: any }) {
  var url = '/api/invoicing/savetemplate'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function canceldelinvoicingnotice(data: {id:any}, options?: { [key: string]: any }) {
  var url = '/api/invoicing/canceldelinvoicingnotice'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function delinvoicingnotice(data: {id:any}, options?: { [key: string]: any }) {
  var url = '/api/invoicing/delinvoicingnotice'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}


