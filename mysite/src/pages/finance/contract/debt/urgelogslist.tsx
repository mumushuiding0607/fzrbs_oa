import { Button, Card, DatePicker, Input, Modal, Popover, Radio, Select, Tag } from 'antd';
import moment from 'moment';

import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import UserAutocomplete from '../../budget/common/userAutocomplete';
import { ActionType, ProFormInstance, ProTable } from '@ant-design/pro-components';
import Dictselect from '../../budget/dict/dictselect';
import { urgelogslist } from './service';
import { downloadAsXlSX } from '../../utils';
import TableScrollSync from '../../common/TableScrollSync';
import UrgeView from './urgeview';
import AddFile from './AddFile';
import ContractSelect from '../contract-select';
import { BalanceTypes } from '../../budget/config';
import View from '../view';

const { RangePicker } = DatePicker;

const tag: CSSProperties = {
  margin: '0 5px 0 0',
  padding: '0px 4px',
  borderRadius: '15%',
}

const Urgelogslist: React.FC<{ scrollTop?: boolean, params?: any }> = ({ params = {}, scrollTop = false }) => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const [urgelog, setUrgelog] = useState<any>({});
  const [showAddFile, setShowAddFile] = useState(false);
  const [showUrgeView, setShowUrgeView] = useState(false);
  const [filekey, setFilekey] = useState(0);
  const [urgeviewkey, setUrgeviewkey] = useState(0);
  var [params,setParams] = useState<any>(params)
  const [contract, setContract] = useState<any>({})
  const [viewmodal,setViewmodal] = useState(false)

  const columns:any = [
    {
      title: 'ID',
      dataIndex: 'id',
      key: 'id',
      width: 60,
      hideInTable: true,
    },
    {
      title: '催收编号',
      dataIndex: 'serial',
      key: 'serial',
      width: 150,
      render: (text: any, record: any) => {
        return text ? (
          <span 
            style={{ color: '#1890FF', cursor: 'pointer' }}
            onClick={() => {
              setUrgelog(record);
              setFilekey(filekey + 1);
              setShowAddFile(true);
            }}
          >
            {text}
          </span>
        ) : null;
      }
    },
    {
      title: '合同名称',
      dataIndex: 'contracttitle',
      key: 'contracttitle',
      width: 200,
      ellipsis: true,
      render:(text:any,record:any)=>{
        var num = 0
        if (record.supplementary!=null && record.supplementary!=''){
          num = (record.supplementary.match(new RegExp('"name','g'))||[]).length
        }
       
        return <span onClick={()=>{
              setViewmodal(true)
              setContract({id:record.contractid})
           
            }}>{text=='-'?'':text}</span>
        
          
      }
    },
    {
      title: '清欠方式',
      dataIndex: 'urgetypename',
      key: 'urgetypename',
      width: 120,
    },
    {
      title: '催收日期',
      dataIndex: 'date',
      key: 'date',
      width: 120,
      sorter: true,
      render: (text: any) => {
        return text ? (text.split(' ')[0]) : null;
      }
    },
    {
      title: '清欠结果',
      dataIndex: 'urgeresultname',
      key: 'urgeresultname',
      width: 120,
    },
    {
      title: '处置日期',
      dataIndex: 'dealdate',
      key: 'dealdate',
      width: 120,
      sorter: true,
      render: (text: any) => {
        return text ? (text.split(' ')[0]) : null;
      }
    },
    {
      title: '创建人',
      dataIndex: 'creatorname',
      key: 'creatorname',
      width: 120,
    },
    {
      title: '操作',
      key: 'action',
      width: 150,
      fixed: 'right',
      render: (_: any, record: any) => (
        <>
          <Button 
            type="link" 
            onClick={() => {
              setUrgelog(record);
              setUrgeviewkey(urgeviewkey + 1);
              setShowUrgeView(true);
            }}
          >
            预览
          </Button>
          <Button 
            type="link"
            onClick={() => {
              setUrgelog(record);
              setFilekey(filekey + 1);
              setShowAddFile(true);
            }}
          >
            添加进度
          </Button>
        </>
      ),
    },
  ];

  // 搜索表单项
  const items = [
    <div style={{ width: 250 }}>
      <ContractSelect multiple={false} showupload={false} type={BalanceTypes.INCOME} onChange={(value: any) => {
     
          params.contractids = value.id
          setParams(params);
        }} />
    </div>,
    <Dictselect
      key="urgetype"
      type="清欠方式"
      multiple={false}
      needAddItem={false}
      style={{ width: '150px' }}
      placeholder="催收方式"
      onChange={(value: any) => {
        params.urgetype = value;
        setParams(params);
      }}
    />,
    <Dictselect
      key="urgeresult"
      type="清欠结果"
      multiple={true}
      needAddItem={false}
      style={{ width: '150px' }}
      placeholder="清欠结果"
      onChange={(value: any) => {
        params.urgeresult = value?value.map((e: any) => e.value).join(',') : null;
        setParams(params);
      }}
    />,
    <div style={{ width: 100 }} key="creator">
      <UserAutocomplete
        style={{ width: '100px' }}
        placeholder="创建人"
        multiple={false}
        onChange={(value: any) => {
          params.creator = value ? value.value : null;
          setParams(params);
        }}
      />
    </div>,
    <RangePicker
      key="daterange"
      style={{ width: '250px' }}
      onChange={(date: any, dateString: any) => {
        if (dateString[0]) {
          params.datestart = dateString[0];
        } else {
          delete params.datestart;
        }
        if (dateString[1]) {
          params.dateend = dateString[1];
        } else {
          delete params.dateend;
        }
      }}
    />,
  ];

  const searbarSize = 4;
  const displayItems = items.slice(0, searbarSize);

  return (
    <div style={{ height: '100%' }}>
      <ProTable
        id="urgelogslist"
        actionRef={actionRef}
        formRef={proTableFormRef}
        scroll={{ x: 'max-content', y: scrollTop ? 500 : undefined }}
        params={params}
        rowKey={(record:any)=>record.id}
        search={false}
        columns={columns}
      
        pagination={{
          defaultPageSize: 20,
          showQuickJumper: true,
          showSizeChanger: true,
        }}
        request={(params, sorter, filter) => {
          if (scrollTop) {
            document.body.scrollTop = document.documentElement.scrollTop = 0;
          }

          // 处理排序
          if (sorter) {
            Object.keys(sorter).forEach((key) => {
              var order = sorter[key] == 'ascend' ? 'asc' : 'desc';
              params.orderby = key + " " + order;
            });
          }

          // 默认查询清欠措施
          params.type = 1;

          return urgelogslist(params).then((res: any) => {
            return res;
          });
        }}
     
        toolbar={{
          filter: (
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', alignItems: 'flex-start' }}>
              {displayItems}
            </div>
          ),
          actions: [
            <Button
              key="export"
              onClick={() => {
                      
                            var par = {...params}
                            par.current=1
                            par.pageSize=10000
                            par.download=1
                            urgelogslist(par).then((res:any)=>{
                  
                              if (res.errorMessage) {
                                Modal.error({title: res.errorMessage})
                              } else {
                                var result = res.data.map((row:any,rowIndex:any)=>{
                                var arr:any = []
                                columns.forEach((h:any,index:number)=>{
                                  
                                  var temp:string = (row[h.dataIndex]||'').toString()
                                  if (temp) {
                                    temp = temp.replaceAll(',','，').trim()
                                  }
                                  switch (h.dataIndex) {
                                    case 'index':
                                      arr.push(rowIndex +1)
                                      break
                                    case 'action':
                                     
                                      break
                                    default:
                                      arr.push(temp)
                                      break;
                                  }
                                  
                                  
                                })
              
                                return arr
                              })
                                var x = columns.map((t:any)=>t.title).filter((t:any)=>t!='操作')
                                result.unshift(x)
                                downloadAsXlSX(result,'清欠措施导出')
                              }
                            })
                          }}
            >
              导出
            </Button>,
          ],
        }}
      />

      <TableScrollSync tableId="urgelogslist" onScroll={(scroll: any) => {
        const tableContent = document.querySelector('#urgelogslist .ant-table-content');
        if (tableContent) {
          tableContent.scrollLeft = scroll;
        }
      }} />

      <AddFile 
        key={filekey}
        visible={showAddFile} 
        data={urgelog} 
        onClose={() => setShowAddFile(false)}
        onChange={() => {
          setShowAddFile(false);
          actionRef.current?.reload();
        }}
      />
      

      <UrgeView 
        key={urgeviewkey}
        contractid={urgelog?.contractid}
        debturgeid={urgelog?.debturgeid}
        urgeserial={urgelog?.serial}
        visible={showUrgeView}
        onVisibleChange={(visible: boolean) => setShowUrgeView(visible)}
      />
      <Modal
        width={850}
        style={{ top: 0}}
        visible={viewmodal}
        onOk={() => setViewmodal(false)}
        onCancel={() => setViewmodal(false)}
        footer= {null}
      >
        
        <View id={contract.id} key={contract.id} paystate={contract.paystate} attachNumber = {contract.attachNumber}/>
      </Modal>
    </div>
  );
};

export default Urgelogslist;
