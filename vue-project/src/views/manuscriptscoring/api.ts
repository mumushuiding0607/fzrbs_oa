import { storeToRefs } from 'pinia';
import { request } from '../../utils/request';
import { useUserStore } from '@/stores';
const { userInfo } = storeToRefs(useUserStore());

var wxuserid = userInfo.value.userId


export const getleaders = (data: any) => request('manuscriptscoring/getleaders', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const uploaddatas = (data: any) => request('manuscriptscoring/uploaddatas', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const commit = (data: any) => request('manuscriptscoring/commit', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const delbycatogory = (data: any) => request('manuscriptscoring/delbycatogory', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const delinfo = (data: any) => request('manuscriptscoring/delinfo', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const commitscore = (data: any) => request('manuscriptscoring/commitscore', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const exportExcel = (data: any) => request('manuscriptscoring/export', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getflow = (data: any) => request('manuscriptscoring/getflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getflowdata = (data: any) => request('manuscriptscoring/getflowdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const startflow = (data: any) => request('manuscriptscoring/startflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const flowact = (data: any) => request('manuscriptscoring/flowact', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const approvallist = (data: any) => request('manuscriptscoring/approvallist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const commitapply = (data: any) => request('manuscriptscoring/commitapply', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const commitbycatogory = (data: any) => request('manuscriptscoring/commitbycatogory', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const history = (data: any) => request('manuscriptscoring/historylist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getlist = (data: any) => request('manuscriptscoring/list', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const listcatogory = (data: any) => request('manuscriptscoring/listcatogory', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const inglist = (data: any) => request('manuscriptscoring/inglist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const finishlist = (data: any) => request('manuscriptscoring/finishlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getnotifydata = (data: any) => request('manuscriptscoring/getnotifydata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getusers = (data: any) => request('manuscriptscoring/getusers', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')




export const save = (data: any) => request('manuscriptscoring/save', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const viewpic = (data: any) => request('manuscriptscoring/viewpic', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getdata = (data: any) => request('manuscriptscoring/getdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const gettabs = (data: any) => request('manuscriptscoring/gettabs', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
