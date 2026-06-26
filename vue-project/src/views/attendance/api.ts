import { storeToRefs } from 'pinia';
import { request } from '../../utils/request';
import { useUserStore } from '@/stores';
const { userInfo } = storeToRefs(useUserStore());

var wxuserid = userInfo.value.userId

export const getflow = (data: any) => request('attendance/getflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getflowdata = (data: any) => request('attendance/getflowdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const startflow = (data: any) => request('attendance/startflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const flowact = (data: any) => request('attendance/flowact', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const approvallist = (data: any) => request('attendance/approvallist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const history = (data: any) => request('attendance/historylist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getlist = (data: any) => request('attendance/list', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const inglist = (data: any) => request('attendance/inglist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const finishlist = (data: any) => request('attendance/finishlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getnotifydata = (data: any) => request('attendance/getnotifydata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getusers = (data: any) => request('attendance/getusers', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')




export const save = (data: any) => request('attendance/save', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const viewpic = (data: any) => request('attendance/viewpic', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getdata = (data: any) => request('attendance/getdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const gettabs = (data: any) => request('attendance/gettabs', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const agreeall = (data: any) => request('attendance/agreeall', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const exception = (data: any) => request('attendance/exception', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const ignore = (data: any) => request('attendance/ignore', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const exportExcel = (data: any) => request('attendance/export', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const exportapply = (data: any) => request('attendance/exportapply', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const department = (data: any) => request('common/department', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

