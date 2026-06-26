import { request } from '../utils/request';

export const badge = (data: any) => request('leave/badge', data, 'post')
export const detail = (data: any) => request('leave/detail', data, 'post')
export const notify = (data: any) => request('leave/notify', data, 'post')
export const reminder = (data: any) => request('leave/reminder', data, 'post')
export const approval = (data: any) => request('leave/approval', data, 'post')
export const dict = (data: any) => request('leave/dict', data, 'post')
export const save = (data: any) => request('leave/save', data, 'post')
export const history = (data: any) => request('leave/history', data, 'post')
export const cancel = (data: any) => request('leave/cancel', data, 'post')
export const urge = (data: any) => request('leave/urge', data, 'post')
export const agree = (data: any) => request('leave/agree', data, 'post')
export const reject = (data: any) => request('leave/reject', data, 'post')
export const withdraw = (data: any) => request('leave/withdraw', data, 'post')
export const saveath = (data: any) => request('leave/saveath', data, 'post')
export const undo = (data: any) => request('leave/undo', data, 'post')


