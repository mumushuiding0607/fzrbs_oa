// @ts-ignore
/* eslint-disable */
import { request } from 'umi';


export async function getapps(
  params: {
    
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/budget/getapps', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}


