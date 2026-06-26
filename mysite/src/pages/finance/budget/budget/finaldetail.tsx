import React from 'react';
import { Divider, Table,Typography } from 'antd';
import {  } from 'antd';
const { Title } = Typography;

const result = {
  columns:[
    [
      {
        title: '名称',
        dataIndex: 'title',
      },
      {
        title: '预算金额',
        dataIndex: 'budget',
      },
      {
        title: '决算金额',
        dataIndex: 'final',
      },
      {
        title: '备注',
        dataIndex: 'memo',
      },
    ],
    [
      {
        title: '付款单位',
        dataIndex: 'title',
      },
      {
        title: '预算金额',
        dataIndex: 'budget',
      },
      {
        title: '决算金额',
        dataIndex: 'final',
      },
      {
        title: '备注',
        dataIndex: 'memo',
      },
    ],
    [
      {
        title: '名称',
        dataIndex: 'title',
      },
      {
        title: '预算金额',
        dataIndex: 'budget',
      },
      {
        title: '决算金额',
        dataIndex: 'final',
      },
      {
        title: '备注',
        dataIndex: 'memo',
      },
    ]
  ],
  datas:[
    [
      {
        key: '1',
        title: '总收入',
        budget: 100,
        budgetmemo: 'New York No. 1 Lake Park',
      },
      {
        key: '2',
        title: '总支出',
        budget: 100,
        memo: 'New York No. 1 Lake Park',
      },
      {
        key: '2',
        title: '支出占比',
        budget: 0.5,
        memo: 'New York No. 1 Lake Park',
      },
    ],
    [
      {
        key: '1',
        title: '总收入',
        budget: 100,
        budgetmemo: 'New York No. 1 Lake Park',
      },
      {
        key: '2',
        title: '总支出',
        budget: 100,
        memo: 'New York No. 1 Lake Park',
      },
      {
        key: '2',
        title: '支出占比',
        budget: 0.5,
        memo: 'New York No. 1 Lake Park',
      },
    ],
    [
      {
        key: '1',
        title: '总收入',
        budget: 100,
        budgetmemo: 'New York No. 1 Lake Park',
      },
      {
        key: '2',
        title: '总支出',
        budget: 100,
        memo: 'New York No. 1 Lake Park',
      },
      {
        key: '2',
        title: '支出占比',
        budget: 0.5,
        memo: 'New York No. 1 Lake Park',
      },
    ]
  ],
  titles:['决算收支总表','决算收入明细','决算支出明细']
}


const Finaldetail: React.FC<{id:any}> = ({id}) => {
  return (
  
    <div style={{margin:'10px'}}>
      {Array.from({length: result.columns.length}).map((_,i)=>(
        <>
          <Title level={5} style={{'textAlign':'left'}}>{result.titles[i]}：</Title>
          <Table bordered columns={result.columns[i]} dataSource={result.datas[i]} size="small" pagination={false} />
        </>
      ))}
    </div>
  )
};

export default Finaldetail;