// @ts-ignore
/* eslint-disable */
import { request } from 'umi';
import CryptoJS from 'crypto-js';

/** 登录接口 */
export async function login(body: API.LoginParams, options?: { [key: string]: any }) {
  const key = CryptoJS.enc.Utf8.parse('PT3ZOOSWtolC7fMJ');
  const iv = CryptoJS.enc.Utf8.parse('r3uvSv17RfsPwd3J');
  let username = body.username;
  let password = body.password;
  const encryptUsername = CryptoJS.AES.encrypt(username, key, {
    iv: iv,
    mode: CryptoJS.mode.CBC,
    padding: CryptoJS.pad.Pkcs7,
  });
  body.username = encryptUsername.ciphertext.toString().toUpperCase();
  const encryptPassword = CryptoJS.AES.encrypt(password, key, {
    iv: iv,
    mode: CryptoJS.mode.CBC,
    padding: CryptoJS.pad.Pkcs7,
  });
  body.password = encryptPassword.ciphertext.toString().toUpperCase();
  return request<API.LoginResult>('/api/login/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    data: body,
    ...(options || {}),
  });
}

/** 发送验证码接口 */
export async function getFakeCaptcha(
  params: {
    phone?: string;
  },
  options?: { [key: string]: any },
) {
  return request<API.FakeCaptcha>('/api/login/dynamic-code', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    data: {
      ...params,
    },
    ...(options || {}),
  });
}
