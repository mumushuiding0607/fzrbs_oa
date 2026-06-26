import React, { useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import { rule, one, updateComment, insideRule, insideOne, updateInsideComment } from './service';
import { useRequest } from 'umi';
import { Card, Divider, List, message, Modal, Space, Tooltip } from 'antd';
import styles from './index.less';
import browser from '@/utils/browser';
import { LikeOutlined, MessageOutlined, EyeOutlined, EllipsisOutlined } from '@ant-design/icons';
import Icon, { CustomIconComponentProps } from '@ant-design/icons/lib/components/Icon';
import CommentModal from './CommentModal';

const FlowerSvg = () => (
  <svg
    t="1668071491001"
    className="icon"
    viewBox="0 0 1024 1024"
    version="1.1"
    xmlns="http://www.w3.org/2000/svg"
    p-id="4349"
    width="1em"
    height="1em"
  >
    <path
      d="M717.312 218.794667c0-40.448-3.242667-68.266667-3.413333-68.949333-1.024-8.192-5.973333-15.530667-13.312-19.456-7.338667-3.925333-16.213333-4.096-23.722667-0.341333l-77.653333 38.912-65.877333-98.645333c-4.608-6.826667-11.946667-10.922667-19.968-11.434667-8.192-0.341333-15.872 3.072-21.162667 9.386667l-75.776 92.501333-67.754667-30.037333c-2.901333-1.706667-6.314667-2.901333-9.898667-3.242667-13.994667-1.706667-26.794667 8.362667-28.501333 22.357333l0 0.170667c-0.170667 0.682667-3.413333 28.501333-3.413333 68.949333 0.170667 96.768 18.090667 289.28 181.589333 309.930667l0 414.378667c0 14.165333 11.434667 25.6 25.6 25.6 14.165333 0 25.6-11.434667 25.6-25.6L539.648 528.213333C699.392 504.832 717.141333 314.709333 717.312 218.794667zM358.4 218.794667c0-10.24 0.170667-19.626667 0.512-27.818667l54.784 24.234667c10.581333 4.608 22.869333 1.706667 30.208-7.168l66.218667-80.896 59.050667 88.576c7.168 10.752 21.162667 14.506667 32.768 8.704L665.6 192.853333c0.341333 7.68 0.512 16.384 0.512 25.941333-0.170667 102.741333-23.893333 260.266667-153.770667 260.608C382.293333 479.061333 358.741333 321.536 358.4 218.794667z"
      p-id="4350"
    ></path>
    <path
      d="M821.077333 565.76c-3.242667-6.144-8.533333-10.581333-15.018667-12.629333-1.365333-0.341333-42.154667-13.482667-90.624-13.653333-25.6 0.170667-53.76 3.584-79.701333 16.725333-50.688 25.770667-60.074667 75.605333-59.562667 102.912 0 15.530667 4.096 32.768 11.264 41.301333 7.68 7.168 36.352 32.768 81.749333 33.28 16.384 0 34.304-3.754667 52.224-12.970667 73.728-39.765333 99.669333-133.802667 101.376-135.68C824.832 578.901333 824.149333 571.904 821.077333 565.76zM698.026667 675.328c-11.093333 5.461333-45.909333 15.872-70.485333-8.192-0.170667-2.218667-3.584-49.493333 31.402667-65.194667 15.701333-6.997333 35.84-11.264 56.490667-11.264 17.749333 0 34.474667 2.389333 47.957333 4.949333C750.762667 622.250667 727.381333 660.992 698.026667 675.328z"
      p-id="4351"
    ></path>
    <path
      d="M392.362667 562.517333c-26.453333-12.458667-54.954667-15.872-81.066667-16.042667-49.664 0.170667-91.477333 12.8-92.842667 13.141333-6.656 2.048-12.288 6.656-15.36 12.8-3.242667 6.144-3.754667 13.482667-1.536 19.968 1.706667 1.877333 29.013333 93.184 104.448 131.072 18.432 8.704 36.522667 12.288 52.906667 12.288 46.421333-0.512 76.117333-25.6 83.285333-32.085333 4.266667-3.413333 7.509333-8.192 8.874667-13.653333l0-0.170667c0.341333-1.536 2.901333-11.776 2.901333-26.965333C454.656 636.074667 443.904 586.922667 392.362667 562.517333zM328.021333 677.376c-30.037333-13.141333-52.736-49.664-65.877333-75.093333 13.824-2.389333 30.890667-4.608 48.981333-4.437333 21.333333 0 43.008 3.242667 59.050667 10.922667 28.330667 13.824 31.744 35.84 32.426667 54.272 0 2.901333-0.170667 5.290667-0.341333 7.338667-8.192 5.802667-24.064 14.848-43.52 14.336C349.696 684.714667 339.626667 682.837333 328.021333 677.376z"
      p-id="4352"
    ></path>
  </svg>
);

const FlowerIcon = (props: Partial<CustomIconComponentProps>) => (
  <Icon component={FlowerSvg} {...props} />
);

export declare type ListType = 'default' | 'listImage';

export type NewsListProps = {
  // 栏目id
  channelId: number;
  // 每页获取数
  pageSize?: number;
  // 列表显示样式
  type?: ListType;
  // 是否分组显示(按子栏目分组)
  group?: boolean;
  // 显示阅读数
  showView?: boolean;
  // 是否显示评论数
  showCommentNum?: boolean;
  // 是否显示点赞数
  showGoodNum?: boolean;
  // 是否显示礼物送花数
  showGiftNum?: boolean;
  // 是否显示作者
  showWriter?: boolean;
  // 显示摘要(listImage默认就有显示)
  showRemark?: boolean;
  // 摘要前面显示的文字
  remarkTitle?: string;
  // 是否显示推荐人信息
  showRecommend?: boolean;
  // 记录浏览用户信息
  saveView?: boolean;
  // 是否在框架里面
  iframe?: boolean;
  // 是否内网新闻
  inside?: boolean;
  onSearch?: () => void;
};

const IconText = ({
  icon,
  text,
  toopTipTile,
}: {
  icon: React.FC;
  text: string;
  toopTipTile: string;
}) => (
  <Space>
    <Tooltip title={toopTipTile}>{React.createElement(icon)}</Tooltip>
    {text}
  </Space>
);

const NewsList = React.forwardRef((props: NewsListProps, ref) => {
  const pageSize = props.pageSize ? props.pageSize : 20;
  const [currentRow, setCurrentRow] = useState<any>();
  const [initialPage, setInitialPage] = useState<boolean>(true);
  const [userParams, setUserParams] = useState<object>({});
  const [visible, setVisible] = useState<boolean>(false);
  const [listData, setListData] = useState<any[]>([]);
  const commentModalRef = useRef();

  const { data, loading, run } = useRequest(
    async (params: any) => {
      if (initialPage) {
        params.pageSize = pageSize;
        params.channelid = props.channelId;
        if (props.group === true) {
          params.group = 1;
        }
        if (props.showCommentNum === true) {
          params.showCommentNum = 1;
        }
        if (props.showGoodNum === true) {
          params.showGoodNum = 1;
        }
        if (props.showGiftNum === true) {
          params.showGiftNum = 1;
        }
        setInitialPage(false);
      }
      setUserParams(params);
      let result;
      if (props.inside) {
        result = await insideRule(params);
      } else {
        result = await rule(params);
      }
      setListData(result.data?.data);
      return result;
    },
    {
      paginated: true,
      // manual: true,
    },
  );

  const list = data?.data || [];
  const total = data?.total || 0;
  const current = data?.current || 1;
  const paginationProps = {
    showTotal: (total, range) => `第  ${range[0]}-${range[1]} 条/总共 ${total} 条`,
    showSizeChanger: true,
    showQuickJumper: true,
    defaultPageSize: pageSize,
    total: parseInt(total),
    current: parseInt(current),
    onChange: (page: number, size: number) => {
      document.body.scrollTop = document.documentElement.scrollTop = 0;
      let params = userParams;
      params.current = page;
      params.pageSize = size;
      run(params);
    },
  };

  const searchData = (values: object, flag: number, refresh: number) => {
    let params = userParams;
    if (refresh == 0) {
      params.current = 1;
    }
    if (values.type) {
      params.type = values.type;
    }
    if (values.keywords) {
      params.keywords = values.keywords;
    } else {
      delete params.keywords;
    }
    if (flag == 1) {
      setUserParams(params);
      run(params);
    }
  };

  const updateViewNum = async (id: number) => {
    listData.forEach((element, index) => {
      if (!props.group) {
        if (id == parseInt(element.id)) {
          listData[index].click = currentRow.click;
        }
      } else {
        element.children.forEach((element1, index1) => {
          if (id == parseInt(element1.id)) {
            listData[index].children[index1].click = currentRow.click;
          }
        });
      }
    });
    setListData([...listData]);
  };

  const showContent = async (id: number) => {
    const params = { id };
    if (props.saveView === true) {
      params.saveView = 1;
    }
    let info;
    if (props.inside) {
      info = await insideOne(params);
    } else {
      info = await one(params);
    }
    setCurrentRow(info.data);
    setVisible(true);
  };

  const updateListData = (result: any, item: any) => {
    listData.forEach((element, index) => {
      if (!props.group) {
        if (parseInt(item.id) == parseInt(element.id)) {
          if (props.showGoodNum) {
            listData[index].goodnum = result.data[item.id].goodnum;
          }
          if (props.showGiftNum) {
            listData[index].flowernum = result.data[item.id].flowernum;
          }
          if (props.showCommentNum) {
            listData[index].commentnum = result.data[item.id].commentnum;
          }
        }
      } else {
        element.children.forEach((element1, index1) => {
          if (parseInt(item.id) == parseInt(element1.id)) {
            if (props.showGoodNum) {
              listData[index].children[index1].goodnum = result.data[item.id].goodnum;
            }
            if (props.showGiftNum) {
              listData[index].children[index1].flowernum = result.data[item.id].flowernum;
            }
            if (props.showCommentNum) {
              listData[index].children[index1].commentnum = result.data[item.id].commentnum;
            }
          }
        });
      }
    });
    setListData([...listData]);
  };

  const updateCommentNum = async (flag: numner, item: object) => {
    let result;
    if (props.inside) {
      result = await updateInsideComment({ id: item.id, flag });
    } else {
      result = await updateComment({ id: item.id, flag });
    }
    if (result.errorMessage) {
      message.warn(result.errorMessage);
      return;
    }
    if (result.data.update) {
      message.success('操作成功');
      updateListData(result, item);
    } else {
      message.warn('您已经操作过了');
    }
  };

  const openCommentModal = (item: any, flag: numner) => {
    let title = '列表';
    if (flag == 1) {
      title = '送花列表';
    } else if (flag == 2) {
      title = '点赞列表';
    } else if (flag == 3) {
      title = '评论列表';
    } else if (flag == 4) {
      title = '浏览列表';
    }
    commentModalRef?.current.setVisible(true, title, flag, item);
  };

  const createAction = (item: object) => {
    const action = [];
    if (props.showView) {
      action.push(
        <Space key="list-vertical-view-o">
          <IconText icon={EyeOutlined} text={item.click} toopTipTile="阅读数" />
          {props.saveView && (
            <EllipsisOutlined
              onClick={() => {
                openCommentModal(item, 4);
              }}
              style={{ cursor: 'pointer' }}
            />
          )}
        </Space>,
      );
    }
    if (props.showGiftNum) {
      action.push(
        <Space key="list-vertical-gift-o">
          <span
            style={{ cursor: 'pointer' }}
            onClick={() => {
              updateCommentNum(1, item);
            }}
          >
            <IconText icon={FlowerIcon} text={item.flowernum} toopTipTile="送花数" />
          </span>
          <EllipsisOutlined
            onClick={() => {
              openCommentModal(item, 1);
            }}
            style={{ cursor: 'pointer' }}
          />
        </Space>,
      );
    }
    if (props.showGoodNum) {
      action.push(
        <Space key="list-vertical-like-o">
          <span
            style={{ cursor: 'pointer' }}
            onClick={() => {
              updateCommentNum(2, item);
            }}
          >
            <IconText icon={LikeOutlined} text={item.goodnum} toopTipTile="点赞数" />
          </span>
          <EllipsisOutlined
            onClick={() => {
              openCommentModal(item, 2);
            }}
            style={{ cursor: 'pointer' }}
          />
        </Space>,
      );
    }
    if (props.showCommentNum) {
      action.push(
        <Space key="list-vertical-message-o">
          <IconText icon={MessageOutlined} text={item.commentnum} toopTipTile="评论数" />
          <EllipsisOutlined
            onClick={() => {
              openCommentModal(item, 3);
            }}
            style={{ cursor: 'pointer' }}
          />
        </Space>,
      );
    }
    return action;
  };

  const ListDataType = () => {
    if (props.type == 'listImage') {
      if (props.group === true) {
        return (
          <List
            rowKey="id"
            loading={loading}
            dataSource={listData.length > 0 ? listData : list}
            pagination={paginationProps}
            className={styles.newsPage}
            renderItem={(item, index) => (
              <>
                <div style={{ width: '100%', clear: 'both' }}>
                  <p
                    style={{
                      backgroundColor: '#fafafa',
                      height: '40px',
                      lineHeight: '40px',
                      paddingLeft: 20,
                      fontSize: '18px',
                      position: 'sticky',
                      top: props.iframe && props.iframe == true ? 0 : 48,
                      zIndex: 90,
                    }}
                  >
                    <strong>{item.title}</strong>
                  </p>

                  <List
                    itemLayout="vertical"
                    size="large"
                    pagination={false}
                    dataSource={item.children}
                    renderItem={(item1) => (
                      <List.Item
                        key={item.id.toString() + item1.id.toString()}
                        actions={createAction(item1)}
                        extra={
                          <a
                            onClick={() => {
                              showContent(item1.id);
                            }}
                          >
                            <img
                              height={80}
                              alt={item1.title}
                              src={
                                item1.image.substr(0, 6) == 'assets'
                                  ? '/' + item1.image
                                  : item1.image
                              }
                            />
                          </a>
                        }
                      >
                        <List.Item.Meta
                          title={
                            <a
                              onClick={() => {
                                if (item1.redirect != '' && item1.redirect.substr(0, 4) == 'http') {
                                  return;
                                }
                                showContent(item1.id);
                              }}
                              href={
                                item1.redirect != '' && item1.redirect.substr(0, 4) == 'http'
                                  ? item1.redirect
                                  : '#!'
                              }
                              target={
                                item1.redirect != '' && item1.redirect.substr(0, 4) == 'http'
                                  ? '_blank'
                                  : '_self'
                              }
                              rel="noreferrer"
                            >
                              <strong>{item1.title}</strong>
                            </a>
                          }
                          description={
                            props.showRecommend
                              ? '部门：' + item1.shorttitle + '　　推荐人：' + item1.redirect
                              : ''
                          }
                        />
                        {props.remarkTitle ? props.remarkTitle + '：' : ''}
                        {item1.remark}
                      </List.Item>
                    )}
                  />
                </div>
              </>
            )}
          />
        );
      }
    }
    return (
      <List
        rowKey="id"
        grid={{
          gutter: 16,
          xs: 1,
          sm: 2,
          md: 2,
          lg: 2,
          xl: 4,
          xxl: 4,
        }}
        loading={loading}
        dataSource={listData.length > 0 ? listData : list}
        pagination={paginationProps}
        className={styles.newsPage}
        renderItem={(item) => (
          <List.Item key={item.id}>
            <Card
              hoverable={true}
              title={
                <>
                  <a
                    onClick={() => {
                      if (item.redirect != '' && item.redirect.substr(0, 4) == 'http') {
                        return;
                      }
                      showContent(item.id);
                    }}
                    href={
                      item.redirect != '' && item.redirect.substr(0, 4) == 'http'
                        ? item.redirect
                        : '#!'
                    }
                    target={
                      item.redirect != '' && item.redirect.substr(0, 4) == 'http'
                        ? '_blank'
                        : '_self'
                    }
                    rel="noreferrer"
                  >
                    {item.title}
                  </a>
                </>
              }
            >
              <p>发布时间：{item.inserttime}</p>
              {props.showWriter && <p>作者：{item.writer}</p>}
              <Space split={<Divider type="vertical" />}>{createAction(item)}</Space>
            </Card>
          </List.Item>
        )}
      />
    );
  };

  useImperativeHandle(ref, () => ({
    search: (values: any) => {
      searchData(values, 1, 0);
    },
  }));

  return (
    <>
      {ListDataType()}
      <div style={{ height: 30 }} />
      {currentRow && (
        <Modal
          title={
            <>
              <h1 dangerouslySetInnerHTML={{ __html: currentRow.title }} />
            </>
          }
          centered
          visible={visible}
          onOk={() => {
            updateViewNum(currentRow.id);
            setVisible(false);
          }}
          onCancel={() => {
            updateViewNum(currentRow.id);
            setVisible(false);
          }}
          width={browser.mobile() ? '100vw' : 800}
          footer={false}
          className={styles.newsPage}
        >
          <p style={{ textAlign: 'center', marginTop: 0 }}>
            发布时间：{currentRow.inserttime}
            {props.showWriter ? '　作者：' + currentRow.writer : ''}
            {props.showRecommend
              ? '　部门： ' + currentRow.shorttitle + '　推荐人：' + currentRow.redirect
              : ''}
          </p>
          {props.showRecommend && (
            <Card title={props.remarkTitle} style={{ width: '100%', marginBottom: 30 }}>
              {currentRow.remark}
            </Card>
          )}
          <div dangerouslySetInnerHTML={{ __html: currentRow.content }} />
        </Modal>
      )}
      <CommentModal
        ref={commentModalRef}
        updateCommentNum={(result: any, item: any) => {
          updateListData(result, item);
        }}
        inside={props.inside ? true : false}
      />
    </>
  );
});
export default NewsList;
