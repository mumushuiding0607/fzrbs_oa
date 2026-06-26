import { request } from '../utils/request';

export const login = (code: any, agentid: any) => request('fzrbs-qyhyy/oauth2-user-id', { code, appid: agentid }, 'post', true)

export const appAutoLogin = (data: any) => request('apps/app-auto-login', data, 'post', true)

export const getSmsCode = (data: any) => request('apps/send-code', data, 'post', true)

export const appUserBind = (data: any) => request('apps/app-user-bind', data, 'post', true)

export const appVisit = (data: any) => request('apps/app-visit', data, 'post', true)