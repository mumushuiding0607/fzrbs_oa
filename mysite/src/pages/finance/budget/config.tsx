export const StatusCn = ['','审批中','已同意','已驳回','已取消']

export  enum BalanceTypes  {
  INCOME =15,
  EXPEND = 16,
  ALL=0
}

export enum ProjectStatesEnum {
  ALL=-1,
  START=1,// 待立项
  BUDGET=2,// 待预算
  FINAL=3,// 待决算
  READYTOSUBMIT=4, // 待提交计量
  SUBMITTED=5, // 已提交计量
  WITHDRAW=6 // 撤回
}
export enum ProjectTypesEnum {
  FEIBAO=5,// 非报创新业务
  HUODONG=9,// 活动促广告业务

  // 需要预决算
  OFFLINE=6,// 非报线下业务
  CHUNXIN=7,// 纯新媒体业务
  QITA=8, // 其他业务
  
}

export enum FlowStateEunm{
        NONE=0,//"未提交"
        ING=1,//"审批中",
        PASS=2,//"已同意",
        REJECT=3,//"已驳回"
        CANCEL=4,//"已取消"
}
export const AGENTID = 1000080

// 判断是否需要预决算
export  function needBudgetCheck(type:number){
  return [ProjectTypesEnum.FEIBAO,ProjectTypesEnum.HUODONG].includes(type)

}