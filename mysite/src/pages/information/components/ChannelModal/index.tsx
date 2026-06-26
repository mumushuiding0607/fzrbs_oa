import { message, Modal } from 'antd';
import React, { useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import MyTree from '../../channel/components/tree';
import styles from './index.less';

export type ChannelModalProps = {
  type: string;
  action: string;
  onOk?: (ids: number[]) => void;
  onCancel?: () => void;
};

const ChannelModal = React.forwardRef((props: ChannelModalProps, ref) => {
  const [visible, setVisible] = useState(false);
  const channelTreeRef = useRef(undefined);

  const handleCancel = () => {
    setVisible(false);
    if (props.onCancel) {
      props.onCancel();
    }
  };
  const handleOk = () => {
    const checkedIds = channelTreeRef?.current.getCheckedKeys();
    if (props.action == 'cut' && checkedIds.length > 1) {
      message.warn('只能选择一个栏目');
      return;
    }
    if (props.onOk) {
      props.onOk(checkedIds);
    }
  };

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean) => {
      setVisible(value);
      setTimeout(() => {
        channelTreeRef?.current.clearChecked();
      }, 200);
    },
  }));

  return (
    <Modal
      visible={visible}
      title="选择栏目"
      onCancel={handleCancel}
      onOk={handleOk}
      className={styles.infomodal}
    >
      <MyTree
        checkable={true}
        selectable={false}
        checkStrictly={true}
        showLeafIcon={false}
        ref={channelTreeRef}
      />
    </Modal>
  );
});
export default ChannelModal;
