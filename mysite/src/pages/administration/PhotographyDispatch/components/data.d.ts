export type TableListItem = {
    id: number;
    dispatch_userid: string;
    dispatch_name: string;
    opt_userid: string;
    opt_name: string;
    created: string;
    updated: string;
    begin_time: number;
    end_time: number;
    st: number;
    grade: number;
    reason: string;
    order_no: string;
    command: string;
    lastlogintime: Date;
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
  