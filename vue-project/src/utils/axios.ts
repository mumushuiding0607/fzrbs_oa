import axios from "axios"
import { closeToast, showFailToast } from "vant/lib/toast";
import { appEnv, appEnvUserrId } from "./common";

const createParams: any = {
    baseURL: "/weixin/",
    timeout: 30000,
}
if (appEnv()) {
    createParams.headers = {
        'appname': 'zsfz',
        'appuserid': appEnvUserrId(),
    }
}
export const instance = axios.create(createParams);

// 添加请求拦截器
instance.interceptors.request.use(
    (config) => {
        // 在发送请求之前做些什么
        config.headers["Content-type"] = "application/json";
        return config;
    },
    (error) => {
        // 对请求错误做些什么
        return Promise.reject(error);
    }
)

// 添加响应拦截器
instance.interceptors.response.use(
    response => {
        const res = response.data
        if (res?.errorMessage && res?.errorMessage != '') {
            closeToast();
            showFailToast({ message: res.errorMessage || '请求错误', className: 'my-toast' });
        }
        return res;
    },
    error => {
        showFailToast(error.response.status);
        return Promise.reject(error);
    }
)