// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

export async function saveinvoicecheck(data: {}, options?: { [key: string]: any }) {
  return request('/api/budget/saveinvoicecheck', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

