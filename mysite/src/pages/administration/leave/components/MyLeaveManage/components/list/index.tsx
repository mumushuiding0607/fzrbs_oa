import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import type { ProDescriptionsItemProps } from '@ant-design/pro-descriptions';
import ProDescriptions from '@ant-design/pro-descriptions';
import React, { useImperativeHandle, useRef, useState, useEffect } from 'react';
import type { TableListItem, DateItem } from '../../../data';
import { leaveList,updateLeave,dict } from '../../../service';
import { Button, Drawer, Space, Steps, Card, Tag, message, Form, Modal,Image,Switch } from 'antd';
import type { ProFormInstance } from '@ant-design/pro-components';
import { DrawerForm, ProForm, ProFormText, ProFormDatePicker,ProFormDigit, ProFormSelect, ProFormTextArea } from '@ant-design/pro-components';
import LeaveExportModal from '../LeaveExportModal';
import {ExportOutlined} from '@ant-design/icons';
import MyUploadFile from '@/components/MyUploadFile';
import moment from "moment";
const { Step } = Steps;

const List = React.forwardRef((props: any, ref) => {
  console.log('info:',props.userAuth);
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<TableListItem>();
  const actionRef = useRef<ActionType>();
  const editFormRef = useRef<any>();
  const [parentId, setParentId] = useState<number>(0);
  const [infoId, setInfoId] = useState<number>(0);
  const modalRef = useRef<any>(); 
  const [initParams, setInitParams] = useState<any>({ module: 'manage' });
  const formRef = useRef<ProFormInstance>();
  const [showEditForm, setShowEditForm] = useState<boolean>(false);  
  const [defaultImage, setDefaultImage] = useState<any[]>([]);
  const [holiday, setHoliday] = useState<any[]>([]);
  const [nonHoliday, setNonHoliday] = useState<any[]>([]);
  const [noHolidayTypes, setNoHolidayTypes] = useState<any[]>([]);
  const noHolidayTypesRef = useRef(noHolidayTypes);
  const holidayRef = useRef(holiday);
  const nonHolidayRef = useRef(nonHoliday);
  const [leaveTypeArr, setLeaveTypeArr] = useState<any>();
  const [statusArr, setStatusArr] = useState<any>();
  const [isoutDict, setIsoutDict] = useState<any>();
  const [timeDict, setTimeDict] = useState<any>();
  const [form] = Form.useForm<{ speech: string }>();
   
  const editItem = (id: number) => {
    setInfoId(id);
    editFormRef?.current.setVisible(true);
  };

  const handleCancel = () => {
    Modal.confirm({
      title: '撤销申请',
      content: '确定要撤销该申请吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        form
        .validateFields()
        .then(async () => {
          const result = await updateLeave({
            id: currentRow?.id,
            thirdNo: currentRow?.thirdNo,
            act: 'mcancel'
          });
          if (result) {
            if (result.errorCode) {
              message.warn(result.errorMessage);
              return false;
            }
            message.success('撤销成功！');
            actionRef.current?.reload();
            setCurrentRow(undefined);
            setShowDetail(false);
          }
        }).catch((err) => { });
        
      },
    }); 
    return true;
  };
  const handleReset = () =>{
    Modal.confirm({
      title: '重置流程',
      content: '确定要重置流程吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        
        const result = await updateLeave({
            id: currentRow?.id,
            thirdNo: currentRow?.thirdNo,
            act: 'resetflow'
          });
          if (result) {
            if (result.errorCode) {
              message.warn(result.errorMessage);
              return false;
            }
            message.success('重置流程成功！');
            actionRef.current?.reload();     
            setCurrentRow(undefined);
            setShowDetail(false);       
          }
        
        
      },
    }); 
    return true;
  }


  const getDateOrTime = (d:string,t:number) => {
    const _dobj = new Date(d);
    if (t === 1) {
      return _dobj.getFullYear() + '-' + (_dobj.getMonth() + 1) + '-' + _dobj.getDate();
    } else if (t === 2) {
      const _t = _dobj.getHours();
      return _t<=12?'上午':(_t>12 && _t<=18?'下午':'晚上')
    }
  }
  const Prezero = (m:number,l:number)=> {
    return ('0'.repeat(l) + m).slice(-l);
  };
  const compareDate = (_d1: string, _d2: string) => {
    console.log(_d1,_d2);
    let d1 = new Date(_d1);
    let d2 = new Date(_d2);
    let ret = d1.getTime() > d2.getTime() ? [_d2, _d1] : [_d1, _d2];
    console.log(ret);
    return ret;
  };
  const addDate = (_date:string, _n:number) => {
    let d = new Date(_date);
    d.setDate(d.getDate() + _n);
    let m = d.getMonth() + 1;
    let ret = d.getFullYear() + '-' + Prezero(m, 2) + '-' + Prezero(d.getDate(), 2);
    console.log(ret);
    return ret;
  };
  const getWorkday:any = (_date:string, _n:number, _t:number) => {
    if (_t == 3) return getWorkday(addDate(_date, _n), _n);
    if (noHolidayTypesRef.current.includes(formRef.current?.getFieldValue('leaveType'))) return _date;
    let dd = new Date(_date.replace(/-/g, '/'));
    let weekDay = dd.getDay();
    // console.log(holidayRef.current,nonHolidayRef.current);
    if (holidayRef.current.includes(_date)) return getWorkday(addDate(_date, _n), _n);
    if ((weekDay == 0 || weekDay == 6) && !nonHolidayRef.current.includes(_date)) return getWorkday(addDate(_date, _n), _n);
    console.log(_date);
    return _date;
  };
  const getWeekends = (_sd:string,_ed:string) => {
    let startDate = new Date(Date.parse(_sd.replace(/-/g, "/")));
    let endDate = new Date(Date.parse(_ed.replace(/-/g, "/")));
    let sDay = startDate;
    let eDay = endDate;
    let s_t = sDay.getTime();
    let e_t = eDay.getTime();
    // 总相差天数
    let diffDay = (e_t - s_t) / (1000 * 60 * 60 * 24) + 1;
    if (diffDay == 0)
      return diffDay;
    // 周末天数
    let weekends = 0;
    if (!noHolidayTypesRef.current.includes(formRef.current?.getFieldValue('leaveType')) && s_t <= e_t) {
      for (let i = s_t; i <= e_t; i += 24 * 3600 * 1000) {
        let d = new Date(i);
        if (d.getDay() == 0 || d.getDay() == 6) {
          weekends++;
        }
      }
      for (let n in holidayRef.current) {
        let _tmphdate = new Date(holidayRef.current[n].replace(/-/g, '/'));
        if (_tmphdate.getDay() == 0 || _tmphdate.getDay() == 6) continue;
        if (_tmphdate.getTime() >= startDate.getTime() && _tmphdate.getTime() <= endDate.getTime()) {
          weekends = weekends + 1;
        }
      }
      for (let n in nonHolidayRef.current) {
        let _tmpnhdate = new Date(nonHolidayRef.current[n].replace(/-/g, '/'));
        // console.log(moment(_tmpnhdate).format('YYYY-MM-DD'),moment(startDate).format('YYYY-MM-DD'),moment(endDate).format('YYYY-MM-DD'));
        if (_tmpnhdate.getTime() >= startDate.getTime() && _tmpnhdate.getTime() <= endDate.getTime()) {
          weekends = weekends - 1;
        }
      }
    }
    return weekends; //休息日天数
  };
  const calctimes = async (thisRef:ProFormInstance) => {
    let values: DateItem = {      
      leaveStartD: Date.now().toString(),
      leaveStartT: 1,
      leaveEndD: Date.now().toString(),
      leaveEndT: 1
    };
    console.log(thisRef.current?.getFieldValue('leaveStartT'),thisRef.current?.getFieldValue('leaveEndT'));
    values.leaveStartD = moment(thisRef.current?.getFieldValue('leaveStartD')).format('YYYY-MM-DD');
    values.leaveEndD = moment(thisRef.current?.getFieldValue('leaveEndD')).format('YYYY-MM-DD');
    const _leaveStartT = thisRef.current?.getFieldValue('leaveStartT')||'上午';
    const _leaveEndT = thisRef.current?.getFieldValue('leaveEndT')||'上午';
    console.log(_leaveStartT, _leaveEndT);
    values.leaveStartT = _leaveStartT=='上午' || _leaveStartT==1?1:(_leaveStartT=='下午' || _leaveStartT==2?2:3);
    values.leaveEndT = _leaveEndT=='上午' || _leaveEndT==1?1:(_leaveEndT=='下午' || _leaveEndT==2?2:3);
    values.leaveStartT = values.leaveStartT || 1;
    values.leaveEndT = values.leaveEndT || 1;
    console.log(values.leaveStartD, values.leaveEndD);
    console.log(values.leaveStartT, values.leaveEndT);
    let d = compareDate(values.leaveStartD, values.leaveEndD);
    console.log(d);
    d[0] = getWorkday(d[0],1,values.leaveStartT);
    d[1] = getWorkday(d[1], -1);
    values.leaveStartT = d[0] == values.leaveStartD ? values.leaveStartT : 1;
    values.leaveEndT = d[1] == values.leaveEndD ? values.leaveEndT : 2;
    console.log(d);
    let d1 = moment(d[0]);
    let d2 = moment(d[1]);
    let dayDiff = d2.diff(d1, "days");
    console.log(dayDiff);
    console.log(values.leaveStartT, values.leaveEndT);
    if (d1==d2) {
      if ((values.leaveStartT==1 && values.leaveEndT==1) || values.leaveStartT==2) {
        dayDiff += 0.5;
      } else if (values.leaveStartT==1 && (values.leaveEndT==2 || values.leaveEndT==3)) {
        dayDiff += 1;
      }
    } else {
      if (values.leaveStartT==1 && (values.leaveEndT==2 || values.leaveEndT==3)) {
        dayDiff += 1;
      } else if ((values.leaveStartT==1 && values.leaveEndT==1) || (values.leaveStartT==2 && (values.leaveEndT==2 || values.leaveEndT==3))) {
        dayDiff += 0.5;
      }
    }
    let _weekends = getWeekends(d[0], d[1]);
    console.log(dayDiff, _weekends);
    dayDiff = dayDiff - _weekends;
    dayDiff = dayDiff<0?0:dayDiff;
    console.log(dayDiff);
    // setDays(dayDiff || 0);
    thisRef.current?.setFieldsValue({leaveTimes:dayDiff || 0});
  };

  const columns: ProColumns<TableListItem>[] = [
    {
      title: '用户ID',
      dataIndex: 'userId',
      hideInTable: true,
      hideInSearch: true,
      hideInDescriptions: true,
    },
    {
      title: '姓名',
      dataIndex: 'userName',
      render: (dom, entity) => {
        return (
          <a
            key={'un_'+entity.id}
            onClick={() => {
              setCurrentRow(entity);
              setShowDetail(true);
            }}
          >
            {dom}
          </a>
        );
      },
    },
    {
      title: '部门',
      hideInSearch: true,
      dataIndex: 'department',
    },
    {
      title: '申请类别',
      dataIndex: 'leaveType',
      valueEnum: {...leaveTypeArr,销假:{text:'销假'}},
      renderFormItem: (item, { type, defaultRender, ...rest }, form, idx) => {
        if (type === 'form') {
          return null;
        }
        return (
          <ProFormSelect.SearchSelect
            {...rest}
            mode="multiple"
          />
        );

      }
    },
    {
      title: '开始时间',
      dataIndex: 'leaveStarttime',
      valueType: 'date',
      render: (_, entity) => {
        return entity.leaveStarttime;
      },
    },
    {
      title: '结束时间',
      dataIndex: 'leaveEndtime',
      valueType: 'date',
      render: (_, entity) => {
        return entity.leaveEndtime;
      },
    },
    {
      title: '天数',
      dataIndex: 'leaveTimes',
      hideInSearch: true,
      render: (_, entity) => {
        return parseFloat(entity.leaveTimes);
      },
    },
    {
      title: '附件',
      dataIndex: 'attachment',
      hideInSearch: true,
      render: (dom, entity) => {
        return entity.attachment==='缺'?<Tag key={'attachment_'+entity.id} color="red">{dom}</Tag>:'';
      },
    },
    {
      title: '是否出省',
      dataIndex: 'isout',
      valueEnum: {
        1: {
          text: '出省',
        },
        2: {
          text: '市内',
        },
        3: {
          text: '出市',
        },
      },
      render: (dom, entity) => {
        return entity.leaveType==='销假' || entity.isout===0 ?'':dom;
      },
    },
    {
      title: '出行位置',
      dataIndex: 'destination',
    },
    {
      title: '请假事由',
      dataIndex: 'leaveReason',
      hideInTable: true,
    },
    {
      title: '状态',
      dataIndex: 'status',
      hideInForm: true,
      valueEnum: statusArr,
      renderFormItem: (item, { type, defaultRender, ...rest }, form) => {
        if (type === 'form') {
          return null;
        }
        return (
          <ProFormSelect.SearchSelect
            {...rest}
            mode="multiple"
          />
        );

      }
    },
    {
      title: '申请时间',
      dataIndex: 'inserttime',
      valueType: 'dateRange',
      render: (_, entity) => {
        return entity.inserttime;
      },
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_, entity) => {
        return [
          <a
            key={'btn_view_'+entity.id}
            onClick={() => {
              setCurrentRow(entity);
              setShowDetail(true);
            }}
          >
            查看详情
          </a>,
          ![3,4].includes(parseInt(entity.status)) && props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('MyLeaveManageModify') && <a
            key={'btn_edit_'+entity.id}
            onClick={() => {
              setCurrentRow(entity);
              setShowDetail(false);
              setShowEditForm(true);
              const attachment = entity.attachment!=''&&entity.attachment!='缺'?entity.attachment.split(';'):[];
              let images = [];
              for(var i in attachment){
                const image = {
                  lid: entity.id.toString(),
                  name: entity.userName,
                  status: 'done',
                  url: '/api/attachment/previewimg?attachment='+attachment[i],
                  thumbUrl: '/api/attachment/previewimg?attachment='+attachment[i],
                };
                images.push(image);
              }
              setDefaultImage(images);
            }}
          >
            修改
          </a>
        ];
      },
    },
  ];

  const reloadListData = () => {
    setTimeout(() => {
      actionRef.current?.reload?.();
    }, 200);
  };


  useImperativeHandle(ref, () => ({
    reload: (id: number) => {
      setParentId(id);
      actionRef.current?.reload();
    },
  }));

  useEffect(() => {
    dict({ id: -1, type: 'dict' }).then((res) => {
      console.log('dict:',res);
      let _leaveTypes = {};
      for(var i in res.data.leaveTypes){
        _leaveTypes[res.data.leaveTypes[i]] = {text:res.data.leaveTypes[i]};
      }
      setLeaveTypeArr(_leaveTypes);
      setStatusArr(res.data.status);
      setIsoutDict(res.data.isout);
      setTimeDict(res.data.times);
      setHoliday(res.data.holiday);
      setNonHoliday(res.data.noholiday);
      setNoHolidayTypes(res.data.noHolidayTypes);
    }); 
    
  },[])

  return (
    <>
      <ProTable<any, any>
        headerTitle="请销假列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          labelWidth: 120,
        }}
        params={initParams}
        request={(params, sorter, filter) => {
          let _leavetypes = params.leaveType ? params.leaveType.map((item: any) => item.value) : '';
          let _status = params.status ? params.status.map((item: any) => item.value) : '';
          params.leaveType = _leavetypes ? _leavetypes.join(',') : '';
          params.status = _status ? _status.join(',') : '';
          params.deptid = parentId;
          return leaveList(params);
        }}
        columns={columns}
        toolBarRender={() => [
          props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('MyLeaveManageExport') && <Button
            type="primary"
            key="btn_export"
            onClick={async () => modalRef?.current.setVisible(true)}
          >
            <ExportOutlined /> 数据导出
          </Button>
        ]}
      />
      <Drawer
        width={600}
        visible={showDetail}
        onClose={() => {
          setCurrentRow(undefined);
          setShowDetail(false);
        }}
        closable={true}
        extra={<Space key="btn_group">
          {[2,5,6].includes(parseInt(currentRow?.status)) && props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('MyLeaveManageCancel') && <Button key="btn_cancel" type="primary" onClick={handleCancel}>撤销</Button>}
          {currentRow?.status==1 && props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('MyLeaveManageReset') && <Button key="btn_reset" type="default" onClick={handleReset}>重置流程</Button>}
        </Space>    
        }
        footer={<Card title="审批流程"><Steps direction="vertical" current={currentRow?.flow.step+1}>{currentRow?.flow.approval.map((item,idx) => {
          let _title = item.status == 2 ? item.title + '（通过）':item.title;
          if (item.items != '') {
            _title = item.items.map((iitem) => {
              return iitem.status == 2 ? iitem.title + '（通过） | ' : iitem.title + ' | ';
            })
          } 
          return [2,5,6].includes(parseInt(currentRow?.status))?<Step key={'step_'+idx} title={_title} subTitle={item.date} description={item.speech} status='finish' />:<Step key={'step_'+idx} title={_title} subTitle={item.date} description={item.speech} />;
        })}</Steps><p>抄送：{currentRow?.flow.notify.map((item)=>item+'、')}</p></Card>}
      >
        {currentRow?.userId && (
          <ProDescriptions<TableListItem>
            column={2}
            title="详情"
            request={async () => ({
              data: currentRow || {},
            })}
            params={{
              id: currentRow?.userId,
            }}
            columns={columns as ProDescriptionsItemProps<TableListItem>[]}
          >
            {(currentRow?.attachment!=''&&currentRow?.attachment!='缺')&&<Image.PreviewGroup >{(currentRow?.attachment.split(';')||[]).map((item,idx) => <Image key={'ath_'+idx} width={100} src={'/api/attachment/previewimg?attachment='+item} />)}</Image.PreviewGroup>}
          </ProDescriptions>
        )}
      </Drawer>
      <DrawerForm
        title="请销假信息修改"
        width={600}
        visible={showEditForm}
        onVisibleChange={setShowEditForm}
        formRef={formRef}
        autoFocusFirstInput
        drawerProps={{
          destroyOnClose: true,
          onClose: () => {
            
          },
        }}
        submitter={{ searchConfig: { submitText: '提交' } }}
        submitTimeout={2000}
        onFinish={async (values) => {
          console.log(values.upload);
          if (values.upload && values.upload.length > 0) {
            let _attachment = [];
            for (let i in values.upload){
              _attachment.push(values.upload[i].response.data.url);
            }  
            values.attachment = _attachment;
          } else if(values.upload != undefined){
            values.attachment = '';
          }
          
          delete values.upload;
          values.act = 'edit';
          console.log(values);
          const result = await updateLeave(values);
          return true;
        }}
        onValuesChange={async (values) => {
          // console.log(values)
          // const _data = { ...dateGroup, ...values };
          // setDateGroup(_data);
          calctimes(formRef);
        }}
        initialValues={{
          id: currentRow?.id,
          thirdNo: currentRow?.thirdNo,
          userName: currentRow?.userName,
          department: currentRow?.department,
          leaveType: currentRow?.leaveType,
          leaveStartD: getDateOrTime(currentRow?.leaveStarttime,1),
          leaveStartT: getDateOrTime(currentRow?.leaveStarttime,2),
          leaveEndD: getDateOrTime(currentRow?.leaveEndtime,1),
          leaveEndT: getDateOrTime(currentRow?.leaveEndtime,2),
          leaveTimes: currentRow?.leaveTimes,
          isout: currentRow?.isout,
          destination: currentRow?.destination,
          leaveReason: currentRow?.leaveReason||' '
          // islock: currentRow ? currentRow.islock + '' : '0',
        }}
        // layout="vertical"
        // grid={true}
      >
          <ProForm.Group>
          <ProFormText width="md" name="id" hidden />
          <ProFormText width="md" name="thirdNo" hidden />
          <ProFormText width="xs" name="userName" label="姓名"  disabled />
          <ProFormText width="md" name="department" label="部门"  disabled />
          </ProForm.Group>
          <ProForm.Group>
          {
            currentRow?.leaveType!='销假'?<ProFormSelect
              width="sm"
              valueEnum={leaveTypeArr}
              name="leaveType"
              label="请假类型"
              allowClear={false}
              rules={[
                {
                  required: true,
                  message: '请选择请假类型！',
                },
              ]}
            />:<ProFormText width="md" name="leaveType" label="请假类型"  disabled />
          }
          
          </ProForm.Group>
          <ProForm.Group>
          <ProFormDatePicker name="leaveStartD" label="开始日期" />
          <ProFormSelect
            width="xs"
            valueEnum={timeDict}
            name="leaveStartT"
            label="开始时间"
            allowClear={false}
          />
          </ProForm.Group>
          <ProForm.Group>
          <ProFormDatePicker name="leaveEndD" label="结束日期" />
          <ProFormSelect
            width="xs"
            valueEnum={timeDict}
            name="leaveEndT"
            label="结束时间"
            allowClear={false}
          />
        </ProForm.Group>
        <ProForm.Group><ProFormDigit width="xs" label="请假天数" name="leaveTimes" min={0} max={365} fieldProps={{ readOnly: true }}   /></ProForm.Group>
        {currentRow?.leaveType!='销假' && <ProForm.Group>
          <ProFormSelect
            width="xs"
            valueEnum={isoutDict}
            name="isout"
            label="出行范围"
            allowClear={false}
            rules={[
              {
                required: true,
                message: '请选择出行范围！',
              },
            ]}
          />
          <ProFormText width="md" name="destination" label="出行位置"  />
        </ProForm.Group>}
        <ProForm.Group>
          <ProFormTextArea
            width="lg"
            name="leaveReason"
            label="请假事由"
        />
        </ProForm.Group> 
        <ProForm.Group>
        <MyUploadFile
            name="upload"
            label="附件上传"
            title=""
            colProps={{ md: 12, xl: 6 }}
            className="infouploaditem"
            max={10}
            multiple={true}
            accept="image/*"
            maxSize={10}
            listType="picture-card"
            uploadPath="leave"
            defaultImage={defaultImage}
            uploadType={1}
          />
        </ProForm.Group>
      </DrawerForm>
      <LeaveExportModal ref={modalRef} deptTreeIds={props.userAuth.departments} leaveTypeArr={leaveTypeArr} statusArr={statusArr} isoutDict={isoutDict} />
    </>
  );
});

export default List;
