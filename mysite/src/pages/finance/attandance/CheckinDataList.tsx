import type { ActionType, ProFormColumnsType, ProFormInstance } from '@ant-design/pro-components';
import {
  ProTable,
  ProFormDateTimeRangePicker,
  ProFormSelect,
} from '@ant-design/pro-components';
import { Button, Modal, Input } from 'antd';
import { useRef, useState } from 'react';
import { checkinDataList, exportCheckinData, exportStat, exportApplyRecord } from './service';
import * as XLSX from 'xlsx';
import DepartmentTreeSelect from '../budget/common/department_treeselect';
import UserAutocomplete from '../budget/common/userAutocomplete';
import Rolelist from '../role/rolelist';

const exceptionTypes = [
  { label: '正常打卡', value: '0' },
  { label: '已处理', value: '1' },
  { label: '时间异常', value: '2' },
  { label: '地点异常', value: '3' },
  { label: '未打卡', value: '4' },
  { label: 'wifi异常', value: '5' },
  { label: '非常用设备', value: '6' },
  { label: '请假', value: '7' },
  { label: '忽略', value: '8' },
];

const CheckinDataList: React.FC = () => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const [params, setParams] = useState<any>({});
  const [rolemodal,setRolemodal] = useState(false)

  const columns: ProFormColumnsType<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      width: 60,
    },
    {
      title: '申请部门',
      dataIndex: 'departmentid',
      key: 'departmentid',
      hideInTable:true,
      renderFormItem: () => {

        return (
          <DepartmentTreeSelect maxTagCount={1}/>

        )
      }
    },
    {
      title:'用户',
      dataIndex:'userid',
      key:'userid',
      width:100,
      hideInTable:true,
      render:(_:any,record:any)=>record.name,
      renderFormItem: () => {

        return (
          <UserAutocomplete multiple={false}/>

        )
      }
    },
    {
      title: '姓名',
      dataIndex: 'username',
      width: 100,
      hideInSearch: true,
    },
    {
      title: '部门',
      dataIndex: 'department',
      width: 150,
      hideInSearch: true,
    },
    {
      title: '打卡时间',
      dataIndex: 'checkin_time',
      width: 150,
      valueType: 'dateTime',
    },
    {
      title: '标准打卡时间',
      dataIndex: 'sch_checkin_time',
      width: 150,
      valueType: 'dateTime',
    },
    {
      title: '异常类型',
      dataIndex: 'exception_type',
      width: 120,
      valueEnum: {
        0: '正常打卡',
        1: '已处理',
        2: '时间异常',
        3: '地点异常',
        4: '未打卡',
        5: 'wifi异常',
        6: '非常用设备',
        7: '请假',
        8: '忽略',
      },
    }
  ];

  // 获取上月时间范围
  const getLastMonthRange = () => {
    const now = new Date();
    const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const lastMonthEnd = new Date(now.getFullYear(), now.getMonth(), 0);
    
    const start = `${lastMonth.getFullYear()}-${String(lastMonth.getMonth() + 1).padStart(2, '0')}-01`;
    const end = `${lastMonthEnd.getFullYear()}-${String(lastMonthEnd.getMonth() + 1).padStart(2, '0')}-${lastMonthEnd.getDate()}`;
    
    return { start, end };
  };

  const handleExport = () => {
    Modal.confirm({
      title: '确认导出',
      content: '确定要导出当前查询条件下的打卡数据吗？',
      onOk: async () => {
        const exportParams = { ...params };
        // 如果没有设置时间，默认导出上月数据
        if (!exportParams.start && !exportParams.end) {
          const { start, end } = getLastMonthRange();
          exportParams.start = start;
          exportParams.end = end;
        }
        
        const res = await exportCheckinData(exportParams);
        if (res.errorMessage) {
          Modal.error({ title: res.errorMessage });
        } else {
          const ws = XLSX.utils.aoa_to_sheet(res.data);
          const wb = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
          XLSX.writeFile(wb, `打卡异常数据_${new Date().toLocaleString()}.xlsx`);
          Modal.info({ title: '导出成功' });
        }
      },
    });
  };

  const handleExportStat = () => {
    Modal.confirm({
      title: '导出异常统计表',
      content: '确定导出异常统计表吗？未选择时间将默认导出上月数据。',
      onOk: async () => {
        const exportParams = { ...params };
        if (!exportParams.start && !exportParams.end) {
          const { start, end } = getLastMonthRange();
          exportParams.start = start;
          exportParams.end = end;
        }
        
        const res = await exportStat(exportParams);
        if (res.errorMessage) {
          Modal.error({ title: res.errorMessage });
        } else {
          const ws = XLSX.utils.aoa_to_sheet(res.data);
          const wb = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
          XLSX.writeFile(wb, `异常统计表_${new Date().toLocaleString()}.xlsx`);
          Modal.info({ title: '导出成功' });
        }
      },
    });
  };

  const handleExportApply = () => {
    Modal.confirm({
      title: '导出异常申请纪录',
      content: '确定导出异常申请纪录吗？未选择时间将默认导出上月数据。',
      onOk: async () => {
        const exportParams = { ...params };
        if (!exportParams.start && !exportParams.end) {
          const { start, end } = getLastMonthRange();
          exportParams.start = start;
          exportParams.end = end;
        }
        
        const res = await exportApplyRecord(exportParams);
        if (res.errorMessage) {
          Modal.error({ title: res.errorMessage });
        } else {
          const ws = XLSX.utils.aoa_to_sheet(res.data);
          const wb = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
          XLSX.writeFile(wb, `异常申请纪录_${new Date().toLocaleString()}.xlsx`);
          Modal.info({ title: '导出成功' });
        }
      },
    });
  };

  return (
    <>
    <ProTable
      style={{ minHeight: 'calc(100vh - 180px)' }}
      headerTitle="打卡异常数据"
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record: any) => record.id}
      search={{
        labelWidth: 'auto',
        defaultCollapsed: false,
      }}
      columns={columns}
      request={async (params) => {
        const res = await checkinDataList(params);
        return {
          data: res.data,
          total: res.total,
          success: true,
        };
      }}
      toolbar={{
        actions: [
          <Button key="exportStat" type="default" onClick={handleExportStat}>
            异常统计表
          </Button>,
          <Button key="exportApply" type="default" onClick={handleExportApply}>
            异常申请纪录
          </Button>,
          <Button type='primary' onClick={() => setRolemodal(true)}>角色设置</Button>,
        ],
      }}
      pagination={{
        pageSize: 20,
        showQuickJumper: true,
        showSizeChanger: true,
      }}
    />
    <Modal
          visible={rolemodal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setRolemodal(false)}
          onCancel={() => setRolemodal(false)}
          footer= {null}
        >
        <Rolelist ></Rolelist>
      </Modal>
    </>
    
  );
};

export default CheckinDataList;
