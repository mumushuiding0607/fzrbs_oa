import { showLoadingToast } from 'vant'
import { aesEncrypt, signature } from './aes'
import { instance } from "./axios"
import { wxAppCheck } from "./common";

export const request = async (url: string, data: any, method: string, showLoading?: boolean, requestConfig?: any) => {
    if (!url) {
        return
    }
    const requestParams = data || {}
    if (process.env.NODE_ENV == 'development') {
        console.log(url);
        console.log(data);
    }
    if (showLoading && wxAppCheck() == 'wxwork') {
        showLoadingToast({
            message: '加载中...',
            forbidClick: true,
        });
    }
    requestParams.client_id = 'rocr8kSx7SZLHfokGTphGuB1bfGIGvOj'
    for (let key in requestParams) {
        const value = requestParams[key]
        if (value === null || value === undefined || value === '') {
            delete requestParams[key]
        }
    }
    const requestData = { content: '', sign: '' }
    requestData.content = aesEncrypt(requestParams)
    requestData.sign = signature(requestParams)
    method = method.toLowerCase();
    if (method == 'post') {
        return instance.post(url, requestData, requestConfig)
    }
}