import { request } from '../utils/request';

export const list = (data: any) => {
    let url = 'news/list'
    if (data?.from && data?.from == 'neiwang') {
        url = 'nei-wang-news/list';
    }
    return request(url, data, 'post')
}
export const info = (data: any) => {
    let url = 'news/view'
    if (data?.from && data?.from == 'neiwang') {
        url = 'nei-wang-news/view';
    }
    return request(url, data, 'post')
}
export const comments = (data: any) => {
    let url = 'news/comments'
    if (data?.from && data?.from == 'neiwang') {
        url = 'nei-wang-news/comments';
    }
    return request(url, data, 'post')
}
export const updateComment = (data: any) => {
    let url = 'news/update-comment'
    if (data?.from && data?.from == 'neiwang') {
        url = 'nei-wang-news/update-comment';
    }
    return request(url, data, 'post')
}
export const channelName = (data: any) => request('news/channel-name', data, 'post')
export const xlxtUpload = (data: any) => request('nei-wang-news/upload', data, 'post')
export const infoUpload = (data: any) => request('news/upload', data, 'post')