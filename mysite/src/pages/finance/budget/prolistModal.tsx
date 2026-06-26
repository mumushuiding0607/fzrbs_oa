import { ActionType, ProColumns, ProTable } from '@ant-design/pro-components';
import { Button, Modal, Tag } from 'antd';
import moment from 'moment';
import React, { useEffect, useRef, useState } from 'react';
import { getlist, getlist2 } from './project/service';
import Apply from './budget/apply';
import { downloadAsXlSX } from '../utils';
const ProlistModal:React.FC<{data?:any,visible:boolean,onVisibleChange:Function}> = ({data,visible=false,onVisibleChange}) =>{
  const [showModal,setShowModal] = useState(visible)
  const [modal,setModal] = useState(false)
  const [project, setProject] = useState<any>({})
  var [refreshKey, setRefreshKey] = useState(0)
  const [params,setParams]=useState<any>({})
  const ref = useRef<ActionType>();
  useEffect(()=>{
    setShowModal(visible)
    ref.current?.setPageInfo?.({ current: 1 });
    ref.current?.reload();  
  },[visible,data])
  let columns: ProColumns<any>[] = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      render:(text:any,record:any,index:number)=>`${index+1}`,
      width: 60,
      search:false,
      fixed:'left',
    },

    {
      title: '项目名称',
      dataIndex: 'title',
      key: 'title',
      width: 200,
      sorter: true,
      fixed:'left',
      search:false,
      render: (text:any,record:any)=>(
        <>
        <p style={{fontWeight:'bolder',margin:0}}>

        <span  onClick={()=>{
          setModal(true)
          record.id = record.projectid
          setProject(record)
          setRefreshKey(++refreshKey)
        }}>{text}</span>

          
            
        </p>
        </>
      )
    },

    {
      title: '项目编号',
      dataIndex: 'serial',
      sorter: true,
      width: 120,
      search:false
    },

  {
      title: '预算',
      dataIndex: 'budget',
      key: 'budget',
      sorter: true,
      width: 120,
      search:false,
      className:'right',
      render: (text:any)=>!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0,

    },
    {
      title: '决算',
      dataIndex: 'final',
      key: 'final',
      sorter: true,
      width: 120,
      search:false,
      className:'right',
      render: (text:any)=>!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0,

    },
    {
      title: '项目负责人/经办',
      dataIndex: 'department',
      sorter: true,
      search:false,
      width:150,
      render:(_:any,record:any)=>(
        <div style={{display:'flex',flexDirection:'column'}}>
    
          <span>{record.chargername+'/'+record.name}</span>

        </div>
      )
    },


    {
      title: '提交时间',
      dataIndex: 'submitdate',
      key: 'submitdate',
      search:false,
      sorter: true,
      width: 120,
      render: (_:any, record:any) => {
        return moment(record.submitdate).format('YYYY-MM-DD')
      },
    },

  ]

  return (
  <>
  <Modal
       
        style={{ top: 0,minHeight:'90vh',minWidth:'100vw' }}
        visible={visible}
        onOk={() => {
          onVisibleChange(false)
        }}
        onCancel={() => onVisibleChange(false)}
        footer={null}
      >

   <ProTable
    scroll={{x:'100%'}}
    pagination={{pageSize:20,showSizeChanger: true,}}
    actionRef={ref}
    rowKey={record=>record.id}
    search={false}
    request={(params, sorter, filter) => {
      params = {...params,...data}
      params.orderby = "serial asc"
      if (sorter){
        Object.keys(sorter).forEach((key)=>{
          var order = sorter[key]=='ascend'?'asc':'desc'
          params.orderby=key+" " + order

        })
      }

      params.directsubmit = 1
      var result = getlist2(params)
      setParams(params)


      return result;
    }}
    

    columns={columns}
    form={{
    
    }}
    toolbar={{

        filter: (
          <>


          </>
        ),
        actions: [
          <Button
            key="primary"
            type="primary"
            onClick={() => {
              const pageSize = params.pageSize
              params.current=1
              params.pageSize=10000
              getlist2(params).then((res:any)=>{
                 params.pageSize=pageSize
                if (res.errorMessage) {
                  Modal.error({title: res.errorMessage})
                } else {
                  var result = res.data.map((row:any,rowIndex:any)=>{
                  var arr:any = []
                  columns.forEach((h:any,index:number)=>{
                    
                    var temp:string = (row[h.dataIndex]||'').toString()
                    if (temp) {
                      temp = temp.replaceAll(',','，').trim()
                    }
                    switch (h.dataIndex) {
                      case 'index':
                        arr.push(rowIndex +1)
                        break
                      case 'final':
                        arr.push(row.final?row.final:'0')
                        break
                      case 'department':
                        arr.push(row.chargername+'/'+row.name)
                        break
                      case 'submitdate':
                        arr.push(row.submitdate?moment(row.submitdate).format('YYYY-MM-DD'):'')
                        break

                      default:
                        arr.push(temp)
                        break;
                    }
                    
                    
                  })

                  return arr
                })
                  var x = columns.map((t:any)=>t.title)
                  result.unshift(x)
                  downloadAsXlSX(result,'分类统计导出')
                }
              })
            }}
          >
            导出
          </Button>,

        ],
      }}

  />
          <Modal
              title="详情预览"
              style={{ top: 20 }}
              width="60vw"
              visible={modal}
              onOk={() => setModal(false)}
              onCancel={() => setModal(false)}
              footer={null}
            >
              <Apply key={refreshKey} data={project} />
            </Modal>
      </Modal>
  </>
  

  )
}
export default ProlistModal;

