import { PageContainer } from '@ant-design/pro-components';
import { Modal, Typography } from 'antd';
import React, { useEffect, useState } from 'react';
import { history, useModel } from 'umi';
import { one } from '../components/NewsList/service';
import styles from '../style.less';

const { Title, Paragraph } = Typography;

const AreaTeam: React.FC = () => {
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  const query = history.location.query;
  const [currentRow, setCurrentRow] = useState<any>();

  useEffect(() => {
    if (!currentUser.wxuserid || currentUser.wxuserid == '') {
      Modal.warning({
        content: '您还未绑定微信企业号，请先绑定微信企业号',
        okButtonProps: { disabled: true },
      });
    }
    if (query.iframe) {
      const header = document.getElementsByTagName('header');
      if (header.length > 0) {
        header.forEach((element: any) => {
          element.style.display = 'none';
        });
      }
    }
    one({ id: 941 }).then((data: any) => {
      if (data.data) {
        setCurrentRow(data.data);
      }
    });
  }, []);

  return (
    <PageContainer
      header={{
        title: (
          <>
            {query.icon && (
              <img
                src={query.icon}
                width={40}
                height={40}
                style={{ borderRadius: 20, marginRight: 5 }}
              />
            )}
            {query.title ? query.title : '企业应用'}
          </>
        ),
      }}
      className={query.iframe ? styles.iframePage : ''}
    >
      {currentRow && (
        <>
          <Typography>
            <Title>
              <h1 dangerouslySetInnerHTML={{ __html: currentRow.title }} />
            </Title>
            <Paragraph>
              <div dangerouslySetInnerHTML={{ __html: currentRow.content }} />
            </Paragraph>
          </Typography>
        </>
      )}
    </PageContainer>
  );
};

export default AreaTeam;
