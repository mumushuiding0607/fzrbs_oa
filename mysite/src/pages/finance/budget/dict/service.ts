import { request } from 'umi';
import { Dict } from './data';


export async function getBykeyword(
  params:{
    keyword?:String;
    showall?:boolean; // 不管部门，显示全部
    userid?:String; // 显示指定用户创建的
    creator?:String;
    agentid?:any
    order?:any,
    subtype?:any
  }
){
  var result = request<{
    data:Dict[]
  }>('/api/budget/getbykeyword',{
    method:'GET',
    params
  })

  return  result
}

export async function savedict(data: {type:any,label:any,userid?:any,value?:any}, options?: { [key: string]: any }) {
  var url = '/api/budget/savedict'
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function savepower(data: {type:any,label:any,userid:any}, options?: { [key: string]: any }) {
  var url = '/api/budget/savepower'
  return request(url, {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function getdictlist(
  params: {
    current?: number;
    pageSize?: number;
    type?: string;
    agentid?:any
    orderby?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/budget/getdictlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function deldict(
  params:{id:any}
){
  return request('/api/budget/deldict',{
    method:'DELETE',
    params:{...params}
  })
}

export async function getdicttypes(
  params:{
    keyword?:String;
  }
){
  var result = request<{
    data:Dict[]
  }>('/api/budget/getdicttypes',{
    method:'GET',
    params
  })

  return  result
}