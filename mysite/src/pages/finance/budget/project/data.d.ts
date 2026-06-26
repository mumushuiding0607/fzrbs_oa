

export type TableListPagination = {
  total: number;
  pageSize: number;
  current: number;
};

export type TableListData = {
  list: TableListItem[];
  pagination: Partial<TableListPagination>;
};


export type ErrorResponse = {
  errorCode: string;
  errorMessage: string;
  success: boolean;
};
