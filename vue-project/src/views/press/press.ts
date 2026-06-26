import { storeToRefs } from 'pinia';
import { request } from '../../utils/request';
import { useUserStore } from '@/stores';
const { userInfo } = storeToRefs(useUserStore());

var wxuserid = userInfo.value.userId



export const getflow = (data: any) => request('qypress/getflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getflowdata = (data: any) => request('qypress/getflowdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const startflow = (data: any) => request('qypress/startflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const flowact = (data: any) => request('qypress/flowact', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const approvallist = (data: any) => request('qypress/approvallist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const history = (data: any) => request('qypress/historylist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getlist = (data: any) => request('qypress/list', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const inglist = (data: any) => request('qypress/inglist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const finishlist = (data: any) => request('qypress/finishlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getnotifydata = (data: any) => request('qypress/getnotifydata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getusers = (data: any) => request('qypress/getusers', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const save = (data: any) => request('qypress/save', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const viewpic = (data: any) => request('qypress/viewpic', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getdata = (data: any) => request('qypress/getdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const findpersononduty = (data: any) => request('qypress/findpersononduty', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const gettabs = (data: any) => request('qypress/gettabs', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const getpapers = (data: any) => request('qypress/getpapers', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
