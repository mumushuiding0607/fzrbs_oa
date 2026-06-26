export type TableListItem = {
  id: number;
  role: number;
  userid: string;
  username: string;
  level: string;
  company: string;
  dept: string;
  agent: string;
  type: number;
};

export type TableRoleItem = {
  title: string;
  key: string;
  isLeaf: boolean; 
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
