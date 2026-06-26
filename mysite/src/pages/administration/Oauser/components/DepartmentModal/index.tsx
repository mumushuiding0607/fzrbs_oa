import DepTree from '../depTree';
import { message, Modal } from 'antd';
import React, { useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import styles from './index.less';
// import { cut } from '../oauser/service';

export type DepartmentModalProps = {
  type: string;
  action: string;
  selectedRows: number[];
  fromId: number;
  onOk?: (ids: number[]) => void;
  onCancel?: () => void;
  callbackOk:(data:any)=>void;
};

const DepartmentModal = React.forwardRef((props: DepartmentModalProps, ref) => {
  const [visible, setVisible] = useState(false);
  const treeRef = useRef(undefined);

  const handleCancel = () => {
    setVisible(false);
    if (props.onCancel) {
      props.onCancel();
    }
  };
  const handleOk = async () => {
    const checkedIds = treeRef?.current.getCheckedKeys();

    if (props.action == 'cut' && checkedIds.length > 1) {
      message.warn('只能选择一个部门');
      return;
    }
    let updateIds = [];
    updateIds = props.selectedRows.map((row) => row.id);
    const result = await props.callbackOk({
      fromId: props.fromId,
      toId: checkedIds[0],
      infoIds: updateIds,
    });

    if (result.errorCode) {
      message.warn(result.errorMessage);
    } else {
      message.success('移动成功');
    }
    if (props.onOk) {
      props.onOk(checkedIds);
    }
    setVisible(false);
  };

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean) => {
      setVisible(value);
      setTimeout(() => {
        treeRef?.current.clearChecked();
      }, 200);
    },
  }));

  return (
    <Modal
      visible={visible}
      title="选择部门"
      onCancel={handleCancel}
      onOk={handleOk}
      className={styles.infomodal}
    >
      <DepTree
        checkable={true}
        selectable={false}
        checkStrictly={true}
        showLeafIcon={false}
        showUser={false}
        ref={treeRef}
      />
    </Modal>
  );
});
export default DepartmentModal;
