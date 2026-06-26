import { TableListItem } from "@/pages/admin/Department/data";
import { MenuOutlined, MinusOutlined, PlusOutlined } from "@ant-design/icons";
import { PageContainer, ProColumns, ProFormColumnsType, ProFormInstance, ProTable } from "@ant-design/pro-components";
import { Affix, Button, Drawer, Layout, List, Modal, message } from "antd";
import { useRef, useState } from "react";

import { useHistory } from "react-router-dom";
import Add from "./addpower";
import { delrole, getrolelist } from "./service";


import { ActionType } from "@ant-design/pro-table";

const Powerlist: React.FC = () => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const treeRef = useRef();
  const [role,setRole] = useState({})
  const [showModal,setShowModal]=useState(false)
  const [open, setOpen] = useState(false);
  const [params, setParams] = useState({});
  var [refresh,setRefresh] = useState(0)
  const history = useHistory();
  const columns: ProColumns = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '角色',
      dataIndex: 'role',

    },
    {
      title: '姓名',
      dataIndex: 'username',
    },
    
    {
      title: '涉及部门',
      dataIndex: 'dept',
      
      hideInSearch: false,
      render:(text:string,entity) => (<span>
          {typeof entity.dept === 'string'?('相关部门'+entity.dept.split(',').length+'个'):''}
        
      </span>)
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_, entity) => [
        <a
          key="edit"
          onClick={() => {
            if (entity.dept && typeof entity.dept === 'string') entity.dept = entity.dept.split(',')
            setRole(entity)
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
                  delrole({id:entity.id}).then((res:any)=>{
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

    var p = proTableFormRef.current?.getFieldsValue()||{}
    p.role = e
    setParams(p)
    actionRef.current?.reload()
  }
  const  addonchange = (e:any)=>{

    actionRef.current?.reload()
    setShowModal(false)
  } 
  const onDeptSel = (e:any)=>{
    var p = proTableFormRef.current?.getFieldsValue()||{}
    p.dept = e.join(',')
    setParams(p)

    actionRef.current?.reload()
    
  }
  return (

          <>
            
            <ProTable
              headerTitle="浏览权限列表"
              actionRef={actionRef}
              params={params}
              formRef={proTableFormRef}
              rowKey={record=>record.id}
              search={false}

              request={(params, sorter, filter) => {
                document.body.scrollTop = document.documentElement.scrollTop = 0;
             
                return getrolelist(params);
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
                    setRole({})
                    setRefresh(++refresh)
                    setShowModal(true)
                  }}
                >
                  <PlusOutlined /> 新建
                </Button>,

              ]}
            />

      <Modal
        title="角色"
        style={{ top: 20, }}
        visible={showModal}
        onOk={() => setShowModal(false)}
        onCancel={() => setShowModal(false)}
        footer={null}
      >
        <Add key={refresh} data={role} onChange={addonchange}></Add>
      </Modal>
    </>
  );
}
export default Powerlist;
