import { Affix, Avatar, Button, List, Modal, Skeleton, Divider } from 'antd';
import React, { useImperativeHandle, useState } from 'react';
import styles from '../../style.less';
import CommentEditor from './CommentEditor';
import { commnets, insideCommnets } from './service';

export type CommentModalProps = {
  updateCommentNum?: (result: any, item: any) => void;
  // 是否内网新闻
  inside?: boolean;
};

const CommentModal = React.forwardRef((props: CommentModalProps, ref) => {
  const pageSize = 20;
  const [current, setCurrent] = useState<number>(1);
  const [currentRow, setCurrentRow] = useState<any>();
  const [visible, setVisible] = useState<boolean>(false);
  const [title, setTitle] = useState<string>('');
  const [flag, setFlag] = useState<number>(0);
  const [newsId, setNewsId] = useState<number>(0);
  const [initLoading, setInitLoading] = useState(true);
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState<any[]>([]);
  const [list, setList] = useState<any[]>([]);
  const [commnetEditorVisible, setcommnetEditorVisible] = useState<boolean>(false);

  const method = props.inside ? insideCommnets : commnets;

  const handleCancel = () => {
    setcommnetEditorVisible(false);
    setVisible(false);
  };

  const loadData = (MyFlag: number, myNewsId: number) => {
    method({ pageSize: pageSize, current: 1, flag: MyFlag, newsId: myNewsId }).then((res) => {
      setInitLoading(false);
      setCurrent(2);
      setData(res.data);
      setList(res.data);
      if (res.data.length < pageSize) {
        setLoading(true);
      }
      const tags = document.getElementsByClassName('ant-modal-body');
      if (tags.length > 0) {
        tags[0].scrollTo(0, 0);
      }
    });
  };

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean, myTitle: string, MyFlag: number, item: object) => {
      setTitle(myTitle);
      setFlag(MyFlag);
      setNewsId(item.id);
      setCurrentRow(item);
      setLoading(false);
      setVisible(value);
      loadData(MyFlag, item.id);
    },
  }));

  const onLoadMore = () => {
    setLoading(true);
    setList(
      data.concat(
        [...new Array(pageSize)].map(() => ({ loading: true, username: '', avatar: '' })),
      ),
    );
    method({ pageSize: pageSize, current: current, flag: flag, newsId }).then((res) => {
      const newData = data.concat(res.data);
      setData([...newData]);
      setList([...newData]);
      setLoading(false);
      setCurrent(current + 1);
      if (res.data.length < pageSize) {
        setLoading(true);
      }
      window.dispatchEvent(new Event('resize'));
    });
  };

  const loadMore =
    !initLoading && !loading ? (
      <div
        style={{
          textAlign: 'center',
          marginTop: 12,
          height: 32,
          lineHeight: '32px',
        }}
      >
        <Button onClick={onLoadMore}>更多</Button>
      </div>
    ) : null;

  const getContent = (item: any) => {
    if (flag == 1) {
      return '在' + item.inserttime + '送上鲜花';
    } else if (flag == 2) {
      return '在' + item.inserttime + '点赞';
    } else if (flag == 4) {
      return '在' + item.inserttime + '浏览';
    }
    return (
      <div
        dangerouslySetInnerHTML={{
          __html:
            (props.inside ? item.comment : item.content_P) +
            '<br><span style="font-size:12px;color:#ddd">发表于：' +
            item.inserttime +
            '</span>',
        }}
      />
    );
  };

  return (
    <Modal
      visible={visible}
      title={title}
      onCancel={handleCancel}
      className={styles.myPage}
      footer={false}
    >
      {flag == 3 && (
        <>
          <Affix
            offsetTop={0}
            style={{
              position: 'absolute',
              top: '10%',
              left: '0',
              width: '100%',
              background: '#fff',
              zIndex: 100000,
            }}
          >
            <div>
              <div style={{ width: '90%', margin: '0 5%' }}>
                <CommentEditor
                  visible={commnetEditorVisible}
                  newsId={newsId}
                  onClose={(value: boolean, submitFlag: boolean, result: any) => {
                    setcommnetEditorVisible(value);
                    if (submitFlag && props.updateCommentNum) {
                      props.updateCommentNum(result, currentRow);
                      loadData(flag, newsId);
                    }
                  }}
                  inside={props.inside ? true : false}
                />
              </div>
              <Divider style={{ margin: 0, display: commnetEditorVisible ? 'block' : 'none' }} />
            </div>
          </Affix>
          <Affix offsetTop={0} style={{ position: 'absolute', top: '2%', right: '10%' }}>
            <Button
              type="primary"
              onClick={() => {
                setcommnetEditorVisible(true);
              }}
            >
              我要评论
            </Button>
          </Affix>
        </>
      )}
      <List
        rowKey="id"
        loading={initLoading}
        itemLayout="horizontal"
        loadMore={loadMore}
        dataSource={list}
        renderItem={(item) => (
          <List.Item key={item.id}>
            <Skeleton avatar title={false} loading={item.loading} active>
              <List.Item.Meta
                avatar={<Avatar src={item.avatar} />}
                title={item.username}
                description={getContent(item)}
              />
            </Skeleton>
          </List.Item>
        )}
      />
    </Modal>
  );
});
export default CommentModal;
