import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import { Modal, Form, Input, Button, Space, Col, Row, DatePicker, InputNumber } from 'antd';
import type { DatePickerProps } from 'antd';
import { getcompany } from '../../company/service';
import Dictselect from '../../budget/dict/dictselect';
import Flow from '../../budget/budget/flow';
import { previewdebtflow } from '../service';
import { geturgelogs, geturges, startdebturge, updateurge } from './service';
import UrgeView from './urgeview';
import ContractSelect from '../contract-select';
import { BalanceTypes } from '../../budget/config';
import Add from '../../company/add';
import MyUploadFile from '@/components/MyUploadFile';
import { getFromUrl, setToUrl } from '../../utils';
import UserAutocomplete from '../../budget/common/userAutocomplete';
import DepartmentTreeSelect from '../../budget/common/department_treeselect';
import dayjs from 'dayjs';
import Companyselect from '../../company/companyselect';

const labelStyle:CSSProperties = { width: 100, textAlign: 'right' }

interface ApprovalModalProps {
  visible: boolean;
  data: any;
  onVisibleChange:any,
  onSuccess?:any,
  action?:any
}

const AddDebtUrge: React.FC<ApprovalModalProps> = ({
  visible,
  data,
  onVisibleChange,
  onSuccess,
  action

}) => {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [contractid,setContractid]=useState(data.id)
  const [cvisible,setCvisible]=useState(false)
  const [company,setCompany]=useState<any>({})
  const uploadRef = useRef<any>(null);
  const [hasContract, setHasContract] = useState(false);
  const [isUpdateMode, setIsUpdateMode] = useState(false);

  const [defaultImage, setDefaultImage] = useState<any>(data.fileurls?data.fileurls.split(',').map((url:any)=>{
    return getFromUrl(url)
  }):[])

  // 初始值设置
  const initialValues = {
    note: data.note || '',
    urgetypename: data.urgetypename || '',
    reason: data.reason || '',
    urgeresultname: data.urgeresultname || '',
    contactor: data.contactor || '',
    mobile: data.mobile || '',
    address: data.address || '',
    contractid: data.contractid || data.id,
    debtamount: data.debtamount !== undefined ? data.debtamount : (data.amount || undefined),
    paycollection: data.paycollection || 0,
    overduedate: data.overduedate ? dayjs(data.overduedate) : undefined,
    creator: data.urgeuserid || undefined,
    departmentid: data.urgedepartmentid || undefined
  };

  useEffect(() => {
    // 判断是否为更新模式
    const isUpdate = action === 'update' || data.id;
    setIsUpdateMode(isUpdate);
    
    // 如果有contractid，设置为有合同状态
    if (data.contractid) {
      setHasContract(true);
    }
    // 如果有parta，获取公司信息填充地址/电话/联系人（除非已存在值）
    // 检查是否已有地址、电话、联系人值
    const hasAddress = data.address && data.address.trim() !== '';
    const hasMobile = data.mobile && data.mobile.trim() !== '';
    const hasContactor = data.contactor && data.contactor.trim() !== '';
    console.log('hasAddress:', hasAddress, 'hasMobile:', hasMobile, 'hasContactor:', hasContactor)
    console.log('data:', data)
    if (!hasAddress || !hasMobile || !hasContactor) {
      if (data.debturgeid){
        geturges({debturgeid: data.debturgeid}).then((res: any) => {
          if (res && res.data[0]) {
            const updateData: any = {};
            if (!hasAddress) updateData.address = res.data[0].address;
            if (!hasMobile) updateData.mobile = res.data[0].contacts||res.data[0].mobile;
            if (!hasContactor) updateData.contactor = res.data[0].contactor;
            updateData.reason = res.data[0].reason;
            updateData.note = res.data[0].note;
            
            if (Object.keys(updateData).length > 0) {
              form.setFieldsValue(updateData);
            }
          }
        })
      }
      else if (data.parta) {
        getcompany({id: data.parta}).then((res: any) => {
          if (res && res[0]) {
            const updateData: any = {};
            if (!hasAddress) updateData.address = res[0].address;
            if (!hasMobile) updateData.mobile = res[0].contacts;
            if (!hasContactor) updateData.contactor = res[0].contactor;
            
            if (Object.keys(updateData).length > 0) {
              form.setFieldsValue(updateData);
            }
          }
        })
      }
    }
    
    
      

    
    
    if (isUpdate) {
      // 更新模式下，如果有debturgeid则获取催收数据
      if (data.debturgeid) {
        geturges({
          debturgeid: data.debturgeid,
        }).then((res: any) => {
          if (res.data && res.data[0]) {
            const urgeData = res.data[0];
            console.log('urge data fetched:', urgeData)
            
            // 格式化逾期时间
            if (urgeData.overduedate) {
              urgeData.overduedate = dayjs(urgeData.overduedate);
            }
            
            form.setFieldsValue(urgeData);
            setDefaultImage(urgeData.fileurls ? urgeData.fileurls.split(',').map((url: any) => {
              return getFromUrl(url)
            }) : [])
          }
        })
      }
      
      
      
      // 设置责任人和部门
      if (data.urgeuserid) {
        form.setFieldsValue({urgeuserid: data.urgeuserid})
      }
      if (data.urgedepartmentid) {
        form.setFieldsValue({urgedepartmentid: data.urgedepartmentid})
      }
    } else {
      // 创建模式下，如果传入了urgeuserid和urgedepartmentid也设置
      if (data.urgeuserid) {
        form.setFieldsValue({urgeuserid: data.urgeuserid})
      }
      if (data.urgedepartmentid) {
        form.setFieldsValue({urgedepartmentid: data.urgedepartmentid})
      }
    }
  }, [visible, data]);

  const onCompanyUpdate = (data: any) => {
    console.log('company data:', data)
    form.setFieldsValue({
      address: data.address,
      contactor: data.contactor,
      mobile: data.contacts
    })
    setCvisible(false)
  }

  const onContractChange = (e: any) => {
    if (e) e = [e]
    if (e && e.length > 0) {
      setHasContract(true);
      var parta = e.map((d: any) => d.parta).join(',')
      form.setFieldsValue({
        contractid: e[0].id,
        urgeuserid: e[0].creator,
        urgedepartmentid: e[0].departmentid
      })
      setContractid(e[0].id)
    
      getcompany({id: parta}).then((res: any) => {
        if (res && res[0]) {
          form.setFieldsValue({
            address: res[0].address,
            mobile: res[0].contacts,
            contactor: res[0].contactor
          })
        }
      })
    } else {
      setHasContract(false);
    }
  }

  const start = (par: {}) => {
    startdebturge({obj: {...par}}).then((res: any) => {
      if (res.errorMessage) {
        Modal.error({
          title: res.errorMessage
        });
      } else {
        onVisibleChange(false)
        onSuccess && onSuccess(res.thirdNo)
      }
    })
  }

  const onFinish = (values: any) => {
    const uploads = uploadRef.current?.getFileList?.();
    if (uploads && uploads.length > 0) {
      values.fileurls = uploads.map((u: any) => setToUrl(u)).join(',');
    } else {
      values.fileurls = '';
    }

    // 处理parta字段：将对象数组转换为逗号分隔的value字符串
    if (values.parta && Array.isArray(values.parta)) {
      values.partaname = values.parta.map((item: any) => item.label).join(',');
      values.parta = values.parta.map((item: any) => item.value).join(',');
    }

    // 处理urgeuserid字段：将对象转换为value值
    if (values.urgeuserid && typeof values.urgeuserid === 'object') {
      values.urgeuserid = values.urgeuserid.value;
    }

    // 处理逾期时间格式
    if (values.overduedate && typeof values.overduedate.format === 'function') {
      values.overduedate = values.overduedate.format('YYYY-MM-DD');
    }

    continueSaveDebtUrge(values);
  }

  const continueSaveDebtUrge = (values: any) => {
    values.contractid = values.contractid || data.id
    values.debturgeid = data.debturgeid || values.id
    
    if (isUpdateMode) {
      updateurge({
        obj: {...values}
      }).then((res: any) => {
        if (res.errorMessage) {
          Modal.error({
            title: res.errorMessage
          });
        } else {
          onVisibleChange(false)
          onSuccess && onSuccess(res)
        }
      })
    } else {
      previewdebtflow({...values}).then((res: any) => {
        if (res.errorMessage || res.message) {
          Modal.error({
            title: res.errorMessage || res.message,
          });
        } else {
          Modal.confirm({
            title: "请确认流程是否正确",
            bodyStyle: {marginLeft: 0},
            width: '600px',
            centered: false,
            content: (
              <div style={{marginLeft: '0!important'}}>
                <Flow data={res.viewdata} statusCn={res.statusCn} step={res.step}></Flow>
              </div>
            ),
            onOk: () => {
              start(values)
            },
          })
        }
      })
    }
  }

  return (
    <>
      <Modal
        title={isUpdateMode ? "编辑欠款催收" : "新建欠款催收"}
        visible={visible}
        confirmLoading={loading}
        width={800}
        onCancel={() => onVisibleChange(false)}
        footer={null}
      >
        <Form
          form={form}
          layout="horizontal"
          initialValues={initialValues}
          onFinish={onFinish}
        >
        
         
            <Form.Item label="合同名称" name="contractid" labelCol={{style: labelStyle}}>
              <ContractSelect multiple={false} showupload={false} type={BalanceTypes.INCOME} onChange={onContractChange} />
            </Form.Item>
          
          
          <Form.Item label="id" name="id" style={{display: 'none'}}>
            <Input disabled/>
          </Form.Item>
          
          <Form.Item label="debturgeid" name="debturgeid" style={{display: 'none'}}>
            <Input />
          </Form.Item>

          <Form.Item
            label="清欠编号"
            name="serial"
            labelCol={{style: labelStyle}}
            rules={[{ required: false, message: '请输入清欠编号' }]}
          >
            <Input placeholder="清欠编号" />
          </Form.Item>

          
            <Row gutter={16}>
              <Col span={12}>
                <Form.Item
                  label="催款金额"
                  name="debtamount"
                  labelCol={{style: labelStyle}}
                  rules={[{ required: true, message: '请输入欠款金额' }]}
                >
                  <InputNumber
                    style={{ width: '100%' }}
                    prefix="￥"
                    placeholder="欠款金额"
                    precision={2}
                    min={0}
                  />
                </Form.Item>
              </Col>
              
              <Col span={12}>
                <Form.Item
                  label="回款金额"
                  name="paycollection"
                  labelCol={{style: labelStyle}}
                  rules={[{ required: true, message: '请输入回款金额' }]}
                >
                  <InputNumber
                    style={{ width: '100%' }}
                    prefix="￥"
                    placeholder="回款金额"
                    precision={2}
                    min={0}
                  />
                </Form.Item>
              </Col>
            </Row>
          

          
          {/* 责任人 + 责任部门 - 仅在无合同时显示 */}
          {!hasContract && (
            <Row gutter={16}>
              <Col span={12}>
                <Form.Item
                  label="责任人"
                  name="urgeuserid"
                  labelCol={{style: labelStyle}}
                  rules={[{ required: true, message: '请选择责任人' }]}
                >
                  <UserAutocomplete multiple={false} />
                </Form.Item>
              </Col>
              <Col span={12}>
                <Form.Item
                  name="urgedepartmentid"
                  label="责任部门"
                  rules={[{ required: true, message: '请选择责任部门' }]}
                >
                  <DepartmentTreeSelect multiple={false} defaultValue={data.urgedepartmentid}/>
                </Form.Item>
              </Col>
            </Row>
          )}

          {/* 欠款金额 + 逾期时间 - 仅在未添加合同时显示 */}
          {!hasContract && (
            <Row gutter={16}>
              <Col span={12}>
                <Form.Item
                  label="债务方"
                  name="parta"
                  labelCol={{style: labelStyle}}
                  rules={[{ required: false, message: '请输入债务方' }]}
                >
                  <Companyselect placeholder={'债务方名称'}   multiple={true} />
                </Form.Item>
              </Col>
              <Col span={12}>
                <Form.Item
                  label="逾期时间"
                  name="overduedate"
                  labelCol={{style: labelStyle}}
                  rules={[{ required: true, message: '请选择逾期时间' }]}
                >
                  <DatePicker style={{ width: '100%' }} placeholder="逾期时间" />
                </Form.Item>
              </Col>
            </Row>
          )}

          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                label="清欠方式"
                name="urgetype"
                labelCol={{style: labelStyle}}
                rules={[{ required: true, message: '请选择清欠方式' }]}
              >
                <Dictselect type={"清欠方式"} needAddItem={false} />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                label="联系人"
                name="contactor"
                labelCol={{style: labelStyle}}
                rules={[{ required: true, message: '请输入联系人' }]}
              >
                <Input placeholder="债务方联系人" />
              </Form.Item>
            </Col>
          </Row>

          <Row gutter={16}>
            <Col span={12}>
              <Form.Item
                label="电话"
                name="mobile"
                labelCol={{style: labelStyle}}
                rules={[
                  { required: true, message: '请输入联系电话' },
                  {
                    pattern: /^(1[3-9]\d{9})|(\d{3,4}-?\d{7,8})$/,
                    message: '请输入正确的手机号或座机号格式',
                  },
                ]}
              >
                <Input placeholder="债务方联系电话" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item
                label="地址"
                name="address"
                labelCol={{style: labelStyle}}
                rules={[{ required: true, message: '请输入地址' }]}
              >
                <Input placeholder="债务方地址" />
              </Form.Item>
            </Col>
          </Row>

          <Form.Item
            label="拖欠原因"
            name="reason"
            labelCol={{style: labelStyle}}
            rules={[{ required: true, message: '请输入拖欠原因' }]}
          >
            <Input.TextArea autoSize={{ minRows: 2 }} placeholder="拖欠原因" />
          </Form.Item>

          <Form.Item label="备注" name="note" labelCol={{style: labelStyle}}>
            <Input.TextArea placeholder="备注内容" autoSize={{ minRows: 2 }} />
          </Form.Item>
          
          <Form.Item label="相关文件：" style={{marginLeft: '10px'}}>
            <MyUploadFile
              ref={uploadRef}
              name="fileurls"
              max={20}
              multiple={false}
              accept="*/*"
              maxSize={100}
              listType="picture-card"
              defaultImage={defaultImage}
              uploadPath="contract"
              uploadType={3}
            />
          </Form.Item>

          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit">
                {isUpdateMode ? '更新' : '提交'}
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Modal>
      
      <Add 
        key={'c' + company.id} 
        visible={cvisible} 
        id={company.id || company.value} 
        company={company.company} 
        onChange={onCompanyUpdate}  
        onVisibleChange={setCvisible}
      />
    </>
  );
};

export default AddDebtUrge;
