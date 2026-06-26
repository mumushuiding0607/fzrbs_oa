import { Button, Card, Form, Input, InputNumber, Modal, Row, Space, Select } from 'antd';
import React, { CSSProperties, useEffect, useState } from 'react';
import { useModel } from 'umi';

import Companyselect from '../company/companyselect';
import ContractSelect from '../contract/contract-select';
import Dictselect from '../budget/dict/dictselect';
import UserAutocomplete from '../budget/common/userAutocomplete';
import { saveAdvitem, updateAdvitem } from './service';
import Orgcascade from './orgcascade';
import Advsize from './advsize';
import Tradecascade from './tradecascade';

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

// 发行区域选项
const editions = [
  { id: 1, text: '本地' },
  { id: 2, text: '全省' },
  { id: 3, text: '全国' },
];

// 广告类型选项
const adtypes = [
  { id: 1, text: '商业广告' },
  { id: 2, text: '公益广告' },
  { id: 3, text: '分类广告' },
];

// 规格选项
const sizes = [
  { id: 1, text: '整版' },
  { id: 2, text: '半版' },
  { id: 3, text: '1/4版' },
  { id: 4, text: '通栏' },
  { id: 5, text: '半通栏' },
];

// 版位选项
const fields = [
  { id: 1, text: '封面' },
  { id: 2, text: '封底' },
  { id: 3, text: '封二' },
  { id: 4, text: '封三' },
  { id: 5, text: '内页' },
];

// 颜色选项
const colors = [
  { id: 1, text: '黑白' },
  { id: 2, text: '彩色' },
];

// 价格表选项
const prices = [
  { id: 1, text: '标准价' },
  { id: 2, text: '优惠价' },
  { id: 3, text: '特价' },
];

// 计价方式选项
const valueModes = [
  { id: 0, text: '固定单价' },
  { id: 1, text: '按面积' },
  { id: 2, text: '按次数' },
];

// 投放日选项
const days = [
  { id: 1, text: '周一' },
  { id: 2, text: '周二' },
  { id: 3, text: '周三' },
  { id: 4, text: '周四' },
  { id: 5, text: '周五' },
  { id: 6, text: '周六' },
  { id: 7, text: '周日' },
];

const AddAdvitem: React.FC<{ data?: any, onChange?: Function }> = ({ data, onChange }) => {
  const [form] = Form.useForm();

  // 下拉选择状态
  const [AI_AdType_ID, setAI_AdType_ID] = useState<any>();
  const [AI_Size_ID, setAI_Size_ID] = useState<any>();
  const [AI_Field_ID, setAI_Field_ID] = useState<any>();
  const [AI_Color_ID, setAI_Color_ID] = useState<any>();
  const [AI_PriceList_ID, setAI_PriceList_ID] = useState<any>();
  const [AI_Price, setAI_Price] = useState<string>();
  const [E_MID_ID, setE_MID_ID] = useState<any>();
  const [AI_PriceModeIC, setAI_PriceModeIC] = useState<any>();
  const [AI_Width, setAI_Width] = useState<string>();
  const [AI_Height, setAI_Height] = useState<string>();
  const [willshow, setWillshow] = useState(false);

  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;

  // 初始化表单值
  useEffect(() => {
    if (data) {
      const initialValues = {
        ...data,
        // 客户需要对象格式
        AI_Customer_ID: data.AI_Customer_ID ? {
          value: data.AI_Customer_ID,
          label: data.AI_Customer || '',
        } : undefined,
        // 广告主需要对象格式
        AI_Advertiser_ID: data.AI_Advertiser_ID ? {
          value: data.AI_Advertiser_ID,
          label: data.AI_Advertiser || '',
        } : undefined,
        // 业务员需要对象格式
        AI_Salesman_ID: data.AI_Salesman_ID ? {
          value: data.AI_Salesman_ID,
          label: data.AI_Salesman || '',
        } : undefined,
        // 刊物需要对象格式
        AI_Publication_ID: data.AI_Publication_ID ? {
          value: data.AI_Publication_ID,
          label: data.AI_Publication || '',
        } : undefined,
        // 发行区域需要对象格式 - 默认选中第一项（本地）
        AI_Edition_ID: data.AI_Edition_ID ? {
          value: data.AI_Edition_ID,
          label: data.AI_Edition || '',
        } : {
          value: 1,
          label: '本地'
        },
        // 颜色需要对象格式
        AI_Color_ID: data.AI_Color_ID ? {
          value: data.AI_Color_ID,
          label: data.AI_Color || '',
        } : undefined,
        // 计价方式需要对象格式
        AI_PriceModeIC: data.AI_PriceModeIC ? {
          value: data.AI_PriceModeIC,
          label: data.AI_PriceModeIC_text || '',
        } : undefined,
      };
      
      form.setFieldsValue(initialValues);
    } else {
      // 创建新广告时，默认选中发行区域第一项
      form.setFieldsValue({
        AI_Edition_ID: {
          value: 1,
          label: '本地'
        }
      });
    }
  }, [data]);

  // 广告类型选择 - 确保规格组件能正确响应变化
  const adtypeOnSel = (value: any) => {
    console.log('广告类型 value', value);
    setAI_AdType_ID(value);
    
    // 先清空表单中的规格相关字段
    form.setFieldsValue({ 
      AI_AdType: value?.label, 
      AI_AdType_ID: value?.value,
      // 清空规格选择，因为规格依赖于广告类型
      AI_Size_ID: undefined,
      AI_Width: undefined,
      AI_Height: undefined
    });
    
    // 清空状态变量
    setAI_Size_ID(undefined);
    setAI_Width(undefined);
    setAI_Height(undefined);
  };

  // 规格选择
  const handleSizeSel = (value: any) => {
    setAI_Size_ID(value);
    if (value) {
      form.setFieldsValue({ AI_Size: value.label, AI_Size_ID: value.value });
    }
  };

  // 版位选择
  const handleFieldSel = (value: any) => {
    setAI_Field_ID(value);
    if (value) {
      form.setFieldsValue({ AI_Field: value.label, AI_Field_ID: value.value });
    }
  };

  // 颜色选择
  const handleColorSel = (value: any) => {
    setAI_Color_ID(value);
    if (value) {
      form.setFieldsValue({ AI_Color: value.label, AI_Color_ID: value.value });
    }
  };

  // 价格表选择
  const handlePriceListSel = (value: any) => {
    setAI_PriceList_ID(value);
    if (value) {
      form.setFieldsValue({ AI_PriceList: value.label, AI_PriceList_ID: value.value });
    }
  };

  // 投放日选择
  const daySel = (value: any) => {
    setE_MID_ID(value);
    if (value) {
      form.setFieldsValue({ E_MID: value.label, E_MID_ID: value.value });
    }
  };

  // 计价方式选择
  const priceModeSel = (value: any) => {
    setAI_PriceModeIC(value);
    if (value) {
      form.setFieldsValue({ AI_PriceModeIC: value.value });
    }
  };

  // 宽高变化
  const handleWHChange = () => {
    form.setFieldsValue({ AI_Width, AI_Height });
  };

  // 单价变化
  const handlePriceChange = (value: number) => {
    setAI_Price(String(value));
    form.setFieldsValue({ AI_Price: String(value) });
  };

  // 计算应收款
  const getAmount = () => {
    const values = form.getFieldsValue();
    if (values.AI_PriceModeIC === 0) {
      // 固定单价
      form.setFieldsValue({ AI_AmountReceivable: AI_Price });
    } else if (values.AI_PriceModeIC === 2) {
      // 按面积计算
      const width = parseFloat(AI_Width || '0');
      const height = parseFloat(AI_Height || '0');
      const price = parseFloat(AI_Price || '0');
      const discount = parseFloat(values.AI_DiscountTotal || '100');
      const amount = width * height * price * discount / 100;
      form.setFieldsValue({ AI_AmountReceivable: amount.toFixed(2) });
    }
  };

  // 提交表单
  const handleSubmit = async (values: any) => {
    try {

      // 如果 AI_Customer_ID 是对象
      if (values.AI_Customer_ID && values.AI_Customer_ID.value) {
        values.AI_Customer = values.AI_Customer_ID.label;
        values.AI_Customer_ID = values.AI_Customer_ID.value;
      }
      // 如果AI_Publication_ID 是对象
      if (values.AI_Publication_ID && values.AI_Publication_ID.value) {
        values.AI_Publication = values.AI_Publication_ID.label;
        values.AI_Publication_ID = values.AI_Publication_ID.value;
      }
  

      let res: any;
      console.log('values:', values);
      console.log('data:',data);
      res = await saveAdvitem(values);

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

  // 重置表单 - 移除 SelectedAdType 重置
  const handleReset = () => {
    form.resetFields();
    setAI_AdType_ID(undefined);
    setAI_Size_ID(undefined);
    setAI_Field_ID(undefined);
    setAI_Color_ID(undefined);
    setAI_PriceList_ID(undefined);
    setAI_Price(undefined);
    setE_MID_ID(undefined);
    setAI_PriceModeIC(undefined);
    setAI_Width(undefined);
    setAI_Height(undefined);
  };

  return (
    <Form
      id="addAdvitem"
      {...formItemLayout}
      form={form}
      onFinish={handleSubmit}
      style={{ maxWidth: 1000, paddingRight: '20px' }}
      initialValues={data}
    >
      <Form.Item name="SYS_DOCUMENTID" style={{ display: 'none' }}>
        <Input disabled />
      </Form.Item>
      <Form.Item name="SYS_CURRENTUSERID" style={{ display: 'none' }}>
        <Input disabled />
      </Form.Item>
      <Form.Item name="SYS_CURRENTUSERNAME" style={{ display: 'none' }}>
        <Input disabled />
      </Form.Item>
      <Form.Item name="I_OperatorID" style={{ display: 'none' }}>
        <Input disabled />
      </Form.Item>
      <Form.Item name="SYS_AUTHORS" style={{ display: 'none' }}>
        <Input disabled />
      </Form.Item>
      <Form.Item name="AI_AdType" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_Publication" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_Edition" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_Field" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_Color" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_PayMode" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_Trade" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_Size" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      


     
        <div style={row}>
          <Form.Item
            style={formitem}
            label="客户"
            name="AI_Customer_ID"
          >
            <Companyselect multiple={false} placeholder="选择客户" />
          </Form.Item>
          
          <Form.Item
            style={formitem}
            label="部门"
            name="AO_Org_ID"
            rules={[{ required: true, message: '请选择部门' }]}
          >
            <Orgcascade placeholder="选择部门" />
          </Form.Item>
     
          <Form.Item
            style={formitem}
            label="刊物"
            name="AI_Publication_ID"
          >
            <Dictselect 
              type="刊物" 
              multiple={false} 
              needAddItem={false} 
              placeholder="选择刊物" 
              onChange={(value: any, item: any) => {
                form.setFieldsValue({ 
                  AI_Publication_ID: value,
                  AI_Publication: item?.label || item 
                });
              }}
            />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={formitem}
            label="发行区域"
            name="AI_Edition_ID"
          >
            <Dictselect 
              type="发行区域" 
              multiple={false} 
              needAddItem={false} 
              placeholder="选择发行区域" 
              initialValue={1}
              onChange={(value: any, item: any) => {
                form.setFieldsValue({ 
                  AI_Edition_ID: value,
                  AI_Edition: item?.label || item 
                });
              }}
            />
          </Form.Item>
          <Form.Item
            style={formitem}
            label="广告类型"
            name="AI_AdType_ID"
          >
            <Dictselect 
              type="广告类型" 
              multiple={false} 
              needAddItem={false} 
              placeholder="选择广告类型" 
              onChange={(value: any, item: any) => {
                form.setFieldsValue({ 
                  AI_AdType_ID: value,
                  AI_AdType: item?.label || item 
                });
              }}
            />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={formitem}
            label="规格"
            name="AI_Size_ID"
          >
            <Advsize 
              key={AI_AdType_ID}
              adTypeId={AI_AdType_ID||null}
              placeholder="请选择规格"
              onChange={(item:any)=>{
                form.setFieldsValue({ 
                  AI_Size_ID: item.value,
                  AI_Size: item?.label || item 
                });
              }}
            />
          </Form.Item>
          <Form.Item
            style={formitem}
            label="版位"
            name="AI_Field_ID"
          >
            <Dictselect 
              type="版位" 
              multiple={false} 
              needAddItem={false} 
              placeholder="选择版位" 
              onChange={(value: any, item: any) => {
                form.setFieldsValue({ 
                  AI_Field_ID: value,
                  AI_Field: item?.label || item 
                });
              }}
            />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={formitem}
            label="颜色"
            name="AI_Color_ID"
          >
            <Dictselect 
              type="颜色" 
              multiple={false} 
              needAddItem={false} 
              placeholder="选择颜色" 
              onChange={(value: any, item: any) => {
                form.setFieldsValue({ 
                  AI_Color_ID: value,
                  AI_Color: item?.label || item 
                });
              }}
            />
          </Form.Item>
          <Form.Item
            style={formitem}
            label="划版备注"
            name="AI_PubMemo"
          >
            <Input placeholder="请输入划版备注" />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={formitem}
            label="宽"
            name="AI_Width"
          >
            <InputNumber 
              style={{ width: '100%' }} 
              placeholder="请输入宽度"
              onChange={(value) => {
                setAI_Width(String(value));
                handleWHChange();
              }}
            />
          </Form.Item>
          <Form.Item
            style={formitem}
            label="高"
            name="AI_Height"
          >
            <InputNumber 
              style={{ width: '100%' }} 
              placeholder="请输入高度"
              onChange={(value) => {
                setAI_Height(String(value));
                handleWHChange();
              }}
            />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={formitem}
            label="折合版面数"
            name="AI_AdvPages"
          >
            <InputNumber style={{ width: '100%' }} placeholder="请输入折合版面数" />
          </Form.Item>
          <Form.Item
            style={formitem}
            label="综合折扣"
            name="AI_DiscountTotal"
          >
            <InputNumber 
              style={{ width: '100%' }} 
              placeholder="请输入折扣" 
              addonAfter="%"
              min={0}
              max={100}
              precision={2}
            />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={formitem}
            label="行业"
            name="AI_TradeID"
          >
            <Tradecascade  placeholder="选择行业" onChange={(item:any)=>{
              form.setFieldsValue({ 
                  AI_TradeID: item.value,
                  AI_Trade: item?.label || item 
                });
            }}/>
          </Form.Item>
          <Form.Item
            style={formitem}
            label="支付方式"
            name="AI_PayMode_ID"
          >
            <Dictselect 
              type="支付方式" 
              multiple={false} 
              needAddItem={false} 
              placeholder="选择支付方式" 
              onChange={(value: any, item: any) => {
                form.setFieldsValue({ 
                  AI_PayMode_ID: value,
                  AI_PayMode: item?.label || item 
                });
              }}
            />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={formitem}
            label="价格表"
            name="AI_PriceList_ID"
          >
            <Dictselect 
              type="价格表" 
              multiple={false} 
              needAddItem={false} 
              placeholder="选择价格表" 
              onChange={(value: any, item: any) => {
                form.setFieldsValue({ 
                  AI_PriceList_ID: value,
                  AI_PriceList: item?.label || item 
                });
              }}
            />
          </Form.Item>
          <Form.Item
            style={formitem}
            label="投放日"
            name="E_MID_ID"
          >
            <Dictselect 
              type="投放日" 
              multiple={false} 
              needAddItem={false} 
              placeholder="选择投放日" 
              onChange={(value: any, item: any) => {
                form.setFieldsValue({ 
                  E_MID_ID: value,
                  E_MID: item?.label || item 
                });
              }}
            />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={formitem}
            label="单价"
            name="AI_Price"
          >
            <InputNumber 
              style={{ width: '100%' }} 
              placeholder="请输入单价"
              onChange={handlePriceChange}
              onBlur={getAmount}
            />
          </Form.Item>
          <Form.Item
            style={formitem}
            label="计价方式"
            name="AI_PriceModeIC"
          >
            <Dictselect 
              type="计价方式" 
              multiple={false} 
              needAddItem={false} 
              placeholder="选择计价方式" 
              onChange={(value: any, item: any) => {
                form.setFieldsValue({ 
                  AI_PriceModeIC: value,
                });
              }}
            />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={formitem}
            label="应收款"
            name="AI_AmountReceivable"
          >
            <InputNumber style={{ width: '100%' }} placeholder="请输入应收款" />
          </Form.Item>
          <Form.Item
            style={formitem}
            label="附加费"
            name="AI_AdditionalFee"
          >
            <InputNumber style={{ width: '100%' }} placeholder="请输入附加费" />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={{ width: '100%' }}
            label="广告内容"
            name="AI_Content"
          >
            <Input.TextArea rows={3} placeholder="请输入广告内容" />
          </Form.Item>
        </div>

        <div style={row}>
          <Form.Item
            style={{ width: '100%' }}
            label="备注"
            name="AI_Memo"
          >
            <Input.TextArea rows={2} placeholder="请输入备注" />
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

export default AddAdvitem;