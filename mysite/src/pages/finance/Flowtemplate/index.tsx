import { MenuOutlined } from '@ant-design/icons';
import { PageContainer } from '@ant-design/pro-components';
import { Affix, Button, Drawer, Layout, Modal } from 'antd';
import React, { useEffect, useRef, useState } from 'react';
import List from './components/list';
import MyTree from './components/tree';
import styles from '../../information/index.less';
import FinanceTemplatelist from './finance';
import Rolelist from '../role/rolelist';
import ViewFlow from './viewflow';
import Payerlist from './payerlist';
import YxkhTemplateList from './yxkhTemplateList';
import Printlist from './printlist';
import Attendancelist from './attendancelist';
import UsesealTemplateList from './usesealtemplatelist';
import Orderlist from './orderlist';
import PreViewFlow from '../budget/flow/previewflow';
import { useHistory } from 'umi';

const Flowtemplate: React.FC = () => {
  const listRef = useRef<any>();
  const treeRef = useRef<any>();
  const [visible, setVisible] = useState(false);
  const [financemodal,setFinancemodal]=useState(false);
  const [rolemodal,setRolemodal] = useState(false)
  const [viewmodal,setViewmodal] = useState(false)
  const [viewFlowThirdNo,setViewFlowThirdNo]=useState<string>('')
  const [payerModal,setPayerModal]=useState(false)
  const [yxkhmodal,setYxkhmodal]=useState(false)
  const [printmodal,setPrintmodal]=useState(false)
  const [attendancemodal,setAttendancemodal]=useState(false)
  const [usesealmodal,setUsesealmodal]=useState(false)
  const [ordermodal,setOrdermodal]=useState(false)
  const [preViewmodal,setPreViewmodal] = useState(false)
  const history = useHistory<any>() as any;
  const thirdNoFromUrl = history.location?.query?.thirdNo || '';

  useEffect(() => {
    if (thirdNoFromUrl) {
      setViewFlowThirdNo(thirdNoFromUrl);
      setViewmodal(true);
    }
  }, [thirdNoFromUrl]);

  const onSelect = (id: number) => {
    listRef?.current.reload(id);
  };

  const onCreate = (parentId: string, data: any) => {
    treeRef?.current.addNode(parentId, data);
  };

  const onUpdate = (key: string, title: string) => {
    treeRef?.current.updateNode(key, title);
  };

  const onDelete = (keys: string[]) => {
    treeRef?.current.deleteNode(keys);
  };

  const onClose = () => {
    setVisible(false);
  };

  return (
    <PageContainer
      header={{
        breadcrumb: {},
      }}
      extra={<div
      style={{
        display: 'flex',
        flexWrap: 'wrap',
        gap: '8px', // 可选：按钮间间距
        alignItems: 'center'
      }}
    >
      <Button onClick={() => setFinancemodal(true)}>付款审批流程</Button>
      <Button onClick={() => setPayerModal(true)}>付款单位设置</Button>
      <Button onClick={() => setPrintmodal(true)}>打印位置设置</Button>
      <Button onClick={() => setRolemodal(true)}>角色设置</Button>
      <Button onClick={() => history.push('/finance/budget/dict/dictlist')}>字典管理</Button>
      <Button onClick={() => setYxkhmodal(true)}>一线考核流程</Button>
       <Button onClick={() => setAttendancemodal(true)}>考勤异常流程</Button>
       <Button onClick={() => setUsesealmodal(true)}>用印审批流程</Button>
       <Button onClick={() => setOrdermodal(true)}>订单审批流程</Button>
       <Button onClick={() => setPreViewmodal(true)}>预览流程</Button>
      <Button onClick={() => setViewmodal(true)}>审批查询</Button>
    </div>}
    >
      <Affix offsetTop={0} style={{ position: 'fixed', bottom: 0, left: 0, zIndex: 100 }}>
        <Button
          type="primary"
          icon={<MenuOutlined />}
          className={styles.menu}
          onClick={() => setVisible(true)}
        />
      </Affix>
      <Drawer title="" placement="left" onClose={onClose} visible={visible} width="100vw">
        <MyTree showLeafIcon={true} selectable={true} onSelect={onSelect} ref={treeRef} />
      </Drawer>

      <Layout>
        <Affix offsetTop={50}>
          <Layout.Sider
            width={250}
            className={styles.sidebar}
            breakpoint={'lg'}
            theme="light"
            collapsedWidth={0}
            trigger={null}
          >
            <MyTree showLeafIcon={true} selectable={true} onSelect={onSelect} ref={treeRef} />
          </Layout.Sider>
        </Affix>
        <Layout>
          <Layout.Content>
            <List onCreate={onCreate} onUpdate={onUpdate} onDelete={onDelete} ref={listRef} />
          </Layout.Content>
        </Layout>
      </Layout>
      <Modal
      
        
          visible={financemodal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setFinancemodal(false)}
          onCancel={() => setFinancemodal(false)}
          footer= {null}
        >
        <FinanceTemplatelist ></FinanceTemplatelist>
      </Modal>
      <Modal
      
        
          visible={yxkhmodal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setYxkhmodal(false)}
          onCancel={() => setYxkhmodal(false)}
          footer= {null}
        >
        <YxkhTemplateList ></YxkhTemplateList>
      </Modal>
      <Modal
      
        
          visible={attendancemodal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setAttendancemodal(false)}
          onCancel={() => setAttendancemodal(false)}
          footer= {null}
        >
        <Attendancelist ></Attendancelist>
      </Modal>
      <Modal
      
        
          visible={usesealmodal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setUsesealmodal(false)}
          onCancel={() => setUsesealmodal(false)}
          footer= {null}
        >
        <UsesealTemplateList />
      </Modal>
      <Modal
      
        
          visible={ordermodal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setOrdermodal(false)}
          onCancel={() => setOrdermodal(false)}
          footer= {null}
        >
        <Orderlist />
      </Modal>
      <Modal
      
        
          visible={payerModal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setPayerModal(false)}
          onCancel={() => setPayerModal(false)}
          footer= {null}
        >
        <Payerlist ></Payerlist>
      </Modal>
      <Modal
          visible={printmodal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setPrintmodal(false)}
          onCancel={() => setPrintmodal(false)}
          footer= {null}
        >
        <Printlist ></Printlist>
      </Modal>
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
      <ViewFlow thirdNo={viewFlowThirdNo} onVisibleChange={(v:boolean)=>{setViewmodal(v);if(!v)setViewFlowThirdNo('')}} visible={viewmodal}/>
      <PreViewFlow onVisibleChange={setPreViewmodal} visible={preViewmodal}/>
    </PageContainer>
    
  );
};

export default Flowtemplate;
