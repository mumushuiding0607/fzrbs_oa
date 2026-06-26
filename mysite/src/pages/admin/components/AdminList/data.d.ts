export type TableListItem = {
  id: number;
  username: string;
  password?: string;
  salt?: string;
  realname: string;
  mobile: string;
  usertype: number;
  department: string;
  lastloginip: string;
  lastlogintime: Date;
  loginnum: number;
  inserttime: Date;
  islock: number;
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
