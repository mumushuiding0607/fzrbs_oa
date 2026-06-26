export const StatusCn = ['','审批中','已同意','已驳回','已取消']

export enum FinanaceType{
  NORMAL=0,
  SALARY=1,
  CAR=2,
  NORMAL_CROSS=3,
  SALARY_CROSS=4,
  TRANSFER=5
}
export enum StatesEnum {
  ALL=-1,
  ING=1,//审批中
  PASS=2,//已同意
  REJECT=3,//已驳回
  CANCEL=4,//已取消
}
export enum FlowStateEunm{
        NONE=0,//"未提交"
        ING=1,//"审批中",
        PASS=2,//"已同意",
        REJECT=3,//"已驳回"
        CANCEL=4,//"已取消"
}
export const AGENTID = 1000066