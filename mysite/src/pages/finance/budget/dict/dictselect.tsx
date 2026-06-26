import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import {getBykeyword, getdictlist, deldict} from './service'
import { Button, Divider, Form, Input, InputRef, Modal, Select, Space, Table, Tag, message } from 'antd';
import { EditOutlined, PlusOutlined, DeleteOutlined } from '@ant-design/icons';

import UserAutocomplete from '../common/userAutocomplete';
import Adddict from './adddict';
const row:CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  width:'100%',
  padding:'0 10px',

}
const formitem:CSSProperties={
  flex:2
}
const Dictselect:React.FC<{needPower?:boolean,type?:string,subtype?:string,value?:any,onChange?:any, onSelect?:Function,disabled?:boolean,needAddItem?:boolean,showall?:boolean,userid?:string,creator?:string,multiple?:boolean,agentid?:any,initialValue?:any,returnLabel?:boolean,style?:any,placeholder?:any,labelInValue?:boolean}> =  ({placeholder,returnLabel=false,style,needPower=true,agentid,showall=false,type,subtype,value,onChange,onSelect,disabled,needAddItem=true,userid,multiple=false,creator,initialValue,labelInValue})=>{

  const [options ,setOptions] = useState<any['options']>([])
  const [data, setData] = useState<any>({id:0});
  const [form] = Form.useForm();
  var [fkey,setFkey]=useState(0)
  const isInternalChange = useRef(false);
  const [defaultValue,setDefaultValue]=useState<any>(initialValue)
  const [addModalVisible, setAddModalVisible] = useState(false);
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [editParams, setEditParams] = useState<any>({});
  
  const getdata = ()=>{
    getBykeyword({keyword:type,showall,userid,creator,agentid,subtype}).then((res:any)=>{

      if (res) {
        res.map((e:any)=>{
          if (!e.value && (e.value!=0||e.value!='0')) e.value = e.id
          return e
        })
      }
      setOptions(res)
      
  
    })
  }
  


// 处理外部传入的 value - 简化版，只注入值不查询
  const handleExternalValue = (val: any) => {
    console.log('Dictselect4 value changed:', val);
    isInternalChange.current = true;
    if (multiple){
            setDefaultValue(val&&val.split?val.split(','):undefined)
    }else if (labelInValue && val && typeof val === 'object' && 'value' in val) {
            // labelInValue模式下，value是{value, label}对象
            setDefaultValue(val)
    }else{
            setDefaultValue(val)
    }

  };
  
  useEffect( ()=>{
    getdata()
  },[type, subtype, agentid, showall, userid, creator])

  // 监听外部传入的 value 变化
  useEffect(() => {
    // 始终更新defaultValue，确保外部值变化时能正确显示
    handleExternalValue(value);
  }, [value]);

  const handleSelect = (e:any)=>{
    // 标记为内部变化
    isInternalChange.current = true;
    
    if (!multiple) {
      
      const indx = options.findIndex((x:any)=>x.value==e)
      setData(options[indx])
      setFkey(++fkey)
      form.setFieldsValue(options[indx])
      onChange && onChange(e,options[indx])
      onSelect && onSelect(options[indx])
    }
    
      
  }
  const onValChange = (e:any)=>{
    
    if (multiple){
      e = (e||[]).filter((x:any)=>x.value||x.value==0)
      onChange && onChange(e)
    }else{
      const indx = options.findIndex((x:any)=>x.value==e)
      onChange && onChange(e,options[indx])

    }
    
  }

  // 处理新增字典项
  const handleAddDict = () => {
    setAddModalVisible(true);
  };

  // 新增成功后重新加载选项
  const handleAddSuccess = (newData: any) => {
    setAddModalVisible(false);
    getdata();
    // 选中新添加的项
    if (newData) {
      setDefaultValue(newData.value);
      onChange && onChange(newData.value, newData);
    }
  };

  // 处理修改字典项
  const handleEditDict = () => {
    setEditParams({ type, subtype, agentid });
    setEditModalVisible(true);
  };

  // 修改成功后重新加载选项
  const handleEditSuccess = () => {
    getdata();
  };

  // 渲染下拉菜单 - 显示新增和修改按钮
  const dropdownRender = (menu:any)=>{
    return (<>
      {menu}
      {
        needAddItem && 
        <>
          <Divider style={{ margin: '8px 0' }} />
          <Button 
            type="primary" 
            onClick={handleAddDict}
            style={{ width: '100%', marginBottom: '8px' }}
          >
            <PlusOutlined /> 新增
          </Button>
          <Button 
            onClick={handleEditDict}
            style={{ width: '100%' }}
          >
            <EditOutlined /> 修改
          </Button>
        </>
      }
    </>)
  }

  return (
    <div>

        <Select
            disabled={disabled}
            style={style}
            mode={multiple?'multiple':undefined}
            showSearch
            maxTagCount={1}
            placeholder={placeholder||type}
            filterOption={(input, option:any) => (option?.label ?? '').includes(input)}
            options={options}
            labelInValue = {labelInValue !== undefined ? labelInValue : (multiple?true:false)}
            value={multiple?((value && value.split?value.split(',').map((e: any)=>parseFloat(e)):(value||undefined))):defaultValue}
            onSelect={handleSelect}
            dropdownRender={dropdownRender}
            allowClear
            autoClearSearchValue
            onChange={onValChange}
 
          />

        {/* 新增字典弹窗 */}
        <Modal
          title={`新增${type}`}
          visible={addModalVisible}
          onCancel={() => setAddModalVisible(false)}
          footer={null}
          width={600}
        >
          <Adddict 
            key={addModalVisible ? 'add' : 'hidden'}
            data={{ type: type, subtype: subtype }} 
            agentid={agentid}
            onChange={handleAddSuccess}
          />
        </Modal>

        {/* 修改字典弹窗 */}
        <Modal
          title={`${type} - 字典管理`}
          visible={editModalVisible}
          onCancel={() => setEditModalVisible(false)}
          footer={null}
          width={900}
          destroyOnClose
        >
          <DictListModal 
            type={type}
            subtype={subtype}
            agentid={subtype ? undefined : agentid}
            onSuccess={handleEditSuccess}
          />
        </Modal>

    </div>
  )
}

// 字典列表弹窗组件
const DictListModal: React.FC<{
  type?: string;
  subtype?: string;
  agentid?: any;
  onSuccess?: () => void;
}> = ({ type, subtype, agentid, onSuccess }) => {
  const [loading, setLoading] = useState(false);
  const [dataSource, setDataSource] = useState<any[]>([]);
  const [pagination, setPagination] = useState({ current: 1, pageSize: 10, total: 0 });
  const [editRecord, setEditRecord] = useState<any>(null);
  const [modalVisible, setModalVisible] = useState(false);

  const fetchData = async (page: number = 1, pageSize: number = 10) => {
    setLoading(true);
    try {
      const params: any = {
        current: page,
        pageSize: pageSize,
        type: type,
      };
      // 如果有subtype，则忽略agentid
      if (subtype) {
        params.subtype = subtype;
      } 
      
      const res: any = await getdictlist(params);
      if (res) {
        setDataSource(res.data || []);
        setPagination({
          current: page,
          pageSize: pageSize,
          total: res.total || 0,
        });
      }
    } catch (error) {
      console.error('Failed to fetch dict list:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [type, subtype, agentid]);

  const handleTableChange = (paginationConfig: any) => {
    fetchData(paginationConfig.current, paginationConfig.pageSize);
  };

  const handleEdit = (record: any) => {
    setEditRecord(record);
    setModalVisible(true);
  };

  const handleDelete = (record: any) => {
    Modal.confirm({
      title: '确定要删除该字典项吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        try {
          const res =await deldict({ id: record.id });
          if (res.errorMessage){
            Modal.error({ 
              title: res.errorMessage,
            });
            return
          }
          message.success('删除成功');
          fetchData(pagination.current, pagination.pageSize);
          onSuccess?.();
        } catch (error) {
          message.error('删除失败');
        }
      },
    });
  };

  const handleModalSuccess = () => {
    setModalVisible(false);
    setEditRecord(null);
    fetchData(pagination.current, pagination.pageSize);
    onSuccess?.();
  };

  const columns = [
    {
      title: 'ID',
      dataIndex: 'id',
      key: 'id',
      width: 60,
    },
    {
      title: '类型',
      dataIndex: 'type',
      key: 'type',
      width: 100,
    },
    {
      title: '子类型',
      dataIndex: 'subtype',
      key: 'subtype',
      width: 100,
    },
    {
      title: '名称',
      dataIndex: 'label',
      key: 'label',
    },
    {
      title: '值',
      dataIndex: 'value',
      key: 'value',
    },
    {
      title: '操作',
      key: 'action',
      width: 120,
      render: (_: any, record: any) => (
        <Space>
          <Button 
            type="link" 
            size="small" 
            icon={<EditOutlined />}
            onClick={() => handleEdit(record)}
          >
            编辑
          </Button>
          <Button 
            type="link" 
            size="small" 
            danger 
            icon={<DeleteOutlined />}
            onClick={() => handleDelete(record)}
          >
            删除
          </Button>
        </Space>
      ),
    },
  ];

  return (
    <div>
      <Table
        columns={columns}
        dataSource={dataSource}
        rowKey="id"
        loading={loading}
        pagination={{
          ...pagination,
          showSizeChanger: true,
          showQuickJumper: true,
          showTotal: (total) => `共 ${total} 条`,
        }}
        onChange={handleTableChange}
        size="small"
      />
      
      <Modal
        title={editRecord ? '编辑字典' : '新增字典'}
        visible={modalVisible}
        onCancel={() => {
          setModalVisible(false);
          setEditRecord(null);
        }}
        footer={null}
        width={600}
      >
        <Adddict 
          data={editRecord || { type, subtype }} 
          agentid={agentid}
          onChange={handleModalSuccess}
        />
      </Modal>
    </div>
  );
};

export default Dictselect
