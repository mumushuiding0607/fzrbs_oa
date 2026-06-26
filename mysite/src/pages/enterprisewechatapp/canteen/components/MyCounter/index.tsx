import { MinusOutlined, PlusOutlined } from '@ant-design/icons';
import { InputNumber, message } from 'antd';
import React, { useState, useEffect, useContext } from 'react';
import { MyContext } from '../..';
import styles from './index.less';
import tools from '@/utils/tools';
import moment from 'moment';

export type MyCounterProps = {
  data?: any;
  today?: string;
};

const MyCounter: React.FC<MyCounterProps> = (props) => {
  const [count, setCount] = useState<number>(0);
  const {
    configData,
    typeMenus,
    setTypeMenus,
    cartMenus,
    setCartMenus,
    menuCount,
    setMenuCount,
    totalMoney,
    setTotalMoney,
    currentUser
  } = useContext(MyContext);

  const updateMenuCount = (data, num) => {
    const typeKey = 'type' + data.typeid.toString();
    const tempData = typeMenus[typeKey];
    tempData.forEach((element, index) => {
      if (element.id == data.id) {
        tempData[index].count = num;
      }
    });
    typeMenus[typeKey] = tempData;
    setTypeMenus({ ...typeMenus });
    data.conut = num;
    if (num == 0) {
      delete cartMenus[data.id];
    } else {
      cartMenus[data.id] = data;
    }
    let money = 0.0;
    Object.values(cartMenus).forEach((element) => {
      money = money + parseFloat(element.price) * element.count;
    });
    setMenuCount(Object.keys(cartMenus).length);
    setTotalMoney(tools.formatCurrency(money));
    setCartMenus({ ...cartMenus });
  };

  const increase = () => {
    const cartMenusData = Object.values(cartMenus);
    if (cartMenusData.length > 0) {
      if (cartMenusData[0].typeid != props.data.typeid) {
        message.warn('不同类型菜单不能添加一起');
        return;
      }
      if (![5].includes(props.data.typeid) && cartMenusData[0].menudate1 != props.data.menudate1) {
        message.warn('不同日期菜单不能添加一起');
        return;
      }
    }
    if (props.data && props.today) {
      if (![5].includes(props.data.typeid) && props.data.menudate1 <= props.today) {
        message.warn('菜单已过期');
        return;
      }
    }
    let h = moment().hour();
    if (configData?.now) {
      h = moment(configData?.now).hour();
    }
    let stopHour = 22;
    if (configData?.dingcantime) {
      stopHour = parseInt(configData?.dingcantime);
    }
    if ([1, 3].includes(props.data.typeid) && h >= stopHour && !configData?.leader.includes(currentUser.wxuserid)) {
      message.warn('预订时间已截止');
      return;
    }
    let tomorrow = moment(new Date()).add(1, 'day').format('YYYY-MM-DD');
    if ([5].includes(props.data.typeid)) {
      if ([5, 6].includes(moment().day())) {
        if (configData?.holiday && configData?.holiday[1] && !configData?.holiday[1].includes(tomorrow)) {
          message.warn('非工作日周末不安排代购');
          return;
        }
      } else if (configData?.holiday && configData?.holiday[0] && configData?.holiday[0].includes(tomorrow)) {
        message.warn('假期不安排代购');
        return;
      }
    }
    if ([5].includes(props.data.typeid) && !configData?.leader.includes(currentUser.wxuserid)) {
      let stopHour1 = 7;
      let stopHour2 = 18;
      if (configData?.daigoutime1) {
        stopHour1 = parseInt(configData?.daigoutime1);
      }
      if (configData?.daigoutime2) {
        stopHour2 = parseInt(configData?.daigoutime2);
      }
      if (stopHour1 < 7 || h >= stopHour2) {
        message.warn('代购下单时间已截止，下单时间为每天' + stopHour1.toString() + '点到' + stopHour2.toString() + '点');
        return;
      }
    }
    if (props.data.buylimit && parseInt(props.data.buylimit) == count) {
      message.warn('最多预订 ' + props.data.buylimit.toString() + ' 份');
      return;
    }
    const newCount = count + 1;
    setCount(newCount);
    updateMenuCount(props.data, newCount);
  };

  const decline = () => {
    if (count == 0) {
      return;
    }
    let newCount = count - 1;
    if (newCount < 0) {
      newCount = 0;
    }
    setCount(newCount);
    updateMenuCount(props.data, newCount);
  };

  useEffect(() => {
    if (props.data.count != undefined) {
      setCount(props.data.count);
    }
  }, [props.data.count]);

  return (
    <InputNumber
      value={count}
      size="small"
      controls={false}
      className={styles.myCounter}
      style={{ width: '100px', textAlign: 'center' }}
      readOnly={true}
      addonAfter={
        <span onClick={increase} style={{ width: '30px', display: 'inline-block' }}>
          <PlusOutlined />
        </span>
      }
      addonBefore={
        <span onClick={decline} style={{ width: '30px', display: 'inline-block' }}>
          <MinusOutlined />
        </span>
      }
    />
  );
};

export default MyCounter;
