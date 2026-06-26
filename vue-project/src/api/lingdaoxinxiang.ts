import { request } from '../utils/request';

export const add = (data: any) => request('leader-mail/add', data, 'post')
export const messageData = (data: any) => request('leader-mail/message', data, 'post')
export const comment = (data: any) => request('leader-mail/comment', data, 'post')
export const commentInfo = (data: any) => request('leader-mail/comment-info', data, 'post')