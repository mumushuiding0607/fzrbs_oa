import { ActionType, PageContainer, ProTable } from '@ant-design/pro-components';
import React, { useRef, useState } from 'react';
import { Button, Card,Modal ,Table,Pagination,Form,Input,
  Select,DatePicker,
  TreeSelect,Space, Tag ,
  MenuProps,
  TableColumnsType} from 'antd';
import { useLocation  } from 'react-router-dom';
import { useHistory } from 'react-router-dom';
import { delbalance, getlist } from './service';
import { PlusOutlined } from '@ant-design/icons';
import { currentUser } from '@/services/ant-design-pro/api';
import { useModel } from 'umi';
import Addbalance from './add';
import { ProjectStatesEnum } from '../config';
// style

// data



// dom
const Listc:React.FC = () =>{
  const history = useHistory();
  const location = useLocation();
  const query = location.query;
  const [contract, setContract] = useState({projectid:0,id:0,type:0})
  var [refreshKey, setRefreshKey]= useState(0)
  const [modal1, setModal1] = useState(false)
  contract.projectid = query.projectid
  const ref = useRef<ActionType>();
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  // 列标
  let columns = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      search: false,
      render:(text,record,index:number)=>`${index+1}`,
      width: 80
    },
    {
      title: '类型',
      dataIndex: 'typename',
      render:(_,record,index:number)=>(<>
      
            <Tag color={record.typename=='收入'?'volcano':'green'}>
              {record.typename}
            </Tag>
      </>),
    },

    {
      title: '项目名称',
      dataIndex: 'title',
      key: 'title',
      render:(_:any,record:any)=>(
        <>
          <span style={{color: '#1890ff'}} onClick={()=>{
            history.push({pathname:'/finance/budget/balance/pdetail',query:{id:record.id,title:query.title,type:query.type,projectstate:query.projectstate}})
          }}>
            {record.title}
          </span>
        </>
      )
    },
    
    {
      title: '预算金额',
      dataIndex: 'budget',
      key: 'budget',
      search: false,
    },

    {
      title: '决算金额',
      dataIndex: 'final',
      key: 'final',
      search: false,
    },

    {
      title: '开票金额',
      dataIndex: 'invoiced',
      key: 'invoiced',
      search: false,
    },
    {
      title: '操作',
      key: 'action',
      search: false,
      width: 100,
      render: (_:any, record:any) => (
    
        <>
        {
          query.projectstate!=ProjectStatesEnum.FINISH && <div>
          <Space size="middle">
              <a onClick={()=>{
                

                setRefreshKey(++refreshKey)
                
                setContract(record)
                
                setModal1(true)
              }}>更新</a>
              <a onClick={()=>{
                delbalance({id:record.id}).then((res)=>{
                  if (res.errorMessage){
                    Modal.error({
                      title: res.errorMessage
                    })
                  } else {
                    ref.current?.reload()
                  }
                })

              }}>删除</a>
          </Space>
        </div>
        }
          
        </>
      ),
    }
  ]
  // method

  const onAddcSuc = (e:any)=>{
    console.log(e)
    ref.current?.reload()
    setModal1(false)

  }

  return (
    <>
      <PageContainer title={query.title?'收支列表-'+query.title:'收支列表'} header={{breadcrumb: {
          routes: [
            {
              path: '',
              breadcrumbName: '上一页',
            },
            {
              path: '/finance/budget/index/',
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
            if (query.projectid) params.projectid = query.projectid
            if (query.type) params.type = query.type
            return getlist(params);
          }}
          actionRef={ref}
          columns={columns}
          form={{
           
          }}
          toolBarRender={() => [
            <Button
              type="primary"
              key="primary"
              onClick={() => {
                if (query.projectstate==ProjectStatesEnum.FINISH){
                  Modal.error({title:'项目已提交考核，不能再更新！'})
                  return
                }
                setRefreshKey(++refreshKey)
                setContract({projectid: query.projectid,type:query.type})
                setModal1(true)
              }}
            >
              <PlusOutlined /> 新建
            </Button>
          ]}
        />
        <Modal
          title="收支"
          style={{ top: 20 }}
          visible={modal1}
          onOk={() => setModal1(false)}
          onCancel={() => setModal1(false)}
          footer= {null}
        >
          <Addbalance key={refreshKey} data={contract} onChange={onAddcSuc}/>
        </Modal>
      </PageContainer>
    </>
  )
  
}
export default Listc