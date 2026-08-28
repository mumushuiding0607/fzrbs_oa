import { TableListItem } from "@/pages/admin/Department/data";
import { MinusOutlined, PlusOutlined } from "@ant-design/icons";
import { ProColumns, ProFormColumnsType, ProFormInstance, ProTable } from "@ant-design/pro-components";
import { Button, Modal, message } from "antd";
import { useRef, useState } from "react";
import { deldict, getdictlist } from "./service";
import { ActionType } from "@ant-design/pro-table";
import Dicttypeselect from "./dicttypeselect";
import Adddict from "./adddict";
import { AGENTID } from "../config";

const Dictlist: React.FC<{ agentid?: any; type?: string }> = ({ agentid, type }) => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const [data, setData] = useState<any>({});
  const [showModal, setShowModal] = useState(false);
  const [params, setParams] = useState<any>({ type, agentid });
  var [refresh, setRefresh] = useState(0);

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
      hideInForm: true,
      renderFormItem: (_, { type: colType, defaultRender, ...rest }, form) => {
        return <Dicttypeselect onChange={onDictChange} value={params.type} />;
      }
    },
    {
      title: '子类型',
      dataIndex: 'subtype',
      hideInSearch: true,
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
      hideInSearch: true,
      render: (_, entity: any) => (
        <span>
          {typeof entity.dept === 'string' ? `相关部门${entity.dept.split(',').length}个` : ''}
        </span>
      )
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      hideInSearch: true,
      render: (_, entity: any) => [
        <a
          key="edit"
          onClick={() => {
            if (entity.dept && typeof entity.dept === 'string') {
              entity.dept = entity.dept.split(',');
            }
            setData(entity);
            setRefresh(refresh + 1);
            setShowModal(true);
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
                const res: any = await deldict({ id: entity.id });
                if (res.errorMessage) {
                  Modal.error({ title: res.errorMessage });
                } else {
                  message.success('删除成功');
                  actionRef.current?.reload();
                }
              },
            });
          }}
        >
          删除
        </a>,
      ],
    },
  ];

  const onDictChange = (e: any) => {
    setParams({ type: e, agentid });
    proTableFormRef.current?.setFieldsValue({ type: e });
  };

  const addonchange = () => {
    actionRef.current?.reload();
    setShowModal(false);
  };

  return (
    <>
      <ProTable
        headerTitle="字典列表"
        actionRef={actionRef}
        formRef={proTableFormRef}
        rowKey={(record: any) => record.id}
        params={params}
        search={{
          labelWidth: 120,
        }}
        request={(params: any) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          const reqParams = { ...params };
          if (agentid) reqParams.agentid = agentid;
          return getdictlist(reqParams);
        }}
        columns={columns as ProColumns<TableListItem>[]}
        toolBarRender={() => [
          <Button
            type="primary"
            key="primary"
            onClick={() => {
              setData({ type });
              setRefresh(refresh + 1);
              setShowModal(true);
            }}
          >
            <PlusOutlined /> 新建
          </Button>,
        ]}
      />

      <Modal
        title={data?.id ? '修改' : '新建'}
        style={{ top: 20 }}
        visible={showModal}
        onCancel={() => setShowModal(false)}
        footer={null}
      >
        <Adddict key={refresh} data={data} onChange={addonchange} agentid={AGENTID} />
      </Modal>
    </>
  );
};

export default Dictlist;