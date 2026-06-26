import { request } from 'umi';

// 获取项目列表信息
export async function getProList(
  params: {
    current?:Number,
    pageSize?:Number
  }
){
  return request<{
    dataSource: any[],
    columns: any[]
    total:number,
    success?:boolean,
    msg:String
  }>('/api/budget',{
    method:'GET',
    params:{
      ...params,
    }
  })
}

export async function refreshprojectreceived(
  params: {
    contractids:any
  },
  options?: { [key: string]: any },
) {
  return request<any>('/api/invoicingsync/refreshprojectreceived', {
    method: 'GET',
    params,
    ...(options || {}),
  });
}

export async function home(
  params: {
  },
  options?: { [key: string]: any },
) {
  return request<{projecttypes:[],projects:[],tasks:[]}>('/api/budget/home', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function todolist(
  params: {
  },
  options?: { [key: string]: any },
) {
  return request<{projecttypes:[],projects:[],tasks:[]}>('/api/budget/todolist', {
    method: 'GET',
    params,
    ...(options || {}),
  });
}
// 统计数据
export async function getstat(
  params: {
  },
  options?: { [key: string]: any },
) {
  return request<{}>('/api/budget/getstat', {
    method: 'GET',
    params,
    ...(options || {}),
  });
}
export async function getstattotal(
  params: {
  },
  options?: { [key: string]: any },
) {
  return request<{}>('/api/budget/getstattotal', {
    method: 'GET',
    params,
    ...(options || {}),
  });
}
export async function getcatogorystat(
  params: {}
){
  return request<{
    datas: any[],
    col: any[]
    total:number,
    success?:boolean,
    msg:String
  }>('/api/budget/getcatogorystat',{
    method:'GET',
    params:{
      ...params,
    }
  })
}

export async function getheaders(
  params: {
    typename:string
  },
  options?: { [key: string]: any },
) {
  return request<any>('/api/budget/getheaders', {
    method: 'GET',
    params,
    ...(options || {}),
  });
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

