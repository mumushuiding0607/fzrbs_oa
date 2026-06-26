import { request } from '../utils/request';

export const channelInfo = (data: any) => request('activity-vote/info', data, 'post')
export const channelList = (data: any) => request('activity-vote/list', data, 'post', true)
export const channelSave = (data: any) => request('activity-vote/save', data, 'post', true)