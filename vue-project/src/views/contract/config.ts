export const StatusCn = ['','审批中','已同意','已驳回','已取消']


export enum StatesEnum {
  ALL=-1,
  ING=1,//审批中
  PASS=2,//已同意
  REJECT=3,//已驳回
  CANCEL=4,//已取消
  END=5,//结束
  
}
export  enum BalanceTypes  {
  INCOME =15,
  EXPEND = 16
}

export enum ProjectStatesEnum {
  ALL=-1,
  START=1,// 待立项
  BUDGET=2,// 待预算
  FINAL=3,// 待决算
  READYTOSUBMIT=4, // 待提交计量
  SUBMITTED=5 // 已提交计量
  
}
export enum FlowStateEunm{
        NONE=0,//"未提交"
        ING=1,//"审批中",
        PASS=2,//"已同意",
        REJECT=3,//"已驳回"
        CANCEL=4,//"已取消"
}
export const AGENTID = 1000080