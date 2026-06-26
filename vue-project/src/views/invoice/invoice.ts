import { storeToRefs } from 'pinia';
import { request } from '../../utils/request';
import { useUserStore } from '@/stores';
const { userInfo } = storeToRefs(useUserStore());

var wxuserid = userInfo.value.userId

// const wxuserid= 'jinmaizi'
// const wxuserid= 'linwei'
// const wxuserid= 'linting'
// const wxuserid = 'liping'
// const wxuserid = 'ZhuFuXing'
// wxuserid = 'jinmaizi'
  //  const wxuserid = 'linmaosheng'
  // wxuserid= 'HuMeiHuang'
  //  wxuserid= 'DengLi'
  // wxuserid='LinChaoFan01'
  // wxuserid='wuwenlin'
  // wxuserid='zengyan'


export const saveinvoicing = (data: any) => request('invoicing/save', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getDatas = (data: any) => request('invoicing/list', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getflowdata = (data: any) => request('invoicing/getflowdata', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const startflow = (data: any) => request('invoicing/startflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getinvoicelist = (data: any) => request('invoicing/getinvoicelist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const flowact = (data: any) => request('invoicing/flowact', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const approvallist = (data: any) => request('invoicing/approvallist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const history = (data: any) => request('invoicing/historylist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getlist = (data: any) => request('invoicing/getlist', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const getviewfileurlprefix = (data: any) => request('invoicing/file', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getbyid = (data: any) => request('invoicing/getbyid', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getcompany = (data: any) => request('invoicing/getcompany', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getcontracts = (data: any) => request('invoicing/getcontracts', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')


export const getinvoiceitems = (data: any) => request('invoicing/getinvoiceitems', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getdictbykeyword = (data: any) => request('invoicing/getdictbykeyword', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getusers = (data: any) => request('invoicing/getusers', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const delinvoiceitem = (data: any) => request('invoicing/delinvoiceitem', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const getprojectbykeyword = (data: any) => request('invoicing/getprojectbykeyword', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const saveinvoice = (data: any) => request('invoicing/saveinvoice', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const savepdfinvoice = (data: any) => request('invoicing/savepdfinvoice', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const delinvoice = (data: any) => request('invoicing/delinvoice', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getprintinfo = (data: any) => request('invoicing/getprintinfo', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const getstates = (data: any) => request('invoicing/getstates', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const addcontract = (data: any) => request('invoicing/addcontract', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const savecompany = (data: any) => request('invoicing/savecompany', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const gettabs = (data: any) => request('invoicing/gettabs', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const savefiles = (data: any) => request('invoicing/savefiles', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const viewflow = (data: any) => request('invoicing/viewflow', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
export const invoicetypes = (data: any) => request('invoicing/invoicetypes', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const delinvoicingnotice = (data: any) => request('invoicing/delinvoicingnotice', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')

export const canceldelinvoicingnotice = (data: any) => request('invoicing/canceldelinvoicingnotice', {...data,wxuserid:data.wxuserid||wxuserid}, 'post')
