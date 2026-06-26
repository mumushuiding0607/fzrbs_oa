import { storeToRefs } from 'pinia';
import { request } from '../../utils/request';
import { useUserStore } from '@/stores';
const { userInfo } = storeToRefs(useUserStore());

var wxuserid = userInfo.value.userId


export const getflow = (data: any) => request('qyuseseal/getflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getflowdata = (data: any) => request('qyuseseal/getflowdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const startflow = (data: any) => request('qyuseseal/startflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const flowact = (data: any) => request('qyuseseal/flowact', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const approvallist = (data: any) => request('qyuseseal/approvallist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const history = (data: any) => request('qyuseseal/historylist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getlist = (data: any) => request('qyuseseal/list', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const inglist = (data: any) => request('qyuseseal/inglist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const finishlist = (data: any) => request('qyuseseal/finishlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getnotifydata = (data: any) => request('qyuseseal/getnotifydata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getusers = (data: any) => request('qyuseseal/getusers', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getpayers = (data: any) => request('qyuseseal/getpayers', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const iscrossdept = (data: any) => request('qyuseseal/iscrossdept', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getbankaccount = (data: any) => request('qyuseseal/getbankaccount', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const isinsidecompany = (data: any) => request('qyuseseal/isinsidecompany', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getcontract = (data: any) => request('qyuseseal/getcontract', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getinvoice = (data: any) => request('qyuseseal/getinvoice', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const save = (data: any) => request('qyuseseal/save', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const viewpic = (data: any) => request('qyuseseal/viewpic', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getdata = (data: any) => request('qyuseseal/getdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const verify = (data: any) => request('qyuseseal/verify', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getdriverleader = (data: any) => request('qyuseseal/getdriverleader', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const gettabs = (data: any) => request('qyuseseal/gettabs', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')