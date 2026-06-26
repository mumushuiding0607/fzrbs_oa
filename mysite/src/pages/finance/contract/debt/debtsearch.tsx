
import React, { useState, useEffect } from 'react';
import { Card, Row, Col, Typography, Divider, Button, Modal } from 'antd';

import Debtlist from './debtlist';
import { ProTable } from '@ant-design/pro-components';
import { debtlistbyfield, debtstat } from './service';

import { downloadAsXlSX } from '../../utils';
import { DownloadOutlined, ReloadOutlined } from '@ant-design/icons';

const { Title, Text } = Typography;


const Debtsearch: React.FC<{id?:any,onSearch?:Function}> = (id,onSearch) => {
  const [parp, setParp]=useState<any>({})
  const [pard, setPard] = useState<any>({})
  const [dkey,setDkey]=useState(0)
  const [data, setData] = useState<any>({
    totalAmount: 0,
    ageGroup: {}
  });
  const [debtlistModal,setDebtlistModal]=useState(false)
  const [debtlistparams,setDebtlistparams]=useState<any>({})
  // 模拟数据加载
  useEffect(() => {
    debtstat({}).then((res:any)=>{
      setData(res)
    })
  }, []);

  const formatMoney = (num: any): string => {
    return num?.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') || '0.00';
  };
   let columns1:any =[
    // {
    //   title: '序号',
    //   dataIndex: 'index',
    //   key:'index',
    //   width: 65,

    //   render: (_: any, record: any, index: number) => {
    //     return (parp.current - 1) * parp.pageSize + index + 1;
    //   }
    // },
    {
      title:'债务方',
      dataIndex:'partaname',
      key:'partaname',
      width: 250,
      render: (text:any,record:any)=>{
    
        return <span onClick={()=>{
          document.body.scrollTop = document.documentElement.scrollTop = 850;
          setDebtlistparams({parta:record.parta})
          setDkey(dkey+1)
        }} >{text||''}</span>
      }
    },
    {
      title:'欠款金额',
      dataIndex:'debt',
      key:'debt',
      sorter: true,
      width: 120,
      render: (text:any,record:any)=>(
        <>
          {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
        </>
      )
    },
    {
      title:'平均账龄',
      dataIndex:'overdue',
      key:'overdue',
      sorter: true,
      width: 80
    },
    {
      title:'总金额',
      dataIndex:'amount',
      key:'amount',
      hideInTable:true,
      width: 120,
      render: (text:any,record:any)=>(
        <>
          {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
        </>
      )
    },
    {
      title:'已回款',
      dataIndex:'paycollection',
      key:'paycollection',
      hideInTable:true,
      width: 120,
      render: (text:any,record:any)=>(
        <>
          {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
        </>
      )
    },
  ]
  let columns2:any =[
    // {
    //   title: '序号',
    //   dataIndex: 'index',
    //   key:'index',
    //   width: 65,
    //   render: (_: any, record: any, index: number) => {
    //     return (pard.current - 1) * pard.pageSize + index + 1;
    //   }
    // },
    {
      title:'部门',
      dataIndex:'department',
      key:'department',
      width: 250,
      render: (text:any,record:any)=>{
    
        return <span onClick={()=>{
          document.body.scrollTop = document.documentElement.scrollTop = 850;
          // 传递参数为父页面
          setDebtlistparams({departmentid:record.departmentid})
          setDkey(dkey+1)
        }} >{text||''}</span>
      }
    },
    {
      title:'欠款金额',
      dataIndex:'debt',
      key:'debt',
      sorter: true,
      width: 120,
      render: (text:any,record:any)=>(
        <>
          {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
        </>
      )
    },
    {
      title:'平均账龄',
      dataIndex:'overdue',
      key:'overdue',
      sorter: true,
      width: 80
    },
    {
      title:'总金额',
      dataIndex:'amount',
      key:'amount',
      hideInTable:true,
      width: 120,
      render: (text:any,record:any)=>(
        <>
          {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
        </>
      )
    },
    {
      title:'已回款',
      dataIndex:'paycollection',
      key:'paycollection',
      hideInTable:true,
      width: 120,
      render: (text:any,record:any)=>(
        <>
          {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
        </>
      )
    },
  ]


  return (
    <div style={{ padding: '24px', backgroundColor: 'white', minHeight: '100vh', fontFamily: 'Arial, sans-serif' }}>
      <Title level={2} style={{ color: '#1f1f1f', marginBottom: 24 }}>欠款总览看板
        {/* <ReloadOutlined style={{ fontSize: '22px',marginLeft:"10px" }} onClick={()=>{
        debtstat({}).then((res:any)=>{
          setData(res)
        })
      }}/> */}
      </Title>
  

      {/* 账龄分布 */}
      <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
        {Object.entries(data.ageGroup).map(([range, amount]) => (
          <Col xs={24} sm={6}>
          <Card type="inner"  title={
    <span style={{ fontSize: '18px' }}>
      {range}
    </span>
  } bordered={true} headStyle={{fontSize:'18px'}} style={{ textAlign: 'center' }}>
            <Text strong style={{ fontSize: 20,  }}>
              ¥{formatMoney(amount)}
            </Text>
          </Card>
        </Col>
        ))}

      </Row>

      <Divider />

      {/* 客户与部门分布 */}
      <Row gutter={[24, 24]}>
        {/* 客户分布 */}
        <Col xs={24} lg={12}>
          
            <ProTable
            
              headerTitle="按客户分布"
              rowKey={(record:any)=>'c'+record.id}
              search={false}
              columns={columns1}
              pagination={
                {
                  pageSize: 5,
                }
              }
              request={(params, sorter, filter) => {
                document.body.scrollTop = document.documentElement.scrollTop = 0;
                if (sorter){
                  Object.keys(sorter).forEach((key)=>{
                    var order = sorter[key]=='ascend'?'asc':'desc'
                    params.orderby=key+" " + order
                  })
                }
                params.field = 'partaname'
                setParp(params)
                return debtlistbyfield(params);
              }}
              toolbar={{
                actions: [
           
                <DownloadOutlined
                  style={{ fontSize: '20px' }}
                  onClick={() => {
        
                    var par:any = {...parp}
                    par.current=1
                    par.pageSize=10000
                    debtlistbyfield(par).then((res:any)=>{
           
                      if (res.errorMessage) {
                        Modal.error({title: res.errorMessage})
                      } else {
                        var result = res.data.map((row:any,rowIndex:any)=>{
                        var arr:any = []
                        columns1.forEach((h:any,index:number)=>{
                          
                          var temp:string = (row[h.dataIndex]||'').toString()
                          if (temp) {
                            temp = temp.replaceAll(',','，').trim()
                          }
                          switch (h.dataIndex) {
                            case 'index':
                              arr.push(rowIndex +1)
                              break

                            default:
                              arr.push(temp)
                              break;
                          }
                          
                          
                        })

                        return arr
                      })
                        var x = columns1.map((t:any)=>t.title)
                        result.unshift(x)
                        downloadAsXlSX(result,'客户分类统计导出')
                      }
                    })
                  }}
                />

                ],
              }}

            />
          
        </Col>

        {/* 部门分布 */}
        <Col xs={24} lg={12}>
          
            <ProTable
         
              headerTitle="按部门分布"
              rowKey={(record:any)=>'d'+record.id}
              search={false}
              columns={columns2}
              pagination={
                {
                  pageSize: 5,
                }
              }
              request={(params, sorter, filter) => {
                document.body.scrollTop = document.documentElement.scrollTop = 0;
                if (sorter){
                  Object.keys(sorter).forEach((key)=>{
                    var order = sorter[key]=='ascend'?'asc':'desc'
                    params.orderby=key+" " + order
                  })
                }
                params.field = 'department'
                setPard(params)
                return debtlistbyfield(params);
              }}
              toolbar={{
                actions: [
                <DownloadOutlined
                  style={{ fontSize: '20px' }}
                  onClick={() => {
        
                    var par:any = {...pard}
                    par.current=1
                    par.pageSize=10000
                    debtlistbyfield(par).then((res:any)=>{
           
                      if (res.errorMessage) {
                        Modal.error({title: res.errorMessage})
                      } else {
                        var result = res.data.map((row:any,rowIndex:any)=>{
                        var arr:any = []
                        columns2.forEach((h:any,index:number)=>{
                          
                          var temp:string = (row[h.dataIndex]||'').toString()
                          if (temp) {
                            temp = temp.replaceAll(',','，').trim()
                          }
                          switch (h.dataIndex) {
                            case 'index':
                              arr.push(rowIndex +1)
                              break

                            default:
                              arr.push(temp)
                              break;
                          }
                          
                          
                        })

                        return arr
                      })
                        var x = columns2.map((t:any)=>t.title)
                        result.unshift(x)
                        downloadAsXlSX(result,'部门分类统计导出')
                      }
                    })
                  }}
                />

                ],
              }}
            />
          
        </Col>
      </Row>
      <Divider/>
      <Debtlist key={dkey} params={debtlistparams}/>
     
    </div>
  );
};

export default Debtsearch;