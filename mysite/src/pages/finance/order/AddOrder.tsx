import { Button, Form, Input, Modal, Space } from 'antd';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import { useModel } from 'umi';

import Companyselect from '../company/companyselect';
import ContractSelect from '../contract/contract-select';
import Dictselect from '../budget/dict/dictselect';
import UserAutocomplete from '../budget/common/userAutocomplete';
import MyUploadFile from '@/components/MyUploadFile';
import { saveOrder, updateOrder } from './service';
import Orgcascade from './orgcascade';
import { getFromUrl, setToUrl } from '../utils';

// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};

const row: CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%',
  padding: 0,
  gap: '2em',
};

const formitem: CSSProperties = {
  width: '50%',
};

const formItemLayout = {
  labelCol: {
    xs: { span: 6 },
    sm: { span: 6 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 24 },
  },
};

const AddOrder: React.FC<{ data?: any, onChange?: Function }> = ({ data, onChange }) => {
  const [form] = Form.useForm();
  const [uprefresh, setUprefresh] = useState(0);
  const [defaultImage, setDefaultImage] = useState<any>(data.fileurls?data.fileurls.split(',').map((url:any)=>{
  
      return getFromUrl(url)
    }):[])
  const uploadRef = useRef<any>(null);

  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;

  // 初始化表单值
  useEffect(() => {

    if (data && data.SYS_DOCUMENTID) {
      // 编辑时需要转换组件需要的格式
      const initialValues = {
        ...data,
        // 合同需要对象格式
        contractid: data.contractid ? {
          value: data.contractid,
          label: data.contractserial || '',
        } : undefined,
        // 主体需要对象格式
        partb: data.partb ? {
          value: data.partb,
          label: data.partbname || '',
        } : undefined,
        // 客户需要对象格式
        AO_Customer_ID: data.AO_Customer_ID ? {
          value: data.AO_Customer_ID,
          label: data.AO_Customer || '',
        } : undefined,
        // 部门需要对象格式
        AO_Org_ID: data.AO_Org_ID ? {
          value: data.AO_Org_ID,
          label: data.AO_Org || '',
        } : undefined,
        // 业务员需要对象格式
        AO_Salesman_ID: data.AO_Salesman_ID ? {
          value: data.AO_Salesman_ID,
          label: data.AO_Salesman || '',
        } : undefined,
        // 协助人员需要对象格式
        assistant: data.assistant ? {
          value: data.assistant,
          label: data.assistantname || '',
        } : undefined,

      };
       
      form.setFieldsValue(initialValues);
    } 
  }, [data]);

  // 合同变更回调
  const onContractChange = (item: any) => {
   
    if (item) {
      form.setFieldsValue({
        AO_Customer_ID: item.parta,
        AO_Customer: item.partaname,
        partb: item.partb,
        partbname: item.partbname,
        contractserial:item.serial
      });
    }
  };

  // 提交表单
  const handleSubmit = async (values: any) => {
    try {
      const uploads = uploadRef?.current?.getFileList();
      if (uploads) {
        values.fileurls = uploads.map((u: any) => setToUrl(u)).join(',');
        console.log('uploads:', uploads.map((u: any) => setToUrl(u)).join(','))
      }
      // 处理合同字段
      if (values.contractid && typeof values.contractid === 'object') {
        values.contractserial = values.contractid.serial;
        values.contractid = values.contractid.value;
      }
      // 处理客户字段
      if (values.AO_Customer_ID && typeof values.AO_Customer_ID === 'object') {
        values.AO_Customer = values.AO_Customer_ID.label || values.AO_Customer_ID.company;
        values.AO_Customer_ID = values.AO_Customer_ID.value || values.AO_Customer_ID.id;
         
      }
      
      if (values.AO_Org_ID && typeof values.AO_Org_ID === 'object') {
        values.AO_Org = values.AO_Org_ID.label;
        values.AO_Org_ID = values.AO_Org_ID.value;
      }
 
      // 处理主体字段
      if (values.partb && typeof values.partb === 'object') {
        values.partbname = values.partb.label || values.partb.company;
        values.partb = values.partb.value || values.partb.id;
      }

      // 处理业务员字段
      if (values.AO_Salesman_ID && typeof values.AO_Salesman_ID === 'object') {
        values.departmentid=values.AO_Salesman_ID.departmentid;
        values.departmentname=values.AO_Salesman_ID.departmentname;
        values.AO_Salesman = values.AO_Salesman_ID.label;
        values.AO_Salesman_ID = values.AO_Salesman_ID.value;
      }
      // 处理协助人员字段
      if (values.assistant && typeof values.assistant === 'object') {
        values.assistantdepartmentid=values.assistant.departmentid;
        values.assistantdepartmentname=values.assistant.departmentname;
        values.assistantname = values.assistant.label;
        values.assistant = values.assistant.value;
      }
  
      if (values.SYS_DELETEFLAG==null) values.SYS_DELETEFLAG=1

      let res: any;
      if (values.SYS_DOCUMENTID) {
        res = await updateOrder(values);
      } else {
    
        values.SYS_CURRENTUSERID = currentUser.id;
        values.SYS_CURRENTUSERNAME = currentUser.realname;
        values.AO_OperatorID = currentUser.id;
        values.SYS_AUTHORS = currentUser.realname;

        res = await saveOrder(values);
      }

      if (res.errorMessage) {
        Modal.error({ title: res.errorMessage });
      } else {
        Modal.success({ title: values.SYS_DOCUMENTID ? '更新成功' : '创建成功' });
        onChange && onChange(res.data);
      }
    } catch (error) {
      console.error(error);
      Modal.error({ title: '提交失败' });
    }
  };

  // 重置表单
  const handleReset = () => {
    form.resetFields();
  };

  return (
     
      <Form
        id="addOrder"
        {...formItemLayout}
        form={form}
        onFinish={handleSubmit}
        style={{ maxWidth: 800,paddingRight:'20px' }}
      >
        <Form.Item name="SYS_DOCUMENTID" style={{ display: 'none' }}>
          <Input disabled />
        </Form.Item>
        <Form.Item name="SYS_DELETEFLAG" style={{ display: 'none' }}>
          <Input disabled  />
        </Form.Item>
        <Form.Item name="SYS_CURRENTUSERID" style={{ display: 'none' }}>
          <Input disabled />
        </Form.Item>
        <Form.Item name="SYS_CURRENTUSERNAME" style={{ display: 'none' }}>
          <Input disabled />
        </Form.Item>
        <Form.Item name="AO_OperatorID" style={{ display: 'none' }}>
          <Input disabled />
        </Form.Item>
        <Form.Item name="SYS_AUTHORS" style={{ display: 'none' }}>
          <Input disabled />
        </Form.Item>


        <div style={row}>
          <Form.Item
            style={formitem}
            label="合同"
            name="contractid"
            rules={[{ required: false, message: '请选择合同' }]}
          >
            <ContractSelect
              multiple={false}
              showupload={false}
              onChange={onContractChange}
            />
          </Form.Item>
          <Form.Item
            style={formitem}
            label="主体"
            name="partb"
            rules={[{ required: true, message: '请选择主体' }]}
          >
            <Companyselect multiple={false} placeholder="选择主体" />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={formitem}
            label="客户"
            name="AO_Customer_ID"
            rules={[{ required: true, message: '请选择客户' }]}
          >
            <Companyselect multiple={false} placeholder="选择客户" />
          </Form.Item>
          
            {
            data.AI_OrderID==null && 
            <Form.Item
              style={formitem}
              label="部门"
              name="AO_Org_ID"
              rules={[{ required: true, message: '请选择部门' }]}
            >
              <Orgcascade
              />
            </Form.Item>
            }
        </div>

        <div style={row}>
          <Form.Item
            style={formitem}
            label="业务员"
            name="AO_Salesman_ID"
            rules={[{ required: false, message: '请选择业务员' }]}
          >
            <UserAutocomplete multiple={false} placeholder="选择业务员" />
          </Form.Item>
         

        </div>

      

        <Form.Item {...tailLayout}>
          <Space>
            <Button type="primary" htmlType="submit">
              {data?.SYS_DOCUMENTID ? '更新' : '创建'}
            </Button>
            <Button htmlType="button" onClick={handleReset}>
              清空
            </Button>
          </Space>
        </Form.Item>
      </Form>
   
  );
};

export default AddOrder;
