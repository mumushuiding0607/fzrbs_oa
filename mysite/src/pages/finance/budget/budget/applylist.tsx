import { ActionType, PageContainer, ProTable } from '@ant-design/pro-components';
import React, { useEffect, useRef, useState } from 'react';
import { Modal ,Space,Avatar,Segmented,Radio} from 'antd';

import { useHistory } from 'react-router-dom';
import { getflowinfo, getlist,getprojectbythirdno } from './service';

import Apply from './apply';
import { AGENTID, ProjectStatesEnum, StatusCn } from '../config';
import { useLocation } from 'umi';
import { getdictlist } from '../dict/service';
import moment from 'moment';

// style

// data



// dom
const Applylist:React.FC = () =>{
  const history = useHistory();
  const [project, setProject] = useState({})
  var [refreshKey, setRefreshKey]= useState(0)
  const [modal, setModal] = useState(false)
  const ref = useRef<ActionType>();
  const location = useLocation();
  const searchParams = new URLSearchParams(location.search);
  const [tabtype, setTabtype]=useState<any>(parseInt(searchParams.get('projectstate')||'-1'))
  const [tabs, setTabs]=useState<any>([])
  useEffect(()=>{
    getdictlist({type:'审批类型',agentid:AGENTID,orderby:'value asc'}).then((res:any)=>{
      if (res.data){
        res.data.unshift({label:'我已审',value:-1})
        setTabs(res.data)
      }
    })
  },[])

  // 列标
  let columns = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 80,
      render:(text:any,record:any,index:number)=>`${index+1}`
    },
    {
      title: '项目名称',
      dataIndex: 'title',
      key: 'title',
      width: 200,
    },
    {
      title: '部门/经办',
      dataIndex: 'department',
      width:150,
      render:(_:any,record:any)=>(
        <div style={{display:'flex',flexDirection:'column'}}>
          <span style={{color:'gray',fontSize:'12px'}}>{record.department}</span>
          <span>{record.name}</span>

        </div>
      )
    },
    
    {
      title: '合作单位',
      dataIndex: 'partaname',
      hideInSearch:true,
      width:180
    },
    {
      title: '项目收入',
      dataIndex: 'budgetincome',
      key: 'budgetincome',
      width: 120,
      hideInSearch:true,
      className:'right',
      render: (_:any,record:any)=>!Number.isNaN(record.finalincome||record.budgetincome)?parseFloat(record.finalincome||record.budgetincome).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0,
    },
    {
      title: '合同',
      dataIndex: 'contractids',
      key: 'contractids',
      hideInSearch:true,
      width: 80,
      search:false,
      render: (_:any,record:any)=>(
        <>
          {
            record.contractids?'已签':'未签'
          }
        </>
      )
    },
    {
      title: '立项时间',
      dataIndex: 'starttime',
      valueType: 'dateRange',
      width: 150,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        return moment(record.starttime).format('YYYY-MM-DD')
      },
    },
    {
      title: '审批人',
      dataIndex: 'approvalUsername',
      
      width:100
    },
    {
      title: '操作',
      key: 'action',
      render: (_:any, record:any) => (
    
        <>
          <div>
            {
               <Space size="middle">
                  {
                    tabtype!=-1 &&record.thirdno!=null &&
                    <a href='#' onClick={()=>{
          
                      getprojectbythirdno({thirdno:record.thirdno}).then(res=>{
                        if(res.data) {
                          setRefreshKey(++refreshKey)
                          setProject(res.data)
                          setModal(true)
                        }
                      })
                    }}>审批</a>
                  }
                  {
                    tabtype==-1 &&
                    <a href='#' onClick={()=>{
          
                      getflowinfo({projectid:record.id}).then((res:any)=>{
                        if(res.errorMessage){
                          Modal.error({title:res.errorMessage})
                        }else{
                          setRefreshKey(++refreshKey)
                          setProject(res.basic)
                          setModal(true)
                        }
                      })
                    }}>查看</a>
                  }
              </Space>
            }
          </div>
        </>
      ),
    }
  ]
 
  const onApplyChange = (e:any)=>{

    ref.current?.reload()
  }
  const onTabtypechange = (e:any)=>{
    setTabtype(e.target.value)
    ref.current?.reload()
  }

  return (
    <>
      <PageContainer title="审批列表" header={{breadcrumb: {
          routes: [
            {
              path: '',
              breadcrumbName: '上一页',
            },
            {
              path: '/oa/finance/budget/index/',
              breadcrumbName: '首页',
            }
            
          ],itemRender(route, params, routes, paths) {
            if (route.breadcrumbName=='上一页'){
              return <a href='#' onClick={()=>history.goBack()}>{route.breadcrumbName}</a>
            } else {
              return <a href={`/${paths.join("/")}`}>{route.breadcrumbName}</a>
            }
            
          
          },

        },}}>

        <ProTable
          rowKey={record=>record.id}
          request={(params, sorter, filter) => {
            document.body.scrollTop = document.documentElement.scrollTop = 0;
            params.projectstate = tabtype
            return getlist(params)
          }}
          actionRef={ref}
          columns={columns}
          form={{
           
          }}
          toolBarRender={() => [

          ]}
          headerTitle={
            <Radio.Group key={refreshKey} value={tabtype} onChange={onTabtypechange} optionType="button" size='large' buttonStyle="solid" style={{ margin: 0 }}>
              {
                 tabs.filter((e:any)=>e.label.indexOf('已提交')==-1).map((e:any,index:any)=>{
                  return <Radio.Button value={e.value}>{(e.value>0?'待':'')+e.label}</Radio.Button>
                 })
              }
             
            </Radio.Group>
          }
        />
      <Modal
        title="流程审批"
        style={{ top: 20 }}
        visible={modal}
        width={'60vw'}
        onOk={() => setModal(false)}
        onCancel={() => setModal(false)}
        footer={null}
      >
        <Apply key={refreshKey} data={project} onchange={onApplyChange}/>
      </Modal>
      </PageContainer>
    </>
  )
  
}
export default Applylist