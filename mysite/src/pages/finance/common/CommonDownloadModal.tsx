
import { Modal } from "antd"
import { useState } from "react"
import { downloadAsXlSX } from "../utils"
import HeaderTransfer from "../contract/header_transfer"
import { request } from "umi"

const CommonDownloadModal:React.FC<{url:any,headers?:any,headersUrl?:any,params:any,onChange?:Function,visible:boolean,onVisibleChange:Function}> = ({url,headersUrl,headers=[],params,onChange,visible,onVisibleChange}) =>{

  const [loading,setLoading]=useState(false)
  var [tempHeaders,setTempHeaders] = useState<any>(headers)
  var [refreshKey, setRefreshKey] = useState(0)
  const onHeaderChange = (h:any)=>{
    if (h.length==0){
      Modal.error({title:'表头不能为空'})
      return
    }
    setTempHeaders(h)
  }
  return <Modal
  width={600}
  style={{ top: 0}}
  visible={visible}
  onOk={() => {
    if(loading) {
      Modal.warn({title:'正在导出，请稍候'})
      return
    }
    setLoading(true)
    setTimeout(() => {
      setLoading(false)
    }, 3000);
    
    if (Object.keys(params).length<1){
      Modal.error({title:'未设置查询条件,如已设置先点查询'})
      onVisibleChange(false)
      return
    }
    params.pageSize = 100000
    params.current = 1


    request<{
      data: [];
      total?: number;
      success?: boolean;
    }>(url, {
      method: 'GET',
      params: {
        ...params,
      }
    }).then((res:any)=>{
      
      if (res.data.length<0){
        Modal.error({title:'数据为空'})
      }else{

        if (!tempHeaders.find((e:any)=>e.key=='index')){
          tempHeaders.unshift({title:'序号',key:'index'})
        }

        
        var temp = res.data.map((row:any,index:any)=>{
          var arr:any = []
          row.index = index+1
          
          tempHeaders.forEach((h:any)=>{

            
            switch (h.key) {

              default:
                arr.push(row[h.key]||'')
                break;
            }
            
            
          })

          return arr
        })
        var x = tempHeaders.map((t:any)=>t.title||'该列列标未设置')
        temp.unshift(x)
        downloadAsXlSX(temp,'信息导出')
      }
    })
  }}
  onCancel={() => {
    onVisibleChange(false)
  }}

>
  
  <HeaderTransfer key={refreshKey} url={headersUrl} onChange={onHeaderChange} headers={tempHeaders} />
</Modal>
}
export default CommonDownloadModal