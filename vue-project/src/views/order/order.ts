import { storeToRefs } from 'pinia';
import { request } from '../../utils/request';
import { useUserStore } from '@/stores';
const { userInfo } = storeToRefs(useUserStore());

var wxuserid = userInfo.value.userId

export const approvallist = (data: any) => request('advertisemanange/approvallist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const finishlist = (data: any) => request('advertisemanange/finishlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getnotifydata = (data: any) => request('advertisemanange/getnotifydata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const gettabs = (data: any) => request('advertisemanange/gettabs', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getorderbyid = (data: any) => request('advertisemanange/getorderbyid', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getflowdata = (data: any) => request('advertisemanange/getflowdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const agree = (data: any) => request('advertisemanange/agree', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const reject = (data: any) => request('advertisemanange/reject', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const cancel = (data: any) => request('advertisemanange/cancel', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const urge = (data: any) => request('advertisemanange/urge', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const flowact = (data: any) => request('advertisemanange/flowact', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const history = (data: any) => request('advertisemanange/historylist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getlist = (data: any) => request('advertisemanange/getlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getadvitemsbyorderid = (data: any) => request('advertisemanange/getadvitemsbyorderid', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

