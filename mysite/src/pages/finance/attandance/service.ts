// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

// 考勤异常打卡数据查询
export async function checkinDataList(
  params: {
    current?: number;
    pageSize?: number;
    start?: string;
    end?: string;
    exception_type?: string;
    userid?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/attendance/checkinlist', {
    method: 'POST',
    data: {
      ...params,
    },
    ...(options || {}),
  });
}

// 考勤异常打卡数据导出
export async function exportCheckinData(
  params: {
    start?: string;
    end?: string;
    exception_type?: string;
    userid?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    errorMessage?: string;
  }>('/api/attendance/exportcheckin', {
    method: 'POST',
    data: {
      ...params,
    },
    ...(options || {}),
  });
}

// 考勤异常审批单查询
export async function attendanceList(
  params: {
    current?: number;
    pageSize?: number;
    start?: string;
    end?: string;
    status?: number;
    userid?: string;
    keyword?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/attendance/list', {
    method: 'POST',
    data: {
      ...params,
    },
    ...(options || {}),
  });
}

// 考勤异常审批单导出
export async function exportAttendanceList(
  params: {
    start?: string;
    end?: string;
    status?: number;
    userid?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    errorMessage?: string;
  }>('/api/attendance/exportapply', {
    method: 'POST',
    data: {
      ...params,
    },
    ...(options || {}),
  });
}

// 撤销考勤异常审批单
export async function cancelAttendance(
  params: {
    thirdNo: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    errorMessage?: string;
    data?: any;
  }>('/api/attendance/cancel', {
    method: 'POST',
    data: {
      ...params,
      act: 'cancel'
    },
    ...(options || {}),
  });
}

// 导出异常统计表
export async function exportStat(
  params: {
    start?: string;
    end?: string;
    parentids?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    errorMessage?: string;
  }>('/api/attendance/export', {
    method: 'POST',
    data: {
      ...params,
    },
    ...(options || {}),
  });
}

// 导出异常申请纪录
export async function exportApplyRecord(
  params: {
    start?: string;
    end?: string;
    status?: number;
    parentids?: string;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any;
    errorMessage?: string;
  }>('/api/attendance/exportapply', {
    method: 'POST',
    data: {
      ...params,
    },
    ...(options || {}),
  });
}
