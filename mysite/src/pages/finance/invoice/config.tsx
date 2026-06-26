export  const INVOICE_AGENTID = 1000085
export enum InvoicingStatesEnum {
  ALL=-1,
  START=0,// 暂存
  INVOICED=1,//已开票
  DELETEED=2,//已撤销
  WAITFORDELETE=6,//待作废
  CONTRACTNOTSIGN=7,//合同未签
  SMALLAMOUNTNOTICE=8,//小额公告合同
}
export enum InvoicingTypeEnum {

  NORMAL=0,//普票
  SPECIAL=1,//专票
}
export enum InvoicedStates{
  Approving=1,//审批中
  WaitForInvoiced=2,//待开票
  Invoiced=3,//已开票
  RedWashed=4,//已红冲
  Saved=5,//暂存
  WAITFORDELETE=6,//待作废
}
export enum ContractTypeEnum{

  NOCONTRACT=3,//无合同
  SMALLAMOUNTNOTICE=4,//小额公司
}