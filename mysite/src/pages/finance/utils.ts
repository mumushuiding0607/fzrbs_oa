// import * as XLSX from 'xlsx'

import { Modal } from "antd";


/**
 * 
 * @param data 为二维数组
 * @param filename 
 */
export function downloadAsXlSX(data:any,filename:any) {
  
  const XLSX=require('xlsx')
  
  const ws = XLSX.utils.aoa_to_sheet(data);
  const wb = XLSX.utils.book_new();
  
  XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');

  // 🔁 遍历所有单元格，设置自动换行
  const range = XLSX.utils.decode_range(ws['!ref'] || 'A1'); // 获取数据范围

  for (let R = range.s.r; R <= range.e.r; ++R) {
    for (let C = range.s.c; C <= range.e.c; ++C) {
      const cellRef = XLSX.utils.encode_cell({ r: R, c: C });
      if (!ws[cellRef]) continue;

      // 设置该单元格样式：自动换行
      ws[cellRef].s = {
        alignment: {
          wrapText: true  // 关键：启用自动换行
        }
      };
    }
  }

  // 导出为 Excel 文件
  filename = (filename||'下载')+'.xlsx'
  XLSX.writeFile(wb, filename);
  // const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' })
  // const blob = new Blob([new Uint8Array(wbout)], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
  // downloadfromBlob({blob,filename})
}
export  function downloadfromBlob (params:{blob:any,filename:string}) {
  if ('download' in document.createElement('a')) {
    const elink = document.createElement('a')
    elink.download = params.filename
    elink.style.display = 'none'
    elink.href = URL.createObjectURL(params.blob)
    document.body.appendChild(elink)
    elink.click()
    URL.revokeObjectURL(elink.href)
    document.body.removeChild(elink)
  } else {
    alert('浏览器不支持a标签下载')
  }
}

export function getFromUrl(url:any){
  var name = extractParameterFromUrl(url,'name')
  if (name) name = decodeURIComponent(name)
  var timestr:any = extractParameterFromUrl(url,'time')
 
  var time  = new Date(parseInt(timestr||0)).toLocaleDateString()
  var attach:any = extractParameterFromUrl(url,'attach') || 0
  if (attach) attach = parseInt(attach)

  return {name,time,url,thumbUrl:url,attach}
}
export function setToUrl(u:any){
  var time = new Date().getTime()
  if (u.response && u.response.data) {
    // u.name中?替换成空
    u.name = u.name.replace(/\?/g,'')
    return u.response.data.url+'?name='+encodeURIComponent(u.name)+'&time='+time+'&size='+u.size
  } else if (u.url){
    return u.url
  }
  return ''
}
export function setToFile(u:any){
  var time = new Date().getTime()
  var obj = {url:u.url,name:''}
  if (u.response && u.response.data) {
    u.name = u.name.replace(/\?/g,'')
    obj.url = u.response.data.url+'?name='+encodeURIComponent(u.name)+'&time='+time+'&size='+u.size
    obj.name = u.name
  }
  return obj
}
export function extractParameterFromUrl(url:any, paramName:any) {
  // 正则表达式匹配参数
  // const pattern = new RegExp(`\\?[^&]*${paramName}=([^&]+)`);
  const pattern = new RegExp(`${paramName}=([^&]+)(?:&|$)`);
  const match = url.match(pattern);
  try {
    if (match&&match[1]) {
      return decodeURIComponent(match[1]);
    }
  }catch(e){
  
  }
  return null;
  
}

export function copyTextToClipboard(text:any) {
  // 创建一个隐藏的输入框
  const textarea = document.createElement('textarea');
  textarea.value = text;
  textarea.style.position = 'fixed';  // 防止页面滚动
  textarea.style.opacity = '0';       // 隐藏输入框
  document.body.appendChild(textarea);

  // 选中并复制文本
  textarea.select();
  document.execCommand('copy');

  // 移除输入框
  document.body.removeChild(textarea);

  Modal.success({
    title:'复制成功',
    content:text
  })
}