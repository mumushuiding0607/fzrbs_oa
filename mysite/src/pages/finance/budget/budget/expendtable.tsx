import React from 'react';
import { Table } from 'antd';
import type { TableColumnsType } from 'antd';

const columns: TableColumnsType<any> = [
  {
    title: '支出项目',
    dataIndex: 'title',
  },
  {
    title: '预算支出',
    dataIndex: 'budgetexpend',
  },
  {
    title: '预算备注',
    dataIndex: 'budgetmemo',
  },
  {
    title: '决算支出',
    dataIndex: 'finalexpend',
  },
  {
    title: '决算备注',
    dataIndex: 'finalmemo',
  },
];

const data = [
  {
    key: '1',
    title: '合作单位A',
    budgetexpend: 150,
    finalexpend: 120,
    budgetmemo: 'New York No. 1 Lake Park',
  },
  {
    key: '2',
    title: '合作单位B',
    budgetexpend: 150,
    finalexpend: 120,
    budgetmemo: 'New York No. 1 Lake Park',
  },
  {
    key: '3',
    title: '合作单位C',
    budgetexpend: 150,
    finalexpend: 120,
    budgetmemo: 'New York No. 1 Lake Park',
  },
  {
    key: '4',
    title: '税费：',
    budgetexpend: 150,
    finalexpend: 120,
    budgetmemo: 'New York No. 1 Lake Park',
  },
  {
    key: '5',
    title: '经营绩效：',
    budgetexpend: 150,
    finalexpend: 120,
    budgetmemo: 'New York No. 1 Lake Park',
  },
  {
    key: '6',
    title: '合作单位C',
    budgetexpend: 150,
    finalexpend: 120,
    budgetmemo: 'New York No. 1 Lake Park',
  },
];

const Expendtable: React.FC = () => (
  <>
    <Table columns={columns} dataSource={data} size="small" />
  </>
);

export default Expendtable;