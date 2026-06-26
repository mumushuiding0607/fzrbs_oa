// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 用户账号列表接口 */
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
  }>('/api/invoicing/getlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function approvallist(
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
  }>('/api/invoicing/approvallist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function getinvoiceitems(
  params: {
    invoicingid: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
  }>('/api/invoicing/getinvoiceitems', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}





export async function addcontract(data: {}, options?: { [key: string]: any }) {
  return request('/api/invoicing/addcontract', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}


export async function saveinvoiceitem(data: {}, options?: { [key: string]: any }) {
  return request('/api/invoicing/saveinvoiceitem', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function saveinvoicing(data: {}, options?: { [key: string]: any }) {
  return request('/api/invoicing/save', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
export async function savepdfinvoice(data: {}, options?: { [key: string]: any }) {
  return request('/api/invoicing/savepdfinvoice', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

export async function delinvoiceitem(
  params:{id:any}
){
  return request('/api/invoicing/delinvoiceitem',{
    method:'DELETE',
    params:{...params}
  })
}
export async function delinvoicing(
  params:{id:any}
){
  return request('/api/invoicing/delinvoicing',{
    method:'DELETE',
    params:{...params}
  })
}
export async function delcontract(
  params:{id:any,contractid:any}
){
  return request('/api/invoicing/delcontract',{
    method:'DELETE',
    params:{...params}
  })
}

export async function getheaders(
  params: {
    typename:string
  },
  options?: { [key: string]: any },
) {
  return request<any>('/api/invoicing/getheaders', {
    method: 'GET',
    params,
    ...(options || {}),
  });
}
export async function getinvoicing(
  params: {
    id: number;
    show?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/invoicing/getbyid', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function getinvoicelist(
  params: {
    current?: number;
    pageSize?: number;
    contractid?:number
    invoicingid?:number
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/invoicing/getinvoicelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
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
export async function getpaycollection(
  params: {
    EIid?:any,
    contractids?:any
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/invoicing/getpaycollection', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function pushInvoiceToAdvertisingSystem(
  params: {
    ids:any
  },
){
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/invoicing/pushinvoicetoadsys', {
    method: 'GET',
    params: {
      ...params,
    },
  });
}
export async function delpushinvoice(
  params: {
    EIid:any
  },
){
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/invoicing/delpushinvoice', {
    method: 'GET',
    params: {
      ...params,
    },
  });
}
export async function delpaycollection(
  params: {
    id:any
  },
){
  return request<{
    data: {};
    errorMessage:String;
  }>('/api/invoicing/delpaycollection', {
    method: 'GET',
    params: {
      ...params,
    },
  });
}

export async function syncinvoice(
  params: {
    ids:any
  }
) {
  return request<any>('/api/invoicingsync/sync', {
    method: 'GET',
    params,
  });
}
export async function cancelsyncinvoice(
  params: {
    ids:any
  }
) {
  return request<any>('/api/invoicingsync/cancelsync', {
    method: 'GET',
    params,
  });
}


