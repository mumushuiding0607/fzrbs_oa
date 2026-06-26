
import { Button, List, Modal, Row, Table, Tag } from "antd"

import { CSSProperties, useEffect, useState } from "react"
import CustomDivider from "../budget/common/CustomDivider"

import JSZip from "jszip"
import { FilePptOutlined } from "@ant-design/icons"
import { getFromUrl } from "../utils"
const tag:CSSProperties = {
  margin: '0 5px 0 0',
  padding: '0px 4px',
  borderRadius: '15%',
}
const Filescard:React.FC<{urls?:any,mode?:string}> = ({urls,mode='table'}) =>{
  const cols = [

    {
      title: '文件名称',
      dataIndex: 'name',
      key: 'name',
      render:(text:any,record:any)=>{
      
        return (<a href={record.url} target="blank">{record.attach?<Tag color='blue' style={tag} >补</Tag>:<></>}{text}</a>)
      }
    },
    {
      title: '上传日期 ',
      dataIndex: 'time',
      key: 'time',
      width:120
    },
  ]
  const [selectedRows, setSelectedRows]=useState<any>([])
  const [options, setOptions] = useState([])
  const [loading, setLoading] = useState(false)
  useEffect(()=>{
    
    if (urls) {

      if (urls.split){
        urls = urls.split(',')||[]
       
        urls = urls.filter((e:any)=>e)
    
      }
    
      var temp:any
      if (urls.map){
        temp = (urls||[]).filter((e:any)=>e).map((u:any,index:number)=>{
          var result:any = getFromUrl(u)
          result.key = index
          return result
        })
      }
      

      
      setOptions(temp)
    }
  },[])

  const  fetchBlob = async (url:string,method="POST",body=null)=>{
   
    
    console.log('fetchBlob:',url)
    const response = await window.fetch(url,{method,body,headers:{
      // "Accept":"application/json",
      // "Content-Type":"application/json",
      // "X-Requested-With":"XMLHttpRequest"
    }})
    const blob = await response.blob()
    console.log('fetchBlob:',blob)
    return blob
  }
  const downloadFile = (url:string,filename:string)=>{
    console.log('download')
    const a = document.createElement('a')
    a.style.display = 'none'
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
  }


  
  return (
  
    mode=='table'?<>
    
        <Table rowKey='key' rowSelection={{
          onChange: (_, selectedRows) => {
            setSelectedRows(selectedRows);
          },
        }} columns={cols} dataSource={options} pagination={false}/>
        <CustomDivider/>
        <Row>
          <Button loading={loading} type="primary" onClick={()=>{
            if (selectedRows.length==0){
              Modal.error({title:'请选择要下载的文件'})
              return
            }
            if (loading){
              Modal.error({title:'正在下载中...'})
              return
            }
            setLoading(true)
            const zip = new JSZip();
            selectedRows.forEach((row:any)=>{
              zip.file(row.name,fetchBlob(row.url))
              // zip.file(row.name,fetchBlob("http://fzrb.fznews.com.cn/index.php?r=qiyehao/attachment/file&savepath=/www/web/fzrb.fznews.com.cn/&attachment=attachment/upload/finance/20250618/876fd73a0fa8acc4ecf70b95eedc9e51.pdf"))
            })
            zip.generateAsync({type:"blob"}).then(blob=>{
     
              const url = window.URL.createObjectURL(blob)
              console.log('打包完成 download url:',url)
              downloadFile(url,"合同附件.zip")
              setLoading(false)
            })

          }}>下载</Button>
        


        </Row>
    </>:
    
    <>
      <List
      itemLayout="horizontal"
      dataSource={options}
      renderItem={(item:any,index:number) => (
       
          <List.Item key={'l'+index}>
            <List.Item.Meta
              avatar={item.attach?<Tag color='blue' style={tag} >补</Tag>:<FilePptOutlined style={{fontSize:'25px',color:'#108ee9'}}/>}
              title={<a href={item.url} target="blank">{item.name||item.url}</a>}
              description=""
            />
            <div style={{marginRight:'10px'}}>{item.time}</div>
          </List.Item>
       
      )}
    />
    </>
  )
}
export default Filescard