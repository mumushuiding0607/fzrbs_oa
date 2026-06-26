import { ActionType, PageContainer, ProTable } from '@ant-design/pro-components';
import React, { useRef, useState } from 'react';
import { Avatar, Button, Card,DatePicker,Modal ,Segmented,Space} from 'antd';
import { useLocation  } from 'react-router-dom';
import AddC from './addc';
import { useHistory } from 'react-router-dom';
import { delcontract, getlist } from './service';
import { PlusOutlined } from '@ant-design/icons';

import moment from 'moment';
import Filescard from './filescard';
import { currentUser } from '@/services/ant-design-pro/api';
import { useModel } from 'umi';
import UserAutocomplete from '../common/userAutocomplete';
import Companyselect from '../../company/companyselect';
import RangeNumber from '../../contract/RangeNumber';
import DepartmentTreeSelect from '../common/department_treeselect';
import { BalanceTypes } from '../config';

// style

// data



// dom
const Listc:React.FC = () =>{
  const history = useHistory();
  const location = useLocation();
  const [contract, setContract] = useState({})
  var [refreshKey, setRefreshKey]= useState(0)
  const [modal1, setModal1] = useState(false)
  const [modal2, setModal2] = useState(false)
  const [urls, setUrls] = useState('')
  const { RangePicker } = DatePicker;
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  const ref = useRef<ActionType>();
  // 列标
  let columns = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      search:false,
      render:(text,record,index:number)=>`${index+1}`
    },
    {
      title: '合同名称',
      dataIndex: 'title',
      key: 'title'
    },
    {
      title: '合同编号',
      dataIndex: 'serial',
      key: 'serial',
    },
    {
      title: '合同总价',
      dataIndex: 'amount',
      key: 'amount'
    },
    {
      title: '签订日期',
      dataIndex: 'signdate',
      key: 'signdate',
      width: 110,
      render: (_, record) => {
        return record.signdate?moment(record.signdate).format('YYYY-MM-DD'):''
      },
    },
    {
      title: '类型',
      dataIndex: 'typename',
      key: 'typename', 
    },
    {
      title: '操作',
      key: 'action',
      search:false,
      render: (_:any, record:any) => (
    
        <>
          <div>
            <Space size="middle">
                <a onClick={()=>{
                  history.push({pathname:'/finance/budget/balance/pdetail',query:{relatedcontractid:record.contractids,type:record.type,projectid:location.query.projectid}})
                }}>查看</a>
                <a onClick={()=>{
                  if (currentUser.wxuserid!=record.creator){
                    Modal.error({title:'只有创建人才能删除'})
                    return 
                  }
                  Modal.confirm({
                    title: '确定要删除吗？',
                    okText: '确认',
                    cancelText: '取消',
                    onOk: async () => {
                      delcontract({id:record.id}).then((res)=>{
                        if (res.errorMessage){
                          Modal.error({title:res.errorMessage})
                        } else {
                          ref.current?.reload()
                        }
                      })
                    },
                  });
                }}>删除</a>
            </Space>
          </div>
        </>
      ),
    }
  ]
  // method

  const onAddcSuc = (e:any)=>{
   
    ref.current?.reload()
    setModal1(false)

  }

  return (
    <>
      <PageContainer title="合同列表" header={{breadcrumb: {
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
            if (location.query.projectid) params.projectid = location.query.projectid
            if (params.partaname && params.partaname[0]) params.parta = params.partaname[0].value
            if (params.partbname && params.partbname[0]) params.partb = params.partbname[0].value
            if (params.amount) {
              if (params.amount[0]!=undefined) params.amountfloor = params.amount[0]
              if (params.amount[1]!=undefined) params.amountceil = params.amount[1]
            }
            if (params.signdate){
              params.signdatestart = params.signdate[0]+' 00:00:00'
              params.signdateend = params.signdate[1]+' 23:59:59'
            }
            if (params.signdate){
              params.signdatestart = params.signdate[0]+' 00:00:00'
              params.signdateend = params.signdate[1]+' 23:59:59'
            }
            if (params.date){
              params.starttime = params.date[0]+' 00:00:00'
              params.endtime = params.date[1]+' 23:59:59'
            }
            if (params.signdept) {
              params.signdeptid = params.signdept.join(',')
            }
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
                setContract({type:location.query.type,projectid:location.query.projectid})
                setRefreshKey(++refreshKey)
                setModal1(true)
              }}
            >
              <PlusOutlined /> 新建
            </Button>
          ]}
        />
        <Modal
          title="新增合同"
          style={{ top: 20 }}
          visible={modal1}
          onOk={() => setModal1(false)}
          onCancel={() => setModal1(false)}
          footer= {null}
        >
          <AddC key={refreshKey} data={contract} onChange={onAddcSuc}/>
        </Modal>

      </PageContainer>
    </>
  )
  
}
export default Listc