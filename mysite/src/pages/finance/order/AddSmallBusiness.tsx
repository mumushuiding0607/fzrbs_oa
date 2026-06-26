import { Button, DatePicker, Form, Input, InputNumber, Modal, Row, Space, Radio, Checkbox, message } from 'antd';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"
import dayjs from 'dayjs';

dayjs.extend(weekday)
dayjs.extend(localeData)
import { useModel } from 'umi';

import Companyselect from '../company/companyselect';
import Dictselect from '../budget/dict/dictselect';
import UserAutocomplete from '../budget/common/userAutocomplete';
import MyUploadFile from '@/components/MyUploadFile';

import { saveAdvitem } from './service';
import { setToUrl } from '../utils';
import Tradecascade from './tradecascade';
import CustomerInfo from './CustomerInfo';
import Orgcascade from './orgcascade';
import { OrderTypeEnum } from './config';
import CustomRangePicker from './CustomRangePicker';
import CustomDatePicker from './CustomDatePicker';
import { CONTRACT_AGENTID } from '../contract/config';
import Advsize from './advsize';

// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};

const row: CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%',
  padding: 0,
  gap: '1em',
};

const formitem: CSSProperties = {
  width: '33.33%',
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

const { TextArea } = Input;

const AddSmallBusiness: React.FC<{ data?: any, onChange?: Function }> = ({ data, onChange }) => {
  const [form] = Form.useForm();
  const [uprefresh, setUprefresh] = useState(0);
  const [defaultImage, setDefaultImage] = useState<any>([]);
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const uploadRef = useRef<any>(null);
  const [publicationId, setPublicationId] = useState<any>(data?.AI_Publication_ID || null);

  // 初始化表单值
  useEffect(() => {
    form.setFieldsValue({
      AI_AdvPages: 0,
      AI_AmountPaid: 0,
      AI_Debt: 0,
      AI_PublishDayCount: 1,
    });
  }, []);

  // 提交表单
  const handleSubmit = async (values: any) => {
    try {
      values.AI_Type = OrderTypeEnum.SmallBuiness
      const uploads = uploadRef?.current?.getFileList();
      if (uploads) {
        values.fileurls = uploads.map((u: any) => setToUrl(u)).join(',');
      }
      if (values.AI_Customer_ID && values.AI_Customer_ID.value) {
        values.AI_Customer = values.AI_Customer_ID.label;
        values.AI_Customer_ID = values.AI_Customer_ID.value;
      }
      if (values.partb && typeof values.partb === 'object') {
        values.partbname = values.partb.label || values.partb.company;
        values.partb = values.partb.value || values.partb.id;
      }
      // 处理合作时间（现在组件直接返回"yyyy-MM-dd至yyyy-MM-dd"格式）
      // values.cooperationtime 已经是字符串格式，无需额外处理
      if (!values.AI_Customer){
        Modal.error({ title: 'AI_Customer is null' });
        return
      }
      const res: any = await saveAdvitem(values);

      if (res.errorMessage) {
        Modal.error({ title: res.errorMessage });
      } else {
        Modal.success({ title: '提交成功' });
        onChange && onChange(res.data);
      }
    } catch (error) {
      console.error(error);
      Modal.error({ title: '提交失败' });
    }
  };

  const handleReset = () => {
    form.resetFields();
  };
  // 处理发布日期变化
    const handlePublishTimeChange = (value: any, dayjsValue: any) => {
      if (!dayjsValue) return;
      
      const publishEndTime = form.getFieldValue('AI_PublishEndTime');
      const publishTime = dayjsValue;
      
      // 如果结束时间为空或结束时间小于开始时间，则将结束时间设置为开始时间
      if (!publishEndTime || (publishEndTime && publishEndTime < value)) {
        form.setFieldsValue({
          AI_PublishEndTime: value,
          AI_PublishDayCount: 1
        });
      } else {
        // 计算天数差（需要将结束时间转换为dayjs对象）
        const endTimeDayjs = dayjs(publishEndTime);
        const daysDiff = endTimeDayjs.diff(publishTime, 'day') + 1;
        form.setFieldsValue({
          AI_PublishDayCount: daysDiff > 1 ? daysDiff : 1
        });
      }
    };
  // 规格变化
  const handleSizeChange = (item: any) => {
    if (item?.value) {
      form.setFieldsValue({
        AI_Size_ID: item.value,
        AI_Size: item?.label || item,
        AI_Width: item?.width,
        AI_Height: item?.height,
        AI_LayoutAmount: item?.layoutAmount
      });
    }
  };
  // 版位变化
  const handleFieldChange = (value: any, item: any) => {
    if (value) {
      form.setFieldsValue({
        AI_Field_ID: value,
        AI_Field: item?.label || item
      });
    }
  };

  // 颜色变化
  const handleColorChange = (value: any, item: any) => {
    if (value) {
      form.setFieldsValue({
        AI_Color_ID: value,
        AI_Color: item?.label || item
      });
    }
  };

  // 处理结束日期变化
  const handlePublishEndTimeChange = (value: any, dayjsValue: any) => {
    if (!dayjsValue) return;
    
    const publishTime = form.getFieldValue('AI_PublishTime');
    
    if (publishTime) {
      // 计算天数差（需要将开始时间转换为dayjs对象）
      const startTimeDayjs = dayjs(publishTime);
      const daysDiff = dayjsValue.diff(startTimeDayjs, 'day') + 1;
      form.setFieldsValue({
        AI_PublishDayCount: daysDiff > 1 ? daysDiff : 1
      });
    } else {
      // 如果开始时间为空，默认天数为1
      form.setFieldsValue({
        AI_PublishDayCount: 1
      });
    }
  };

  return (
    <Form
      id="addSmallBusiness"
      {...formItemLayout}
      form={form}
      onFinish={handleSubmit}
      style={{ maxWidth: 900, paddingRight: '10px' }}
      initialValues={data}
    >
      {/* 隐藏字段 */}
      <Form.Item name="AI_Type" style={{ display: 'none' }}>
        <Input value={2}/>
      </Form.Item>
      <Form.Item name="SYS_DOCUMENTID" style={{ display: 'none' }}>
        <Input disabled />
      </Form.Item>
      <Form.Item name="SYS_CURRENTUSERID" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="SYS_CURRENTUSERNAME" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="SYS_AUTHORS" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_OrderID" style={{ display: 'none' }}>
        <Input />
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
      <Form.Item name="AI_PublishEndTime" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_PriceList" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="E_MID" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_Publication" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_Salesman" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      

      {/* 标题 */}
      <div style={{ textAlign: 'center', marginBottom: 10, fontSize: 18, fontWeight: 'bold' }}>
        福州日报社小额业务确认单
      </div>
      <div style={{ textAlign: 'center', marginBottom: 20, color: '#666', fontSize: 12 }}>
        (此确认单仅适用于金额壹万元以下的小额业务)
      </div>

      {/* 签订日期 */}
      <div style={row}>
        <Form.Item style={formitem}
          label="签订日期"
          name="SYS_CREATED">
          <CustomDatePicker format="YYYY-MM-DD" style={{ width: '100%' }} />
        </Form.Item>
 
        <Form.Item
            style={formitem}
            label="主体"
            name="partb"
            rules={[{ required: true, message: '请选择主体' }]}
          >
            <Companyselect multiple={false} placeholder="选择主体" />
          </Form.Item>
        <Form.Item
          style={formitem}
          label="客户"
          name="AI_Customer_ID"
          rules={[{ required: true, message: '请选择' }]}
        >
          <Companyselect multiple={false} placeholder="选择客户" />
        </Form.Item>
      </div>

      {/* 发布平台 */}
      <div style={row}>
        <Form.Item
          style={formitem}
          label="发布平台"
          name="AI_Publication_ID"
          rules={[{ required: true, message: '请选择' }]}
        >
          <Dictselect
            type="刊物"
            multiple={false}
            needAddItem={true}
            agentid={CONTRACT_AGENTID}
            placeholder="选择发布平台"
            onChange={(value: any, item: any) => {
              console.log('Dictselect onChange:', value, item);
              // 更新发布平台ID状态
              setPublicationId(value);
              // 切换发布平台时清空版位选择
              form.setFieldsValue({
                AI_Publication_ID: value,
                AI_Field_ID: undefined,
                AI_Field: undefined
              });
              // 这里只需要设置显示用的AI_Publication字段
              if (item) {
                form.setFieldsValue({
                  AI_Publication: item.label || item
                });
              }
            }}
          />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="支付方式"
          name="AI_PayMode_ID"
        >
          <Dictselect
            type="支付方式"
            multiple={false}
            needAddItem={true}
            agentid={CONTRACT_AGENTID}
            placeholder="选择支付方式"
          />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="金额"
          name="AI_AmountReceivable"
          rules={[{ required: true, message: '请输入金额' }]}
        >
          <InputNumber
            style={{width:'100%'}}
            placeholder="请输入金额"
            min={0}
            precision={2}
          />
        </Form.Item>

        

        
      </div>

      <div style={row}>
        
        <Form.Item
          style={formitem}
          label="合作日期"
          name="AI_PublishTime"
          rules={[{ required: true, message: '请选择开始日期' }]}
        >
          <CustomDatePicker 
            format="YYYY-MM-DD" 
            style={{ width: '100%' }} 
            placeholder="请选择开始日期"
            onChange={handlePublishTimeChange}
          />
        </Form.Item>
        <Form.Item
          style={formitem}
          label=""
          name="AI_PublishEndTime"
          rules={[{ required: true, message: '请选择结束日期' }]}
        >
          <CustomDatePicker 
            format="YYYY-MM-DD" 
            style={{ width: '100%' }} 
            placeholder="请选择结束日期"
            onChange={handlePublishEndTimeChange}
          />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="次数"
          name="AI_PublishDayCount"
        >
          <InputNumber min={1} defaultValue={1} style={{ width: '100%' }} placeholder="请输入次数" />
        </Form.Item>
        
     </div>
     <div style={row}>
        <Form.Item
          style={formitem}
          label="规格"
          name="AI_Size_ID"
        >
          <Advsize
            adTypeId={publicationId}
            placeholder="请选择规格"
            onChange={handleSizeChange}
          />
        </Form.Item>
        <Form.Item name="AI_Width" style={formitem}
          label="宽">
          <Input />
        </Form.Item>
        <Form.Item name="AI_Height" style={formitem}
          label="高">
          <Input />
        </Form.Item>
        
        
      </div>
      <div style={row}>
        <Form.Item
          style={formitem}
          label="版位"
          name="AI_Field_ID"
          rules={[{ required: true, message: '请选择版位' }]}
        >
          <Dictselect
            type="版位"
            multiple={false}
            needAddItem={true}
            placeholder="选择位置"
            agentid={CONTRACT_AGENTID}
            subtype={publicationId}
            
            onChange={handleFieldChange}
          />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="颜色"
          name="AI_Color_ID"
        >
          <Dictselect
            type="颜色"
            multiple={false}
            needAddItem={true}
            placeholder="选择颜色"
            agentid={CONTRACT_AGENTID}
            onChange={handleColorChange}
          />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="期数"
          name="issueno"
        >
          <Input placeholder="请输入广告期数" />
        </Form.Item>
      </div>
      
        {
            data.AI_OrderID==null && 
            <div style={row}>
        <Form.Item
          style={formitem}
          label="业务"
          name="AI_TradeID"
          rules={[{ required: true, message: '请选择主体' }]}
        >
          <Tradecascade placeholder="选择业务名称" onChange={(item: any) => {
            if (item?.value) {
              form.setFieldsValue({
                AI_TradeID: item.value,
                AI_Trade: item?.label || item
              });
            }
          }} />
        </Form.Item>
        <Form.Item
            
            style={formitem}
            label="部门"
            name="AO_Org_ID"
            rules={[{ required: true, message: '请选择部门' }]}
          >
            <Orgcascade  />
          </Form.Item>
          </div>
          }
      
     <div style={row}>
      {
        data.AI_OrderID!=null && 
        <Form.Item
          style={formitem}
          label="业务"
          name="AI_TradeID"
          rules={[{ required: true, message: '请选择主体' }]}
        >
          <Tradecascade placeholder="选择业务名称" onChange={(item: any) => {
            if (item?.value) {
              form.setFieldsValue({
                AI_TradeID: item.value,
                AI_Trade: item?.label || item
              });
            }
          }} />
        </Form.Item>
      }
      <Form.Item
          style={formitem}
          label="经办"
          name="AI_Salesman_ID"
        >
          <UserAutocomplete multiple={false} placeholder="选择业务经办人" />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="电话"
          rules={[{ required: true, message: '请输入' }]}
          name="salemantel"
        >
          <Input placeholder="请输入经办电话" />
        </Form.Item>
  
     </div>
      <Form.Item labelCol={{ span: 2 }} name="customerinfo" label="客户信息">
        <CustomerInfo placeholder={['姓名', '电话', '身份证']} />
      </Form.Item>

      <div style={row}>
        <Form.Item
          style={formitem}
          label="广告标题"
          name="AI_Content"
        >
          <Input  placeholder="请输入广告标题" />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="备注"
          name="AI_Memo"
        >
          <Input  placeholder="请输入备注" />
        </Form.Item>
        

   
      </div>
      
      {/* 其他 */}
      <div >
        <Form.Item
          style={{width:'100%'}}
          label="合作内容"
          labelCol={{ span: 2 }}
          name="content"
        >
          <TextArea rows={4} placeholder="请输入合作内容" />
        </Form.Item>

      </div>
        
        {
        !data.SYS_DOCUMENTID&&!data.AI_OrderID&&
        <Form.Item
          labelCol={{ span: 2 }}
          label="附件"
        >
          <MyUploadFile
            ref={uploadRef}
            key={uprefresh}
            name="fileurls"
            label=""
            max={20}
            multiple={false}
            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
            maxSize={50}
            listType="picture-card"
            defaultImage={defaultImage}
            uploadPath="advertisement"
            uploadType={3}
          />
        </Form.Item>
      }
        {/* 声明 */}
      <div style={{ 
        margin: '20px 0', 
        padding: '15px', 
        background: '#f5f5f5', 
        border: '1px solid #ddd',
        fontSize: 12,
        lineHeight: 1.8
      }}>
        <div style={{ fontWeight: 'bold', marginBottom: 10 }}>声明：</div>
        <div>1.本确认单具备与合同同等的法律效力。</div>
        <div>2.广告刊户所投放的广告必须符合国家广告法及新闻管理部门的有关规定，维护知
  识产权。若违反国家相关法规，一切经济责任与法律责任由广告刊户自行承担。</div>
        <div>3.遇重大事件（如国家政策等）及不可抗力因素，广告刊出时间由报社统一安排。
  广告如在约定的两日内刊出，广告经营单位不另行通知刊户。 </div>
        <div>4.双方共同遵守刊登媒体广告刊例的所有内容，特殊情况应在确认单中说明。 </div>
        <div>5.经刊户（签字人）仔细核对确认，以上刊登内容准确无误、真实可信，如有不
  实，刊户本人愿承担由此产生的一切法律责任。</div>
        <div>6.见报后如有更正，由刊户本人自行承担费用。</div>
        <div>7.报纸可邮寄，免费赠送刊户报纸一份，邮费自行承担（快递顺丰到付）。</div>
      </div>
    

      {/* 提交按钮 */}
      <Form.Item {...tailLayout}>
        <Space>
          <Button type="primary" htmlType="submit">
            提交
          </Button>
          <Button htmlType="button" onClick={handleReset}>
            清空
          </Button>
        </Space>
      </Form.Item>
    </Form>
  );
};

export default AddSmallBusiness;
