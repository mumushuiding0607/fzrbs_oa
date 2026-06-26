import { ActionType, ProColumns, ProFormInstance, ProTable } from '@ant-design/pro-components';
import React, { useRef, useState, useEffect } from 'react';
import { Button, Modal, Popover, Tag, Input, Select, DatePicker } from 'antd';
import { PlusOutlined, SearchOutlined, PrinterOutlined, DownloadOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons';
import { useModel } from 'umi';

import { getPricelist } from './service';
import AddPrice from './AddPrice';
import Dictselect from '../budget/dict/dictselect';
import TableScrollSync from '../common/TableScrollSync';

interface PricelistProps {
  onChange?: () => void;
}

const Pricelist: React.FC<PricelistProps> = ({ onChange }) => {
  const actionRef = useRef<ActionType>();
  const formRef = useRef<ProFormInstance>();
  const [modalVisible, setModalVisible] = useState(false);
  const [editData, setEditData] = useState<any>(null);
  const [refreshKey, setRefreshKey] = useState(0);
  const [params, setParams] = useState<any>({});
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;

  // 组件挂载时自动刷新
  useEffect(() => {
    // 延迟执行，等待 ProTable 初始化完成
    const timer = setTimeout(() => {
      actionRef.current?.reload();
    }, 100);
    return () => clearTimeout(timer);
  }, []);

  const columns: ProColumns<any>[] = [
    {
      title: '序号',
      dataIndex: 'index',
      key: 'index',
      width: 65,
      fixed: 'left',
      render: (_: any, record: any, index: number) => `${index + 1}`,
    },
    {
      title: '刊物',
      dataIndex: 'E_PID',
      key: 'E_PID',
      width: 120,
      search: false,
    },
    {
      title: '投放日',
      dataIndex: 'E_MID',
      key: 'E_MID',
      width: 120,
      search: false,
    },
    {
      title: '版位',
      dataIndex: 'E_AdField_ID',
      key: 'E_AdField_ID',
      width: 120,
      search: false,
    },
    {
      title: '颜色',
      dataIndex: 'E_Color_ID',
      key: 'E_Color_ID',
      width: 100,
      search: false,
    },
    {
      title: '规格',
      dataIndex: 'E_AdSize_ID',
      key: 'E_AdSize_ID',
      width: 120,
      search: false,
    },
    {
      title: '单价',
      dataIndex: 'E_Price',
      key: 'E_Price',
      width: 100,
    },

    {
      title: '操作',
      key: 'action',
      fixed: 'right',
      width: 100,
      render: (_: any, record: any) => (
        <>
          <Button
            type="link"
            size="small"
            icon={<EditOutlined />}
            onClick={() => {
              setEditData(record);
              setModalVisible(true);
            }}
          >
            编辑
          </Button>
        </>
      ),
    },
  ];

  // 搜索栏表单项
  const searchItems = [
    <Dictselect
      key="E_PID"
      type="刊物"
      multiple={false}
      needAddItem={true}
      style={{ width: 150 }}
      placeholder="刊物"
      onChange={(value: any) => {
        setParams({ ...params, E_PID: value });
      }}
    />,
    <Dictselect
      key="E_MID"
      type="投放日"
      multiple={false}
      needAddItem={true}
      style={{ width: 150 }}
      placeholder="投放日"
      onChange={(value: any) => {
        setParams({ ...params, E_MID: value });
      }}
    />,
    <Dictselect
      key="E_AdField_ID"
      type="版位"
      multiple={false}
      needAddItem={true}
      style={{ width: 150 }}
      placeholder="版位"
      onChange={(value: any) => {
        setParams({ ...params, E_AdField_ID: value });
      }}
    />,
    <Dictselect
      key="E_Color_ID"
      type="颜色"
      multiple={false}
      needAddItem={true}
      style={{ width: 150 }}
      placeholder="颜色"
      onChange={(value: any) => {
        setParams({ ...params, E_Color_ID: value });
      }}
    />,
    <Dictselect
      key="E_AdSize_ID"
      type="规格"
      multiple={false}
      needAddItem={true}
      style={{ width: 150 }}
      placeholder="规格"
      onChange={(value: any) => {
        setParams({ ...params, E_AdSize_ID: value });
      }}
    />,
  ];

  // 处理弹窗关闭
  const handleModalClose = () => {
    setModalVisible(false);
    setEditData(null);
  };

  // 提交成功后刷新
  const handleSuccess = () => {
    handleModalClose();
    actionRef.current?.reload();
  };

  return (
    <div>
      <ProTable
        actionRef={actionRef}
        formRef={formRef}
        search={false}
        form={{
          ignoreRules: false,
        }}

        scroll={{ x: 1300 }}
        params={params}
        columns={columns}
        request={async (params = {}, sorter, filter) => {
          const { current, pageSize, ...restParams } = params;
          const result = await getPricelist({
            current: current || 1,
            pageSize: pageSize || 20,
            ...restParams,
          });
          return {
            data: result.data || [],
            total: result.total || 0,
            success: result.success !== false,
          };
        }}
        rowKey="SYS_DOCUMENTID"
        pagination={{
          showSizeChanger: true,
          showQuickJumper: true,
          showTotal: (total) => `共 ${total} 条`,
        }}
        dateFormatter="string"
        toolbar={{
          filter: (
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, alignItems: 'flex-start' }}>
              {searchItems}
              <Button
                type="primary"
                icon={<SearchOutlined />}
                onClick={() => {
                  actionRef.current?.reload();
                }}
              >
                搜索
              </Button>
            </div>
          ),
          actions: [
            <Button
              key="add"
              type="primary"
              icon={<PlusOutlined />}
              onClick={() => {
                setEditData(null);
                setModalVisible(true);
              }}
            >
              新增
            </Button>,
          ],
        }}
      />

      {/* 新增/编辑弹窗 */}
      <Modal
        title={editData ? '编辑刊例价' : '新增刊例价'}
        visible={modalVisible}
        onCancel={handleModalClose}
        width={800}
        footer={null}
        destroyOnClose
      >
        <AddPrice data={editData} onChange={handleSuccess} />
      </Modal>
    </div>
  );
};

export default Pricelist;
