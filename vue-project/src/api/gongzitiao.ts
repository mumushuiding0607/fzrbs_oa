import { request } from '../utils/request';

export const monthSalary = (data: any) => request('salary/month-salary', data, 'post')