export type TableListItem = {
  id: number;
  opt_id: number;
  send_st: number;
  sign_st: number;
  col_a: number;
  col_b: number;
  col_c: number;
  col_d: number;
  bonus_year: number;
  bonus_type: number;
  userid: string;
  mobile: string;
  username?: string;
  opt_name?: string;
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


// export const bonusType = {
//   0:{text:"年终奖",status:''},
//   1:{text:"文明奖",status:''},
//   2:{text:"创城奖",status:''},
//   3:{text:"综治奖",status:''},
// };