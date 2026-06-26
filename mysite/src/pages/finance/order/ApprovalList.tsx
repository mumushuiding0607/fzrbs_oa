import { ActionType, ProColumns, ProFormInstance, ProTable } from '@ant-design/pro-components';
import React, { useRef, useState, useEffect } from 'react';
import { Button, Modal, Popover, Tag, Input, Select, DatePicker } from 'antd';
import { PlusOutlined, SearchOutlined, PrinterOutlined, DownloadOutlined } from '@ant-design/icons';
import { useModel } from 'umi';

import { approvallist, getflowdata, getAdvitem } from './service';
import TableScrollSync from '../common/TableScrollSync';
import AdvitemViewModal from './AdvitemViewModal';
import UserAutocomplete from '../budget/common/userAutocomplete';
import Filescard from '../contract/filescard';

interface ApprovalListProps {
  onChange?: () => void;
}

const ApprovalList: React.FC<ApprovalListProps> = ({ onChange }) => {
  const actionRef = useRef<ActionType>();
  const formRef = useRef<ProFormInstance>();
  const [refreshKey, setRefreshKey] = useState(0);
  const [params, setParams] = useState<any>({});
  const [tableHeight, setTableHeight] = useState<string>('calc(100vh - 400px)');
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [approvalModalVisible, setApprovalModalVisible] = useState(false);
  const [approvalRecord, setApprovalRecord] = useState<any>(null);
  const [previewVisible, setPreviewVisible] = useState(false);
  const [previewData, setPreviewData] = useState<any>({});

  // 组件挂载时自动刷新
  useEffect(() => {
    // 延迟执行，等待 ProTable 初始化完成
    const timer = setTimeout(() => {
      actionRef.current?.reload();
    }, 100);
    return () => clearTimeout(timer);
  }, []);

  // 格式化金额显示
  const formatMoney = (text: any) => {
    if (Number.isFinite(text)) {
      return text.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    }
    return '0.00';
  };

  const columns: ProColumns[] = [
    {
      title: '审批状态',
      dataIndex: 'status',
      key: 'status',
      width: 100,
      render: (text: any) => {
        const statusMap: { [key: string]: { color: string; text: string } } = {
          '1': { color: 'blue', text: '审批中' },
          '2': { color: 'green', text: '已同意' },
          '3': { color: 'red', text: '已驳回' },
          '4': { color: 'gray', text: '已撤销' },
        };
        const status = statusMap[text] || { color: 'default', text: text || '未知' };
        return <Tag color={status.color}>{status.text}</Tag>;
      },
    },
{
      title: '审批单号',
      dataIndex: 'thirdNo',
      key: 'thirdNo',
      width: 180,
      render: (text: any, record: any) => {
        if (!text || text === '-') return <span>暂无编号</span>;

        const temp = text.split(',');
        return (
          <div>
            {temp.map((item: string, index: number) => (
              <span
                key={index}
                onClick={() => {
                  if (record.data){
                    const infoid = JSON.parse(record.data).infoid;
                    setApprovalRecord({ id: infoid, thirdNo: record.thirdNo });
                    setApprovalModalVisible(true);
                  }else{
                    Modal.error({
                      title: '订单id无法获取'
                    });
                  }
                }}
                style={{ color: '#1890FF', cursor: 'pointer' }}
              >
                {item}
              </span>
            ))}
          </div>
        );
      },
    },
    {
      title: '广告编号',
      dataIndex: 'advitemid',
      key: 'advitemid',
      width: 120,
      hideInSearch: true,
      render: (_: any, record: any) => {
        if (!record.data) return '-';
        try {
          const dataObj = JSON.parse(record.data);
          const advId = dataObj.infoid;
          return advId ? (
            <a
              onClick={async () => {
                const res: any = await getAdvitem({ advitemId: advId });
                if (res.data && res.data[0]) {
                  setPreviewData(res.data[0]);
                  setPreviewVisible(true);
                }
              }}
            >
              {advId}
            </a>
          ) : '-';
        } catch {
          return '-';
        }
      },
    },

    {
      title: '申请人',
      dataIndex: 'userName',
      key: 'userName',
      width: 100,
      render: (_: any, record: any) => (
        <div>
          <div>{record.userName}</div>
        </div>
      ),
    },
    {
      title: '部门',
      dataIndex: 'department',
      key: 'department',
      width: 120,
      render: (text: any) => text || '-',
    },
    {
      title: '审批人',
      dataIndex: 'approvalUsername',
      key: 'approvalUsername',
      width: 100,
      render: (_: any, record: any) => (
        <div>
          <div>{record.approvalUsername }</div>
        </div>
      ),
    },
    {
      title: '申请时间',
      dataIndex: 'inserttime',
      key: 'inserttime',
      width: 150,
      render: (text: any) => text ? text.substring(0, 19) : '-',
    },

  ];

  // 搜索栏表单项
  const searchItems = [
    <Input
      key="SYS_DOCUMENTID"
      placeholder="广告编号"
      style={{ width: 150 }}
      onChange={(e: any) => {
        params.SYS_DOCUMENTID = e.target.value;
        setParams({ ...params });
      }}
    />,
    <Input
      key="thirdNo"
      placeholder="审批单号"
      style={{ width: 150 }}
      onChange={(e: any) => {
        params.thirdNo = e.target.value;
        setParams({ ...params });
      }}
    />,
    <UserAutocomplete
      key="userId"
      multiple={false}
      placeholder="申请人"
      width={'150px'}
      onChange={(value: any) => {
        console.log('value:',value)
        params.userId = value?.value;
        setParams({ ...params });
      }}
    />,
    <UserAutocomplete
      key="approvalUserid"
      multiple={false}
      placeholder="审批人"
      width={'150px'}
      onChange={(value: any) => {
        params.approvalUserid = value?.value;
        setParams({ ...params });
      }}
    />,
  ];

  return (
    <>
      <ProTable
        id="approvalTable"
        actionRef={actionRef}
        formRef={formRef}
        columns={columns}
        search={false}
        rowKey={(record: any) => 'approval' + record.id}
        pagination={{
          defaultPageSize: 20,
          showQuickJumper: true,
          showSizeChanger: true,
          showTotal: (total, range) => `共 ${total} 条记录，显示 ${range[0]}-${range[1]} 条`,
        }}
        scroll={{ x: 'max-content' }}
        params={params}
        request={async (params: any) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;

          const result: any = await approvallist(params);

          return {
            data: result.data || [],
            total: result.total || 0,
            success: result.success !== false,
          };
        }}
        toolbar={{
          filter: (
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', alignItems: 'flex-start' }}>
              {searchItems}
            </div>
          ),
          actions: [
            <Button
              key="search"
              type="primary"
              onClick={() => {
                setParams({ ...params });
                actionRef.current?.reload();
              }}
            >
              搜索
            </Button>,
          ],
        }}
      />
      <AdvitemViewModal
        id={approvalRecord?.id}
        thirdNo={approvalRecord?.thirdNo}
        visible={approvalModalVisible}
        onVisibleChange={setApprovalModalVisible}
        onApplyChange={() => {
          actionRef.current?.reload();
        }}
      />
      <Modal
        title="广告详情"
        width={800}
        visible={previewVisible}
        onCancel={() => setPreviewVisible(false)}
        footer={null}
        destroyOnClose
      >
        <div style={{ padding: '20px' }}>
          <div style={{ marginBottom: '20px', borderBottom: '1px solid #eee', paddingBottom: '10px' }}>
            <h3 style={{ margin: 0 }}>基本信息</h3>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '20px' }}>
            <div><strong>广告编号：</strong>{previewData.SYS_DOCUMENTID}</div>
            <div><strong>刊物：</strong>{previewData.AI_Publication}</div>
            <div><strong>主体：</strong>{previewData.partbname}</div>
            <div><strong>客户：</strong>{previewData.AI_Customer}</div>
            <div><strong>合同编号：</strong>{previewData.contractserial}</div>
            <div><strong>业务员：</strong>{previewData.AI_Salesman}</div>
            <div><strong>协助人员：</strong>{previewData.assistantname}</div>
            <div><strong>协助部门：</strong>{previewData.assistantdepartmentname}</div>
            <div><strong>部门：</strong>{previewData.AI_Org}</div>
            <div><strong>广告内容：</strong>{previewData.AI_Content}</div>
            <div><strong>内容详情：</strong>{previewData.content}</div>
          </div>

          <div style={{ marginBottom: '20px', borderBottom: '1px solid #eee', paddingBottom: '10px' }}>
            <h3 style={{ margin: 0 }}>投放信息</h3>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '20px' }}>
            <div><strong>规格：</strong>{previewData.AI_Size}</div>
            <div><strong>版位：</strong>{previewData.AI_Field}</div>
            <div><strong>颜色：</strong>{previewData.AI_Color}</div>
            <div><strong>投放日期：</strong>{previewData.AI_PublishTime?.substring?.(0, 10)}</div>
            <div><strong>结束日期：</strong>{previewData.AI_PublishEndTime?.substring?.(0, 10)}</div>
            <div><strong>投放天数：</strong>{previewData.AI_PublishDayCount}</div>
          </div>

          <div style={{ marginBottom: '20px', borderBottom: '1px solid #eee', paddingBottom: '10px' }}>
            <h3 style={{ margin: 0 }}>金额信息</h3>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '20px' }}>
            <div><strong>单价：</strong>¥{formatMoney(previewData.AI_Price)}</div>
            <div><strong>应收款：</strong>¥{formatMoney(previewData.AI_AmountReceivable)}</div>
            <div><strong>已付金额：</strong>¥{formatMoney(previewData.AI_AmountReceived)}</div>
            <div><strong>欠款：</strong><span style={{ color: previewData.AI_Debt > 0 ? 'red' : 'inherit' }}>¥{formatMoney(previewData.AI_Debt)}</span></div>
            <div><strong>支付状态：</strong>{previewData.AI_PayStatus}</div>
          </div>
          <div style={{ marginBottom: '20px', borderBottom: '1px solid #eee', paddingBottom: '10px' }}>
            <h3 style={{ margin: 0 }}>附件</h3>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '20px' }}>
            <div><Filescard urls={previewData.fileurls} mode="list" /></div>
          </div>

          {previewData.AI_PubMemo && (
            <>
              <div style={{ marginBottom: '20px', borderBottom: '1px solid #eee', paddingBottom: '10px' }}>
                <h3 style={{ margin: 0 }}>备注</h3>
              </div>
              <div>{previewData.AI_PubMemo}</div>
            </>
          )}
        </div>
      </Modal>
      <TableScrollSync tableId="approvalTable" onScroll={(scroll:any)=>{
        const tableContent = document.querySelector('#approvalTable .ant-table-content');
        if (tableContent){
          tableContent.scrollLeft = scroll;
        }
      }} />
    </>
  );
};

export default ApprovalList;