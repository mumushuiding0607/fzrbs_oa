import { appEnv } from "@/utils/common"

export const StatusCn = ['','审批中','任务中','已驳回','已取消','结束']


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
export const App_Name = '稿件打分'
export const isApp = !appEnv()