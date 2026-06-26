import { Card, List, Rate } from 'antd';
import React, { useContext } from 'react';
import { useState } from 'react';
import { rule } from '../service';
import { useRequest } from 'umi';
import Meta from 'antd/lib/card/Meta';
import MyCounter from './MyCounter';
import tools from '@/utils/tools';
import moment from 'moment';
import { MyContext } from '..';

export type MyListProps = {
  flag: string;
  // 是否在框架里面
  iframe?: boolean;
};

const MyList: React.FC<MyListProps> = (props) => {
  const [currentTypeId, setCurrentTypeId] = useState<number>(1);
  const [today, setToday] = useState<string>('');
  const { configData, typeMenus, setTypeMenus, setTomorrowMenus } = useContext(MyContext);
  let tomorrow: string = '';

  const getTypeId = () => {
    const typeId = parseInt(props.flag.split('_')[1]);
    setCurrentTypeId(typeId);
    return typeId;
  };

  const formatShowMenuData = (data: any[]) => {
    const days: string[] = [];
    const returnData: any[] = [];
    let i = -1;
    const typeId = getTypeId();
    const randMenus: object[] = [];
    if (typeId != 5) {
      data.forEach((element) => {
        element.count = 0;
        if (days.includes(element.menudate1) === false) {
          i++;
          days.push(element.menudate1);
          returnData[i] = { date: element.menudate1, menus: [] };
          returnData[i].menus.push(element);
        } else {
          returnData[i].menus.push(element);
        }
        if (typeId == 1 && element.menudate1 == tomorrow) {
          randMenus.push(element);
        }
      });
    } else {
      data.forEach((element) => {
        returnData.push(element);
      });
    }
    if (typeId == 1) {
      setTomorrowMenus(randMenus);
    }
    return returnData;
  };

  const { data, loading, run } = useRequest(
    async (params: any) => {
      const flagArray = props.flag.split('_');
      params.typeId = flagArray[1];
      if (flagArray.length == 3) {
        params.flag = flagArray[2];
      }
      const resultData = await rule(params);
      const menus = resultData?.data || [];
      typeMenus['type' + params.typeId] = menus;
      setTypeMenus({ ...typeMenus });
      setToday(resultData?.today || moment().format('YYYY-MM-DD'));
      tomorrow = resultData?.tomorrow || moment(new Date()).add(1, 'day').format('YYYY-MM-DD');
      const showData = formatShowMenuData(menus);
      return { data: { data: showData } };
    },
    {
      paginated: true,
      // manual: true,
    },
  );

  const menus = data?.data || [];

  return currentTypeId != 5 ? (
    <List
      pagination={false}
      loading={loading}
      dataSource={menus}
      renderItem={(item, index) => (
        <List.Item key={item.date}>
          <div style={{ width: '100%', clear: 'both' }}>
            {index == 0 && configData.notice[currentTypeId] && (
              <p style={{ backgroundColor: '#f90', color: '#fff', padding: '5px 10px' }}>
                {configData.notice[currentTypeId]}
              </p>
            )}
            <p
              style={{
                backgroundColor: '#fafafa',
                height: '40px',
                lineHeight: '40px',
                paddingLeft: 20,
                fontSize: '18px',
                position: 'sticky',
                top: props.iframe && props.iframe == true ? 46 : 93,
                zIndex: 90,
              }}
            >
              <strong>{item.menus[0].typename}</strong>
            </p>
            <List
              grid={{
                gutter: 16,
                xs: 2,
                sm: 3,
                md: 4,
                lg: 4,
                xl: 5,
                xxl: 7,
              }}
              pagination={false}
              loading={loading}
              dataSource={item.menus}
              renderItem={(item1) => (
                <List.Item key={item1.id}>
                  <Card
                    hoverable
                    title={item1.name}
                    cover={
                      <img
                        alt={item1.name}
                        src={item1.image.substr(0, 6) == 'assets' ? '/' + item1.image : item1.image}
                        height={120}
                        style={{
                          margin: '0 auto',
                          marginTop: 5,
                          width: '90%',
                          borderRadius: 10,
                          minWidth: 120,
                        }}
                      />
                    }
                    extra={[]}
                  >
                    <Meta
                      description={
                        '当日预订：' +
                        item1.todaynum +
                        '，累计订数：' +
                        item1.buynum +
                        '，点赞数：' +
                        item1.support
                      }
                    />
                    <Rate
                      disabled
                      allowHalf
                      defaultValue={item1.star == '' ? 0 : parseFloat(item1.star)}
                    />
                    <p>
                      售价：
                      <span style={{ fontSize: '20px', marginTop: '-5px' }}>
                        {tools.formatCurrency(parseFloat(item1.price))}
                      </span>
                    </p>
                    <MyCounter data={item1} today={today} />
                  </Card>
                </List.Item>
              )}
            />
          </div>
        </List.Item>
      )}
    />
  ) : (
    <List
      pagination={false}
      loading={loading}
      dataSource={[{ date: '代购' }]}
      renderItem={(item, index) => (
        <List.Item key={item.date}>
          <div style={{ width: '100%', clear: 'both' }}>
            {index == 0 && configData.notice[currentTypeId] && (
              <p style={{ backgroundColor: '#f90', color: '#fff', padding: '5px 10px' }}>
                {configData.notice[currentTypeId]}
              </p>
            )}
            <p
              style={{
                backgroundColor: '#fafafa',
                height: '40px',
                lineHeight: '40px',
                paddingLeft: 20,
                fontSize: '18px',
              }}
            >
              <strong>{item.date}</strong>
            </p>
            <List
              grid={{
                gutter: 16,
                xs: 2,
                sm: 3,
                md: 4,
                lg: 4,
                xl: 5,
                xxl: 7,
              }}
              pagination={false}
              loading={loading}
              dataSource={menus}
              renderItem={(item1) => (
                <List.Item key={item1.id}>
                  <Card
                    hoverable
                    title={item1.name}
                    cover={
                      <img
                        alt={item1.name}
                        src={item1.image.substr(0, 6) == 'assets' ? '/' + item1.image : item1.image}
                        height={120}
                        style={{
                          margin: '0 auto',
                          marginTop: 5,
                          width: '90%',
                          borderRadius: 10,
                          minWidth: 120,
                        }}
                      />
                    }
                    extra={[]}
                  >
                    <Meta
                      description={
                        '当日预订：' +
                        item1.todaynum +
                        '，总预订数：' +
                        item1.buynum +
                        '，点赞数：' +
                        item1.support
                      }
                    />
                    <Rate
                      disabled
                      allowHalf
                      defaultValue={item1.star == '' ? 0 : parseFloat(item1.star)}
                    />
                    <p>
                      售价：
                      <span style={{ fontSize: '20px', marginTop: '-5px' }}>
                        {tools.formatCurrency(parseFloat(item1.price))}
                      </span>
                    </p>
                    <MyCounter data={item1} today={today} />
                  </Card>
                </List.Item>
              )}
            />
          </div>
        </List.Item>
      )}
    />
  );
};

export default MyList;
