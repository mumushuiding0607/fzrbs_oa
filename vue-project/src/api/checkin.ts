import { request } from '../utils/request';

export const checkLocal = (data: any) => request('checkin/local-check', data, 'post')
export const saveFace = (data: any) => request('checkin/save-face', data, 'post')
export const save = (data: any) => request('checkin/save', data, 'post')
export const options = (data: any) => request('checkin/options', data, 'post')
export const face = (data: any) => request('checkin/face', data, 'post')
export const log = (data: any) => request('checkin/log', data, 'post')
export const rule = (data: any) => request('checkin/rule', data, 'post')
export const lastdata = (data: any) => request('checkin/lastdata', data, 'post')