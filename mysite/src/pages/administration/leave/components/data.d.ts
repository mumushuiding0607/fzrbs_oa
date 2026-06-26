
export type TableListItem = {
  id: number;
  userId?: string;
  userName: string;
  departmentid?: number;
  department: string;
  thirdNo?: string;
  leaveType: string;
  leaveStarttime: Date;
  leaveEndtime: Date;
  leaveTimes: number;
  leaveReason: string;
  attachment: any;
  isout: number;
  destination: string;
  status: number;
  inserttime: Date;
  approvalUserid?: string;
  flow: map;
  files: map;
};



export type TableListPagination = {
  total: number;
  pageSize: number;
  current: number;
};

export type TableListData = {
  list: TableListItem[];
  pagination: Partial<TableListPagination>;
};

export type TableListParams = {
  status?: string;
  name?: string;
  desc?: string;
  key?: number;
  pageSize?: number;
  currentPage?: number;
  filter?: Record<string, any[]>;
  sorter?: Record<string, any>;
};

export type ErrorResponse = {
  errorCode: string;
  errorMessage: string;
  success: boolean;
};

export type DateItem = {
  leaveStartD: string;
  leaveStartT: number;
  leaveEndD: string;
  leaveEndT: number;
};

export type DictItem = {
  holiday: map;
  noholiday: map;
  leaveTypes: map;
  noHolidayTypes: map;
  afterstart: string;
  status: map;
  isout: map;
  times: map;
};
