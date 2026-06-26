import wx from "weixin-sdk-js"
import { weixinConfigData, uploadFiles, userInfo, weixinAgentConfigData } from '../api/config';
import { showFailToast, showToast } from "vant";
import router from '@/router';
import * as ww from '@wecom/jssdk'
import CryptoJS from 'crypto-js'

export const loadWeixinConfig = (option: any) => {
    const url = window.location.href.split('#')[0]
    weixinConfigData(url).then((res: any) => {
        if (res.data) {
            wx.config({
                beta: true,
                debug: false,
                appId: res.data.appId, // 必填，企业微信的corpID
                timestamp: res.data.timestamp, // 必填，生成签名的时间戳
                nonceStr: res.data.nonceStr, // 必填，生成签名的随机串
                signature: res.data.signature,// 必填，签名，见附录1
                jsApiList: [
                    'hideOptionMenu',
                    'previewImage',
                    'openEnterpriseContact',
                    'selectEnterpriseContact',
                    'invoke',
                    'scanQRCode',
                    'hideMenuItems',
                    'onMenuShareAppMessage',
                    'onMenuShareTimeline',
                    'updateAppMessageShareData',
                    'getBrandWCPayRequest',
                    'closeWindow'
                ] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
            });
            wx.ready(function () {
                if (option?.hideOptionMenu) {
                    wx.hideOptionMenu();
                } else {
                    wx.hideMenuItems({
                        menuList: ['menuItem:share:timeline', 'menuItem:share:qq', 'menuItem:share:weiboApp', 'menuItem:favorite', 'menuItem:share:facebook', 'menuItem:share:QZone', 'menuItem:copyUrl', 'menuItem:originPage', 'menuItem:openWithQQBrowser', 'menuItem:openWithSafari', 'menuItem:share:email', 'menuItem:share:brand']
                    });
                }
                if (option?.updateAppMessageShareData) {
                    wx.onMenuShareAppMessage(option?.updateAppMessageShareData)
                }
            });
        }
    })
}

export const previewImage = (urls: string[], url: string) => {
    wx.previewImage({
        current: url,
        urls
    });
}

export const scanQRCode = () => {
    return new Promise((resolve, reject) => {
        wx.scanQRCode({
            needResult: 0,
            scanType: ["qrCode", "barCode"],
            success: function (res: any) {
                resolve(res);
            },
            error: function (res: any) {
                reject(res);
            }
        });
    })
}

export const closeWindow = () => {
    wx.closeWindow();
}

export const handleLink = (url: string, replace?: boolean) => {
    if (url.startsWith('http')) {
        window.location.href = url;
    } else if (url.startsWith('#')) {
        showFailToast('敬请期待');
    } else {
        if (replace) {
            router.replace(url)
            return
        }
        router.push(url);
    }
}

export const formatCurrency = (number: any) => {
    const options = {
        style: 'currency',
        currency: 'CNY',
    };
    return number.toLocaleString('zh-CN', options);
}

export const toUrlParams = (params: any) => {
    const temp = [];
    for (let i in params) {
        temp.push(i + "=" + params[i]);
    }
    return temp.length > 0 ? temp.join('&') : '';
}

export const selectEnterpriseContact = (optioin: any) => {
    return new Promise((resolve, reject) => {
        wx.invoke("selectEnterpriseContact", {
            "fromDepartmentId": 0,
            "mode": optioin?.mode ? optioin?.mode : 'single',
            "type": optioin?.type ? optioin?.type : ["user"],
            "selectedDepartmentIds": optioin?.selectedDepartmentIds ? optioin?.selectedDepartmentIds : [],
            "selectedUserIds": optioin?.selectedUserIds ? optioin?.selectedUserIds : []
        }, function (res: any) {
            if (res?.err_msg == "selectEnterpriseContact:ok" || res?.errMsg == "selectEnterpriseContact:ok") {
                if (typeof res.result == 'string') {
                    res.result = JSON.parse(res.result)
                }
                const departmentIds = [];
                const userIds = [];
                var selectedDepartmentList = res.result.departmentList;
                for (var i = 0; i < selectedDepartmentList.length; i++) {
                    var department = selectedDepartmentList[i];
                    departmentIds.push(department.id);
                }
                var selectedUserList = res.result.userList;
                for (var i = 0; i < selectedUserList.length; i++) {
                    var user = selectedUserList[i];
                    userIds.push(user.id);
                }
                const selected = {
                    departmentIds,
                    userIds,
                }
                resolve(selected);
            } else {
                reject(res?.err_msg || res?.errMsg);
            }
        })
    })
}

export const openUserProfile = (optioin: any) => {
    wx.invoke('openUserProfile', {
        "type": optioin?.type ? optioin?.type : 1,
        "userid": optioin.userid
    }, function (res: any) {
        if (res.err_msg != "openUserProfile:ok") {
        }
    });
}

export const wxAppCheck = () => {
    var ua = window.navigator.userAgent.toLowerCase();
    if (/(wxwork)/i.test(ua) && /(micromessenger)/i.test(ua)) {
        return 'wxwork'
    }
    return ''
}

export const deviceCheck = () => {
    let client = '';
    if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)) {
        client = 'iOS';
    } else if (/(Android)/i.test(navigator.userAgent)) {
        client = 'Android';
    }
    return client;
}

export const checkUploadType = (type: number, file: any, errMsg?: any) => {
    const msg: any = { 1: '请上传图片格式', 2: '请上传音视频格式', 3: '请上传.txt,.doc,.docx,.xls,.xlsx,.rar,.zip,.pdf格式文件' };
    const showErrMsg = errMsg ? errMsg : msg[type]
    if (Array.isArray(file)) {
        let flag = 1;
        if (type == 1) {
            file.forEach((item, index) => {
                if (item.type.indexOf('image') == -1) {
                    flag = 0;
                }
            })
        } else if (type == 2) {
            file.forEach((item, index) => {
                if (item.type.indexOf('video') == -1 && item.type.indexOf('audio') == -1) {
                    flag = 0;
                }
            })
        } else if (type == 3) {
            file.forEach((item, index) => {
                if (item.type.indexOf('application') == -1 && item.type.indexOf('plain') == -1) {
                    flag = 0;
                }
            })
        }
        if (flag == 0) {
            showToast(showErrMsg);
            return false;
        }
    } else {
        if (type == 1) {
            if (file.type.indexOf('image') == -1) {
                showToast(showErrMsg);
                return false;
            }
        } else if (type == 2) {
            if (file.type.indexOf('video') == -1 && file.type.indexOf('audio') == -1) {
                showToast(showErrMsg);
                return false;
            }
        } else if (type == 3) {
            if (file.type.indexOf('application') == -1 && file.type.indexOf('plain') == -1) {
                showToast(showErrMsg);
                return false;
            }
        }
    }
    return true;
}

export const uploadedFiles = async (files: any, data: any, callback?: any, hideUploading?: any) => {
    if (Array.isArray(files)) {
        if (!hideUploading) {
            files.forEach((item, index) => {
                item.status = 'uploading';
                item.message = '上传中...';
            })
        }
    } else {
        if (!hideUploading) {
            files.status = 'uploading';
            files.message = '上传中...';
        }
    }
    let result: any = []
    if (Array.isArray(files)) {
        for (let item of files) {
            if (data?.uploadType?.toString() == "1") {
                item.file = await compressImg(item.file);
            }
            const formData = new FormData();
            formData.append('upfile', item.file);
            for (let key in data) {
                formData.append(key, data[key]);
            }
            formData.append('client_id', 'rocr8kSx7SZLHfokGTphGuB1bfGIGvOj');
            if (callback) {
                const res = await uploadFiles(formData, item, callback);
                result.push(res)
            } else {
                uploadFiles(formData, item, callback);
            }
        }
    } else {
        const formData = new FormData();
        if (data?.uploadType?.toString() == "1") {
            files.file = await compressImg(files.file);
        }
        formData.append('upfile', files.file);
        if (data) {
            for (let key in data) {
                formData.append(key, data[key]);
            }
            formData.append('client_id', 'rocr8kSx7SZLHfokGTphGuB1bfGIGvOj');
        }
        if (callback) {
            const res = await uploadFiles(formData, files, callback);
            result.push(res)
        } else {
            uploadFiles(formData, files, callback);
        }
    }
    if (callback) {
        callback(result)
    }
}

export const checkMobile = () => {
    let ua = navigator.userAgent;
    let isWindowsPhone = /(?:Windows Phone)/.test(ua);
    let isSymbian = /(?:SymbianOS)/.test(ua);
    let isAndroid = /(?:Android)/.test(ua);
    let isFireFox = /(?:Firefox)/.test(ua);
    let isTablet =
        /(?:iPad|PlayBook)/.test(ua) ||
        (isAndroid && !/(?:Mobile)/.test(ua)) ||
        (isFireFox && /(?:Tablet)/.test(ua));
    let isPhone = /(?:iPhone)/.test(ua) && !isTablet;
    let isChrome = /(?:Chrome|CriOS)/.test(ua);
    let isMobile = isWindowsPhone || isSymbian || isAndroid || isPhone;
    return isMobile ? true : false;
}

export const softKeyboard = () => {
    let u = navigator.userAgent
    let isIOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/)
    if (isIOS) {
        const activeElement = document.activeElement;
        window.scrollTo(0, document.documentElement.scrollHeight);
    }
}

export const getUserInfo = async (userId: any) => {
    const res = await userInfo({ wxuserid: userId });
    return res;
}

export const loadWeComConfig = async (option?: any) => {
    const params: any = {
        corpId: 'ww36092db762bf3430',
        jsApiList: [
            'getExternalContact',
            'setClipboardData',
            'getClipboardData'
        ],
        getConfigSignature
    }
    if (option?.agentId) {
        const url = window.location.href.split('#')[0]
        params.agentId = '1000002'
        params.getAgentConfigSignature = getAgentConfigSignature
    }
    ww.register(params)
}

async function getConfigSignature(url: any) {
    const res: any = await weixinConfigData(url)
    return { timestamp: res.data.timestamp, nonceStr: res.data.nonceStr, signature: res.data.signature }
}

async function getAgentConfigSignature(url: any) {
    const res: any = await weixinAgentConfigData({ url, agentId: '1000002' })
    return { timestamp: res.data.timestamp, nonceStr: res.data.nonceStr, signature: res.data.signature }
}

export const urlToBase64 = (url: any) => {
    return new Promise((resolve) => {
        fetch(url).then(data => {
            const blob = data.blob()
            return blob;
        }).then(blob => {
            let reader = new FileReader()
            reader.onloadend = function () {
                const dataURL = reader.result
                resolve(dataURL)
            }
            reader.readAsDataURL(blob)
        })

    })
}

export const base64ToFile = (dataURL: any) => {
    var arr = dataURL?.split?.(',')
    let mime = arr[0].match(/:(.*?);/)[1]
    let bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
    while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
    }
    let filename = new Date().getTime() + "" + Math.ceil(Math.random() * 100) + "." + mime.split("/")[1]
    return (new File([u8arr], filename, { type: mime }))
}

export const base64ToBlob = (data: any, type: any) => {
    let raw = window.atob(data);
    let rawLength = raw.length;
    let uInt8Array = new Uint8Array(rawLength);
    for (let i = 0; i < rawLength; ++i) {
        uInt8Array[i] = raw.charCodeAt(i);
    }
    return window.URL.createObjectURL(new Blob([uInt8Array], { type: type }));
}

export const appEnv = () => {
    var flag= false
    const userAgent = window.navigator.userAgent
    if (userAgent.match(/appName\/xyApp/i)) {
        flag=true
    }
    return flag
}

export const appEnvUserrId = () => {
    const userAgent = window.navigator.userAgent
    let userId = -1
    if (appEnv()) {
        userId = parseInt(userAgent.split('##')[1])
    }
    return userId
}

export const stringForAES = (str: string) => {
    const key = CryptoJS.enc.Utf8.parse('PT3ZOOSWtolC7fMJ');
    const iv = CryptoJS.enc.Utf8.parse('r3uvSv17RfsPwd3J');
    const encryptStr = CryptoJS.AES.encrypt(str, key, {
        iv: iv,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.Pkcs7,
    });
    return encryptStr.ciphertext.toString().toUpperCase();
}

export const compressImg = async (file: any) => {
    let compressImg = await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (e: any) => {
            const img = new Image();
            img.src = e.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const ctx: any = canvas.getContext('2d');
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0, img.width, img.height);
                canvas.toBlob((blob: any) => {
                    resolve(new File([blob], file.name, { type: 'image/jpeg' }));
                }, 'image/jpeg', 0.8);
            };
            img.onerror = error => reject(error);
        };
    });
    return compressImg
}
