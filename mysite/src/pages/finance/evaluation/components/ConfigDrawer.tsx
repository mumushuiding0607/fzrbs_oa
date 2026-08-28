import React from 'react';
import { Drawer } from 'antd';
import Dictlist from '../../budget/dict/dictlist';

interface ConfigDrawerProps {
  visible: boolean;
  type: string;  // 配置类型，如 "评分档次", "被考评部门" 等
  onClose: () => void;
}

const ConfigDrawer: React.FC<ConfigDrawerProps> = ({ visible, type, onClose }) => {
  return (
    <Drawer
      title={type || '配置管理'}
      placement="right"
      width="100%"
      closable
      onClose={onClose}
      visible={visible}
    >
      <Dictlist type={type} />
    </Drawer>
  );
};

export default ConfigDrawer;