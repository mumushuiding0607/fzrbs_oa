import { Avatar, Button, Comment, Form, Input, message, Space } from 'antd';
import React, { useEffect, useState } from 'react';
import { useModel } from 'umi';
import { updateComment, myComment, updateInsideComment } from './service';

const { TextArea } = Input;

interface EditorProps {
  onChange: (e: React.ChangeEvent<HTMLTextAreaElement>) => void;
  onSubmit: () => void;
  onClose: () => void;
  submitting: boolean;
  value: string;
}

const Editor = ({ onChange, onSubmit, onClose, submitting, value }: EditorProps) => (
  <>
    <Form.Item>
      <TextArea rows={4} onChange={onChange} value={value} placeholder="请输入评论内容" />
    </Form.Item>
    <Form.Item>
      <Space>
        <Button htmlType="submit" loading={submitting} onClick={onSubmit} type="primary">
          提交
        </Button>
        <Button htmlType="submit" onClick={onClose} type="default">
          取消
        </Button>
      </Space>
    </Form.Item>
  </>
);

export type CommentEditorProps = {
  visible: boolean;
  newsId: number;
  onClose: (value: boolean, submitFlag: boolean, result: any) => void;
  // 是否内网新闻
  inside?: boolean;
};

const CommentEditor = React.forwardRef((props: CommentEditorProps, ref) => {
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  const [submitting, setSubmitting] = useState(false);
  const [value, setValue] = useState('');
  const [visible, setVisible] = useState<boolean>(props.visible);

  const method = props.inside ? updateInsideComment : updateComment;

  const handleSubmit = async () => {
    if (!value) return;
    setSubmitting(true);
    const result = await method({ id: props.newsId, flag: 3, commnet: value });
    setSubmitting(false);
    if (result.errorMessage) {
      message.warn(result.errorMessage);
      return;
    }
    message.success('提交成功');
    setValue('');
    props.onClose(false, true, result);
    setVisible(false);
  };

  const handleChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    setValue(e.target.value);
  };

  const handleClose = () => {
    setVisible(false);
    props.onClose(false, false, undefined);
  };

  useEffect(() => {
    setVisible(props.visible);
    if (props.visible && !props.inside) {
      myComment({ id: props.newsId }).then((res) => {
        if (res.data) {
          setValue(res.data);
        } else {
          setValue('');
        }
      });
    }
  }, [props.visible]);

  return (
    <div style={{ display: visible ? 'block' : 'none' }}>
      <Comment
        avatar={<Avatar src={currentUser.avatar} />}
        content={
          <Editor
            onChange={handleChange}
            onSubmit={handleSubmit}
            onClose={handleClose}
            submitting={submitting}
            value={value}
          />
        }
      />
    </div>
  );
});
export default CommentEditor;
