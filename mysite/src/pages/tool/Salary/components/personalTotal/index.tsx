import { Layout } from 'antd';
import React, { useRef, useState, useEffect } from 'react';
import type { ColumnsType } from 'antd/es/table';
import { Table,message, Space } from 'antd';
import { ProForm,ProFormDatePicker } from '@ant-design/pro-components';
import { personTotal } from '../salary/service';
import DeptSel from '@/components/DepartmentTreeSelect';
import styles from '../salary/index.less';
import moment from 'moment';
interface DataType {
    key: string;
    type: string;
    year: number;
    yf: number;
    dk: number;
    sf: number;
  }
const PersonalTotalIndex: React.FC = () => {
  const [data, setData] = useState<DataType[]>([]);
  const sysuserRef = useRef();
  const isNumeric = (input: string): boolean => {
    const pattern = /^\d+$/;
    return pattern.test(input);
  };
  const requestData = async (data: any)=>{
    await personTotal(data).then((res) => {
        console.log('data:',res);
        setData(res.data);
      }); 
  }
  const columns: ColumnsType<DataType> = [
    {
      title: '项目',
      dataIndex: 'project',
      children: [
        {
            title: '类型',
            dataIndex: 'type',
            key: 'type',
            width: 100,
            onCell: (_, index) => {
                if(index == 0 || index == 2 || index == 4 || index == 6 || index == 8){
                    return {rowSpan: 2 };
                }else{
                    return {rowSpan: 0 };
                }
            }
        },
        {
            title: '年份',
            dataIndex: 'year',
            key: 'year',
            width: 80,
        },
      ]            
    },
    {
      title: '应发总额',
      dataIndex: 'yf',
      width: 100,
      align: 'right'
    },
    {
      title: '代扣代缴',
      dataIndex: 'dk',
      width: 100,
      align: 'right'
    },
    {
      title: '实发总额',
      dataIndex: 'sf',
      width: 100,
      align: 'right'
    },
  ];

  useEffect(() => {
   
    
  },[])
  return (
    <>  
      <Layout>
        <Layout.Sider className={styles.wbg} width="350px">
            <ProForm
                layout='vertical'
                submitter={{
                    searchConfig: {
                        resetText: '重置',
                        submitText: '汇总',
                    },   
                    render: (props, doms) => {
                        return (
                            <ProForm.Group style={{marginTop:'1.5vw'}}>
                              <Space>{doms}</Space>
                            </ProForm.Group>
                        );
                      },                
                }}
                initialValues={{
                    year: moment().format("YYYY")
                }}
                onFinish={async (values) => {
                    values.userid = sysuserRef?.current?.getCheckedKeys();
                    if(isNumeric(values.userid)){
                        message.error('只能汇总个人数据！');
                        return;
                    }
                    requestData(values);
                }}
            >
                <ProForm.Group label="年份">
                    <ProFormDatePicker.Year name="year" width="xs" />
                </ProForm.Group>
                <ProForm.Group label="选择职工">
                    <DeptSel local={true} showLeafIcon={true} showAll={true} showUser={true} checkable={false} ref={sysuserRef} width='260px' showCheckedStrategy='SHOW_CHILD' />
                </ProForm.Group>
            </ProForm>
        </Layout.Sider>
        <Layout.Content className={styles.wbg}>
            <Space><Table columns={columns} dataSource={data} pagination={false} bordered /></Space>
        </Layout.Content>
      </Layout>
    </>


  );
};

export default PersonalTotalIndex;
