import { TableListItem } from "@/pages/admin/Department/data";
import { MenuOutlined, MinusOutlined, PlusOutlined } from "@ant-design/icons";
import { PageContainer, ProColumns, ProFormColumnsType, ProFormInstance, ProTable } from "@ant-design/pro-components";
import { Affix, Button, Drawer, Layout, List, Modal, message } from "antd";
import { useRef, useState } from "react";
import { TableListPagination } from "../project/data";
import { useHistory, useLocation } from "react-router-dom";
import { deldict, getdictlist } from "./service";
import { ActionType } from "@ant-design/pro-table";
import Dicttypeselect from "./dicttypeselect";
import Adddict from "./adddict";
import { AGENTID } from "../config";
const Dictlist: React.FC<{agentid?:any}> = ({agentid}) => {
  const proTableFormRef = useRef<ProFormInstance>();
  const location = useLocation() as any;
  const actionRef = useRef<ActionType>();
  const treeRef = useRef();
  const [data,setData] = useState({})
  const [showModal,setShowModal]=useState(false)
  const [open, setOpen] = useState(false);
  const [params, setParams] = useState({type:location.query.type,agentid});
  var [refresh,setRefresh] = useState(0)
  
  const history = useHistory();
  const columns: ProFormColumnsType<TableListItem>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '类型',
      dataIndex: 'type',
      renderFormItem: (_, { type, defaultRender, ...rest }, form) => {

        return (
          <Dicttypeselect  onChange={onDictChange}/>

        )
      }
    },
    {
      title: '子类型',
      dataIndex: 'subtype',
    },
    {
      title: '名称',
      dataIndex: 'label',
    },
    {
      title: 'key值',
      dataIndex: 'value',
    },
    {
      title: '涉及部门',
      dataIndex: 'dept',
      hideInSearch: false,
      render:(_,entity:any) => (<span>
          {typeof entity.dept === 'string'?('相关部门'+entity.dept.split(',').length+'个'):''}
        
      </span>)
    },

    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_, entity:any) => [
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
                  deldict({id:entity.id}).then((res:any)=>{
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
  const onDictChange = (e:any)=>{
    params.type = e
    setParams(params)
    proTableFormRef.current?.setFieldsValue({type:e})
    actionRef.current?.reload()
  }
  const  addonchange = (e:any)=>{

    actionRef.current?.reload()
    setShowModal(false)
  } 

  return (
    <PageContainer
      title='字典管理'
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
              headerTitle="流程角色用户列表"
              actionRef={actionRef}
              formRef={proTableFormRef}
              rowKey={record=>record.id}
              params={params}
              search={{
                labelWidth: 120,
              }}

              request={(params, sorter, filter) => {
                document.body.scrollTop = document.documentElement.scrollTop = 0;
                if (agentid) params.agentid = agentid
                return getdictlist(params);
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
                    setData(params||{})
                    setRefresh(++refresh)
                    setShowModal(true)
                  }}
                >
                  <PlusOutlined /> 新建
                </Button>,
                <Button
                  type="primary"
                  key="delete"
                  onClick={async () => {
                   
                  }}
                >
                  <MinusOutlined /> 批量删除
                </Button>,
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
        <Adddict key={refresh} data={data} onChange={addonchange} agentid={AGENTID}/>
      </Modal>
    </PageContainer>
  );
}
export default Dictlist;
