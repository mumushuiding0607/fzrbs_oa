import { request } from '../utils/request';

export const driverDestination = () => request('truck-driver/driver-destination', undefined, 'post')