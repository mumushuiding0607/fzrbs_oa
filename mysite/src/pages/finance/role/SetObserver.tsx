import React, { useEffect, useState } from 'react';
import { Button, Form, Input, Modal, Select, Space, message } from 'antd';
import UserAutocomplete from '../budget/common/userAutocomplete';
import DepartmentTreeSelect from '../budget/common/department_treeselect';
import PayerSelect from './payerSelect';
import Dictselect from '../budget/dict/dictselect';
import Orgcascade from '../order/orgcascade';
import Agentselect from './agentselect';
import { saveflowrole, getrolelist } from './service';

interface SetObserverProps {
  visible: boolean;
  onCancel: () => void;
  onSuccess: () => void;
  agentid?: any;
}

const SetObserver: React.FC<SetObserverProps> = ({
  visible,
  onCancel,
  onSuccess,
}) => {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [dictKey, setDictKey] = useState(0);
  const [orgKey, setOrgKey] = useState(0);
  const [payerKey, setPayerKey] = useState(0);

  const handleUserChange = async (value: any) => {
    if (value?.value || value) {
      const userId = value?.value || value;
      console.log('handleUserChange called with:', value, userId);
      try {
        const res: any = await getrolelist({ userid: userId, role: 53 });
        console.log('getrolelist response:', res);
        if (res && res.data && res.data.length > 0) {
          const existingObserver = res.data.find(
            (r: any) => r.userid == userId
          );
          console.log('existingObserver:', existingObserver);
          if (existingObserver) {
            setDictKey(prev => prev + 1);
            setOrgKey(prev => prev + 1);
            setPayerKey(prev => prev + 1);
            form.setFieldsValue({
              id: existingObserver.id,
              dept: existingObserver.dept,
              company: existingObserver.company,
              publicationid: existingObserver.publicationid,
              orgid: existingObserver.orgid,
              agent: existingObserver.agent,
            });
          } else {
            console.log('No matching observer found, clearing fields');
            setDictKey(prev => prev + 1);
            setOrgKey(prev => prev + 1);
            setPayerKey(prev => prev + 1);
            form.setFieldsValue({
              id: undefined,
              dept: undefined,
              company: undefined,
              publicationid: undefined,
              orgid: undefined,
              agent: undefined,
            });
          }
        } else {
          console.log('No data returned, clearing fields');
          setDictKey(prev => prev + 1);
          setOrgKey(prev => prev + 1);
          setPayerKey(prev => prev + 1);
          form.setFieldsValue({
            id: undefined,
            dept: undefined,
            company: undefined,
            publicationid: undefined,
            orgid: undefined,
            agent: undefined,
          });
        }
      } catch (error) {
        console.error('Failed to check existing observer:', error);
      }
    }
  };

  const handleFinish = async (values: any) => {
    setLoading(true);
    try {
      let userId = values.userid?.value || values.userid;
      let username = values.userid?.label || values.username;

      const submitData: any = {
        id: values.id,
        role: 53,
        type: 0,
        agent: Array.isArray(values.agent) ? values.agent.join(',') : values.agent,
        userid: userId,
        username: username,
      };

      if (values.dept) {
        submitData.dept = Array.isArray(values.dept) ? values.dept.join(',') : values.dept;
      }
      if (values.company) {
        submitData.company = Array.isArray(values.company)
          ? values.company.map((c: any) => c.value || c).join(',')
          : values.company;
      }
      console.log('values.publicationid:',values.publicationid)
      if (values.publicationid&&Array.isArray(values.publicationid)) {
        const pubArr = values.publicationid.map((p: any) => typeof p === 'object' ? p.value : p);
        submitData.publicationid = pubArr.join(',');
      }
      if (values.orgid) {
        submitData.orgid = Array.isArray(values.orgid) ? values.orgid.join(',') : values.orgid;
      }

      const res: any = await saveflowrole(submitData);
      if (res.errorMessage) {
        Modal.error({ title: res.errorMessage });
      } else {
        message.success('观察员设置成功');
        form.resetFields();
        onSuccess();
        onCancel();
      }
    } catch (error) {
      console.log('err:',error)
      Modal.error({ title: '设置失败' });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!visible) {
      form.resetFields();
    }
  }, [visible]);

  return (
    <Modal
      title="设置观察员"
      visible={visible}
      onCancel={onCancel}
      footer={null}
      width={600}
      destroyOnClose
    >
      <Form form={form} layout="vertical" onFinish={handleFinish}>
        <Form.Item label="id" name="id" style={{ display: 'none' }}>
          <Input disabled />
        </Form.Item>
        <Form.Item label="类别：" name="type" rules={[{ required: false, message: 'Please input!' }]}>
          <Select
            defaultValue={0}
            defaultActiveFirstOption={true}
      
            options={[
              {
                value: 0,
                label: '审批',
              },
              {
                value: 1,
                label: '抄送',
              }
            ]}
          />
        </Form.Item>
  
        <Form.Item label="用户：" name="username" style={{display:'none'}}>
          <Input />
        </Form.Item>
        <Form.Item label="用户" name="userid" rules={[{ required: true, message: '请选择用户' }]}>
          <UserAutocomplete multiple={false} onChange={handleUserChange} />
        </Form.Item>
        <Form.Item label="部门" name="dept" rules={[{ required: true, message: '请选择部门' }]}>
          <DepartmentTreeSelect maxTagCount={2} showTreeCheckStrictly={true} />
        </Form.Item>

        <Form.Item label="应用" name="agent" rules={[{ required: true, message: '请选择应用' }]}>
          <Agentselect multiple={true} />
        </Form.Item>
<Form.Item label="主体：" name="company" rules={[{ required: false, message: 'Please input!' }]}>
          <PayerSelect key={payerKey} multiple={true}/>
        </Form.Item>
        <Form.Item
          label="刊物"
          name="publicationid"
          rules={[{ required: false, message: '请选择发布平台' }]}
        >
          <Dictselect
            key={dictKey}
            type="刊物"
            multiple={true}
            needAddItem={true}
            placeholder="选择发布平台"
          />
        </Form.Item>
        <Form.Item
   
          label="行业部门"
          name="orgid"
          rules={[{ required: false, message: '请选择行业部门' }]}
        >
          <Orgcascade
            key={orgKey}
            multiple={true}

          />
        </Form.Item>
        <Form.Item>
          <Space>
            <Button type="primary" htmlType="submit" loading={loading}>
              保存
            </Button>
            <Button htmlType="button" onClick={onCancel}>
              取消
            </Button>
          </Space>
        </Form.Item>
      </Form>
    </Modal>
  );
};

export default SetObserver;