import { storeToRefs } from 'pinia';
import { request } from '../../utils/request';
import { useUserStore } from '@/stores';
const { userInfo } = storeToRefs(useUserStore());

var wxuserid = userInfo.value.userId
// wxuserid='yeyibin'

export const getflow = (data: any) => request('photodispatch/getflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getflowdata = (data: any) => request('photodispatch/getflowdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const startflow = (data: any) => request('photodispatch/startflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const flowact = (data: any) => request('photodispatch/flowact', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const approvallist = (data: any) => request('photodispatch/approvallist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const history = (data: any) => request('photodispatch/historylist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getlist = (data: any) => request('photodispatch/list', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const inglist = (data: any) => request('photodispatch/inglist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const finishlist = (data: any) => request('photodispatch/finishlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getnotifydata = (data: any) => request('photodispatch/getnotifydata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getusers = (data: any) => request('photodispatch/getusers', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')




export const save = (data: any) => request('photodispatch/save', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const viewpic = (data: any) => request('photodispatch/viewpic', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getdata = (data: any) => request('photodispatch/getdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const gettabs = (data: any) => request('photodispatch/gettabs', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getreporters = (data: any) => request('photodispatch/getreporters', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const rate = (data: any) => request('photodispatch/rate', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const reporterlist = (data: any) => request('photodispatch/reporterlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const reportst = (data: any) => request('photodispatch/reportst', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const savereporter = (data: any) => request('photodispatch/savereporter', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const delreporter = (data: any) => request('photodispatch/delreporter', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
