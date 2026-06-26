import { storeToRefs } from 'pinia';
import { request } from '../../utils/request';
import { useUserStore } from '@/stores';
const { userInfo } = storeToRefs(useUserStore());

var wxuserid = userInfo.value.userId



export const getflow = (data: any) => request('qyfinance/getflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getflowdata = (data: any) => request('qyfinance/getflowdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const startflow = (data: any) => request('qyfinance/startflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const flowact = (data: any) => request('qyfinance/flowact', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const approvallist = (data: any) => request('qyfinance/approvallist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const history = (data: any) => request('qyfinance/historylist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getlist = (data: any) => request('qyfinance/list', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const inglist = (data: any) => request('qyfinance/inglist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const finishlist = (data: any) => request('qyfinance/finishlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getnotifydata = (data: any) => request('qyfinance/getnotifydata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getusers = (data: any) => request('qyfinance/getusers', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getpayers = (data: any) => request('qyfinance/getpayers', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const iscrossdept = (data: any) => request('qyfinance/iscrossdept', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getbankaccount = (data: any) => request('qyfinance/getbankaccount', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const isinsidecompany = (data: any) => request('qyfinance/isinsidecompany', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getcontract = (data: any) => request('qyfinance/getcontract', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getinvoice = (data: any) => request('qyfinance/getinvoice', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const save = (data: any) => request('qyfinance/save', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const viewpic = (data: any) => request('qyfinance/viewpic', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getdata = (data: any) => request('qyfinance/getdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const verify = (data: any) => request('qyfinance/verify', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getdriverleader = (data: any) => request('qyfinance/getdriverleader', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const gettabs = (data: any) => request('qyfinance/gettabs', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const alterspeech = (data: any) => request('qyfinance/alterspeech', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const setposition = (data: any) => request('qyfinance/setposition', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const updateapproval = (data: any) => request('qyfinance/updateapproval', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

