import { showLoadingToast, showSuccessToast } from 'vant';
import { request } from '../utils/request';
import axios from "axios"

export const weixinConfigData = (url: string) => request('config/weixin', { url }, 'post')
export const weixinAgentConfigData = (data: any) => request('config/weixin-agent', data, 'post')
export const weixinGroupConfigData = (url: string) => request('config/weixin-group', { url }, 'post')
export const addressBook = (data: any) => request('address-book/index', data, 'post')
export const userInfo = (data: any) => request('config/user-info', data, 'post')

export const uploadFiles = async (data: any, file: any) => {
    // axios.post('/weixin/common/upload', data, {
    //     headers: {
    //         "content-type": "multipart/form-data",
    //     },
    // }).then(res => {
    //     if (res.data?.data) {
    //         file._url = res.data?.data.url
    //         file.status = 'done';
    //         file.message = '';
    //     }
    // })
    const res = await axios.post('/weixin/common/upload', data, {
        headers: {
            "content-type": "multipart/form-data",
        },
    })
    if (res.data?.data) {
        file._url = res.data?.data.url
        file.status = 'done';
        file.message = '';
    }
    return res.data?.data
}
export const uploadDelete = (fileurl: string, protect?: any) => request('common/upload-delete', { fileurl, protect: protect ? protect : '' }, 'post')

export const downloadFile = async (url: any, data: any, saveFile: any) => {
    showLoadingToast({
        message: '正在下载',
        forbidClick: true,
    });
    request(url, data, 'post', false, { responseType: 'blob' }).then((response: any) => {
        showSuccessToast('下载成功');
        const aLink = document.createElement('a');
        document.body.appendChild(aLink);
        aLink.style.display = 'none';
        const objectUrl = window.URL.createObjectURL(response);
        aLink.href = objectUrl;
        aLink.download = saveFile;
        aLink.click();
        document.body.removeChild(aLink);
    })
}

export const previewFile = (data: any) => request('common/preview-file', data, 'post', false)

export const departmentRule = (data: any) => request('common/department', data, 'post', false)
export const searchNameRule = (data: any) => request('common/search-user', data, 'post', false)