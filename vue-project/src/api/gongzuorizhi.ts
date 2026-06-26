import { request } from '../utils/request';

export const todayLog = (data: any) => request('daily-working-log/today', data, 'post')
export const logSave = (data: any) => request('daily-working-log/log-save', data, 'post')
export const myLog = (data: any) => request('daily-working-log/my-log', data, 'post')
export const comment = (data: any) => request('daily-working-log/comment', data, 'post')
export const config = (data: any) => request('daily-working-log/config', data, 'post')
export const commentInfo = (data: any) => request('daily-working-log/comment-info', data, 'post')