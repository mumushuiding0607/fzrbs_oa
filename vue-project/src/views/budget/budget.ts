import { storeToRefs } from 'pinia';
import { request } from '../../utils/request';
import { useUserStore } from '@/stores';
const { userInfo } = storeToRefs(useUserStore());

var wxuserid = userInfo.value.userId





export const getDatas = (data: any) => request('budget/list', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getflowdata = (data: any) => request('budget/getflowdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const flowact = (data: any) => request('budget/flowact', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const approvallist = (data: any) => request('budget/approvallist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const history = (data: any) => request('budget/historylist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getprolist = (data: any) => request('budget/getprolist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getproject = (data: any) => request('budget/getproject', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getbudgetinfo = (data: any) => request('budget/getbudgetinfo', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getviewfileurlprefix = (data: any) => request('budget/file', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getfileurlsbycontractids = (data: any) => request('budget/getfileurlsbycontractids', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getbalancefileurls = (data: any) => request('budget/getbalancefileurls', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getcontract = (data: any) => request('budget/getcontract', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const paycollectioncheck = (data: any) => request('budget/paycollectioncheck', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const paycollectionchecklist = (data: any) => request('budget/paycollectionchecklist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const transfileurl = (data: any) => request('budget/transfileurl', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const debtlist = (data: any) => request('budget/debtlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const viewdebt = (data: any) => request('budget/viewdebt', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const startdebturge = (data: any) => request('budget/startdebturge', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const previewdebtflow = (data: any) => request('budget/previewdebtflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

