import { ActionType, ProColumns, ProFormInstance, ProTable } from '@ant-design/pro-components';
import React, { useRef, useState, useEffect } from 'react';
import { Button, Modal, Popover, Tag, Input, Select, DatePicker, Tooltip, Transfer } from 'antd';
import { PlusOutlined, SearchOutlined, PrinterOutlined, DownloadOutlined } from '@ant-design/icons';
import { useModel } from 'umi';

import { getAdvitemList, deleteAdvitem, getAdvitem, exportAdvitemList, getorderbyid, startadvflow, viewadvflow, setadvitemflag } from './service';
import AddAdvitem from './AddAdvitem';
import AddFzAdv from './AddFzAdv';
import AddSmallBusiness from './AddSmallBusiness';
import PrintAddFzAdvPage from './PrintAddFzAdvPage';
import PrintSmallBusiness from './PrintSmallBusiness';
import PrintMixed from './PrintMixed';
import Dictselect from '../budget/dict/dictselect';
import Companyselect from '../company/companyselect';
import DepartmentTreeSelect from '../budget/common/department_treeselect';
import UserAutocomplete from '../budget/common/userAutocomplete';
import ContractSelect from '../contract/contract-select';
import { OrderTypeEnum } from './config';
import Tradecascade from './tradecascade';
import TableScrollSync from '../common/TableScrollSync';
import Orgcascade from './orgcascade';
import tools from '../../../utils/tools';
import Filescard from '../contract/filescard';
import AdvitemStatistics from './AdvitemStatistics';
import AdvitemViewModal from './AdvitemViewModal';
import Flow from '../budget/budget/flow';
import './common.css';
import ContractView from '../contract/view';
import Advsize from './advsize';

interface AdvitemListProps {
  order?: any;
  onChange?: () => void;
}

const AdvitemList: React.FC<AdvitemListProps> = ({ order, onChange }) => {
  const actionRef = useRef<ActionType>();
  const formRef = useRef<ProFormInstance>();
  const [modalVisible, setModalVisible] = useState(false);
  const [editData, setEditData] = useState<any>({});
  const [refreshKey, setRefreshKey] = useState(0);
  const [params, setParams] = useState<any>({});
  const [advType, setAdvType] = useState<string>('fzadv'); // fzadv: 广告登记, smallbusiness: 小额业务, advitem: 普通广告
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [row,setRow]=useState<any>({});
  const [printModalVisible, setPrintModalVisible] = useState(false);
  const [printRecord, setPrintRecord] = useState<any>({});
  const [previewVisible, setPreviewVisible] = useState(false);
  const [previewData, setPreviewData] = useState<any>({});
  const [total,setTotal]=useState<any>(0);
  const [selectedRows, setSelectedRows] = useState<any>([]);
  const [tableHeight, setTableHeight] = useState<string>('calc(100vh - 300px)');
  const [statkey, setStatkey] = useState(0);
  const [approvalModalVisible, setApprovalModalVisible] = useState(false);
  const [approvalRecord, setApprovalRecord] = useState<any>({});
  const [searchExpanded, setSearchExpanded] = useState(false);
  const [columnModalVisible, setColumnModalVisible] = useState(false);
  const [targetKeys, setTargetKeys] = useState<string[]>([]);
  const [targetSelectedKeys, setTargetSelectedKeys] = useState<string[]>([]);
  const [contractModalVisible, setContractModalVisible] = useState(false);
  const [contractDetailId, setContractDetailId] = useState<any>(null);
  const [fieldSearchValue, setFieldSearchValue] = useState<string>('');

  // 完整的列定义
  const allColumns: ProColumns[] = [
    {
      title: '广告编号',
      dataIndex: 'SYS_DOCUMENTID',
      key: 'SYS_DOCUMENTID',
      width: 120,
      sorter: true,
      hideInSearch: true,
      render: (_: any, record: any) => {
        return (
            <>{
                record.SYS_DELETEFLAG == 1 ?
                (
                  record.thirdNo ?
                  <Tag
                    color="blue"
                    style={{ cursor: 'pointer' }}
                    onClick={() => {
                      setApprovalRecord({
                        id: record.SYS_DOCUMENTID,
                        thirdNo: record.thirdNo,
                      });
                      setApprovalModalVisible(true);
                    }}
                  >
                    审批中
                  </Tag>
                  :
                  <Tag
                    color="red"
                    style={{ cursor: 'pointer' }}
                    onClick={() => {
                      viewadvflow({ advitemid: record.SYS_DOCUMENTID }).then((res: any) => {
                        if (res.errorMessage) {
                          Modal.error({ title: res.errorMessage });
                        } else {
                          Modal.confirm({
                            title:"请确认流程是否正确",
                            bodyStyle:{marginLeft:0},
                            width: '600px',
                            centered:false,
                            content:(
                              <div style={{marginLeft:'0!important'}}>
                                <Flow data={res.viewdata} statusCn={res.statusCn} step={res.step}></Flow>
                                <div>抄送：{(res?.viewdata?(res?.viewdata?.notify||[]):[]).join(',')}</div>
                              </div>
                            ),
                            okText:'确认提交',
                            onOk: async () => {
                              const resStart: any = await startadvflow({ advitemid: record.SYS_DOCUMENTID });
                              if (resStart.errorMessage) {
                                Modal.error({ title: resStart.errorMessage });
                              } else {
                                Modal.info({ title: '审批流程已启动' });
                                actionRef.current?.reload();
                                onChange?.();
                              }
                            },
                          });
                        }
                      });
                    }}
                  >
                    未提交
                  </Tag>
                )
                :(
                  <a onClick={() => {
                    Modal.confirm({
                      title: '确认重新审批吗？',
                      okText: '确认',
                      cancelText: '取消',
                      cancelButtonProps: { danger: true },
                      onOk: async () => {
                        const resFlag: any = await setadvitemflag({
                          advitemid: record.SYS_DOCUMENTID,
                          flag: 1,
                        });
                        if (resFlag.errorMessage) {
                          Modal.error({ title: resFlag.errorMessage });
                        } else {
                          Modal.info({ title: '已设置为未审' });
                          actionRef.current?.reload();
                          onChange?.();
                        }
                      },
                    });
                  }}>
                    {record.SYS_DOCUMENTID}
                  </a>
                )
              }</>
          );
      },
    },
    {
      title: '刊物',
      dataIndex: 'AI_Publication',
      key: 'AI_Publication',
      width: 120,
      sorter: true,
    },
    {
      title: '主体',
      dataIndex: 'partbname',
      key: 'partbname',
      width: 150,
      hideInSearch: true,
      sorter: true,
      render: (text: any, record: any) => (
        <a onClick={() => {
          setPreviewData(record);
          setPreviewVisible(true);
        }}>
          {text}
        </a>
      ),
    },
    {
      title: '客户',
      dataIndex: 'AI_Customer',
      key: 'AI_Customer',
      width: 150,
      hideInSearch: true,
      sorter: true,
    },
    {
      title: '合同编号',
      dataIndex: 'contractserial',
      key: 'contractserial',
      width: 150,
      hideInSearch: true,
      sorter: true,
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
      title: '广告内容',
      dataIndex: 'AI_Content',
      key: 'AI_Content',
      sorter: true,
      width: 120,
      render: (text: any) => (
        <Tooltip title={text} placement="topLeft">
          <div
            style={{
              maxWidth: '150px',
              overflow: 'hidden',
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
              cursor: 'pointer'
            }}
          >
            {text}
          </div>
        </Tooltip>
      ),
    },
    {
      title: '内容详情',
      dataIndex: 'content',
      key: 'content',
      sorter: true,
      width: 120,
      render: (text: any) => (
        <Tooltip title={text} placement="topLeft">
          <div
            style={{
              maxWidth: '150px',
              overflow: 'hidden',
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
              cursor: 'pointer'
            }}
          >
            {text}
          </div>
        </Tooltip>
      ),
    },
    {
      title: '业务员',
      dataIndex: 'AI_Salesman',
      key: 'AI_Salesman',
      width: 150,
      hideInSearch: true,
      sorter: true,
    },
    {
      title: '协助人员',
      dataIndex: 'assistantname',
      key: 'assistantname',
      width: 120,
      hideInSearch: true,
      sorter: true,
      render: (text: any, record: any) => (
        <div
        >
          <div>{text}</div>
          <div>{record.assistantdepartmentname||''}</div>
        </div>
      ),
    },
    {
      title: '行业部门',
      dataIndex: 'AI_Org',
      key: 'AI_Org',
      width: 120,
      hideInSearch: true,
      sorter: true,
    },
    {
      title: '大行业',
      dataIndex: 'AI_Trade',
      key: 'AI_Trade',
      width: 120,
      hideInSearch: true,
      sorter: true,
    },
    {
      title: '规格',
      dataIndex: 'AI_Size',
      key: 'AI_Size',
      width: 100,
      sorter: true,
      render: (text: any, record: any) => (
        <div style={{ textAlign: 'center' }}>
          <div>{text}</div>
          {text === '异形广告' && (
            <span style={{ fontSize: 12, color: '#888' }}>
              {`${record.AI_Height}×${record.AI_Width}`}
            </span>
          )}
        </div>
      ),
    },
    {
      title: '版面数',
      dataIndex: 'AI_AdvPages',
      key: 'AI_AdvPages',
      width: 70,
      sorter: true,
    },
    {
      title: '版位',
      dataIndex: 'AI_Field',
      key: 'AI_Field',
      width: 100,
      sorter: true,
    },
    {
      title: '颜色',
      dataIndex: 'AI_Color',
      key: 'AI_Color',
      width: 80,
      sorter: true,
    },
    {
      title: '投放日期',
      dataIndex: 'AI_PublishTime',
      key: 'AI_PublishTime',
      width: 120,
      sorter: true,
      render: (text: any) => text&&text.substring ? text.substring(0, 10) : '',
    },
    {
      title: '结束日期',
      dataIndex: 'AI_PublishEndTime',
      key: 'AI_PublishEndTime',
      width: 120,
      sorter: true,
      render: (text: any) => text&&text.substring ? text.substring(0, 10) : '',
    },
    {
      title: '星期',
      dataIndex: 'AI_Week',
      key: 'AI_Week',
      width: 80,
      sorter: true,
    },
    {
      title: '投放天数',
      dataIndex: 'AI_PublishDayCount',
      key: 'AI_PublishDayCount',
      sorter: true,
      width: 90,
    },
    {
      title: '单价',
      dataIndex: 'AI_Price',
      key: 'AI_Price',
      width: 100,
      sorter: true,
      className: 'right',
      render: (text: any) => `¥${formatMoney(text)}`,
    },
    {
      title: '应收款',
      dataIndex: 'AI_AmountReceivable',
      key: 'AI_AmountReceivable',
      width: 120,
      sorter: true,
      className: 'right',
      render: (text: any) => `¥${formatMoney(text)}`,
    },
    {
      title: '已付金额',
      dataIndex: 'AI_AmountReceived',
      key: 'AI_AmountReceived',
      width: 120,
      sorter: true,
      className: 'right',
      render: (text: any) => `¥${formatMoney(text)}`,
    },
    {
      title: '欠款',
      dataIndex: 'AI_Debt',
      key: 'AI_Debt',
      width: 120,
      className: 'right',
      sorter: true,
      render: (text: any) => (
        <span style={{ color: text > 0 ? 'red' : 'inherit' }}>
          ¥{formatMoney(text)}
        </span>
      ),
    },
    {
      title: '支付状态',
      dataIndex: 'AI_PayStatus',
      key: 'AI_PayStatus',
      width: 100,
      sorter: true,
      render: (text: any) => {
        const statusMap: { [key: string]: { color: string; text: string } } = {
          '未付': { color: 'red', text: '未付' },
          '部分付款': { color: 'orange', text: '部分付款' },
          '已付': { color: 'green', text: '已付' },
        };
        const status = statusMap[text] || { color: 'default', text: text || '未付' };
        return <Tag color={status.color}>{status.text}</Tag>;
      },
    },
     {
      title: '备注',
      sorter: true,
      dataIndex: 'AI_Memo',
      key: 'AI_Memo',
      width: 120,
      hideInSearch: true,
      render: (text: any) => (
        <Tooltip title={text} placement="topLeft">
          <div
            style={{
              maxWidth: '150px',
              overflow: 'hidden',
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
              cursor: 'pointer'
            }}
          >
            {text}
          </div>
        </Tooltip>
      ),
    },
    {
      title: '创建时间',
      dataIndex: 'SYS_CREATED',
      key: 'SYS_CREATED',
      width: 150,
      sorter: true,
      render: (text: any) => text ? text.substring(0, 19) : '',
    },
    {
      title: '操作',
      key: 'action',
      dataIndex: 'action',
      fixed: 'right',
      width: 80,
      search: false,
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
                icon={<PrinterOutlined />}
                onClick={async () => {
                  const res: any = await getAdvitem({ advitemId: record.SYS_DOCUMENTID });
                  if (res.data){
                    setPrintRecord(res.data);
                    setPrintModalVisible(true);
                  }
                }}
              >
                打印
              </Button>
              <Button
                type="text"
                onClick={async () => {
                  setModalVisible(true);
                  const aiType = record.AI_Type;
                  if (aiType === OrderTypeEnum.FzAdv) {
                    setAdvType('fzadv');
                  } else if (aiType === OrderTypeEnum.SmallBuiness) {
                    setAdvType('smallbusiness');
                  } else {
                    setAdvType('advitem');
                  }
                  var res: any = await getAdvitem({ advitemId: record.SYS_DOCUMENTID });
                  setEditData(res.data[0]||record);
                  setRefreshKey(refreshKey + 1);
                }}
              >
                编辑
              </Button>
              <Button
                type="text"
                onClick={async () => {
                  setPreviewData(record);
                  setPreviewVisible(true);
                }}
              >
                预览
              </Button>
              <Button
                type="text"
                danger
                onClick={() => {
                  Modal.confirm({
                    title: '确定要删除该广告吗？',
                    okText: '确认',
                    cancelText: '取消',
                    onOk: async () => {
                      const res: any = await deleteAdvitem({ SYS_DOCUMENTID: record.SYS_DOCUMENTID });
                      if (res.errorMessage) {
                        Modal.error({ title: res.errorMessage });
                      } else {
                        actionRef.current?.reload();
                        onChange?.();
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

  // 根据保存的列配置过滤并排序columns
  const getFilteredColumns = () => {
    const savedCols = localStorage.getItem('advitem_columns');
    if (savedCols) {
      const savedKeys = JSON.parse(savedCols) as string[];
      // 先按savedKeys的顺序排序，action列固定在最后
      const filtered = allColumns.filter((col: any) =>
        col.key === 'action' || savedKeys.includes(col.dataIndex as string)
      );
      return filtered.sort((a, b) => {
        if (a.key === 'action') return 1;
        if (b.key === 'action') return -1;
        return savedKeys.indexOf(a.dataIndex as string) - savedKeys.indexOf(b.dataIndex as string);
      });
    }
    return allColumns;
  };

  const columns = getFilteredColumns();
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

    // 工具栏 (统计栏等)
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
    const calculatedHeight = Math.max(availableHeight + 50, 300); // 最小高度300px，额外增加45px

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

  // 组件挂载时自动刷新
  useEffect(() => {
    // 延迟执行，等待 ProTable 初始化完成
    const timer = setTimeout(() => {
      actionRef.current?.reload();
      calculateTableHeight(); // 再次计算，确保准确
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

  // 从columns生成可选列配置
  const getColumnDataFromColumns = () => {
    return allColumns
      .filter((col: any) => col.dataIndex && col.dataIndex !== 'action' && col.key !== 'action')
      .map((col: any) => ({
        key: col.dataIndex,
        title: col.title,
      }));
  };

  const columnData = getColumnDataFromColumns();

  // 从columns中提取导出所需的列名和表头（逗号分割的字符串）
  const getExportColumns = () => {
    const exportColumns: string[] = [];
    const exportColnames: string[] = [];
    columns.forEach((col: any) => {
      // 排除操作列
      if (col.dataIndex && col.dataIndex !== 'action' && col.key !== 'action') {
        exportColumns.push(col.dataIndex);
        exportColnames.push(col.title);
      }
    });
    return {
      exportColumns: exportColumns.join(','),
      exportColnames: exportColnames.join(','),
    };
};

  // 搜索栏表单项
  const searchItems = [

    <DatePicker.RangePicker
      key="dateRange"
      style={{ width: 250 }}
      placeholder={['刊期开始日', '刊期结束']}
      onChange={(dates: any, dateStrings: any) => {
        params.AI_PublishTime = dateStrings[0];
        params.AI_PublishEndTime = dateStrings[1];
        setParams({ ...params });
      }}
    />,
    <Dictselect
      key="AI_Type"
      type="广告金额类型"
      placeholder="广告金额类型"
      style={{ width: 150 }}
      onChange={(value: any) => {
        params.AI_Type = value ? (value.value || value) : null;
        setParams({ ...params });
      }}
    />,
    
    <Dictselect
      key="AI_Publication"
      type="刊物"
      multiple={true}
      needAddItem={false}
      style={{ width: 150 }}
      placeholder="刊物"
      onChange={(value: any) => {
        params.AI_Publication_ID = value ? (Array.isArray(value) ? value.map((e:any)=>e.value).join(',') : value.value || value) : null;
        setParams({ ...params });
      }}
    />,
    <div style={{width:250}}>
      <Tradecascade
        key="AI_TradeID"
        placeholder="行业"
        multiple={true}
        onChange={(item: any) => {
          params.AI_TradeID = item?.value;
          setParams({ ...params });
        }}
      />
    </div>,
    <div style={{width:250}}>
      <Orgcascade
        key="AI_Org_ID"
        multiple={true}
        onChange={(item: any) => {
          
          if (Array.isArray(item)){
            params.AI_Org_ID = item.join(',')
          }else{
            params.AI_Org_ID = item?.value;
          }
          
          setParams({ ...params });
        }}
      />
    </div>,

    <Input
      key="AI_Field"
      placeholder="版位"
      style={{ width: 120 }}
      value={fieldSearchValue}
      onChange={(e: any) => {
        setFieldSearchValue(e.target.value);
      }}
    />,
    <Advsize
      adTypeId={params.AI_Publication_ID}
      placeholder="规格"
      style={{ width: 180 }}
      selectFirst={false}
      onChange={(value:any)=>{
        params.AI_Size_ID = value?.value;
        setParams({ ...params });
      }}
    />,
    <UserAutocomplete
      key="AI_Salesman_ID"
      multiple={false}
      placeholder="业务员"
      width={'150px'}
      onChange={(value: any) => {
        params.AI_Salesman_ID = value?.value;
        setParams({ ...params });
      }}
    />,
    <Input
      key="AI_Content"
      placeholder="广告内容"
      style={{ width: 120 }}
      onChange={(e: any) => {
        params.AI_Content = e.target.value;
        setParams({ ...params });
      }}
    />,
    <Input
      key="AI_Memo"
      placeholder="备注"
      style={{ width: 120 }}
      onChange={(e: any) => {
        params.AI_Memo = e.target.value;
        setParams({ ...params });
      }}
    />,
    
    
    <Companyselect
      key="partb"
      multiple={false}
      placeholder="主体"
      style={{ width: 150 }}
      onChange={(value: any) => {
        params.partb = value?.id;
        setParams({ ...params });
      }}
    />,
    <Companyselect
      key="AI_Customer_ID"
      multiple={false}
      placeholder="客户"
      style={{ width: 150 }}
      onChange={(value: any) => {
        params.AI_Customer_ID = value?.id;
        setParams({ ...params });
      }}
    />,
    <ContractSelect
      key="contractid"
      showupload={false}
      multiple={false}
      
      style={{ width: '150px' }}
      onChange={(value: any) => {
        params.contractid = value?.id;
        setParams({ ...params });
      }}
    />,
    
    <UserAutocomplete
      key="assistant"
      multiple={false}
      placeholder="协助人员"
      width={'150px'}
      onChange={(value: any) => {
        params.assistant = value?.value;
        setParams({ ...params });
      }}
    />,

    <div style={{ width: 200 }}>
      <DepartmentTreeSelect
        key="assistantdepartmentid"
        multiple={false}
        placeholder="协助部门"
        onChange={(value: any) => {
          // DepartmentTreeSelect 可能返回 id 或 value 属性
          const deptId = value?.id || value?.value || value;
          params.assistantdepartmentid = deptId;
          setParams({ ...params });
          // 手动触发 ProTable 搜索
          actionRef.current?.reload();
        }}
      />
      
    </div>,
    <Input
      key="SYS_DOCUMENTID"
      placeholder="广告编号"
      style={{ width: 100 }}
      onChange={(e: any) => {
        params.SYS_DOCUMENTID = e.target.value;
        setParams({ ...params });
      }}
    />,
  ];

  return (
    <>
      <ProTable
        id="advTable"
        actionRef={actionRef}
        formRef={formRef}
        columns={columns}
    
         
        search={order && order.SYS_DOCUMENTID ? false : { filterType: 'light' }}
        rowKey={(record: any) => 'advitem' + record.SYS_DOCUMENTID}
        pagination={{
          pageSize: 50,
          showQuickJumper: true,
          showSizeChanger: true,
          showTotal: (total, range) => `共 ${total} 条记录，显示 ${range[0]}-${range[1]} 条`,
          position: ['bottomRight']
        }}
        scroll={{ x: 'max-content', y: tableHeight }}
        sticky={{ offsetHeader: 64 }}
        params={params}
        rowSelection={{
          onChange: (_, selectedRows) => {
            setSelectedRows(selectedRows);
          },
        }}
        title={()=>[order&&order.SYS_DOCUMENTID?'':<AdvitemStatistics key={statkey} params={{...params}} total={total} />]}
        request={async (params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          if (sorter){
              Object.keys(sorter).forEach((key)=>{
                var order = sorter[key]=='ascend'?'asc':'desc'
                params.orderby=key+" " + order
              })
            }
          // 添加订单ID筛选
          if (order&&order.SYS_DOCUMENTID) {
            params.AI_OrderID = order.SYS_DOCUMENTID;
          }

          // 合并搜索参数
          const searchParams = { ...params,withdeleted:1 };

          const result: any = await getAdvitemList(searchParams);

          setStatkey(statkey+1)
         
          if(result.rows && result.rows[0]) {
            var row = {...result.rows[0]}
            delete row.SYS_DOCUMENTID;
            delete row.AI_PublishTime;
            delete row.AI_PublishEndTime;
            delete row.AI_Price;
            delete row.AI_AmountReceivable;
            row.AI_PublishDayCount=1;
            setRow(row)
          }
          // 无论是否有数据，都更新 total
          setTotal(result.total || 0);
          // 兼容 rows 和 data 两种返回格式
          return {
            data: result.rows || result.data || [],
            total: result.total || 0,
            success: result.success !== false,
          };
        }}
        toolbar={{
          filter: !order || !order.SYS_DOCUMENTID ? (
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, alignItems: 'flex-start' }}>
              {searchItems.slice(0, searchExpanded ? searchItems.length : 4)}
              {searchItems.length > 4 && (
                <Button type="link" onClick={() => setSearchExpanded(!searchExpanded)} style={{ fontWeight: 'bold', fontSize: 16 }}>
                  {searchExpanded ? '收起' : '展开'}
                </Button>
              )}
            </div>
          ) : undefined,
          actions: [
            // 只有当order有值时才显示"添加广告"按钮
            ...(order && order.SYS_DOCUMENTID ? [
              <Button
                key="add"
                type="primary"
                icon={<PlusOutlined />}
                onClick={() => {
                  // 根据订单类型设置广告类型
                  const orderType = order?.AO_Type;
                  if (orderType === 1) {
                    setAdvType('fzadv');
                  } else if (orderType === 2) {
                    setAdvType('smallbusiness');
                  } else {
                    setAdvType('advitem');
                  }
                  setEditData(row || {
                    AI_OrderID: order?.SYS_DOCUMENTID,
                    AI_Customer_ID: order?.AI_Customer_ID,
                    I_Customer: order?.AI_Customer,
                    AI_Publication_ID: order?.publicationid,
                    AI_Publication: order?.publication,
                    // 发行区域
                    AI_Edition_ID: 1,
                    AI_Edition: '全国发行',
                    // 颜色
                    AI_Color_ID: 1,
                    AI_Color: '黑色',
                    // 支付方式
                    AI_PayMode_ID: 2,
                    AI_PayMode: '转账',
                    // 投放日
                    E_MID_ID:1,
                    E_MID:'全部',
                    // 计价方式
                    AI_PriceModeIC: 0,
                  });
                  setRefreshKey(refreshKey + 1);
                  setModalVisible(true);
                }}
              >
                添加广告
              </Button>
            ] : []),
            <Button
              key="manualSearch"
              icon={<SearchOutlined />}
              onClick={() => {
                const newParams = { ...params, AI_Field: fieldSearchValue };
                setParams(newParams);
                actionRef.current?.reload();
              }}
            >
              搜索
            </Button>,
            <Button
              key="batchPrint"
              type="primary"
              onClick={() => {
                if (!selectedRows || selectedRows.length === 0) {
                  Modal.error({ title: '请选择要打印的广告' });
                  return;
                }

                // 一次性传递所有选中广告的ID到后端批量查询
                const advitemIds = selectedRows.map((advitem: any) => advitem.SYS_DOCUMENTID).join(',');
                getAdvitem({ advitemId: advitemIds }).then((res: any) => {
                  if (res.errorMessage) {
                    Modal.error({ title: `获取批量打印数据失败: ${res.errorMessage}` });
                    return;
                  }
                  setPrintModalVisible(true);
                  setPrintRecord(res.data);
                });
              }}
            >
              批量打印 ({selectedRows.length})
            </Button>,
            <Button
              key="customColumns"
              onClick={() => {
                // 从localStorage读取已保存的列配置
                const savedCols = localStorage.getItem('advitem_columns');
                setTargetKeys(savedCols ? JSON.parse(savedCols) : []);
                setColumnModalVisible(true);
              }}
            >
              自定义列
            </Button>,
            <Button
              key="export"
              icon={<DownloadOutlined />}
              onClick={() => {
                // 导出Excel
                const { exportColumns, exportColnames } = getExportColumns();
                var exportParams = { ...params, columns: exportColumns, colnames: exportColnames };
                if (order && order.SYS_DOCUMENTID) {
                  exportParams.AI_OrderID = order.SYS_DOCUMENTID;
                }
                exportParams.pageSize=10000
                // 判断exportParams的字段数量是否超过3个
                if (Object.keys(exportParams).length <= 3) {
                  Modal.error({
                    title:"必须设置筛选条件，无法导出全部！"
                  })
                  return
                }
                tools.downloadFile("/api/advertisemanange/advitemslist", exportParams, "广告列表.xlsx")

              }}
            >
              导出(仅生效)
            </Button>
          ],
        }}
      />
<TableScrollSync tableId="advTable" onScroll={(scroll:any)=>{
            const tableContent = document.querySelector('#advTable .ant-table-content');
            if (tableContent){
              tableContent.scrollLeft = scroll;
            }
          }} />
      <Modal
        title={editData?.SYS_DOCUMENTID ? '编辑广告' : '添加广告'}
        style={{ top: 20 }}
        width={advType === 'advitem' ? 1100 : 900}
        visible={modalVisible}
        onOk={() => setModalVisible(false)}
        onCancel={() => setModalVisible(false)}
        footer={null}
        destroyOnClose
      >
        {advType === 'fzadv' && (
          <AddFzAdv
            key={refreshKey}
            data={editData}
            onChange={() => {
              setModalVisible(false);
              actionRef.current?.reload();
              onChange?.();
            }}
          />
        )}
        {advType === 'smallbusiness' && (
          <AddSmallBusiness
            key={refreshKey}
            data={editData}
            onChange={() => {
              setModalVisible(false);
              actionRef.current?.reload();
              onChange?.();
            }}
          />
        )}
        {advType === 'advitem' && (
          <AddAdvitem
            key={refreshKey}
            data={editData}
            onChange={() => {
              setModalVisible(false);
              actionRef.current?.reload();
              onChange?.();
            }}
          />
        )}
      </Modal>

      {/* 打印预览弹窗 */}
      <Modal
        title=""
        width={900}
        visible={printModalVisible}
        onCancel={() => setPrintModalVisible(false)}
        footer={null}
        destroyOnClose
      >
        {printRecord && printRecord.length > 0 && (
          <PrintMixed records={printRecord} />
        )}
      </Modal>

      {/* 广告订单预览弹窗 */}
      <Modal
        title="广告订单详情"
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
            <div><Filescard  urls={previewData.fileurls} mode="list"/></div>
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

      <AdvitemViewModal
        id={approvalRecord.id}
        thirdNo={approvalRecord.thirdNo}
        visible={approvalModalVisible}
        onVisibleChange={setApprovalModalVisible}
        onApplyChange={() => {
          actionRef.current?.reload();
          onChange?.();
        }}
      />

      <Modal
        title="自定义列"
        visible={columnModalVisible}
        onCancel={() => setColumnModalVisible(false)}
        onOk={() => {
          localStorage.setItem('advitem_columns', JSON.stringify(targetKeys));
          setColumnModalVisible(false);
        }}
        width={700}
      >
        <div style={{ display: 'flex', justifyContent: 'space-around', marginBottom: 8 }}>
          <span style={{ width: 300, textAlign: 'center', fontWeight: 'bold' }}>可选列</span>
          <span style={{ width: 300, textAlign: 'center', fontWeight: 'bold' }}>已选列</span>
        </div>
        <Transfer
          dataSource={columnData}
          targetKeys={targetKeys.length > 0 ? targetKeys : columnData.map(c => c.key)}
          onChange={(keys) => {
            if (keys.length === 0) {
              // 移除所有列时，默认全选
              setTargetKeys(columnData.map(c => c.key));
            } else {
              setTargetKeys(keys);
            }
          }}
          onSelectChange={(sourceSelectedKeys, targetSelectedKeys) => setTargetSelectedKeys(targetSelectedKeys)}
          render={(item) => item.title || item.key}
          listStyle={{ width: 300, height: 400 }}
          operations={['添加', '移除']}
        />
        <div style={{ marginTop: 10, textAlign: 'right' }}>
          <Button
            size="small"
            onClick={() => {
              if (targetSelectedKeys.length === 0) return;
              const firstIdx = targetKeys.indexOf(targetSelectedKeys[0]);
              if (firstIdx === 0) return;
              const newKeys = [...targetKeys];
              const selectedItems = targetSelectedKeys.map(k => newKeys[newKeys.indexOf(k)]);
              const startIdx = newKeys.indexOf(targetSelectedKeys[0]);
              newKeys.splice(startIdx, targetSelectedKeys.length);
              newKeys.splice(firstIdx - 1, 0, ...selectedItems);
              setTargetKeys(newKeys);
            }}
            style={{ marginRight: 8 }}
          >
            上移
          </Button>
          <Button
            size="small"
            onClick={() => {
              if (targetSelectedKeys.length === 0) return;
              const lastIdx = targetKeys.indexOf(targetSelectedKeys[targetSelectedKeys.length - 1]);
              if (lastIdx === targetKeys.length - 1) return;
              const newKeys = [...targetKeys];
              const selectedItems = targetSelectedKeys.map(k => newKeys[newKeys.indexOf(k)]);
              const startIdx = newKeys.indexOf(targetSelectedKeys[0]);
              newKeys.splice(startIdx, targetSelectedKeys.length);
              newKeys.splice(lastIdx + 1, 0, ...selectedItems);
              setTargetKeys(newKeys);
            }}
            style={{ marginRight: 8 }}
          >
            下移
          </Button>
          <Button type="primary" size="small" onClick={() => {
            localStorage.setItem('advitem_columns', JSON.stringify(targetKeys));
            setColumnModalVisible(false);
            window.location.reload();
          }}>
            保存
          </Button>
        </div>
      </Modal>

      <Modal
        title=""
        visible={contractModalVisible}
        onCancel={() => setContractModalVisible(false)}
        footer={null}
        width={800}
        destroyOnClose
      >
        <ContractView id={contractDetailId} paystate="" />
      </Modal>

    </>
  );
};

export default AdvitemList;
