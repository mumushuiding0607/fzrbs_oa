import { request } from 'umi';




export async function savecompany(data: {}, options?: { [key: string]: any }) {
  return request('/api/company/save', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function getcompany(
  params: {
    company?: any,//公司名称
    id?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/company/getcompany', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
