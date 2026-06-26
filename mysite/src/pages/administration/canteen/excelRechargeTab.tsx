import { BarChartOutlined, ImportOutlined } from '@ant-design/icons';
import { ActionType, ProColumns, ProFormText, ProTable } from '@ant-design/pro-components';
import { Button, Card, List, Modal } from 'antd';
import React, { useEffect, useRef, useState } from 'react';
import ExcelImportRechargeModal from './components/ExcelImportRechargeModal';
import { type, rechargeLog, excelRechargeTotal } from './components/list/service';

const ExcelRechargeTab: React.FC = () => {
  const actionRef = useRef<ActionType>();
  const [userType, setUserType] = useState<any>({});
  const modalRef = useRef<any>();
  const [open, setOpen] = useState(false);
  const [excelRechargeData, setExcelRechargeData] = useState([]);

  const columns: ProColumns<any>[] = [
    {
      title: '充值时间',
      dataIndex: 'inserttime',
      valueType: 'dateRange',
      render: (_, entity) => {
        return entity.inserttime;
      },
    },
    {
      title: '操作人',
      dataIndex: 'urealname',
      hideInSearch: true,
    },
    {
      title: '备注',
      dataIndex: 'intro',
      hideInSearch: true,
    },
    {
      title: '用户分类',
      dataIndex: 'usertype',
      valueEnum: userType,
    },
    {
      title: '姓名',
      key: 'realname',
      dataIndex: 'realname',
      renderFormItem: (item, { type, defaultRender, ...rest }, form) => {
        return <ProFormText width="xs" name="realname" />;
      },
      hideInTable: true,
    },
  ];

  const handleCancel = () => setOpen(false);

  useEffect(() => {
    type().then((res) => {
      setUserType(res.data);
    });
  }, []);

  return (
    <>
      <ProTable<any, any>
        headerTitle="Excel导入充值日志列表"
        actionRef={actionRef}
        rowKey="id"
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          params.type = 'excel';
          return rechargeLog(params);
        }}
        columns={columns}
        toolBarRender={() => [
          <Button
            type="primary"
            key="import"
            onClick={async () => {
              modalRef?.current.setVisible(true);
            }}
          >
            <ImportOutlined /> Excel文件导入
          </Button>,
          <Button
            type="primary"
            key="sum"
            onClick={async () => {
              const result = await excelRechargeTotal();
              if (!result.errorMessage) {
                setExcelRechargeData(result.data);
                setTimeout(() => {
                  setOpen(true);
                }, 200);
              }
            }}
          >
            <BarChartOutlined /> 本月每个部门充值金额
          </Button>,
        ]}
      />
      <ExcelImportRechargeModal ref={modalRef} onOk={() => actionRef?.current?.reload()} />
      <Modal
        title="本月每个部门充值金额"
        footer={false}
        visible={open}
        onCancel={handleCancel}
        width="80vw"
      >
        <List
          grid={{
            gutter: 16,
            xs: 1,
            sm: 2,
            md: 4,
            lg: 4,
            xl: 4,
            xxl: 4,
          }}
          dataSource={excelRechargeData}
          renderItem={(item) => (
            <List.Item>
              <Card title={item.userTypeName}>￥{item.money}元</Card>
            </List.Item>
          )}
        />
      </Modal>
    </>
  );
};

export default ExcelRechargeTab;
