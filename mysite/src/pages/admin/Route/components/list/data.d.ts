export type TableListItem = {
  id: number;
  name: string;
  path?: string;
  icon?: string;
  access: string;
  hidechildreninmenu: number;
  hideinmenu: number;
  hideinbreadcrumb: number;
  headerrender: number;
  menurender: number;
  menuheaderrender: number;
  parentids: string;
  parentid: number;
  children: number;
  displayorder: number;
  image: string;
  inserttime: Date;
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
