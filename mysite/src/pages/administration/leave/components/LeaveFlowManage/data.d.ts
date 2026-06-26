
export type TableListItem = {
  id: number;
  templateid?: string;
  templatename: string;
  level?: number;
  is_company: number;
  dids?: string;
  uids: string;
  min: number;
  max: number;
  type: number;
  agentid: number;
  isdel: number;
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
