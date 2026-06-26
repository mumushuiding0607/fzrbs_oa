import { request } from '../utils/request';

export const appsConfig = () => request('config/apps', {}, 'post')

export const userInfoApi = (data: any) => request('common/user-info', data, 'post')