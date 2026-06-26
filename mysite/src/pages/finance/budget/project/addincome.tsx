
import { Modal } from 'antd';
import React, { useEffect, useState } from 'react';
import Incomelist from './income/incomelist';
import { BalanceTypes } from '../config';

// balancetype 15收入，16支出
const Addincome: React.FC<{pid:any,balancetype:any,onChange?:any,visible?:boolean,onClose?:Function,onlyBalance?:boolean}> = ({onlyBalance=false,balancetype,pid,onChange,visible=false,onClose}) =>{
  const [modal1, setModal1] = useState(visible)
  useEffect(()=>{
    
    setModal1(visible)
  },[visible])
  return (
    <Modal

        title={balancetype==BalanceTypes.INCOME?'收入详情':'支出详情'}
        style={{ top: 20 }}
        width={880}

        visible={modal1}
        onOk={() => setModal1(false)}
        onCancel={() => setModal1(false)}
        afterClose={()=>{
          onClose && onClose(false)
        }}
        footer={null}
      >
        <Incomelist  pid={pid} balancetype={balancetype} onlyBalance={onlyBalance}/>
      </Modal>
  )
}
export default Addincome;