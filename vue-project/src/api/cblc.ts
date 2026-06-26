import { request } from '../utils/request';

export const configApi = (data: any) => request('compilation-process/config', data, 'post')
export const saveApi = (data: any) => request('compilation-process/save', data, 'post')
export const listApi = (data: any) => request('compilation-process/list', data, 'post')
export const infoApi = (data: any) => request('compilation-process/info', data, 'post')
export const agreeApi = (data: any) => request('compilation-process/agree', data, 'post')
export const rejectApi = (data: any) => request('compilation-process/reject', data, 'post')
export const cancelApi = (data: any) => request('compilation-process/cancel', data, 'post')
export const urgeApi = (data: any) => request('compilation-process/urge', data, 'post')


export const getUploadFileDataApi = (data: any) => request('instruction-disposal/upload-file-data', data, 'post')
export const instructionSaveApi = (data: any) => request('instruction-disposal/save', data, 'post')
export const instructionListApi = (data: any) => request('instruction-disposal/list', data, 'post')
export const instructionInfoApi = (data: any) => request('instruction-disposal/info', data, 'post')
export const instructionAgreeApi = (data: any) => request('instruction-disposal/agree', data, 'post')
export const instructionRejectApi = (data: any) => request('instruction-disposal/reject', data, 'post')
export const instructionCancelApi = (data: any) => request('instruction-disposal/cancel', data, 'post')
export const instructionUrgeApi = (data: any) => request('instruction-disposal/urge', data, 'post')