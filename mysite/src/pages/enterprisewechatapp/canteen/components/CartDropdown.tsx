import { DeleteOutlined, ShoppingCartOutlined } from '@ant-design/icons';
import { Avatar, Badge, Button, Card, Dropdown, List } from 'antd';
import React, { useContext, useRef, useState } from 'react';
import { MyContext } from '..';
import MyCounter from './MyCounter';
import tools from '@/utils/tools';
import OrderConfirmModal from './OrderConfirmModal';

const CartDropdown: React.FC = () => {
  const [typeId, setTypeId] = useState<number>(1);
  const orderConfirmModalRef = useRef<any>();
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
    tomorrowMenus,
    activeTabKey,
  } = useContext(MyContext);
  const randNum = Math.floor(3 + Math.random() * 3);

  const removeCart = (data: any) => {
    // 清空
    if (data == undefined) {
      const cartMenusData = Object.values(cartMenus);
      const menuTypeId = cartMenusData[0].typeid.toString();
      const tempIds = Object.keys(cartMenus);
      const tempData = typeMenus['type' + menuTypeId];
      tempData.forEach((element, index) => {
        if (tempIds.includes(element.id.toString())) {
          tempData[index].count = 0;
        }
      });
      typeMenus['type' + menuTypeId] = tempData;
      setCartMenus({});
      setMenuCount(0);
      setTotalMoney(0);
    } else {
      const menuTypeId = data.typeid.toString();
      let findKey = '';
      for (let key in cartMenus) {
        if (cartMenus[key].id == data.id) {
          findKey = key;
          break;
        }
      }
      if (findKey != '') {
        const tempData = typeMenus['type' + menuTypeId];
        tempData.forEach((element, index) => {
          if (element.id == data.id) {
            tempData[index].count = 0;
          }
        });
        typeMenus['type' + menuTypeId] = tempData;
        delete cartMenus[findKey];
        setCartMenus({ ...cartMenus });
        let money = 0.0;
        Object.values(cartMenus).forEach((element) => {
          money = money + parseFloat(element.price) * element.count;
        });
        setMenuCount(Object.keys(cartMenus).length);
        setTotalMoney(tools.formatCurrency(money));
      }
    }
    setTypeMenus({ ...typeMenus });
  };

  const orderModalOk = () => removeCart(undefined);

  const cartMenu = (
    <>
      <Card style={{ width: 350, maxHeight: 500, overflow: 'auto' }}>
        <List
          itemLayout="horizontal"
          dataSource={Object.values(cartMenus)}
          renderItem={(item) => (
            <List.Item
              actions={[
                <MyCounter data={item} key={'cartCounter' + item.id} />,
                <DeleteOutlined key={'remove' + item.id} onClick={() => removeCart(item)} />,
              ]}
            >
              <List.Item.Meta
                avatar={<Avatar src={item.image.substr(0, 6) == 'assets' ? '/' + item.image : item.image} />}
                title={item.name}
                description={tools.formatCurrency(parseFloat(item.price))}
              />
            </List.Item>
          )}
        />
      </Card>
      <Card style={{ width: 350 }}>
        <p style={{ textAlign: 'center', fontSize: '18px' }}>总计：{totalMoney}</p>
        <Button
          disabled={menuCount > 0 ? false : true}
          type="primary"
          onClick={() => removeCart(undefined)}
          block={true}
          style={{ marginBottom: 10 }}
        >
          清空购物车
        </Button>
        <Button
          disabled={menuCount > 0 ? false : true}
          type="primary"
          onClick={() => {
            const menusData = Object.values(cartMenus);
            if (menusData.length > 0) {
              setTypeId(parseInt(menusData[0].typeid));
            }
            setTimeout(() => {
              orderConfirmModalRef?.current.setVisible(true);
            }, 200);
          }}
          block={true}
        >
          去结算
        </Button>
        {activeTabKey == 'tab_1' &&
          tomorrowMenus.length > randNum &&
          Object.values(cartMenus).length == 0 ? (
          <Button
            type="primary"
            onClick={() => {
              const menuNum = tomorrowMenus.length;
              const randMenuId: any[] = [];
              do {
                const tempNum = Math.floor(1 + Math.random() * (menuNum - 1));
                const tempId = tomorrowMenus[tempNum].id;
                if (!randMenuId.includes(tempId)) {
                  randMenuId.push(tempId);
                  cartMenus[tempId] = tomorrowMenus[tempNum];
                }
              } while (Object.values(cartMenus).length < randNum);

              const cartMenusData = Object.values(cartMenus);
              const menuTypeId = cartMenusData[0].typeid.toString();
              const tempIds = Object.keys(cartMenus);
              const tempData = typeMenus['type' + menuTypeId];
              tempData.forEach((element, index) => {
                if (tempIds.includes(element.id.toString())) {
                  tempData[index].count = 1;
                  cartMenus[element.id] = tempData[index];
                }
              });
              // console.log(cartMenus);
              typeMenus['type' + menuTypeId] = tempData;
              let money = 0.0;
              Object.values(cartMenus).forEach((element) => {
                money = money + parseFloat(element.price) * element.count;
              });
              setTypeMenus({ ...typeMenus });
              setCartMenus({ ...cartMenus });
              setMenuCount(Object.keys(cartMenus).length);
              setTotalMoney(tools.formatCurrency(money));
            }}
            block={true}
            style={{ marginTop: 10 }}
          >
            随便来{randNum}个菜
          </Button>
        ) : null}
      </Card>
    </>
  );
  return (
    <>
      <Dropdown overlay={cartMenu} placement="bottomLeft" arrow>
        <Badge count={menuCount} key="cartBadge" style={{ marginRight: 20 }}>
          <ShoppingCartOutlined style={{ fontSize: 26 }} />
        </Badge>
      </Dropdown>
      <span style={{ fontSize: '18px', float: 'left' }}>{menuCount > 0 ? totalMoney : ''}</span>
      <OrderConfirmModal
        menus={cartMenus}
        ref={orderConfirmModalRef}
        typeId={typeId}
        configData={configData}
        onOk={orderModalOk}
      />
    </>
  );
};

export default CartDropdown;
