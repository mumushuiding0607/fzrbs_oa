// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 用户账号列表接口 */
export async function getlist(
  params: {
    current?: number;
    pageSize?: number;
    contractids?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/contract/getcontractlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function ledgerlist(
  params: {
    current?: number;
    pageSize?: number;
    contractids?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/contract/ledgerlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}



export async function savecontract(data: {}, options?: { [key: string]: any }) {
  return request('/api/contract/savecontract', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function altercharger(data: {}, options?: { [key: string]: any }) {
  return request('/api/contract/altercharger', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}




export async function saveledger(data: {}, options?: { [key: string]: any }) {
  return request('/api/contract/saveledger', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function delcontract(
  params:{id:any,agentid:any}
){
  return request('/api/contract/delcontract',{
    method:'DELETE',
    params:{...params}
  })
}
export async function delledger(
  params:{id:any}
){
  return request('/api/contract/delledger',{
    method:'DELETE',
    params:{...params}
  })
}

export async function getcontract(
  params: {
    id: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/contract/getcontract', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function getledger(
  params: {
    id: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/contract/getledger', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}


export async function previewdebtflow(
  params: any
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/contract/previewdebtflow', {
    method: 'GET',
    params
  });
}
export async function getbykeyword(
  params: {
    keyword: any;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/contract/getbykeyword', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function getbyids(
  params: {
    ids: any;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/contract/getbyids', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function delpaycondition(
  params:{id:any}
){
  return request('/api/contract/delpaycondition',{
    method:'DELETE',
    params:{...params}
  })
}

export async function savepaycollection(data: {}, options?: { [key: string]: any }) {
  return request('/api/contract/savepaycollection', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function delpaycollection(
  params:{id:any,agentid:any,signdeptid:any}
){
  return request('/api/contract/delpaycollection',{
    method:'DELETE',
    params:{...params}
  })
}

export async function haseditauth() {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/contract/haseditauth', {
    method: 'GET',
  });
}

export async function getlogs(
  params: {
    id: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/contract/getlogs', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function getlog(
  params: {
    id: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/contract/getlog', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function lock(
  params: {
    id: number;
    state:number,
    agentid:number
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/contract/lock', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function nullify(
  params: {
    id: number
    agentid:number
  }
) {
  return request<{
    errorMessage:String;
  }>('/api/contract/nullify', {
    method: 'GET',
    params: {
      ...params,
    }
  });
}
export async function getpowers(
  params: {
    agentid:number
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/financerole/getpowers', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function getheaders() {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/contract/getheaders', {
    method: 'GET',
  });
}
// 发票
export async function saveinvoice(data: {}, options?: { [key: string]: any }) {
  return request('/api/contract/saveinvoice', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function getinvoicelist(
  params: {
    current?: number;
    pageSize?: number;
    contractid?:number
    invoicingid?:number
    projectid?:number
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
export async function delinvoice(
  params:{id:any}
){
  return request('/api/invoicing/delinvoice',{
    method:'DELETE',
    params:{...params}
  })
}


export async function getdebt(
  params:{companyid:any}
) {
  return request<{
    
    errorMessage:String;
  }>('/api/contract/getdebt', {
    method: 'GET',
    params
  });
}

export async function paycollectioncheck(
  params:{id:any,agentid:any,note?:any}
){
  return request('/api/budget/paycollectioncheck',{
    method:'get',
    params:{...params}
  })
}
export async function delpaycollectioncheck(
  params:{id:any,agentid:any}
){
  return request('/api/budget/delpaycollectioncheck',{
    method:'get',
    params:{...params}
  })
}


export async function paycollectionnotice(
  params:{id:any}
){
  return request('/api/budget/paycollectionnotice',{
    method:'get',
    params:{...params}
  })
}
export async function downloadpurchase(
  params:{}
){
  return request('/api/contract/downloadpurchase',{
    method:'get',
    params:{...params}
  })
}



