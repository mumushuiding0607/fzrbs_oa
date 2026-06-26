import { ActionType, PageContainer, ProColumns, ProFormInstance, ProFormSelect, ProTable } from '@ant-design/pro-components';
import React, { useRef, useState, useEffect } from 'react';
import { Button, Modal, Popover, Select, Tag, Dropdown, Menu, Form, Input, Space } from 'antd';
import { PlusOutlined, DownOutlined } from '@ant-design/icons';

import { getOrderList, deleteOrder, getAdvitem, printorder, setOrderFlag, getorderbyid } from './service';
import SetObserver from '../role/SetObserver';
import { getcontract } from '../contract/service';
import AddOrder from './AddOrder';
import ContractView from '../contract/view';
import EditOrder from './EditOrder';
import { OrderTypeEnum } from './config';

import AdvitemList from './AdvitemList';
import Pricelist from './Pricelist';
import AddFzAdv from './AddFzAdv';
import AddSmallBusiness from './AddSmallBusiness';
import OrderPreview from './OrderPreview';
import Companyselect from '../company/companyselect';
import DepartmentTreeSelect from '../budget/common/department_treeselect';
import Dictselect from '../budget/dict/dictselect';
import ContractSelect from '../contract/contract-select';
import { useModel } from 'umi';
import dayjs from 'dayjs'
import Tradecascade from './tradecascade';
import PrintAddFzAdvPage from './PrintAddFzAdvPage';
import PrintSmallBusiness from './PrintSmallBusiness';
import PrintMixed from './PrintMixed';
import DownloadDocDropdown from '../Flowtemplate/DownloadDocDropdown';
import { CONTRACT_AGENTID } from '../contract/config';
import Orgcascade from './orgcascade';
import Flow from '../budget/budget/flow';
import { viewflow, startflow,  } from './service';
import ViewModal from './viewModal';
import ApprovalList from './ApprovalList';
import TableScrollSync from '../common/TableScrollSync';
import PayerSelect from '../role/payerSelect';
import Agentselect from '../role/agentselect';
import UserAutocomplete from '../budget/common/userAutocomplete';

const OrderList: React.FC = () => {
  const actionRef = useRef<ActionType>();
  const formRef = useRef<ProFormInstance>();
  const [modalVisible, setModalVisible] = useState(false);
  const [advModalVisible, setAdvModalVisible] = useState(false);
  const [currentOrder, setCurrentOrder] = useState<any>({});
  const [refreshKey, setRefreshKey] = useState(0);
  const [deleteFlag, setDeleteFlag] = useState<string | undefined>(undefined);
  const [orderType, setOrderType] = useState<string>('fzadv');
  const [printModal, setPrintModal] = useState(false);
  const [printData, setPrintData] = useState<any>({});
  const [activeTabKey, setActiveTabKey] = useState<string>('order');
const [previewVisible, setPreviewVisible] = useState(false);
  const [previewOrder, setPreviewOrder] = useState<any>({});
  const [contractModalVisible, setContractModalVisible] = useState(false);
  const [contractDetailId, setContractDetailId] = useState<any>(null);
  const [viewModalVisible, setViewModalVisible] = useState(false);
  const [tempOrderRecord, setTempOrderRecord] = useState<any>(null);
  const [tableHeight, setTableHeight] = useState<string>('calc(100vh - 220px)');
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [loading, setLoading]=useState(false);
  const [observerModalVisible, setObserverModalVisible] = useState(false);
  const [observerRecord, setObserverRecord] = useState<any>(null);

  // 动态计算表格高度
  const calculateTableHeight = () => {
    const windowHeight = window.innerHeight;

    // 计算固定UI元素高度
    let fixedHeight = 0;

    // PageContainer标题区域 (约120px)
    const pageHeader = document.querySelector('.ant-pro-page-container > .ant-page-header');
    if (pageHeader) {
      fixedHeight += (pageHeader as HTMLElement).offsetHeight;
    } else {
      fixedHeight += 120; // 默认值
    }

    // 搜索栏 (动态高度)
    const searchBar = document.querySelector('.ant-pro-table-search');
    if (searchBar) {
      fixedHeight += (searchBar as HTMLElement).offsetHeight;
    } else {
      fixedHeight += 80; // 默认搜索栏高度
    }

    // 工具栏 (如果有统计栏等)
    const toolbar = document.querySelector('.ant-pro-table-list-toolbar');
    if (toolbar) {
      fixedHeight += (toolbar as HTMLElement).offsetHeight;
    }

    // 表格头部高度 (约55px)
    fixedHeight += 55;

    // 分页高度 (约64px)
    fixedHeight += 64;

    // 底部滚动控件 (约70px)
    fixedHeight += 70;

    // 额外边距 (约20px)
    fixedHeight += 20;

    // 计算可用高度
    const availableHeight = windowHeight - fixedHeight;
    const calculatedHeight = Math.max(availableHeight, 400); // 最小高度300px

    setTableHeight(`${calculatedHeight}px`);
  };

  // 窗口大小改变时重新计算高度
  useEffect(() => {
    calculateTableHeight();
    const handleResize = () => calculateTableHeight();
    window.addEventListener('resize', handleResize);

    // 组件挂载后延迟计算，确保DOM完全渲染
    const timer = setTimeout(calculateTableHeight, 500);

    return () => {
      window.removeEventListener('resize', handleResize);
      clearTimeout(timer);
    };
  }, []);

  const columns:any = [
    {
      title: '订单编号',
      dataIndex: 'SYS_DOCUMENTID',
      key: 'SYS_DOCUMENTID',
      width: 120,
      sorter: true,
      hideInSearch:true,
      render: (_: any, record: any) => {
        return (<div onClick={() => {
                  setAdvModalVisible(true);
                  setCurrentOrder(record);
                }}>
                  {
                    record.SYS_DELETEFLAG?<Tag color="red">在审或未审</Tag>:<span>{record.SYS_DOCUMENTID}</span>
                  }
                </div>
            
        )
      },
    },
    {
      title: '合同编号',
      dataIndex: 'contractserial',
      key: 'contractserial',
      width: 150,
      hideInSearch: false,
      renderFormItem: () => {
        return <ContractSelect showupload={false} multiple={false}  />;
      },
      render: (text: any, record: any) => (
        <span
          style={{ color: '#1890ff', cursor: 'pointer' }}
          onClick={() => {
            if (record.contractid) {
              setContractDetailId(record.contractid);
              setContractModalVisible(true);
            }
          }}
        >
          {text}
        </span>
      ),
    },
    {
      title: '主体',
      dataIndex: 'partbname',
      key: 'partbname',
      width: 200,
      sorter: true,
      render: (text: any, record: any) => (
        <span
          style={{ color: '#1890ff', cursor: 'pointer' }}
          onClick={() => {
            console.log('点击主体：', record);
            setPreviewOrder(record);
            setPreviewVisible(true);
          }}
        >
          {text}
        </span>
      ),
      renderFormItem: () => {
        return <Companyselect multiple={false} placeholder="选择主体" />;
      },
    },
    {
      title: '客户',
      dataIndex: 'AO_Customer',
      key: 'AO_Customer',
      width: 200,
      sorter: true,
      renderFormItem: () => {
        return <Companyselect multiple={false} placeholder="选择客户" />;
      },
    },
    {
      title: '总应收款',
      dataIndex: 'AO_AllMoney',
      key: 'AO_AllMoney',
      hideInSearch: true,
      width: 120,
      className:'right',
      render: (text:any)=>{
        if (Number.isFinite(text)){
          return text.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })
        }else{
          return 0.00
        }
      }
    },
    {
      title: '总营业额',
      dataIndex: 'AO_AmountPaid',
      key: 'AO_AmountPaid',
      hideInSearch: true,
      width: 120,
      className:'right',
      render: (text:any)=>{
        if (Number.isFinite(text)){
          return text.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })
        }else{
          return 0.00
        }
      }
    },
    {
      title: '已收款',
      dataIndex: 'AO_ReceivedMoney',
      key: 'AO_ReceivedMoney',
      hideInSearch: true,
      width: 120,
      className:'right',
      render: (text:any)=>{
        if (Number.isFinite(text)){
          return text.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })
        }else{
          return 0.00
        }
      }
    },
    {
      title: '欠款',
      dataIndex: 'AO_DebtMoney',
      key: 'AO_DebtMoney',
      hideInSearch: true,
      width: 120,
      className:'right',
      render: (text:any)=>{
        if (Number.isFinite(text)){
          return text.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })
        }else{
          return 0.00
        }
      }
    },
    {
      title: '行业部门',
      dataIndex: 'AO_Org_ID',
      key: 'AO_Org_ID',
      hideInTable: true,
      renderFormItem: () => {
        return <Orgcascade />;
      },
    },
    {
      title: '申请部门',
      dataIndex: 'departmentid',
      key: 'departmentid',
      hideInTable: true,
      renderFormItem: () => {
        return <DepartmentTreeSelect 
          multiple={false} 
          placeholder="业务员所在部门" 
          showTreeCheckStrictly={true}
        />;
      },
    },
    {
      title: '业务员',
      dataIndex: 'AO_Salesman',
      key: 'AO_Salesman',
      width: 120,
      sorter: true,
      renderFormItem: () => {
        return <UserAutocomplete multiple={false} placeholder="选择业务员" />;
      },
    },

    {
      title: '刊物',
      dataIndex: 'publication',
      key: 'publication',
      width: 150,
      renderFormItem: () => {
        return <Dictselect type="刊物" needAddItem={false} placeholder="选择刊物" />;
      },
    },
    {
      title: '行业部门',
      dataIndex: 'AO_Org',
      key: 'AO_Org',
      width: 120,
      hideInSearch: true,
    },
    {
      title: '创建人',
      dataIndex: 'SYS_CURRENTUSERNAME',
      key: 'SYS_CURRENTUSERNAME',
      width: 120,
      hideInSearch: true,
    },
    {
      title: '创建时间',
      dataIndex: 'SYS_CREATED',
      key: 'SYS_CREATED',
      width: 150,
      sorter: true,
      valueType: 'dateRange',
      hideInSearch: false,
      render: (_: any, record: any) => {
        return record.SYS_CREATED ? record.SYS_CREATED.substring(0, 10) : '';
      },
    },
    {
      title: '操作',
      key: 'action',
      fixed: 'right',
      width: 100,
      search: false,
      onHeaderCell: () => ({
        style: {
          right: '-5px!important',
        },
      }),
      render: (_: any, record: any, index: number) => (
        <Popover
          placement="topLeft"
          trigger={'click'}
          key={'popover' + index}
          overlay={<div key={'overlay' + index} id={'overlay' + index}></div>}
          content={
            <>
              <Button
                type="text"
                onClick={async () => {
                  setCurrentOrder(record);
                  setOrderType('editorder');
                  setRefreshKey(refreshKey + 1);
                  setModalVisible(true);
                }}
              >
                编辑
              </Button>
              <Button
                type="text"
                loading={loading}
                onClick={async () => {
                  const aoType = record.AO_Type;
                  setLoading(true);setPrintModal(true);
                  const res: any = await printorder({ orderid: record.SYS_DOCUMENTID });
                  if (res.errorMessage){
                    Modal.error({
                      title: res.errorMessage,
                    });
                  }else{
                    setLoading(false);
                    res.data.AI_Type=aoType
                    console.log('res.data:',res.data)
                    
                    setPrintData([{
                      ...record,
                      ...res.data,
                     }]);
                     
                  }
                }}
              >
                打印
              </Button>
              <Button
                type="text"
                onClick={() => {
                  setAdvModalVisible(true);
                  setCurrentOrder(record);
                }}
              >
                广告
              </Button>
              <Button
                type="text"
                danger
                onClick={() => {
                  Modal.confirm({
                    title: '确定要删除该订单吗？',
                    okText: '确认',
                    cancelText: '取消',
                    onOk: async () => {
                      const res: any = await deleteOrder({ SYS_DOCUMENTID: record.SYS_DOCUMENTID });
                      if (res.errorMessage) {
                        Modal.error({ title: res.errorMessage });
                      } else {
                        actionRef.current?.reload();
                      }
                    },
                  });
                }}
              >
                删除
              </Button>

            </>
          }
        >
          <Button key={'button' + index}>操作</Button>
        </Popover>
      ),
    },
  ];

  const onAddSuccess = () => {
    actionRef.current?.reload();
    setModalVisible(false);
    setCurrentOrder({});
  };

return (
    <PageContainer title="广告管理"
      extra={[
        <Button
          key="observer"
          type='default'
          onClick={() => {
            setObserverModalVisible(true);
          }}
        >
          设置观察员
        </Button>,
        <DownloadDocDropdown key="doc" />
      ]}
      tabList={[
        {
          tab: '订单列表',
          key: 'order',
        },
        {
          tab: '广告列表',
          key: 'advitem',
        },
        {
          tab: '待审批',
          key: 'approval',
        }
      ]}
      onTabChange={(key) => {
        setActiveTabKey(key);
      }}
    >
      {activeTabKey === 'order' && (
        <>
          <div >
            <ProTable
            id="orderTable"
            // tableLayout={'fixed'}
            sticky={{ offsetHeader: 0 }}
            scroll={{ y: tableHeight }}
            pagination={{
              pageSize: 20,
              showSizeChanger: true,
              showQuickJumper: true,
              showTotal: (total, range) => `共 ${total} 条记录，显示 ${range[0]}-${range[1]} 条`,
              position: ['bottomRight']
            }}
            rowKey={(record: any) => 'order' + record.SYS_DOCUMENTID}
            actionRef={actionRef}
            formRef={formRef}
            columns={columns}
            request={async (params: any, sorter: any) => {
              document.body.scrollTop = document.documentElement.scrollTop = 0;
              
              if (sorter) {
                Object.keys(sorter).forEach((key) => {
                  const order = sorter[key] === 'ascend' ? 'asc' : 'desc';
                  params.orderby = key + ' ' + order;
                });
              }

              if (params.AO_Customer && typeof params.AO_Customer === 'object') {
                params.AO_Customer_ID = params.AO_Customer.value || params.AO_Customer.id;
                delete params.AO_Customer;
              }
              if (params.contractserial && typeof params.contractserial === 'object') {
                params.contractid = params.contractserial.value || params.contractserial.id;
                delete params.contractserial;
              }

              if (params.partbname && typeof params.partbname === 'object') {
                params.partb = params.partbname.value || params.partbname.id;
                delete params.partbname;
              }

              if (params.AO_Org_ID && typeof params.AO_Org_ID === 'object') {
                params.AO_Org_ID = params.AO_Org_ID.value
              }

              if (params.applydept && typeof params.applydept === 'object') {
                params.applydept = params.applydept.value
              }

              if (params.AO_Salesman && typeof params.AO_Salesman === 'object') {
                params.AO_Salesman_ID = params.AO_Salesman.value;
                delete params.AO_Salesman;
              }

              if (params.publication && typeof params.publication === 'object') {
                params.publicationid = params.publication.value;
                delete params.publication;
              }

              if (deleteFlag !== undefined) {
                params.SYS_DELETEFLAG = deleteFlag;
              }else{
                params.withdeleted = 1;
              }

              if (params.SYS_CREATED) {
                params.SYS_CREATED_START = params.SYS_CREATED[0] + ' 00:00:00';
                params.SYS_CREATED_END = params.SYS_CREATED[1] + ' 23:59:59';
                delete params.SYS_CREATED;
              }
              return getOrderList(params);
            }}
            toolBarRender={() => [
              <Select
                key="deleteFlagFilter"
                style={{ width: 120 }}
                placeholder="订单状态"
                allowClear
                value={deleteFlag}
                onChange={(value) => {
                  setDeleteFlag(value);
                  actionRef.current?.reload(true);
                }}
                options={[
                  { label: '全部', value: undefined },
                  { label: '生效', value: '0' },
                  { label: '未审', value: '1' },
                ]}
              />,
              
              <Dropdown
                overlay={
                  <Menu
                    onClick={({ key }) => {
                      if (key!='order'){
                        setCurrentOrder({
                          SYS_CREATED: dayjs(),
                          AI_AmountPaid: 0,
                          AI_Debt: 0,
                          AI_PublishDayCount: 1,
                          AI_PayTime_ID: 1,
                          AI_Publication_ID: 1,
                          AI_Publication:'福州日报',
                          AI_Color_ID: 1,
                          AI_Color:'黑白',
                          AI_PayMode_ID: 2, AI_PayMode: '转账' ,
                          AI_Salesman_ID: currentUser ? currentUser.wxuserid : undefined,
                          AI_Salesman: currentUser ? currentUser.realname  : undefined,
                        })
                      }else{
                        setCurrentOrder({});
                      }
                      setOrderType(key);
                      setRefreshKey(refreshKey + 1);
                      setModalVisible(true);
                    }}
                    items={[
                      {
                        key: 'fzadv',
                        label: '福州日报社广告登记',
                      },
                      {
                        key: 'smallbusiness',
                        label: '小额业务确认单',
                      },
                      {
                        key: 'order',
                        label: '传统订单方式',
                      },
                    ]}
                  />
                }
              >
                <Button type="primary" key="primary">
                  <PlusOutlined /> 新建订单 <DownOutlined />
                </Button>
              </Dropdown>,
            ]}
          />
          
          </div>
        </>
      )}
      <TableScrollSync  tableId="orderTable" onScroll={(scroll:any)=>{
            const tableContent = document.querySelector('#orderTable .ant-table-content');
            if (tableContent){
              tableContent.scrollLeft = scroll;
            }
          }} />
      
      {activeTabKey === 'advitem' && (
        <AdvitemList
          key={activeTabKey}
        />
      )}

      {activeTabKey === 'approval' && (
        <ApprovalList
          key={activeTabKey}
        />
      )}

      

      <Modal
        title={orderType === 'fzadv' ? '福州日报社广告登记' : orderType === 'smallbusiness' ? '小额业务确认单' : orderType === 'editorder' ? '编辑订单（含广告列表）' : currentOrder.SYS_DOCUMENTID ? '编辑订单' : '新建订单'}
        style={{ top: 20 }}
        width={orderType === 'editorder' ? 900 : 900}
        visible={modalVisible}
        onOk={() => setModalVisible(false)}
        onCancel={() => setModalVisible(false)}
        footer={null}
        destroyOnClose
      >
        {orderType === 'editorder' && (
          <EditOrder 
            key={refreshKey} 
            data={currentOrder} 
            onChange={() => {
              setModalVisible(false);
              actionRef.current?.reload();
            }} 
          />
        )}
        {orderType === 'fzadv' && (
          <AddFzAdv 
            key={refreshKey} 
            data={currentOrder} 
            onChange={() => {
              setModalVisible(false);
              actionRef.current?.reload();
            }} 
          />
        )}
        {orderType === 'smallbusiness' && (
          <AddSmallBusiness 
            key={refreshKey} 
            data={currentOrder} 
            onChange={() => {
              setModalVisible(false);
              actionRef.current?.reload();
            }} 
          />
        )}
        {orderType === 'order' && (
          <AddOrder key={refreshKey} data={currentOrder} onChange={onAddSuccess} />
        )}
      </Modal>

      <Modal
        title=""
        style={{ top: 20 }}
        width="80%"
        visible={printModal}
        onOk={() => setPrintModal(false)}
        onCancel={() => setPrintModal(false)}
        footer={null}
        destroyOnClose
      >

        {printData && printData.length > 0 && (
          <PrintMixed records={printData} />
        )}
      </Modal>

      <Modal
        title="广告列表"
        style={{ top: 20 }}
        width={1400}
        visible={advModalVisible}
        onOk={() => setAdvModalVisible(false)}
        onCancel={() => setAdvModalVisible(false)}
        footer={null}
        destroyOnClose
      >
        <AdvitemList 
          order={currentOrder}
          onChange={() => {
            actionRef.current?.reload();
          }}
        />
      </Modal>

{/* 订单预览弹窗 */}
      <OrderPreview
        visible={previewVisible}
        order={previewOrder}
        onClose={() => setPreviewVisible(false)}
        onRefresh={() => actionRef.current?.reload()}
      />

      {/* 合同详情弹窗 */}
      <Modal
        title=""
        style={{ top: 20 }}
        width={900}
        visible={contractModalVisible}
        onOk={() => setContractModalVisible(false)}
        onCancel={() => setContractModalVisible(false)}
        footer={null}
        destroyOnClose
      >
        <ContractView id={contractDetailId} paystate="" />
      </Modal>

      {/* 审批详情弹窗 */}
      <ViewModal
        id={tempOrderRecord?.SYS_DOCUMENTID}
        thirdNo={tempOrderRecord?.thirdNo}
        visible={viewModalVisible}
        onVisibleChange={setViewModalVisible}
        onApplyChange={() => {
          actionRef.current?.reload();
        }}
      />

{/* 观察员弹窗 */}
      <SetObserver
        visible={observerModalVisible}
        onCancel={() => setObserverModalVisible(false)}
        onSuccess={() => {
          actionRef.current?.reload();
        }}
        agentid="1000083"
      />

    </PageContainer>
  );
};

export default OrderList;
