import { request } from 'umi';

// 获取项目列表信息
export async function getdeptlist(
  params: {
    current?:Number,
    pageSize?:Number
  }
){
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/budget/getdeptlist',{
    method:'GET',
    params:{
      ...params,
    }
  })
}
export async function getdeptcode() {
  return request<any>('/api/budget/getdeptcode', {
    method: 'GET'
  });
}
export async function savedeptcode(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/savedeptcode', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function refreshprojectdeptcode(
  params: {
    departmentid:Number,
  }
) {
  return request<any>('/api/budget/refreshprojectdeptcode', {
    method: 'GET',
    params
  });
}





