// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
import token from '@/utils/token';

/** 列表接口 */
export async function rule(
  params: {
    current?: number;
    pageSize?: number;
    lastlogintime?: any;
    inserttime?: any;
  },
  options?: { [key: string]: any },
) {
  if (params.inserttime) {
    params.inserttime = params.inserttime.join(',');
  }
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/truck-order', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
/** 月度统计列表接口 */
export async function statistics(
  params: {
    current?: number;
    pageSize?: number;
    lastlogintime?: any;
    inserttime?: any;
  },
  options?: { [key: string]: any },
) {
  if (params.inserttime) {
    params.inserttime = params.inserttime.join(',');
  }
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/truck-order/statistics', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
/** 司机去向表 */
export async function driverStatus(
  params: {
    current?: number;
    pageSize?: number;
    lastlogintime?: any;
    inserttime?: any;
  },
  options?: { [key: string]: any },
) {
  if (params.inserttime) {
    params.inserttime = params.inserttime.join(',');
  }
  return request<{
    data: any[];
    total?: number;
    success?: boolean;
  }>('/api/truck-order/driver-status', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}

/** 操作参数接口 */
export async function params(
  params: {
    logid?: number;
  },
  options?: { [key: string]: any },
) {
  return request<{
    data: any[];
    success?: boolean;
  }>('/api/operation-log-params', {
    method: 'GET',
    params: {
      ...params,
    },
    ...(options || {}),
  });
}
/** 修改数据信息 */
export async function update(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<TableListItem>('/api/truck-order/' + data.id, {
    data,
    method: 'PUT',
    ...(options || {}),
  });
}

/** 修改出发地 */
export async function updateNewStartPlace(
  data: { [id: number]: any; [values: string]: any },
  options?: { [key: string]: any },
) {
  return request<Record<string, any>>('/api/truck-order/new-start-place', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

/** 删除接口 */
export async function remove(data: { id: number[] }, options?: { [key: string]: any }) {
  const ids = data.id.join(',');
  return request<Record<string, any>>('/api/truck-order/' + ids, {
    method: 'DELETE',
    ...(options || {}),
  });
}
// 获取用户数据
export async function getStaff(data: { id: number[] }, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/truck-order/get-staff', {
    method: 'GET',
    ...(options || {}),
  });
}
// 获取司机数据
export async function getDriver(data: { id: number[] }, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/truck-order/get-driver', {
    method: 'GET',
    ...(options || {}),
  });
}
// 获取车牌信息数据
export async function getLicence(data: { id: number[] }, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/truck-order/get-licence', {
    method: 'GET',
    ...(options || {}),
  });
}

// 获取该车最后里程数
export async function getCarEndMile(data: { carId: number[] }, options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/truck-order/get-car-end-miles', {
    data,
    method: 'POST',
    ...(options || {}),
  });
}

//导出数据
export async function download(params: {}) {
  fetch('/api/truck-order/export', {
    method: 'POST',
    body: window.JSON.stringify(params),
    credentials: 'include',
    headers: new Headers({ 'Content-Type': 'application/json', Authorization: token.get() }),
  })
    .then((response) => {
      response.blob().then((blob) => {
        const aLink = document.createElement('a');
        document.body.appendChild(aLink);
        aLink.style.display = 'none';
        const objectUrl = window.URL.createObjectURL(blob);
        aLink.href = objectUrl;
        aLink.download = '报社派车信息导出';
        aLink.click();
        document.body.removeChild(aLink);
      });
    })
    .catch((error) => {
      console.log(error);
      // message.error('下载失败，请重试');
    });
}
//月度统计-导出数据
export async function monthDownload(params: {}) {
  fetch('/api/truck-order/export-month', {
    method: 'POST',
    body: window.JSON.stringify(params),
    credentials: 'include',
    headers: new Headers({ 'Content-Type': 'application/json', Authorization: token.get() }),
  })
    .then((response) => {
      response.blob().then((blob) => {
        const aLink = document.createElement('a');
        document.body.appendChild(aLink);
        aLink.style.display = 'none';
        const objectUrl = window.URL.createObjectURL(blob);
        aLink.href = objectUrl;
        aLink.download = '报社派车月度汇总';
        aLink.click();
        document.body.removeChild(aLink);
      });
    })
    .catch((error) => {
      console.log(error);
      // message.error('下载失败，请重试');
    });
}
//月度统计-明细导出数据
export async function monthDetailDownload(params: {}) {
  fetch('/api/truck-order/export-month-detail', {
    method: 'POST',
    body: window.JSON.stringify(params),
    credentials: 'include',
    headers: new Headers({ 'Content-Type': 'application/json', Authorization: token.get() }),
  })
    .then((response) => {
      response.blob().then((blob) => {
        const aLink = document.createElement('a');
        document.body.appendChild(aLink);
        aLink.style.display = 'none';
        const objectUrl = window.URL.createObjectURL(blob);
        aLink.href = objectUrl;
        aLink.download = '报社派车月度汇总-明细';
        aLink.click();
        document.body.removeChild(aLink);
      });
    })
    .catch((error) => {
      console.log(error);
      // message.error('下载失败，请重试');
    });
}
