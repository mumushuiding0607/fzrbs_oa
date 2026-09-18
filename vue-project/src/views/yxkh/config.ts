// 考核状态中文
export const StatusCn = ['', '审批中', '已通过', '已驳回', '已取消', '结束'];

// 考核状态枚举
export enum StatesEnum {
  ALL = -1,
  ING = 1,       // 审批中
  PASS = 2,      // 已同意
  REJECT = 3,    // 已驳回
  CANCEL = 4,    // 已取消
  FINISHING = 5, // 结束
}

// 流程状态枚举
export enum FlowStateEunm {
  NONE = 0,      // 未提交
  ING = 1,       // 审批中
  PASS = 2,      // 已同意
  REJECT = 3,    // 已驳回
  CANCEL = 4,    // 已取消
}

// 自评选项
export const SelfEvaluationOptions = [
  { text: '优秀', name: '优秀' },
  { text: '合格', name: '合格' },
  { text: '基本合格', name: '基本合格' },
  { text: '不合格', name: '不合格' },
];

// 业务类型
export const BUSINESS_TYPE_YDKH = '月度考核';

// 审批操作
export enum PerformAct {
  SUBMIT = 0,    // 提交
  SAVE = 1,      // 保存草稿
  PASS = 2,      // 通过
  REJECT = 3,    // 驳回
  TRANSFER = 4,  // 转审
  CANCEL = 5,    // 撤销
}

// 状态颜色
export const stateColor: Record<number, string> = {
  [StatesEnum.ING]: '#FF6D25',
  [StatesEnum.PASS]: 'green',
  [StatesEnum.REJECT]: 'gray',
  [StatesEnum.CANCEL]: 'gray',
  [StatesEnum.FINISHING]: 'green',
};
