import { request } from '../utils/request';

export const suggestType = (data: any) => request('suggest/type', data, 'post')
export const suggestSave = (data: any) => request('suggest/save', data, 'post')