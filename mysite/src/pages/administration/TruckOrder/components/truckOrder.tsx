/* eslint-disable react/jsx-key */
/* eslint-disable @typescript-eslint/ban-types */
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, useState } from 'react';
import { rule, update, download, getStaff, getDriver, getLicence, getCarEndMile, updateNewStartPlace } from './service';
import { Button, Table, message } from 'antd';
import type { ProFormInstance, ProDescriptionsActionType } from '@ant-design/pro-components';
import { DrawerForm, ProFormSelect, ProFormText, ModalForm, ProFormDateTimePicker, ProForm, ProFormTextArea, ProFormDigit, ProDescriptions } from '@ant-design/pro-components';
import styles from './style.less';
import ReactToPrint from "react-to-print"; //打印
import { SaveOutlined } from '@ant-design/icons';

const ListTable: React.FC = () => {

  const [drawerVisit, setDrawerVisit] = useState(false);//编辑框显示
  const [drawerView, setDrawerView] = useState(false);//查看框显示
  const [currentRow, setCurrentRow] = useState(false);//获取编辑的数据信息
  const [driver, setDriver] = useState(false);//编辑框-司机信息

  const [selectedRowsState, setSelectedRows] = useState<any[]>([]);// 表格选中

  const refNewPlace = useRef<any>();
  const formRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const descriptionRef = useRef<ProDescriptionsActionType>();
  const componentRef = useRef();//打印参数

  // 打印页面 lebal 字体 加粗
  const labelStyle = {
    color: '#000',
    fontWeight: 'bold',
  };

  // 导出数据
  const handleDownload = async (params: {}) => {
    download(params);
  };

  const handleUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      if (updateRow == undefined) {
        message.success('数据错误');
        return;
      } else {
        result = await update({
          id: updateRow.id,
          values,
        });
      }
      hide();
      return result;
    } catch (error) {
      message.success('保存失败！');
      return false;
    }
  }

  const columns: ProColumns<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      width: 80,
      search: false,
    },
    {
      title: '申请人',
      search: false,
      width: 60,
      fixed: 'left',
      dataIndex: 'opt_name',
    },
    {
      title: '审批编号',
      search: false,
      width: 140,
      dataIndex: 'order_no',
    },
    {
      title: '创建时间',
      search: false,
      width: 120,
      ellipsis: true,
      dataIndex: 'created',
    },
    {
      title: '所在部门',
      search: false,
      width: 130,
      dataIndex: 'dep_name',

    },
    {
      title: '用车事由',
      search: false,
      width: 220,
      ellipsis: true,
      copyable: true,
      dataIndex: 'reason',
    },
    {
      title: '同行人',
      search: false,
      width: 120,
      copyable: true,
      ellipsis: true,
      dataIndex: 'companyNames',
    },
    {
      title: '开始时间',
      width: 120,
      ellipsis: true,
      search: false,
      dataIndex: 'start_time1',
    },
    {
      title: '开始时间',
      width: 120,
      hideInTable: true,
      ellipsis: true,
      valueType: 'date',
      dataIndex: 'start_time',
    },
    {
      title: '结束时间',
      width: 120,
      ellipsis: true,
      search: false,
      dataIndex: 'end_time',
    },
    {
      title: '结束时间',
      width: 120,
      hideInTable: true,
      ellipsis: true,
      valueType: 'date',
      dataIndex: 'end_time1',
    },
    {
      title: '超时',
      search: false,
      width: 120,
      dataIndex: 'over_time',
    },
    {
      title: '出发地点',
      search: false,
      width: 150,
      ellipsis: true,
      copyable: true,
      dataIndex: 'start_place',
    },
    {
      title: '目的地',
      search: false,
      width: 150,
      ellipsis: true,
      copyable: true,
      dataIndex: 'destination',
    },
    {
      title: '状态',
      // search: false,
      width: 120,
      ellipsis: true,
      dataIndex: 'st',
      filters: true,
      onFilter: true,
      valueType: 'select',
      valueEnum: {
        0: { text: '驳回', status: 'Default' },
        1: { text: '审核中', status: 'Default' },
        2: { text: '任务中', status: 'Processing' },
        3: { text: '任务暂时保存', status: 'Success' },
        4: { text: '结束派车', status: 'Error' },
        5: { text: '确认结束', status: 'Error' },
      },
    },
    {
      title: '审批意见',
      search: false,
      width: 220,
      ellipsis: true,
      dataIndex: 'remark',
    },
    {
      title: '指派车牌号',
      width: 80,
      order: 2,
      dataIndex: 'car_licence',
    },
    {
      title: '司机',
      width: 60,
      order: 1,
      dataIndex: 'driver_name',
    },
    {
      title: '司机电话',
      search: false,
      width: 120,
      ellipsis: true,
      dataIndex: 'driver_mobile',
    },
    {
      title: '出发里程',
      search: false,
      width: 120,
      ellipsis: true,
      dataIndex: 'start_mile',
    },
    {
      title: '结束里程',
      search: false,
      width: 120,
      ellipsis: true,
      dataIndex: 'end_mile',
    },
    {
      title: '本次里程',
      search: false,
      ellipsis: true,
      width: 120,
      dataIndex: 'mile',
    },
    {
      title: '停车费',
      search: false,
      ellipsis: true,
      width: 80,
      dataIndex: 'park_fee',
    },
    {
      title: '过路费',
      search: false,
      ellipsis: true,
      width: 80,
      dataIndex: 'toll',
    },
    {
      title: '总费用',
      search: false,
      ellipsis: true,
      width: 100,
      dataIndex: 'total_fee',
    },
    {
      title: '满意度',
      search: false,
      ellipsis: true,
      width: 60,
      dataIndex: 'comment_tp',
    },
    {
      title: '评论',
      search: false,
      ellipsis: true,
      width: 120,
      dataIndex: 'comment',
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      align: 'center',
      width: 90,
      fixed: 'right',
      render: (_, entity) => [
        <a
          key="view"
          onClick={() => {
            setDrawerView(true);
            descriptionRef.current?.reload();
            console.log('entity', entity);
            setCurrentRow(entity);
          }}
        >
          查看
        </a>,
        <a
          key="edit"
          onClick={() => {
            setCurrentRow(entity);
            setDrawerVisit(true);
          }}
        >
          编辑
        </a>,
      ],
    },

  ];

  return (
    <>
      <ProTable<any, any>
        columns={columns}
        actionRef={actionRef}
        request={rule}
        scroll={{ x: 1300 }}
        rowKey="id"
        headerTitle="派车订单列表"
        rowSelection={{
          selections: [Table.SELECTION_ALL, Table.SELECTION_INVERT],
          defaultSelectedRowKeys: [],
          onChange: (_selRowKey, selectedRows) => {
            setSelectedRows(_selRowKey);
          },
        }}
        search={{
          defaultCollapsed: false,
          labelWidth: 120,
          optionRender: (searchConfig, formProps, dom) => [
            ...dom.reverse(),
            <Button
              key="out"
              onClick={() => {
                const values = searchConfig?.form?.getFieldsValue();
                values.selId = selectedRowsState;
                handleDownload(values);
                setSelectedRows([]);
                actionRef.current.clearSelected();// 清除打勾选项
                actionRef.current?.reload?.();
              }}
            >
              导出
            </Button>,
          ],
        }}

        pagination={{
          pageSize: 15,
        }}
        tableAlertRender={false}
      />
      {/* 查看 */}
      <ModalForm
        onVisibleChange={setDrawerView}
        title="查看车辆派单信息"
        visible={drawerView}
        autoFocusFirstInput
        modalProps={{
          destroyOnClose: true,
          onCancel: () => console.log('run'),
        }}

        formRef={refNewPlace}
        initialValues={{
          ...currentRow,
        }}

        submitter={{
          render: () => {
            return [
              <div>
                <ReactToPrint
                  trigger={() => <Button key="print">打印</Button>}
                  content={() => componentRef.current}
                  pageStyle="marginLeft:25px;marginTop:15px;"
                />
              </div>
            ];
          },
        }}

        grid={true}
        layout="inline"
      >
        {/* 修改出发地 */}
        <div className={styles.update}>
          <ProFormText
            width="xl"
            name="new_start_place"
            label="修改出发地"
            placeholder="请输入出发地"
          // initialValue={startPlace}
          />
          <Button icon={<SaveOutlined />} type="primary"
            onClick={async () => {
              const updateId = refNewPlace.current?.getFieldValue('id');
              const oldPlace = refNewPlace.current?.getFieldValue('start_place');
              const newPlace = refNewPlace.current?.getFieldValue('new_start_place');
              if (!newPlace) {
                message.success('请输入出发地');
                return false;
              }
              // if (oldPlace == newPlace) {
              //   message.success('修改失败，出发地一致');
              //   return false;
              // }

              const newPlaceRes = await updateNewStartPlace({
                id: updateId,
                oldPlace: oldPlace,
                newPlace: newPlace,
              });
              if (newPlaceRes) {
                message.success('保存成功！');
                // 修改出发地 打印内容 重新->赋值
                currentRow.start_place = newPlace;
                setCurrentRow(currentRow);
                setTimeout(() => {
                  descriptionRef.current?.reload()
                }, 100);

                actionRef.current?.reloadAndRest?.();//刷新数据
                // 清空
                refNewPlace.current.resetFields();
              }
            }}

          >确定</Button>
          <ProFormText name="start_place" hidden />
          <ProFormText name="id" hidden />
        </div>

        <div ref={componentRef} className={styles.center}>
          <ProDescriptions column={6} actionRef={descriptionRef} title="福州日报社用车审批单" className={styles.printBox}
            // editable={{
            //   onSave: async (keypath, newInfo, oriInfo) => {
            //     console.log(keypath, newInfo, oriInfo);
            //     return true;
            //   },
            // }}
            // columns={[
            //   {
            //     title: '编号',
            //     key: 'order_no',
            //     dataIndex: 'order_no',
            //     editable: false,
            //     span: 3,
            //     labelStyle: labelStyle
            //   },
            //   {
            //     title: '时间',
            //     key: 'created',
            //     dataIndex: 'created',
            //     editable: false,
            //     span: 3,
            //     labelStyle: labelStyle
            //   },
            // ]}
            request={async () => {
              return { success: true, data: currentRow };
            }}
          >
            <ProDescriptions.Item span={3} label="编号" labelStyle={labelStyle} dataIndex="order_no" />
            <ProDescriptions.Item span={3} label="时间" labelStyle={labelStyle} dataIndex="created" />
            <ProDescriptions.Item span={3} label="类型" labelStyle={labelStyle} dataIndex="tp" />
            <ProDescriptions.Item span={3} label="车牌" labelStyle={labelStyle} dataIndex="car_licence" />
            <ProDescriptions.Item span={3} label="驾驶员" labelStyle={labelStyle} dataIndex="driver_name" />
            <ProDescriptions.Item span={3} label="司机电话" labelStyle={labelStyle} dataIndex="driver_mobile" />
            <ProDescriptions.Item span={2} label="用车部门" labelStyle={labelStyle} dataIndex="dep_name" />
            <ProDescriptions.Item span={2} label="用车人" labelStyle={labelStyle} dataIndex="opt_name" />
            <ProDescriptions.Item span={2} label="联系电话" labelStyle={labelStyle} dataIndex="opt_mobile" />
            <ProDescriptions.Item span={6} label="同行人" labelStyle={labelStyle} dataIndex="companyNames" />
            <ProDescriptions.Item span={3} label="审批人" labelStyle={labelStyle} dataIndex="checkMan" />
            <ProDescriptions.Item span={3} label="派车事由" labelStyle={labelStyle} dataIndex="reason" />
            <ProDescriptions.Item span={3} label="出发点" labelStyle={labelStyle} dataIndex="start_place" />
            <ProDescriptions.Item span={3} label="目的地" labelStyle={labelStyle} dataIndex="destination" />
            <ProDescriptions.Item span={3} label="出车时间" labelStyle={labelStyle} dataIndex="start_time1" />
            <ProDescriptions.Item span={3} label="结束时间" labelStyle={labelStyle} dataIndex="end_time1" />
            <ProDescriptions.Item span={2} label="出车里程" labelStyle={labelStyle} dataIndex="start_mile" />
            <ProDescriptions.Item span={2} label="结束里程" labelStyle={labelStyle} dataIndex="end_mile" />
            <ProDescriptions.Item span={2} label="总里程" labelStyle={labelStyle} dataIndex="mile" />
            <ProDescriptions.Item span={2} label="停车费" labelStyle={labelStyle} dataIndex="park_fee" />
            <ProDescriptions.Item span={2} label="高速通行费" labelStyle={labelStyle} dataIndex="toll" />
            <ProDescriptions.Item span={2} label="总费用" labelStyle={labelStyle} dataIndex="total_fee" />
            <ProDescriptions.Item span={4} label="" />
            <ProDescriptions.Item span={2} label="复核" labelStyle={labelStyle} />
          </ProDescriptions>
        </div>
      </ModalForm>

      {/* 编辑 */}
      <DrawerForm
        onVisibleChange={setDrawerVisit}
        title="编辑车辆派单信息"
        visible={drawerVisit}
        formRef={formRef}
        autoFocusFirstInput
        drawerProps={{
          destroyOnClose: true,
          onClose: () => {
          },
        }}
        onFinish={async (values: any) => {
          message.success('提交成功');
          const result = await handleUpdate(currentRow, values);
          if (result) {
            if (result.errorCode) {
              return false;
            }
            message.success('保存成功！');
            actionRef.current?.reloadAndRest?.();//刷新数据
          }
        }}
        initialValues={{
          ...currentRow,
          companyUserid: currentRow ? currentRow.companyUserid.split(",") : '',
        }}
      >
        <ProForm.Group>
          <ProFormText disabled width="md" name="order_no" label="审批编号" />

          <ProFormText disabled width="md" name="dep_name" label="所在部门" />
        </ProForm.Group>

        <ProForm.Group>
          <ProFormText disabled width="md" name="opt_name" label="申请人" />
          <ProFormSelect
            options={[
              { value: '0', label: '驳回', },
              { value: '1', label: '审核中', },
              { value: '2', label: '任务中', },
              { value: '3', label: '任务暂时保存', },
              { value: '4', label: '结束派车', },
              { value: '5', label: '确认结束', },
            ]}
            disabled width="md" name="st" label="状态" />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormDateTimePicker width="md" name="created" label="创建时间" rules={[{ required: true, message: '创建时间' }]} />
          <ProFormSelect
            width="md"
            name="companyUserid"
            label="同行人"
            showSearch
            debounceTime={300}
            fieldProps={{
              mode: 'multiple',
            }}
            request={async () => {
              const statff = await getStaff({
                id: []
              });
              return statff.data;
            }}
            placeholder="请选择同行人"
            rules={[{ required: true, message: '请选择同行人' }]}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormText width="md" name="start_place" label="出发地" rules={[{ required: true, message: '请输入出发地' }]} />

          <ProFormText width="md" name="destination" label="目的地" placeholder="请输入目的地" rules={[{ required: true, message: '请输入目的地' }]} />
        </ProForm.Group>

        <ProForm.Group>
          <ProFormDateTimePicker width="md" name="start_time" label="出发时间" fieldProps={{
            format: (value) => value.format('YYYY-MM-DD HH:mm'),
          }} rules={[{ required: true, message: '请输入出发时间' }]} />
          <ProFormDateTimePicker width="md" name="end_time" label="结束时间" fieldProps={{
            format: (value) => value.format('YYYY-MM-DD HH:mm'),
          }} />
        </ProForm.Group>

        <ProForm.Group>
          <ProFormSelect
            options={[
              { value: 1, label: '5座', },
              { value: 2, label: '7座', },
              { value: 3, label: '11座', },
              { value: 4, label: '14座', }
            ]}
            width="md"
            name="tp"
            label="车辆类型"
            rules={[{ required: true, message: '请输入车辆类型' }]}
          />
          {/* <ProFormText  width="md" name="car_licence" label="车牌"  rules={[{ required: true, message: '请输入车牌' }]}/> */}
          <ProFormSelect
            width="md"
            name="car_id"
            label="车牌"
            showSearch
            debounceTime={300}
            request={async () => {
              const data = await getLicence({ id: [] });
              return data.data;
            }}
            fieldProps={{
              onChange: async (value) => {
                const datamile = await getCarEndMile({ carId: value });
                formRef?.current?.setFieldsValue({
                  start_mile: datamile.data.end_mile,
                });

              }
            }}
            placeholder="请选择车牌"
            rules={[{ required: true, message: '请选择车牌' }]}
          />

        </ProForm.Group>
        <ProForm.Group>
          <ProFormSelect width="md" name="driver" label="司机" showSearch debounceTime={300}
            request={async () => {
              const data = await getDriver({ id: [] });
              const obj = {};
              if (data.data.length) {
                const tempDriver = data.data;

                tempDriver.map(function (e) {
                  obj[e.value] = e.mobile;
                })
              }
              setDriver(obj);
              return data.data;
            }}
            onChange={(value: string) => {
              //更新司机电话
              formRef?.current?.setFieldsValue({
                driver_mobile: driver[value],
              });
            }}
            placeholder="请选择司机"
            rules={[{ required: true, message: '请选择司机' }]}
          />
          <ProFormText width="md" name="driver_mobile" label="司机电话" disabled />

        </ProForm.Group>
        <ProForm.Group>
          <ProFormDigit width="md" label="出发里程" name="start_mile" min={0} max={99999999999} fieldProps={{ precision: 0 }}
            onChange={(value: number) => {
              const end_mile = formRef.current?.getFieldsFormatValue?.().end_mile;
              formRef?.current?.setFieldsValue({
                mile: (end_mile - value),
              });
            }} />
          <ProFormDigit width="md" label="结束里程" name="end_mile" min={0} max={99999999999} fieldProps={{ precision: 0 }}
            onChange={(value: number) => {
              const start_mile = formRef.current?.getFieldsFormatValue?.().start_mile;
              formRef?.current?.setFieldsValue({
                mile: (value - start_mile),
              });
            }} />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormDigit width="md" label="本次里程" name="mile" disabled />
          <ProFormDigit width="md" label="停车费" name="park_fee" min={0} max={99999999999} fieldProps={{ precision: 2 }} />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormSelect
            options={[
              { value: '0', label: '无' },
              { value: '1', label: '有', },
              { value: '2', label: '无', }
            ]}
            width="md"
            name="toll"
            label="过路费"
          />
          <ProFormText width="md" name="total_fee" label="总费用" />
        </ProForm.Group>

        <ProForm.Group>
          <ProFormTextArea width="md" label="用车事由" name="reason" />
          <ProFormTextArea width="md" label="审批意见" name="remark" />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormSelect
            options={[
              { value: '1', label: '满意' },
              { value: '2', label: '一般', },
              { value: '3', label: '不满意', }
            ]}
            width="md"
            name="comment_tp"
            label="满意度"
          />
          <ProFormTextArea width="md" label="评论内容" name="comment" />
        </ProForm.Group>
      </DrawerForm>
    </>
  );
};

export default ListTable;
