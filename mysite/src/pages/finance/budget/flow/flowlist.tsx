import { TableListItem } from "@/pages/admin/Department/data";
import { MenuOutlined, MinusOutlined, PlusOutlined } from "@ant-design/icons";
import { PageContainer, ProColumns, ProFormColumnsType, ProFormInstance, ProTable } from "@ant-design/pro-components";
import { Affix, Button, Drawer, Layout, List, Modal, message } from "antd";
import { useRef, useState } from "react";

import { useHistory, useLocation } from "react-router-dom";

import { ActionType } from "@ant-design/pro-table";
import { delflow, getlist, save } from "./service";
import Add from "./add";


const Flowlist: React.FC = () => {
  const proTableFormRef = useRef<ProFormInstance>();
  const location = useLocation();
  const actionRef = useRef<ActionType>();
  const treeRef = useRef();
  const [data,setData] = useState({})
  const [showModal,setShowModal]=useState(false)
  const [open, setOpen] = useState(false);
  const [params, setParams] = useState({type:location.query.type});
  var [refresh,setRefresh] = useState(0)
  
  const history = useHistory();
  const columns: ProColumns = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      hideInForm: true,
      hideInTable: true,
      hideInDescriptions: true,
    },

    {
      title: '流程名称',
      dataIndex: 'templatename',
    },
    {
      title: '流程id',
      dataIndex: 'templateid',
    },
    {
      title: '金额大于',
      dataIndex: 'lamount',
    },
    {
      title: '金额小于',
      dataIndex: 'hamount',
    },
    {
      title: '询价',
      dataIndex: 'inquire',
      render: (text:any,record:any)=>(
        <>
          {text==1?'是':''}
        </>
      )
    },

    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_:any, entity:any) => [
        <a
          key="edit"
          onClick={() => {
            if (entity.dept && typeof entity.dept === 'string') entity.dept = entity.dept.split(',')
            setData(entity)
            setRefresh(++refresh)
            setShowModal(true)
          }}
        >
          修改
        </a>,
        <a
          key="delete"
          onClick={() => {
            Modal.confirm({
                title: '确定要删除吗？',
                okText: '确认',
                cancelText: '取消',
                onOk: async () => {
                  delflow({id:entity.id}).then((res:any)=>{
                    if (res.errorMessage){
                      Modal.error({title:res.errorMessage})
                    } else {
                      actionRef.current?.reload()
                    }
                  })
                },
              });
           }}
        >
          删除
        </a>,
      ],
    },
  ];

  const  addonchange = (e:any)=>{

    actionRef.current?.reload()
    setShowModal(false)
  } 

  return (
    <PageContainer
      title='流程设置'
      header={{
        breadcrumb: {
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
    
        },
      }}
    >
      

      <Layout>

        <Layout>
          <Layout.Content>
            
            <ProTable
              
              actionRef={actionRef}
              params={params}
              formRef={proTableFormRef}
              rowKey={record=>record.id}
              search={{
                labelWidth: 120,
              }}

              request={(params, sorter, filter) => {
                document.body.scrollTop = document.documentElement.scrollTop = 0;
             
                return getlist(params);
              }}
              columns={columns as ProColumns<TableListItem>[]}
              rowSelection={{
                onChange: (_, selectedRows) => {
            
                },
              }}
              tableAlertRender={false}
              toolBarRender={() => [
                <Button
                  type="primary"
                  key="primary"
                  onClick={() => {
                    setData({})
                    setRefresh(++refresh)
                    setShowModal(true)
                  }}
                >
                  <PlusOutlined /> 新建
                </Button>
              ]}
            />
          </Layout.Content>
        </Layout>
      </Layout>
      <Modal
        title="更新"
        style={{ top: 20, }}
        visible={showModal}
        onOk={() => setShowModal(false)}
        onCancel={() => setShowModal(false)}
        footer={null}
      >
        <Add key={refresh} data={data} onChange={addonchange}/>
      </Modal>
    </PageContainer>
  );
}
export default Flowlist;
