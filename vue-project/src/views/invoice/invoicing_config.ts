export const StatusCn = ['','审批中','已同意','已驳回','已取消']

export enum InvoicingStatesEnum {
  ALL=-1,
  START=0,// 暂存
  INVOICED=1,//已开票
  DELETEED=2,//已撤销
  WAITFORDELETE=6,//待作废
  CONTRACTNOTSIGN=7,//合同未签
}
export enum FlowStateEunm{
        NONE=0,//"未提交"
        ING=1,//"审批中",
        PASS=2,//"已同意",
        REJECT=3,//"已驳回"
        CANCEL=4,//"已取消"
}
export const AGENTID = 1000085