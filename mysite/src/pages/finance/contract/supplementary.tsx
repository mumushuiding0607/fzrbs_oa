import { Button, DatePicker, Input, InputNumber, Modal, Space, Timeline, Tooltip } from "antd"
import moment from "moment"
import { useRef, useState } from "react"

import MyUploadFile from "@/components/MyUploadFile"
import { CloseCircleOutlined, CloseOutlined } from "@ant-design/icons"
import { uploadDelete } from "@/services/ant-design-pro/api"
import { extractParameterFromUrl, setToUrl } from "../utils"



const Supplementary:React.FC<{defaultValues:[],onChange?:Function,editable?:boolean,onDeleteFile?:Function,onaddFile?:Function}> = ({onaddFile,defaultValues=[],onChange,onDeleteFile,editable=true})=>{

  var [values,setValues] = useState<any>(defaultValues||[])
  const [name,setName]=useState('')
  const [amount,setAmount]=useState(0)
  const [loading,setLoading]=useState(false)
  const uploadRef = useRef<any>();
  const [showModal,setShowModal] = useState(false)
  const [urls,setUrls] = useState<any>('')
  if (typeof values=='string') {
    values = JSON.parse(values)
  }
  const add = ()=>{
    if (loading)return
    if (!name) {
      Modal.error({title:'协议名称不能为空'})
      return
    }

    setLoading(true)
    setTimeout(() => {
      setLoading(false)
    }, 3000);
    var filename = extractParameterFromUrl(urls,'name')
    var nval = {name,amount,urls,file:filename,date:moment().format('YYYY-MM-DD')}
    var temp:any = [nval,...values]

    setValues(temp)
  
    onaddFile && onaddFile({url:nval.urls,name:nval.file})
    onChange && onChange(temp)
    
    setUrls('')
    
  }
  const onAddFile = ()=>{


    const uploads = uploadRef?.current?.getFileList();
   
    
    if (uploads && uploads.map) {
      var temp = uploads.map((u:any)=>{
        var url = setToUrl(u)
        url+='&attach=1'
        return url
      }).join(',')

      setUrls(temp)
      
    } else {
      Modal.error({title:'请上传附件'})
      return
    }
    Modal.success({title:'附件上传成功'})
    setShowModal(false)
  }
  const remove = (e:any)=>{
    Modal.confirm({
      title: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        var temp = values.filter((v:any)=>v.name!=e.name)
        setValues(temp)
        onChange && onChange(temp)
        if (e.urls && e.urls.length>0){
          uploadDelete({ fileurl:e.urls, protect: 0 })
          onDeleteFile&&onDeleteFile(e.urls)
        }
      },
    });
  }
  return (<>
  

  <div id="supplementary" style={{width:'100%'}}>

      {
        editable &&

        <div style={{marginBottom:'20px',width:'100%',display:'flex',flexDirection:'row',justifyContent:'space-between'}}>
          <Input style={{width:'41%'}}   placeholder="协议名称" onChange={(e:any)=>{
       
            setName(e.target.value?e.target.value.replaceAll(' ',''):e.target.value)
          }} />
          <InputNumber style={{width:'29%'}}  prefix="￥" placeholder="补充金额" onChange={(e:any)=>{
            setAmount(e)
          }} />
          <Button style={{ width: '15%' }} loading={loading} type="default" onClick={()=>setShowModal(true)}>
            附件
          </Button>
          <Button style={{ width: '15%' }} loading={loading} type="primary" onClick={add}>
            添加
          </Button>
        </div>
      }
      <Timeline>
          {
            (values || []).map((e:any,index:any)=>{
              return (
              
              <Timeline.Item key={'stimtline'+index}>
                  <div>
                    <span onClick={()=>{editable && remove(e)}}>{(e.date?e.date.substring(0,10):e.date)}：《{e.name}》</span>
                    <span onClick={()=>{editable && remove(e)}}>,协议金额￥{e.amount}元</span>
                  
                    {
                      e.file && e.file.length>0 &&
                      <>
                      <span><a href={e.urls}>{'《'+e.file+'》'}</a></span>
                      <span><CloseOutlined onClick={()=>{
                        Modal.confirm({
                          title: '要删除附件吗？',
                          okText: '确认',
                          cancelText: '取消',
                          onOk: async () => {
                            uploadDelete({ fileurl:e.urls, protect: 0 })
                            onDeleteFile&&onDeleteFile(e.urls)
                            delete(values[index].file)
                            delete(values[index].urls)
                            setValues(values)
                            onChange && onChange(values)
                            
                          },
                        })
                      }}  style={{color:'red',fontSize:"18px"}}/></span>
                      </>
                      
                    }
                  </div>
              </Timeline.Item>
                
             )
            })
          }
      </Timeline>
      
  </div>
  <Modal
        title="添加附件"
        style={{ top: 20, }}
        visible={showModal}
        onOk={() => {
          onAddFile()
        }}
        onCancel={() => setShowModal(false)}
        
      >
        <MyUploadFile
              
              name="fileurls"
              label="附件："
              max={1}
              multiple={false}
              accept="*/*"
              maxSize={100}
              listType="picture-card"
              defaultImage={[]}
              uploadPath="contract"
              uploadType={2}
              ref={uploadRef}
            />
      </Modal>
  
  </>)
}

export default Supplementary