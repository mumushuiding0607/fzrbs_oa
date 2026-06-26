import { request } from 'umi';


export async function getBykeyword(
  params:{
    keyword?:String;
  }
){
  var result = request<{
    data:any[]
  }>('/api/budget/getcompany',{
    method:'GET',
    params
  })

  return  result
}

export async function savecompany(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/savecompany', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}