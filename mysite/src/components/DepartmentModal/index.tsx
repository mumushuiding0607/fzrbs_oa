import DepartmentTree from '@/components/DepartmentTree';
import { Avatar, Input, message, Modal } from 'antd';
import React, { useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import styles from './index.less';
import { searchUser } from './service';
import { useRequest } from 'umi';
import { CheckCard } from '@ant-design/pro-components';

const { Search } = Input;

export type DepartmentModalProps = {
  multiple: boolean;
  onOk?: (checkedValue: any) => void;
  onCancel?: () => void;
};

const DepartmentModal = React.forwardRef((props: DepartmentModalProps, ref) => {
  const [visible, setVisible] = useState(false);
  const [title, setTitle] = useState<string>('请选择用户');
  const [isSearch, setIsSearch] = useState<boolean>(false);
  const [serachLoading, setSerachLoading] = useState<boolean>(false);
  const [mySelectValue, setMySelectValue] = useState<any>();
  const [multiple, setMultiple] = useState<boolean>(props.multiple);
  const [searchChecked, setSearchChecked] = useState<any>(undefined);
  const treeRef = useRef(undefined);

  const handleCancel = () => {
    if (props.onCancel) {
      props.onCancel();
    }
    setVisible(false);
  };
  const handleOk = async () => {
    let selectUsers;
    if (isSearch) {
      if (searchChecked == undefined) {
        message.warn('请选择用户');
        return;
      }
      selectUsers = searchChecked;
    } else {
      const checkedIds = treeRef?.current.getCheckedKeys();
      if (checkedIds.length == 0) {
        message.warn('请选择用户');
        return;
      }
      if (!props.multiple && checkedIds.length > 1) {
        message.warn('只能选择一个用户');
        return;
      }
      selectUsers = checkedIds;
    }
    if (props.onOk) {
      props.onOk(selectUsers);
    }
    setVisible(false);
  };

  const { data, loading, run } = useRequest(
    async (params: any) => {
      if (params.username != '') {
        const result = await searchUser(params);
        setSerachLoading(false);
        const userData: any[] = [];
        result.data.data.forEach((element, index) => {
          userData.push({
            title: element.name,
            avatar: <Avatar size={32} shape="square" src={element.avatar} />,
            description: element.departmentname,
            value: element.userid,
          });
        });
        return { data: { data: userData } };
      }
      return { data: { data: [] } };
    },
    {
      paginated: true,
      manual: true,
    },
  );

  const users = data?.data || [];

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean) => {
      setVisible(value);
      setTimeout(() => {
        treeRef?.current.clearChecked();
        setIsSearch(false);
        setSerachLoading(false);
        setSearchChecked(undefined);
        setMySelectValue([]);
        run({ username: '' });
      }, 200);
    },
    setTitle: (value: string) => {
      setTitle(value);
    },
    setMultiple: (value: boolean) => {
      setMultiple(value);
    },
  }));

  const onSearch = (value: string) => {
    if (value != '') {
      setIsSearch(true);
      setSerachLoading(true);
      run({ username: value });
    }
  };

  const onChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { value } = e.target;
    if (value == '') {
      setIsSearch(false);
      run({ username: '' });
    }
  };

  const onCheckCardChange = (values) => {
    setSearchChecked(values);
    setMySelectValue(values);
  };

  return (
    <Modal
      visible={visible}
      title={
        <>
          {title}
          <Search
            style={{ marginTop: 8 }}
            placeholder="搜索"
            onSearch={onSearch}
            loading={serachLoading}
            allowClear={true}
            onChange={onChange}
          />
        </>
      }
      onCancel={handleCancel}
      onOk={handleOk}
      className={styles.infomodal}
    >
      <div style={{ display: isSearch ? 'none' : 'block' }}>
        <DepartmentTree
          checkable={true}
          selectable={false}
          checkStrictly={true}
          showLeafIcon={false}
          showUser={true}
          showAll={false}
          local={false}
          ref={treeRef}
        />
      </div>
      <div style={{ display: isSearch ? 'block' : 'none' }}>
        <CheckCard.Group
          options={users}
          size="large"
          multiple={multiple}
          onChange={onCheckCardChange}
          value={mySelectValue}
        />
      </div>
    </Modal>
  );
});
export default DepartmentModal;
