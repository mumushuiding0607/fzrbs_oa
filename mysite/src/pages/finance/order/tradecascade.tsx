import React, { useEffect, useState } from 'react';
import { Button, TreeSelect, Modal, message, Space, Tree, Form, Input, Popconfirm } from 'antd';
import { EditOutlined, PlusOutlined, DeleteOutlined } from '@ant-design/icons';
import { request } from 'umi';
import { getpowers } from '../role/service';

interface TradeNode {
  id: string;
  label: string;
  value?: string;
  title?: string;
  children?: TradeNode[];
  [key: string]: any;
}

interface TradecascadeProps {
  value?: string | { value: string; label: string } | any;
  label?: string;
  onChange?: (value: { value: string; label: string }) => void;
  placeholder?: string;
  multiple?:boolean;
}

const Tradecascade: React.FC<TradecascadeProps> = ({ value, onChange, placeholder = "请选择行业",multiple=false }) => {
  const [treeData, setTreeData] = useState<TradeNode[]>([]);
  const [loading, setLoading] = useState(true);
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [formVisible, setFormVisible] = useState(false);
  const [editingNode, setEditingNode] = useState<TradeNode | null>(null);
  const [parentNode, setParentNode] = useState<TradeNode | null>(null);
  const [form] = Form.useForm();
  const [powers, setPowers] = useState<string[]>([]);
  const [hasDeletePower, setHasDeletePower] = useState(false);

  const parseValue = (val: any): string | undefined => {
    if (!val) return undefined;
    if (typeof val === 'string' || typeof val === 'number') return String(val);
    if (typeof val === 'object') {
      return val.value || val.id || undefined;
    }
    return undefined;
  };

  const findNodeById = (
    tree: TradeNode[],
    targetId: string,
  ): TradeNode | null => {
    for (const node of tree) {
      if (node.id === targetId) {
        return node;
      }
      if (Array.isArray(node.children) && node.children.length > 0) {
        const found = findNodeById(node.children, targetId);
        if (found) return found;
      }
    }
    return null;
  };

  useEffect(() => {
    setLoading(true);
    request<{
      data: TradeNode[];
      message?: string;
    }>('/api/advertisemanange/trades', {
      method: 'GET',
    }).then((res: any) => {
      const data = res?.data || res || [];
      setTreeData(data);
      setLoading(false);
    }).catch(() => {
      setLoading(false);
    });
  }, []);

  useEffect(() => {
    checkPowers();
  }, []);

  const checkPowers = async () => {
    try {
      const powerRes = await getpowers({ menu: 'tradecascade' });
      if (powerRes && powerRes.data) {
        const powerList:any = powerRes.data || [];
        setPowers(powerList);
        setHasDeletePower(powerList.includes('delete'));
      }
    } catch (error) {
      console.error('Failed to get powers:', error);
    }
  };

  const handleChange = (val: any) => {
    if (val) {
      if (multiple){
        onChange && onChange({ value: val.join(','), label: '' });
        return
      }
      const node = findNodeById(treeData, val);
      if (node) {
        onChange && onChange({ value: node.id, label: node.label });
      }
    } else {
      onChange && onChange({ value: '', label: '' });
    }
  };

  const getDisplayValue = () => {
    const parsed = parseValue(value);
    if (parsed && treeData.length > 0) {
      const node = findNodeById(treeData, parsed);
      if (node) {
        return node.id;
      }
    }
    return parsed;
  };

  const refreshTreeData = () => {
    request<{
      data: TradeNode[];
      message?: string;
    }>('/api/advertisemanange/trades', {
      method: 'GET',
    }).then((res: any) => {
      const data = res?.data || res || [];
      setTreeData(data);
    });
  };

  const handleOpenEditModal = () => {
    setEditModalVisible(true);
  };

  const handleAddChild = (node: TradeNode) => {
    setEditingNode(null);
    setParentNode(node);
    form.resetFields();
    form.setFieldsValue({ parentid: node.id, parentLabel: node.label });
    setFormVisible(true);
  };

  const handleEdit = (node: TradeNode) => {
    setEditingNode(node);
    setParentNode(null);
    const { parentid, ...rest } = node;
    form.setFieldsValue({ ...rest });
    setFormVisible(true);
  };

  const handleFormCancel = () => {
    setFormVisible(false);
    setEditingNode(null);
    setParentNode(null);
    form.resetFields();
  };

  const handleSave = async () => {
    try {
      const values = await form.validateFields();
      const { parentid, ...rest } = values;
      if (editingNode) {
        const res = await request('/api/advertisemanange/savetrade', {
          method: 'POST',
          data: { ...rest, id: editingNode.id },
        });
        if (res?.errorMessage) {
          message.error(res.errorMessage);
          return;
        }
        message.success('修改成功');
      } else {
        const res = await request('/api/advertisemanange/savetrade', {
          method: 'POST',
          data: { ...rest, parentid },
        });
        if (res?.errorMessage) {
          message.error(res.errorMessage);
          return;
        }
        message.success('新增成功');
      }
      handleFormCancel();
      refreshTreeData();
    } catch (error) {
      message.error(editingNode ? '修改失败' : '新增失败');
    }
  };

  const handleDeleteTrade = (node: TradeNode) => {
    Modal.confirm({
      title: '确定要删除该行业吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        try {
          const res = await request('/api/advertisemanange/deletetrade', {
            method: 'POST',
            data: { id: node.id },
          });
          if (res?.errorMessage) {
            message.error(res.errorMessage);
            return;
          }
          message.success('删除成功');
          refreshTreeData();
        } catch (error) {
          message.error('删除失败');
        }
      },
    });
  };

  const convertToTreeData = (nodes: TradeNode[]): any[] => {
    return nodes.map(node => ({
      ...node,
      key: node.id,
      title: (
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%' }}>
          <span>{node.label}</span>
          <Space size="small">
            <Button type="link" size="small" icon={<PlusOutlined />} onClick={(e) => { e.stopPropagation(); handleAddChild(node); }}>
              新增
            </Button>
            <Button type="link" size="small" icon={<EditOutlined />} onClick={(e) => { e.stopPropagation(); handleEdit(node); }}>
              编辑
            </Button>
            {hasDeletePower && (
              <Popconfirm
                title="确定要删除该行业吗？"
                onConfirm={(e) => { e?.stopPropagation(); handleDeleteTrade(node); }}
                onCancel={(e) => e?.stopPropagation()}
              >
                <Button type="link" size="small" danger icon={<DeleteOutlined />} onClick={(e) => e.stopPropagation()}>
                  删除
                </Button>
              </Popconfirm>
            )}
          </Space>
        </div>
      ),
      children: node.children ? convertToTreeData(node.children) : [],
    }));
  };

  return (
    <div>
      <div style={{ display: 'flex', width: '100%' }}>
        <TreeSelect
          treeData={treeData}
          value={getDisplayValue()}
          onChange={handleChange}
          showSearch={true}
          treeNodeFilterProp="title"
          allowClear
          multiple={multiple}
          loading={loading}
          placeholder={placeholder}
          style={{ flex: 1 }}
        />
        <Button icon={<EditOutlined />} onClick={handleOpenEditModal} style={{ marginLeft: 4 }} />
      </div>

      <Modal
        title="行业管理"
        visible={editModalVisible}
        onCancel={() => { setEditModalVisible(false); handleFormCancel(); }}
        footer={null}
        width={700}
        destroyOnClose
      >
        <div style={{ marginBottom: 16 }}>
          <Button type="primary" icon={<PlusOutlined />} onClick={() => { setEditingNode(null); setParentNode(null); form.resetFields(); setFormVisible(true); }}>
            新增根节点
          </Button>
        </div>
        <Tree
          treeData={convertToTreeData(treeData)}
          showLine={{ showLeafIcon: false }}
          defaultExpandAll
        />
      </Modal>

      <Modal
        title={editingNode ? '编辑行业' : '新增行业'}
        visible={formVisible}
        onCancel={handleFormCancel}
        footer={null}
        destroyOnClose
      >
        <Form form={form} layout="vertical">
          <Form.Item name="label" label="名称" rules={[{ required: true, message: '请输入名称' }]}>
            <Input placeholder="请输入名称" />
          </Form.Item>
          {parentNode && (
            <Form.Item name="parentid" label="父节点">
              <Input disabled />
            </Form.Item>
          )}
          <Form.Item>
            <Space>
              <Button type="primary" onClick={handleSave}>
                保存
              </Button>
              <Button onClick={handleFormCancel}>
                取消
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
};

export default Tradecascade;
