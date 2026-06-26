// @ts-ignore
/* eslint-disable */
import { request } from 'umi';


/** 获取订单列表 */
export async function getOrderList(
  params: {
    current?: number;
    pageSize?: number;
    SYS_DOCUMENTID?: string;
    contractserial?: string;
    partb?: string;
    AO_Customer_ID?: string;
    AO_Org_ID?: string;
    AO_Salesman_ID?: string;
    publicationid?: string;
    SYS_CREATED_START?: string;
    SYS_CREATED_END?: string;
    orderby?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/advertisemanange/getorderlist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 保存订单（新建） */
export async function saveOrder(data: {}, options?: { [key: string]: any }) {
  return request('/api/advertisemanange/saveorder', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 更新订单 */
export async function updateOrder(data: {}, options?: { [key: string]: any }) {
  return request('/api/advertisemanange/updateorder', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除订单 */


/** 根据ID获取订单详情 */
export async function getOrderById(
  params: {
    SYS_DOCUMENTID: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: {};
    errorMessage?: string;
  }>('/api/advertisemanange/getbyid', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 根据合同ID获取相关订单 */
export async function getOrdersByContract(
  params: {
    contractid: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
  }>('/api/advertisemanange/getbycontract', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
/** 删除广告 */
export async function deleteAdvitem(
  params: {
    SYS_DOCUMENTID: string;
  },
  options?: { [key: string]: any },
) {
  return request('/api/advertisemanange/deleteadvitem', {
    method: 'DELETE',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
export async function deleteOrder(
  params: {
    SYS_DOCUMENTID: string;
  },
  options?: { [key: string]: any },
) {
  return request('/api/advertisemanange/deleteorder', {
    method: 'DELETE',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
/** 保存广告 */
export async function saveAdvitem(data: {}, options?: { [key: string]: any }) {
  return request('/api/advertisemanange/saveadvitem', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 更新广告 */
export async function updateAdvitem(data: {}, options?: { [key: string]: any }) {
  return request('/api/advertisemanange/updateadvitem', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 获取广告列表 */
export async function getAdvitemList(
  params: any,
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/advertisemanange/advitemslist', {
    method: 'post',
    data: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 导出广告列表Excel */
// export async function exportAdvitemList(
//   params: any,
// ) {
//   return request<Blob>('/api/advertisemanange/advitemslist', {
//     method: 'post',
//     data: {
//       ...params
//     },
//     responseType: 'blob',
//   });
// }
export async function exportAdvitemList(params: any) {
  return request('/api/advertisemanange/advitemslist', {
    method: 'POST',
    data: params,
    responseType: 'blob',  // 指定响应类型为 blob
  });
}



/** 保存广告登记（福州日报社广告以及纯服务收入登记表） */
export async function saveFzAdv(data: {}, options?: { [key: string]: any }) {
  return request('/api/advertisemanange/saveadvitem', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 保存小额业务 */
export async function saveSmallBusiness(data: {}, options?: { [key: string]: any }) {
  return request('/api/advertisemanange/saveadvitem', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 根据广告ID或订单ID获取广告完整信息（包括Order表中的字段） */
export async function getAdvitem(
  params: {
    advitemId?: any;
    advitemIds?: any;
    orderId?: any;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    errorMessage?: string;
  }>('/api/advertisemanange/getadvitem', {
    method: 'GET',
    params,
    ...(options || {}),
  });
}
export async function printorder(
  params: {
    orderid?: any;
    orderids?: any;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    errorMessage?: string;
  }>('/api/advertisemanange/printorder', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function getorderbyid(
  params: {

    orderid?: any;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    errorMessage?: string;
  }>('/api/advertisemanange/getorderbyid', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 设置订单和广告的生效状态 */
export async function setOrderFlag(
  params: {
    orderid?: any;
    flag?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    errorMessage?: string;
  }>('/api/advertisemanange/setorderflag', {
    method: 'GET',
    params,
    ...(options || {}),
  });
}

/** 设置单个广告的生效状态(只改变自己) */
export async function setadvitemflag(
  params: {
    advitemid?: any;
    flag?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    errorMessage?: string;
  }>('/api/advertisemanange/setadvitemflag', {
    method: 'GET',
    params,
    ...(options || {}),
  });
}

/** 获取待审批列表 */
export async function approvallist(
  params: {
    current?: number;
    pageSize?: number;
    keyword?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/advertisemanange/approvallist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 获取刊例价列表 */
export async function getPricelist(
  params: {
    current?: number;
    pageSize?: number;
    E_PID?: number;
    E_MID?: number;
    E_AdField_ID?: number;
    E_Color_ID?: number;
    E_AdSize_ID?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/advertisemanange/pricelist', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 根据条件获取刊例价（返回ID最大的那条） */
export async function getPriceByConditions(
  params: {
    E_PID?: number;
    E_MID?: number;
    E_AdField_ID?: number;
    E_Color_ID?: number;
    E_AdSize_ID?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data?: any;
    success?: boolean;
  }>('/api/advertisemanange/getprice', {
    method: 'GET',
    params: {
      ...params,
      ...options,
    },
  });
}

/** 保存刊例价 */
export async function savePricelist(data: {}, options?: { [key: string]: any }) {
  return request('/api/advertisemanange/savepricelist', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 保存广告规格（新增/编辑） */
export async function saveAdvsize(data: {}, options?: { [key: string]: any }) {
  return request('/api/advertisemanange/saveadvsize', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 流程预览 */
export async function viewflow(
  params: {
    infoid: string;
    act?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    viewdata: any;
    statusCn: any[];
    step: number;
    invoicers?: string;
  }>('/api/advertisemanange/viewflow', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 启动流程 */
export async function startflow(data: {
  flowtype: number;
  thirdNo?: string;
  infoid: string;
  act: number;
}, options?: { [key: string]: any }) {
  return request('/api/advertisemanange/startflow', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 获取流程数据 */
export async function getflowdata(
  params: {
    thirdNo?: string;
    state?: number;
    infoid?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    viewdata: any;
    statusCn: any[];
    basic: any;
    info: any;
  }>('/api/advertisemanange/getflowdata', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

export async function getthirdno() {
  return request<{
    errorMessage: string,
    data:string
  }>('/api/advertisemanange/getthirdno');
}

export async function flowact(params:{thirdNo?:any,speech?:string,act:string}){
  return request<{errorMessage:String,data:{}}>('/api/advertisemanange/flowact',{
    method: 'GET',
    params:{...params}
  })
}

/** 启动广告审批流程 */
export async function startadvflow(
  params: {
    advitemid?: any;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    errorMessage?: string;
  }>('/api/advertisemanange/startadvflow', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 获取广告审批流程预览 */
export async function viewadvflow(
  params: {
    advitemid?: any;
  },
  options?: { [key: string]: any },
) {
  return request<{
    viewdata: any;
    statusCn: any[];
    step: number;
    errorMessage?: string;
  }>('/api/advertisemanange/viewadvflow', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 广告统计 */
export async function advitemstatistics(
  params: any
) {
  return request<{
    data: [];
    total?: number;
    success?: boolean;
  }>('/api/invoicingsync/statistics', {
    method: 'POST',
    data:{...params}
  });
}