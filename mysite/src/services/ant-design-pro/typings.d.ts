// @ts-ignore
/* eslint-disable */

declare namespace API {
  type LoginResult = {
    status?: string;
    type?: string;
    currentAuthority?: string;
    msg: string;
  };

  type LoginParams = {
    username?: string;
    password?: string;
    autologin?: boolean;
    type?: string;
  };

  type CurrentUser = {
    id: number;
    username: string;
    realname: string;
    mobile: string;
    usertype: number;
    department: string;
    lastloginip: string;
    lastlogintime: Date;
    loginnum: number;
    inserttime: Date;
    islock: number;
    access?: string;
  };

  type PageParams = {
    current?: number;
    pageSize?: number;
  };

  type FakeCaptcha = {
    success?: boolean;
    errorCode?: string;
    errorMessage?: string;
  };

  type ErrorResponse = {
    errorCode: string;
    errorMessage?: string;
    success?: boolean;
  };
}
