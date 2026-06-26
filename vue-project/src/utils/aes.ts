import CryptoJS from 'crypto-js'

const AesKey = 'PT3ZOOSWtolC7fMJ'
const AesIV = 'r3uvSv17RfsPwd3J'

/**
  接口参数加密
*/
export const aesEncrypt = (data: any) => {
  var paramStr = JSON.stringify(data)
  var key = CryptoJS.enc.Utf8.parse(AesKey)
  var iv = CryptoJS.enc.Utf8.parse(AesIV)
  var encrypted = CryptoJS.AES.encrypt(paramStr, key, {
    iv: iv,
    mode: CryptoJS.mode.CBC,
    padding: CryptoJS.pad.Pkcs7
  })
  return encrypted.ciphertext.toString().toUpperCase()
}

/**
  接口参数签名
*/
export const signature = (data: any) => {
  var tmpArr = []
  for (var i in data) {
    tmpArr.push(i)
  }
  tmpArr.sort()
  var params = []
  for (var i in tmpArr) {
    var value = data[tmpArr[i]]
    if (typeof value == 'object') {
      value = JSON.stringify(value)
    }
    params.push(tmpArr[i] + '=' + value)
  }
  var paramStr = params.join('&')
  return CryptoJS.SHA256(paramStr).toString()
}