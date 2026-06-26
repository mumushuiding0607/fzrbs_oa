import { Button, DatePicker, Form, Input, InputNumber, Modal, Row, Space, Radio, message } from 'antd';
import React, { CSSProperties, useEffect, useState, useRef } from 'react';
import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"
import dayjs from 'dayjs';

dayjs.extend(weekday)
dayjs.extend(localeData)


import Companyselect from '../company/companyselect';
import Dictselect from '../budget/dict/dictselect';
import ContractSelect from '../contract/contract-select';
import UserAutocomplete from '../budget/common/userAutocomplete';
import MyUploadFile from '@/components/MyUploadFile';
import { saveAdvitem } from './service';
import { getFromUrl, setToUrl } from '../utils';
import Advsize from './advsize';
import Tradecascade from './tradecascade';
import Orgcascade from './orgcascade';
import { OrderTypeEnum } from './config';
import CustomDatePicker from './CustomDatePicker';
import PriceSelect from './priceselect';
import { CONTRACT_AGENTID } from '../contract/config';


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

const AddFzAdv: React.FC<{ data?: any, onChange?: Function }> = ({ data, onChange }) => {
  const [form] = Form.useForm();

  const [priceParams, setPriceParams] = useState<any>({});

  const [uprefresh, setUprefresh] = useState(0);

  const [publicationId, setPublicationId] = useState<any>(data?.AI_Publication_ID || null);
  const [sizeDisabled, setSizeDisabled] = useState(false);
  const uploadRef = useRef<any>(null);
  const [defaultImage, setDefaultImage] = useState<any>(data.fileurls?data.fileurls.split(',').map((url:any)=>{
    
        return getFromUrl(url)
      }):[])
 
  // 初始化表单值
  useEffect(() => {
    // 如果有附件数据
    // if (data?.fileurls) {
    //   const fileList = data.fileurls.split(',').map((url: string, index: number) => ({
    //     uid: -index - 1,
    //     name: url.split('/').pop() || '附件' + (index + 1),
    //     url: url,
    //   }));
    //   setDefaultImage(fileList);
    // }
    // 初始化发布平台ID
    if (data?.AI_Publication_ID) {
      setPublicationId(data.AI_Publication_ID);
    }
    // 初始化刊例价相关参数（用于更新模式）
    if (data) {
      // 处理可能的对象类型值（如E_AdSize_ID可能是对象）
      const getValue = (val: any) => {
        if (val === null || val === undefined) return undefined;
        return typeof val === 'object' ? (val.value ?? val.id ?? undefined) : val;
      };
      setPriceParams({
        E_PID: getValue(data.AI_Publication_ID),
        E_MID: getValue(data.E_MID),
        E_AdField_ID: getValue(data.AI_Field_ID),
        E_Color_ID: getValue(data.AI_Color_ID),
        E_AdSize_ID: getValue(data.AI_Size_ID),
        AI_Price: data.AI_Price,
        AI_PriceList: data.AI_PriceList,
      });
    }
  }, [data]);

  // 合同选择变化
  const handleContractChange = (item: any) => {
    if (item) {
      
      form.setFieldsValue({
        contractserial: item.serial,
        partbname: item.partbname,
        partb: item.partb,
        AI_Customer_ID: item.parta,
        AI_Customer: item.partaname,
      });
    }
  };



  // 规格变化
  const handleSizeChange = (item: any) => {
    const isSpecialShape = item?.label === '异形广告';
    if (item?.value) {
      form.setFieldsValue({
        AI_Size_ID: item.value,
        AI_Size: item?.label || item,
        AI_Width: item?.width,
        AI_Height: item?.height,
        AI_LayoutAmount: item?.layoutAmount
      });
      // item.label 等于异形广告时，AI_Width和AI_Height 对应的form表单允许编辑，否则禁止编辑
      setSizeDisabled(!isSpecialShape);
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

  // 行业变化
  const handleTradeChange = (item: any) => {
    if (item?.value) {
      form.setFieldsValue({
        AI_TradeID: item.value,
        AI_Trade: item?.label || item
      });
    }
  };

  // 支付方式变化
  const handlePayModeChange = (value: any, item: any) => {
    if (value) {
      form.setFieldsValue({
        AI_PayMode_ID: value,
        AI_PayMode: item?.label || item
      });
    }
  };

  // 计算实收金额 = 刊例价 × (折扣比例 / 100)，保留两位小数
  const calculateAmountReceivable = () => {
    const price = form.getFieldValue('AI_Price') || 0;
    const discount = form.getFieldValue('discount') || 100;
    const amountReceivable = price * (discount / 100);
    form.setFieldsValue({
      AI_AmountReceivable: Number(amountReceivable.toFixed(2))
    });
  };

  // 逆推折扣比例 = (实收金额 / 刊例价) × 100，保留两位小数
  const calculateDiscount = () => {
    const price = form.getFieldValue('AI_Price') || 0;
    const amountReceivable = form.getFieldValue('AI_AmountReceivable') || 0;
    if (price > 0) {
      const discount = (amountReceivable / price) * 100;
      form.setFieldsValue({
        discount: Number(discount.toFixed(2))
      });
    }
  };

  // 提交表单
  const handleSubmit = async (values: any) => {
    try {
      values.AI_Type = OrderTypeEnum.FzAdv;
      // 处理文件上传
      const uploads = uploadRef?.current?.getFileList();
      if (uploads) {
        values.fileurls = uploads.map((u: any) => setToUrl(u)).join(',');
      }
      if (values.contractid && values.contractid instanceof Object){
        values.contractid = values.contractid.id
      }
      // 处理客户ID
      if (values.AI_Customer_ID && values.AI_Customer_ID.value) {
        values.AI_Customer = values.AI_Customer_ID.label;
        values.AI_Customer_ID = values.AI_Customer_ID.value;
      }

      
      if (values.partb && typeof values.partb === 'object') {
        values.partbname = values.partb.label || values.partb.company;
        values.partb = values.partb.value || values.partb.id;
      }
      if (values.AI_Org_ID && typeof values.AI_Org_ID === 'object') {
        values.AI_Org = values.AI_Org_ID.label
        values.AI_Org_ID = values.AI_Org_ID.value
      }
      
      // 处理业务经办人ID
      if (values.AI_Salesman_ID instanceof Object) {
        values.AI_Salesman = values.AI_Salesman_ID.label;
        values.AI_Salesman_ID = values.AI_Salesman_ID.value;
      }
      if (values.assistant && values.assistant instanceof Object){
        values.assistantname = values.assistant.label
        values.assistantdepartmentid = values.assistant.departmentid
        values.assistantdepartmentname = values.assistant.departmentname
        values.assistant = values.assistant.value
      } else{
        values.assistant = null
        values.assistantname = null
        values.assistantdepartmentid = null
        values.assistantdepartmentname = null
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
      id="addFzAdv"
      {...formItemLayout}
      form={form}
      onFinish={handleSubmit}
      onValuesChange={(changedValues, allValues) => {
        // 监听刊例价相关字段变化，更新PriceSelect参数
        if (changedValues.AI_Publication_ID !== undefined ||
            changedValues.E_MID !== undefined ||
            changedValues.AI_Field_ID !== undefined ||
            changedValues.AI_Color_ID !== undefined ||
            changedValues.AI_Size_ID !== undefined) {
          // 处理可能的对象类型值（如E_AdSize_ID可能是对象）
          const getValue = (val: any) => {
            if (val === null || val === undefined) return undefined;
            return typeof val === 'object' ? (val.value ?? val.id ?? undefined) : val;
          };
          setPriceParams({
            E_PID: getValue(allValues.AI_Publication_ID),
            E_MID: getValue(allValues.E_MID),
            E_AdField_ID: getValue(allValues.AI_Field_ID),
            E_Color_ID: getValue(allValues.AI_Color_ID),
            E_AdSize_ID: getValue(allValues.AI_Size_ID),
          });
        }
      }}
      style={{ maxWidth: 1400, paddingRight: '20px' }}
      initialValues={data}
    >
      {/* 隐藏字段 */}
      <Form.Item name="AI_Type" style={{ display: 'none' }}>
        <Input value={1}/>
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

      <Form.Item name="AI_PriceList" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="E_MID" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="paytime" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="AI_Publication" style={{ display: 'none' }}>
              <Input />
            </Form.Item>
      <Form.Item name="AI_Salesman" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="assistantname" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="assistantdepartmentid" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      <Form.Item name="assistantdepartmentname" style={{ display: 'none' }}>
        <Input />
      </Form.Item>
      
      

      {/* 标题 */}
      <div style={{ textAlign: 'center', marginBottom: 20, fontSize: 18, fontWeight: 'bold' }}>
        福州日报社广告以及纯服务收入登记表
      </div>

      {/* 第一行：下单日期、单据编号、关联合同 */}
      <div style={row}>
        <Form.Item
          style={formitem}
          label="下单日期"
          name="SYS_CREATED"
        >
          <CustomDatePicker format="YYYY-MM-DD" style={{ width: '100%' }} />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="关联合同"
          name="contractid"
        >
          <ContractSelect
            multiple={false}
            showupload={false}
            onChange={handleContractChange}
          />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="合同"
          name="contractserial"
        >
          <Input placeholder="合同编号" />
        </Form.Item>
        
      </div>

      {/* 第二行：客户信息 */}
      <div style={row}>
        <Form.Item
          style={formitem}
          label="客户"
          name="AI_Customer_ID"
          rules={[{ required: true, message: '请选择客户' }]}
        >
          <Companyselect multiple={false} placeholder="选择客户" />
        </Form.Item>
        {
          data.AI_OrderID==null && <Form.Item
          style={formitem}
          label="部门"
          name="AI_Org_ID"
          rules={[{ required: true, message: '请选择部门' }]}
        >
          <Orgcascade />
        </Form.Item>
        }
        <Form.Item
            style={formitem}
            label="主体"
            name="partb"
            rules={[{ required: true, message: '请选择主体' }]}
          >
            <Companyselect multiple={false} placeholder="选择主体" />
          </Form.Item>
        
      </div>

      {/* 第三行：发布平台 */}
      <div style={row}>
        <Form.Item
          style={formitem}
          label="发布平台"
          name="AI_Publication_ID"
          rules={[{ required: true, message: '请选择发布平台' }]}
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
          label="行业"
          name="AI_TradeID"
        >
          <Tradecascade placeholder="选择行业" onChange={handleTradeChange} />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="收款时间"
          name="paytimeid"
          rules={[{ required: true, message: '请选择收款时间' }]}
        >
          <Dictselect
            type="收款时间"
            multiple={false}
            needAddItem={true}
            agentid={CONTRACT_AGENTID}
            placeholder="选择收款时间"
            onChange={(value: any, item: any) => {
              console.log('Dictselect onChange:', value, item);
              if (item) {
                form.setFieldsValue({
                  paytimeid: item.value,
                  paytime:item.label
                });
              }
            }}
          />
        </Form.Item>
 
        
      </div>
      <div style={row}>
        <Form.Item
          style={formitem}
          label="发布日期"
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
    

      {/* 第五行：规格、位置、次数、广告内容 */}
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
          <Input disabled={sizeDisabled} />
        </Form.Item>
        <Form.Item name="AI_Height" style={formitem}
          label="高">
          <Input disabled={sizeDisabled} />
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

      
      <div style={row}>
        <Form.Item
          style={formitem}
          label="刊例价"
          name="AI_Price"
          rules={[{ required: true, message: '请输入刊例价' }]}
        >
          <PriceSelect
            style={{ width: '100%' }}
            placeholder="请选择刊例价"
            value={data?.AI_Price}
            E_PID={priceParams.E_PID}
            E_MID={priceParams.E_MID}
            E_AdField_ID={priceParams.E_AdField_ID}
            E_Color_ID={priceParams.E_Color_ID}
            E_AdSize_ID={priceParams.E_AdSize_ID}
            onChange={(value, record) => {
              if (record) {
                form.setFieldsValue({
                  AI_Price: value,
                  AI_PriceList: record.SYS_DOCUMENTID
                });
              } else {
                form.setFieldsValue({
                  AI_Price: value,
                  AI_PriceList: undefined
                });
              }
              // 自动计算实收金额
              calculateAmountReceivable();
            }}
          />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="折扣比例"
          name="discount"
        >
          <InputNumber
            style={{ width: '100%' }}
            placeholder="请输入折扣比例"
            min={0}
            max={100}
            defaultValue={100}
            formatter={(value: any) => `${value}%`}
            parser={(value: any) => parseFloat(value?.replace('%', '') || '0')}
            onChange={calculateAmountReceivable}
          />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="实收金额"
          name="AI_AmountReceivable"
          
          rules={[{ required: true, message: '请输入实收金额' }]}
        >
          <InputNumber
            style={{ width: '100%' }}
            placeholder="实收金额"
            min={0}
            precision={2}
            onChange={calculateDiscount}
          />
        </Form.Item>
      </div>

      {/* 第八行：收款方式 */}
      <div style={row}>
        <Form.Item
          style={formitem}
          label="收款方式"
          name="AI_PayMode_ID"
        >
          <Dictselect
            type="支付方式"
            multiple={false}
            needAddItem={true}
            placeholder="选择收款方式"
            agentid={CONTRACT_AGENTID}
            onChange={handlePayModeChange}
          />
        </Form.Item>
        
    
        <Form.Item
          style={formitem}
          label="业务经办人"
          name="AI_Salesman_ID"
        >
          <UserAutocomplete multiple={false} placeholder="选择业务员" />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="协助人员"
          name="assistant"
        >
          <UserAutocomplete multiple={false} placeholder="选择协助人员" />
        </Form.Item>
      </div>
      <div style={row}>
        <Form.Item
          style={formitem}
          label="广告内容"
          name="AI_Content"
        >
          <Input.TextArea rows={2} placeholder="请输入广告内容" />
        </Form.Item>
        <Form.Item
          style={formitem}
          label="备注"
          name="AI_Memo"
        >
          <Input.TextArea rows={2} placeholder="请输入备注" />
        </Form.Item>
        
        
        
      </div>
      
      <div style={{...row}}>
        <Form.Item
          
          label=" 附 件 "
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

export default AddFzAdv;

