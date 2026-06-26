/* eslint-disable */
// @ts-ignore
import { request } from 'umi';

export interface ContractGanttData {
  contract: {
    id: number;
    amount: number;
    signdate: string;
    paycollection: number;
  };
  payconditions: { date: string; rate: number }[];
  paycollections: { date: string; amount: number; state: number; valid: number }[];
}

// 调用现有 getcontract 接口并裁剪为组件所需字段
export async function getContractGantt(
  params: { id: number | string },
  options?: { [key: string]: any },
) {
  return request<{ data: ContractGanttData; errorMessage?: string }>(
    '/api/contract/getcontract',
    {
      method: 'GET',
      params: { ...params },
      ...(options || {}),
    },
  ).then((res: any) => {
    if (res.errorMessage) return { errorMessage: res.errorMessage };
    const d = res.data || {};
    return {
      data: {
        contract: {
          id: d.id,
          amount: parseFloat(d.amount) || 0,
          signdate: d.signdate || '',
          paycollection: parseFloat(d.paycollection) || 0,
        },
        payconditions: (d.payconditions || []).map((p: any) => ({
          date: p.date,
          rate: parseFloat(p.rate) || 0,
        })),
        paycollections: (d.paycollections || []).map((p: any) => ({
          date: p.date,
          amount: parseFloat(p.amount) || 0,
          state: parseInt(p.state, 10) || 0,
          valid: parseInt(p.valid, 10) || 1,
        })),
      },
    };
  });
}
