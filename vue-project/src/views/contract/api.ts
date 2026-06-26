import { storeToRefs } from 'pinia';
import { request } from '../../utils/request';
import { useUserStore } from '@/stores';
const { userInfo } = storeToRefs(useUserStore());

var wxuserid = userInfo.value.userId


export const getflowdata = (data: any) => request('contract/getflowdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const flowact = (data: any) => request('contract/debtflowact', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const approvallist = (data: any) => request('contract/approvallist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const history = (data: any) => request('contract/historylist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const debtlist = (data: any) => request('contract/debtlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const viewdebt = (data: any) => request('contract/viewdebt', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const urgelogslist = (data: any) => request('contract/urgelogslist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const startdebturge = (data: any) => request('contract/startdebturge', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const previewdebtflow = (data: any) => request('contract/previewdebtflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const inglist = (data: any) => request('contract/inglist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const finishlist = (data: any) => request('contract/finishlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const updateurge = (data: any) => request('contract/updateurge', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const saveurgelog = (data: any) => request('contract/saveurgelog', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const delurgelog = (data: any) => request('contract/delurgelog', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const geturgelogs = (data: any) => request('contract/geturgelogs', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const endurge = (data: any) => request('contract/endurge', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
