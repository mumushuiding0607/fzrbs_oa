// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

/** 列表接口 */
export async function rule(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/apps/news', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 单条数据接口 */
export async function one(
  params: {
    id?: number;
  },
  options?: { [key: string]: any },
) {
  let url = '/api/apps/news/' + params.id;
  if (params.saveView) {
    url = url + '?saveView=1';
  }
  return request<{
    data: any[];
    success?: boolean;
  }>(url, {
    method: 'GET',
    ...(options || {}),
  });
}

/** 更新评论点赞 */
export async function updateComment(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/news/update-comment', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 获取评论点赞 */
export async function commnets(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/news/comments', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 我的评论*/
export async function myComment(
  params: {
    id?: number;
  },
  options?: { [key: string]: any },
) {
  return request('/api/apps/news/my-comment?id=' + params.id, {
    method: 'GET',
    ...(options || {}),
  });
}

/** 内网列表接口 */
export async function insideRule(
  params: {
    current?: number;
    pageSize?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/apps/nei-wang-news', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 内网单条数据接口 */
export async function insideOne(
  params: {
    id?: number;
  },
  options?: { [key: string]: any },
) {
  let url = '/api/apps/nei-wang-news/' + params.id;
  if (params.saveView) {
    url = url + '?saveView=1';
  }
  return request<{
    data: any[];
    success?: boolean;
  }>(url, {
    method: 'GET',
    ...(options || {}),
  });
}

/** 内网更新评论点赞 */
export async function updateInsideComment(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/nei-wang-news/update-comment', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 获取内网评论点赞 */
export async function insideCommnets(data: any, options?: { [key: string]: any }) {
  return request('/api/apps/nei-wang-news/comments', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}
