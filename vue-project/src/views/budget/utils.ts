
export  function downloadfromUrl (url:any) {
  
  if ('download' in document.createElement('a')) {
    const fileUrl = url;
    var name:any = extractParameterFromUrl(url,'name')
    const link = document.createElement('a');
    link.href = fileUrl;
    link.download = name; 
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

  
  } else {
    alert('浏览器不支持a标签下载')
  }
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
  var size = extractParameterFromUrl(url,'size')
  var timestr:any = extractParameterFromUrl(url,'time')
 
  var time  = new Date(parseInt(timestr||0)).toLocaleDateString()
  var attach:any = extractParameterFromUrl(url,'attach') || 0
  if (attach) attach = parseInt(attach)
  var type = ''
  if (name) type = name.substring(name.lastIndexOf('.'))

  return {name,type,size,time,url,thumbUrl:url,attach}
}

export function setToUrlFromFile(file:any,url:any){

  var time = new Date().getTime()
  if (file) {
    // 字符串,【等特殊字符替换成空
    var name = file.name
    if (name) name = name.replace(/,/g, '')
    return url+'?name='+name+'&time='+time+'&size='+file.size
  } 
  return ''
}
export function setToUrl(u:any){
  var time = new Date().getTime()
  if (u) {
    // 字符串,【等特殊字符替换成空
    
    if (u.originalName) u.originalName = u.originalName.replace(/,/g, '')
    return u.url+'?name='+u.originalName+'&time='+time+'&size='+u.size
  } 
  return ''
}
export function setToFile(u:any){
  var time = new Date().getTime()
  var obj = {url:u.url,name:''}
  const name = decode(u.name)
 
  if (u.response && u.response.data) {
    obj.url = u.response.data.url+'?name='+name+'&time='+time+'&size='+u.size
    obj.name = name
  }
  return obj
}
function decode(val:any){
  try {
      return decodeURIComponent(val);
    } catch (error) {
      return val;
    }
}
export function extractParameterFromUrl(url:any, paramName:any) {
  // 正则表达式匹配参数
  // const pattern = new RegExp(`\\?[^&]*${paramName}=([^&]+)`);
  const pattern = new RegExp(`${paramName}=([^&]+)(?:&|$)`);
  const match = url.match(pattern);

  if (match) {
    
    if (paramName!='name') return match[1];
    return decode(match[1])

  } else {
      return null;
  }
}
export function isFileType(url:string){
  if (!url) return false
  // 
  const type = url.match(/\.([a-zA-Z0-9]+)(?:$|[\?#])/)?.[1]
  return ['.pdf','.doc','.docx','.xml','.xlsx','.ppt','xls'].includes('.'+type)
 
}