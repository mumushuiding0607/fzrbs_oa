import { PlusCircleOutlined } from '@ant-design/icons';
import { Avatar, List, Modal, Tooltip } from 'antd';
import React, { useEffect, useImperativeHandle, useState } from 'react';
import styles from './style.less';

export type AppsModalProps = {
  ids: any[];
  add?: (item: any) => void;
};

const AppsModal = React.forwardRef((props: AppsModalProps, ref) => {
  const [visible, setVisible] = useState<boolean>(false);
  const [data, setData] = useState<any[]>([]);
  const [ids, setIds] = useState<any[]>(props.ids);

  const handleCancel = () => {
    setVisible(false);
  };

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean, myData: any) => {
      const listData: any[] = [];
      myData.forEach((element: any, index: number) => {
        if (index > 0) {
          if (element.children && element.children.length > 0) {
            element.children.forEach((element1: any) => {
              listData.push(element1);
            });
          }
        }
      });
      setData(listData);
      setVisible(value);
    },
  }));

  useEffect(() => {
    setIds(props.ids);
  }, [props.ids]);

  return (
    <Modal
      visible={visible}
      title="应用列表"
      onCancel={handleCancel}
      className={styles.myPage}
      footer={false}
    >
      <List
        rowKey="id"
        itemLayout="horizontal"
        dataSource={data}
        renderItem={(item) => {
          const actions = [];
          if (!ids.includes(item.id)) {
            actions.push(
              <Tooltip title="添加到我的应用" key={'add' + item.id}>
                <PlusCircleOutlined
                  style={{ cursor: 'pointer' }}
                  onClick={() => {
                    if (props.add) {
                      props.add(item);
                    }
                  }}
                />
              </Tooltip>,
            );
          }
          return (
            <List.Item key={item.id} actions={actions}>
              <List.Item.Meta avatar={<Avatar src={item.image} />} title={item.name} />
            </List.Item>
          );
        }}
      />
    </Modal>
  );
});
export default AppsModal;
