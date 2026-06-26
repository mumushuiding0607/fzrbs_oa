import { ActionType, PageContainer, ProCard, ProColumns, ProFormInstance, ProTable } from '@ant-design/pro-components';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import { Avatar, Badge, Button, Card,ConfigProvider,DatePicker,Menu,Modal ,Popover,Radio,Segmented,Space, Tabs, Tag} from 'antd';
import Debtsearch from './debtsearch';
import Debtlist from './debtlist';
import TableScrollSync from '../../common/TableScrollSync';
import Paycollectionlist from './Paycollectionlist';
import DownloadDocDropdown from '../../Flowtemplate/DownloadDocDropdown';
import Urgelogslist from './urgelogslist';



// style
const tag:CSSProperties = {
  margin: '0 5px 0 0',
  padding: '0px 4px',
  borderRadius: '15%',
}

// data



// dom
const Debtmanager:React.FC = () =>{

 
  
  const [rolemodal,setRolemodal] = useState(false)

  
 
  const [activeKey, setActiveKey] = useState('tab1');

  const handleTabChange = (key:any) => {
    setActiveKey(key);
  };


  return (
    <ConfigProvider >
      <PageContainer title="欠款管理" 
      extra={<div
            style={{
              display: 'flex',
              flexWrap: 'wrap',
              gap: '8px', // 可选：按钮间间距
              alignItems: 'center'
            }}
          >
            <DownloadDocDropdown />
          </div>}
        tabList={[
            {
              tab: '欠款管理',
              key: 'tab1',
            },
            {
              tab: '催收记录',
              key: 'tab5',
            },
            {
              tab: '清欠措施',
              key: 'tab6',
            },
            {
              tab: '逾期明细表',
              key: 'tab2',
            },
            {
              tab: '账销案全表',
              key: 'tab3',
            },
            {
              tab: '期间回款查询',
              key: 'tab4',
            }
          ]}
       onTabChange={handleTabChange}
       
       header={{breadcrumb: {},}} >
        
        {
          activeKey=='tab1'&&
          <>
          <Debtsearch id='debtsearch' />
          </>
        }
        {
          activeKey=='tab2'&&
          <Debtlist key="逾期明细表" table="逾期明细表" searbarSize={11}/>

        }
        {
          activeKey=='tab5'&&
          <Debtlist key="催收记录" table="催收记录" searbarSize={11}/>

        }
        {
          activeKey=='tab6'&&
          <Urgelogslist  />

        }
        {
          activeKey=='tab3'&&
          <Debtlist key="账销案全表" table="账销案全表"/>
        }
        {
          activeKey=='tab4'&&
          <Paycollectionlist />
        }
        
        
        
        
        
        
        

        
        <Modal
         
         
          visible={rolemodal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setRolemodal(false)}
          onCancel={() => setRolemodal(false)}
          footer= {null}
        >
          
          
        </Modal>


      </PageContainer>
    </ConfigProvider>
  )
  
}
export default Debtmanager