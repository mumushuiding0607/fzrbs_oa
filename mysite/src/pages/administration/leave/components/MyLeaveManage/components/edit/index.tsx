import { DrawerForm, ProForm, ProFormSelect, ProFormText, ProFormDatePicker,ProFormDigit, ProFormTextArea } from '@ant-design/pro-components';
import type { ProFormInstance } from '@ant-design/pro-components';
import { Form, message } from 'antd';
import React, { useImperativeHandle, useRef, useState } from 'react';
import type { TableListItem, TableListPagination, DateItem } from '../../../data';

import MyUploadFile from '@/components/MyUploadFile';
import moment from "moment";

export type EditFormProps = {
  id: number;
  channelId: number;
  reload?: () => void;
};

const EditForm = React.forwardRef((props: EditFormProps, ref) => {
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const [defaultImage, setDefaultImage] = useState<any[]>([]);
  const [flag, setFlag] = useState<boolean>(false);

  const [holiday, setHoliday] = useState<any[]>([]);
  const [nonHoliday, setNonHoliday] = useState<any[]>([]);
  const [noHolidayTypes, setNoHolidayTypes] = useState<any[]>([]);
  const noHolidayTypesRef = useRef(noHolidayTypes);
  const holidayRef = useRef(holiday);
  const nonHolidayRef = useRef(nonHoliday);
  const [isahead, setIsahead] = useState<boolean>(false);
  const [beoverdue, setBeoverdue] = useState<boolean>(false);
  noHolidayTypesRef.current = noHolidayTypes;
  holidayRef.current = holiday;
  nonHolidayRef.current = nonHoliday;
  const initParams = {module:'own'};
  const leaveTypeDict = {
    非工作日: {
      text: '非工作日',
    },
    年假: {
      text: '年假',
    },
    事假: {
      text: '事假',
    },
    病假: {
      text: '病假',
    },
    调休: {
      text: '调休',
    },
    公务: {
      text: '公务',
    },
    产假: {
      text: '产假',
    },
    陪产假: {
      text: '陪产假',
    },
    婚假: {
      text: '婚假',
    },
    丧假: {
      text: '丧假',
    },
    探亲: {
      text: '探亲',
    },
    工伤: {
      text: '工伤',
    },
    独生子女护理假: {
      text: '独生子女护理假',
    },
    育儿假: {
      text: '育儿假',
    },
    销假: {
      text: '销假',
    },
  };
  const isoutDict = {
    1: {
      text: '出省',
    },
    2: {
      text: '市内',
    },
    3: {
      text: '出市',
    },
  };
  const timeDict = {
    1: {
      text: '上午',
    },
    2: {
      text: '下午',
    },
    3: {
      text: '晚上',
    },
  };
  const isBeoverdue = (d:string) => {
    return (new Date(d)).getTime() > (new Date()).getTime() ? true:false;
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


  const handleAddAndUpdate = async (id: number, values: any) => {
    const hide = message.loading('正在保存');
    try {
      values.channelid = props.channelId;
      let result;
      // if (id == 0) {
      //   result = await addRule({
      //     values,
      //   });
      // } else {
      //   result = await updateRule({
      //     id: id,
      //     values,
      //   });
      // }
      hide();
      return result;
    } catch (error) {
      message.error('保存失败！');
      return false;
    }
  };

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
  const getBeoverdueday:any = (_date:string, _n:number, _t:number) => {
    if (_t > 1) return getWorkday(addDate(_date, _n), _n);
    let dd = new Date(_date.replace(/-/g, '/'));
    let weekDay = dd.getDay();
    // console.log(holidayRef.current,nonHolidayRef.current);
    if (holidayRef.current.includes(_date)) return getWorkday(addDate(_date, _n), _n);
    if ((weekDay == 0 || weekDay == 6) && !nonHolidayRef.current.includes(_date)) return getWorkday(addDate(_date, _n), _n);
    console.log(_date);
    return _date;
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
    console.log(dayDiff);
    // setDays(dayDiff || 0);
    thisRef.current?.setFieldsValue({leaveTimes:dayDiff || 0});
  };

  useImperativeHandle(ref, () => ({
    setVisible: (visible: boolean) => {
      setShowForm(visible);
      setFlag(true);
    },
  }));

  return (
    <>
      <DrawerForm
        title="提交请假申请"
        width={600}
        visible={showForm}
        onVisibleChange={setShowForm}
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
          if (values.upload && values.upload.length > 0) {
            values.image = values.upload[0].response.data.url;
          } else {
            values.image = '';
          }
          delete values.upload;
          const result = await handleAddAndUpdate(props.id, values);
          if (result) {
            if (result.errorCode) {
              message.warn(result.errorMessage);
              return false;
            }
            message.success('保存成功！');
          }
          if (props.reload) {
            setFlag(false);
            props.reload();
          }
          return true;
        }}
        request={async () => {
          if (props.id > 0) {
            const info = await one({ id: props.id });
            setUeditorData(info.data.content);
            if (info.data.image != '') {
              const image = {
                uid: info.data.id.toString(),
                name: info.data.title,
                status: 'done',
                url: info.data.image,
                thumbUrl: info.data.image,
              };
              setDefaultImage([image]);
            } else {
              setDefaultImage([]);
            }
            return info.data;
          } else {
            setUeditorData('');
            setDefaultImage([]);
          }
          return {};
        }}
        onValuesChange={async (values) => {
          // console.log(values)
          // const _data = { ...dateGroup, ...values };
          // setDateGroup(_data);
          calctimes(formRef);
        }}
        // layout="vertical"
        // grid={true}
      >
        <ProForm.Group>
          <ProFormSelect
            width="md"
            valueEnum={leaveTypeDict}
            name="leaveType"
            label="请假类型"
            allowClear={false}
            rules={[
              {
                required: true,
                message: '请选择请假类型！',
              },
            ]}
          />
          </ProForm.Group>
          <ProForm.Group>
          <ProFormSelect
            width="sm"
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
          <ProFormText
            name="destination"
            width="sm"
            label="目的地"
            placeholder="出省或出市请填写目的地"
            />
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
        <ProForm.Group>
          <ProFormTextArea
            width="lg"
          name="leaveReason"
          label="请假事由"
          placeholder="请输入请假事由"
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
    </>
  );
});

export default EditForm;
