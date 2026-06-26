import { ProForm } from '@ant-design/pro-components';
import { Input, Select } from 'antd';
import React, { useState } from 'react';

const { Search } = Input;

export type NewsSearchProps = {
  onSearch?: (value: any) => void;
};

const NewsSearch = React.forwardRef((props: NewsSearchProps, ref) => {
  const [type, setType] = useState<string>('1');

  const handleChange = (value: string) => {
    setType(value);
  };

  const onSearch = (value: string) => {
    if (props.onSearch) {
      props.onSearch({ type: type, keywords: value });
    }
  };

  return (
    <>
      <ProForm.Group>
        <Select
          defaultValue="1"
          style={{ width: 120 }}
          onChange={handleChange}
          options={[
            {
              value: '1',
              label: '标题',
            },
            {
              value: '2',
              label: '标题和内容',
            },
          ]}
        />
        <Search
          placeholder="请输入搜索关键字"
          enterButton="搜索"
          onSearch={onSearch}
          allowClear={true}
        />
      </ProForm.Group>
    </>
  );
});
export default NewsSearch;
