import token from '@/utils/token';
import { message } from 'antd';
import CryptoJS from 'crypto-js';
import uniqid from 'uniqid';
import { passwordStrength } from 'check-password-strength';
import { useModel, history } from 'umi';

export default {
  // 格式化货币
  formatCurrency(number) {
    const options = {
      style: 'currency',
      currency: 'CNY',
    };
    return number.toLocaleString('zh-CN', options);
  },
  // 下载文件
  downloadFile(url, params, saveFile) {
    const hide = message.loading({ content: '正在生成导出下载文件', duration: 0 });
    fetch(url, {
      method: 'POST',
      body: window.JSON.stringify(params),
      credentials: 'include',
      headers: new Headers({ 'Content-Type': 'application/json', Authorization: token.get(), PathName: history.location.pathname }),
    })
      .then((response) => {
        hide();
        response.blob().then((blob) => {
          const aLink = document.createElement('a');
          document.body.appendChild(aLink);
          aLink.style.display = 'none';
          const objectUrl = window.URL.createObjectURL(blob);
          aLink.href = objectUrl;
          aLink.download = saveFile;
          aLink.click();
          document.body.removeChild(aLink);
        });
      })
      .catch((error) => {
        hide();
        console.log(error);
      });
  },
  // 生成md5字符串
  md5String(string) {
    let str = string == '' || !string ? uniqid() : string;
    return CryptoJS.MD5(str).toString();
  },
  // 密码强度
  passwordStrength(string) {
    const result = passwordStrength(string);
    if (result.value != 'Strong') {
      return false;
    }
    return true;
  },
  getMonthLength(date) {
    const d = new Date(date);
    d.setMonth(d.getMonth() + 1);
    d.setDate('1');
    d.setDate(d.getDate() - 1);
    return d.getDate();
  },
  // 获取指定年份的默认周末
  getDefaultWeekend(year) {
    const weekend = [];
    for (let i = 1; i <= 12; i++) {
      const days = this.getMonthLength(`${year}-${i}-01`);
      for (let j = 1; j <= days; j++) {
        if (
          new Date(`${year}-${i}-${j}`).getDay() === 0 ||
          new Date(`${year}-${i}-${j}`).getDay() === 6
        ) {
          const ii = i.toString().length == 1 ? `0${i}` : i;
          const jj = j.toString().length == 1 ? `0${j}` : j;
          weekend.push(`${year}-${ii}-${jj}`);
        }
      }
    }
    return weekend;
  },
  //字符aes加密
  stringForAES(str) {
    const key = CryptoJS.enc.Utf8.parse('PT3ZOOSWtolC7fMJ');
    const iv = CryptoJS.enc.Utf8.parse('r3uvSv17RfsPwd3J');
    const encryptStr = CryptoJS.AES.encrypt(str, key, {
      iv: iv,
      mode: CryptoJS.mode.CBC,
      padding: CryptoJS.pad.Pkcs7,
    });
    return encryptStr.ciphertext.toString().toUpperCase();
  },
  // websocket
  createWebSocketChannel(channelId, handle) {
    const pushStream = new PushStream({
      host: window.location.host.indexOf(':') != -1 ? window.location.host.substring(0, window.location.host.indexOf(':')) : window.location.host,
      port: window.location.port,
      useSSL: window.location.protocol == 'https:' ? true : false,
      modes: "websocket|eventsource|longpolling", urlPrefixWebsocket: "/sysws", urlPrefixEventsource: "/sysev", urlPrefixLongpolling: "/syslp",
      reconnectOnTimeoutInterval: 1000
    });
    pushStream.onmessage = handle;
    pushStream.addChannel(channelId);
    pushStream.connect();
  },
};
