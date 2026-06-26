import { request } from 'umi';





export async function save(data: {type:any,label:any,userid:any}, options?: { [key: string]: any }) {
  var url = '/api/budget/saveflow'
 
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

// 预览流程
export async function getflow(
  params:any
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/budget/getflow',{
    method: 'GET',
    params:{...params}
  })
}
export async function getinvoicingflow(
  params:any
){
  return request<{errorMessage:string,basic:{},viewdata:{}}>('/api/invoicing/viewflow',{
    method: 'GET',
    params:{...params}
  })
}



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
  }>('/api/budget/getflowlist', {
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

