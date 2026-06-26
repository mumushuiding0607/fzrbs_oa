import React, { useState, useEffect } from 'react';
import { Table, Input, Button, Space, message, Form } from 'antd';
import { EditOutlined, DeleteOutlined, CheckOutlined, CloseOutlined, PlusOutlined } from '@ant-design/icons';
import { getpowers } from '../role/service';

interface OrgTableModalProps {
  tableData: any[];
  pagination: {
    current: number;
    pageSize: number;
    total: number;
  };
  loading: boolean;
  onChange: (paginationConfig: any) => void;
  onSave: (values: any) => Promise<boolean>;
  onDelete: (record: any) => void;
}

const OrgTableModal: React.FC<OrgTableModalProps> = ({
  tableData,
  pagination,
  loading,
  onChange,
  onSave,
  onDelete,
}) => {
  const [editingKey, setEditingKey] = useState<string>('');
  const [form] = Form.useForm();
  const [powers, setPowers] = useState<string[]>([]);
  const [hasDeletePower, setHasDeletePower] = useState(false);

  useEffect(() => {
    checkPowers();
  }, []);

  const checkPowers = async () => {
    try {
      const powerRes = await getpowers({ menu: 'orgcascade' });
      if (powerRes && powerRes.data) {
        const powerList = powerRes.data || [];
        setPowers(powerList);
        setHasDeletePower(powerList.includes('delete'));
      }
    } catch (error) {
      console.error('Failed to get powers:', error);
    }
  };

  const isEditing = (record: any) => record.id === editingKey || record.key === editingKey;

  const handleEdit = (record: any) => {
    form.setFieldsValue({ ...record });
    const key = record.id || record.key;
    setEditingKey(key);
  };

  const handleCancel = () => {
    setEditingKey('');
    form.resetFields();
  };

  const handleSave = async (record: any) => {
    try {
      const values = await form.validateFields();
      const key = record.id || record.key;
      const saveData = { ...values, id: key };
      const success = await onSave(saveData);
      if (success) {
        setEditingKey('');
        form.resetFields();
      }
    } catch (error) {
      console.error('Validation failed:', error);
    }
  };

  const handleAdd = () => {
    form.setFieldsValue({ id: undefined, label: '', value: '' });
    setEditingKey('new');
  };

  const handleAddSave = async () => {
    try {
      const values = await form.validateFields();
      const success = await onSave(values);
      if (success) {
        setEditingKey('');
        form.resetFields();
      }
    } catch (error) {
      console.error('Validation failed:', error);
    }
  };

  const handleAddCancel = () => {
    setEditingKey('');
    form.resetFields();
  };

  const columns = [
    {
      title: '名称',
      dataIndex: 'label',
      key: 'label',
      render: (_: any, record: any) => {
        if (isEditing(record) || (editingKey === 'new' && !record.id)) {
          return (
            <Form.Item name="label" rules={[{ required: true, message: '请输入名称' }]}>
              <Input />
            </Form.Item>
          );
        }
        return record.label;
      },
    },
    {
      title: '值',
      dataIndex: 'value',
      key: 'value',
      render: (_: any, record: any) => {
        if (isEditing(record) || (editingKey === 'new' && !record.id)) {
          return (
            <Form.Item name="value" rules={[{ required: true, message: '请输入值' }]}>
              <Input />
            </Form.Item>
          );
        }
        return record.value;
      },
    },
    {
      title: '操作',
      key: 'action',
      width: 150,
      render: (_: any, record: any) => {
        const editable = isEditing(record);
        if (editable) {
          return (
            <Space>
              <Button type="link" size="small" icon={<CheckOutlined />} onClick={() => handleSave(record)}>
                保存
              </Button>
              <Button type="link" size="small" icon={<CloseOutlined />} onClick={handleCancel}>
                取消
              </Button>
            </Space>
          );
        }
        return (
          <Space>
            <Button type="link" size="small" icon={<EditOutlined />} onClick={() => handleEdit(record)}>
              编辑
            </Button>
            {hasDeletePower && (
              <Button type="link" size="small" danger icon={<DeleteOutlined />} onClick={() => onDelete(record)}>
                删除
              </Button>
            )}
          </Space>
        );
      },
    },
  ];

  return (
    <div>
      <div style={{ marginBottom: 16 }}>
        <Button type="primary" icon={<PlusOutlined />} onClick={handleAdd} disabled={editingKey !== ''}>
          新增
        </Button>
      </div>
      {editingKey === 'new' && (
        <div style={{ marginBottom: 16, padding: 16, background: '#f5f5f5', borderRadius: 4 }}>
          <Form form={form} layout="inline">
            <Form.Item name="label" label="名称" rules={[{ required: true, message: '请输入名称' }]}>
              <Input placeholder="请输入名称" />
            </Form.Item>
            <Form.Item name="value" label="值" rules={[{ required: true, message: '请输入值' }]}>
              <Input placeholder="请输入值" />
            </Form.Item>
            <Form.Item>
              <Button type="primary" onClick={handleAddSave}>
                保存
              </Button>
            </Form.Item>
            <Form.Item>
              <Button onClick={handleAddCancel}>
                取消
              </Button>
            </Form.Item>
          </Form>
        </div>
      )}
      <Table
        dataSource={tableData}
        pagination={pagination}
        onChange={onChange}
        loading={loading}
        columns={columns}
        rowKey={(record) => record.id || record.key}
      />
    </div>
  );
};

export default OrgTableModal;
