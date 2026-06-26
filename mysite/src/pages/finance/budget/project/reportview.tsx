import React, { useEffect, useRef, useState } from 'react';
import { Button, Card, Col, Form, Input, Modal, Row, Space, Statistic } from 'antd';

import { alterproreport, getreportbyprojectid } from './service';
import UEditorComponent from '../../UEditorComponent';
import Budgetdetail from '../budget/budgetdetail';


const ReportView: React.FC<{id:any,field:any,edit?:boolean,onChange?:Function}> = ({id,field,edit=false,onChange}) => {
  const [form] = Form.useForm();
  const editorRef = useRef<any>();
  const [ueditorData, setUeditorData] = useState<any>('');
  const [text,setText]=useState('')
  const [first,setFirst]=useState(false)
  const [isSaving, setIsSaving] = useState(false);
  // var text = ''
  const [ueditorConfig] = useState({
    initialFrameWidth: 679,
    initialFrameHeight: 297*2,
    placeholder:'请输入报告内容'
  });
  const autoSaveInterval = useRef<NodeJS.Timeout | null>(null);
  const setValue=(e:any)=>{
    setText(e)
  }
  useEffect(()=>{
    
      getreportbyprojectid({id,field}).then((res:any)=>{
        if(res && res.data){
          
          setUeditorData(res.data)
    
          setValue(res.data)
          
        }else{
          setUeditorData('')
          setValue('')
        }
        setFirst(true)
      })
    
      autoSaveInterval.current = setInterval(() => {
        autosave()
        console.log(new Date()+' 自动保存')
      }, 5 * 60 * 1000);

      return () => {
        if (autoSaveInterval.current) {
          clearInterval(autoSaveInterval.current);
          autoSaveInterval.current = null;
        }
      };

   
  },[])
  const autosave=()=>{
    var par:any={id}
    // 获取编辑器内容
    par[field]=editorRef?.current?.getUEContent();
    console.log('：',par)
    if (!par[field]) return
    alterproreport(par).then((res:any)=>{
        if (res.errorMessage) {
          Modal.error({
            title: res.errorMessage,
          });
        }else{
          onChange && onChange(text)
        }
      })
  }
  const save = ()=>{
    var par:any={id}
      par[field]=text
    if (id && field){
      
      alterproreport(par).then((res:any)=>{
        if (res.errorMessage) {
          Modal.error({
            title: res.errorMessage,
          });
        }else{
          Modal.success({
            title: '操作成功',
          })
          onChange && onChange(text)
        }
      })
    }
  }
  

  return (
    <div>

      {
        !edit && text &&
        <div dangerouslySetInnerHTML={{ __html: text }} />
      }
      {
        !edit && !text &&
        <div>报告内容为空</div>
      }
      {
        edit  && first &&
        <>
        <Budgetdetail  id={id} showTab={true} show={'final'}></Budgetdetail>
        <Form  form={form} >

          <Form.Item labelCol={{span: 3, offset: 30}} label="" name="report" >

            <UEditorComponent
              ref={editorRef}
              editorid={field+id}
              config={ueditorConfig}
              initData={ueditorData}
              setContent={(e:any) => {
                console.log('set content:', e)
                setValue(e)
                onChange && onChange(e)
              }}
            />

        </Form.Item>
        <Form.Item >

          <Space>
            <Button type="default" onClick={()=>{
              save()

            }}>
            保存内容
            </Button>

  
        </Space>
        </Form.Item>
 
        </Form>
        </>
        
      }
    </div>
  )

}

export default ReportView;