import { AntDesignOutlined } from "@ant-design/icons";
import { Avatar, Row } from "antd";
import { CSSProperties } from "react";
import { useModel } from "umi";

const contaniner:CSSProperties = {
  background: '#348BFF',
  width:'100%',
  height: '10vw',
  borderRadius:'1vw',
  display: 'flex',
  flexDirection: 'row',
  alignItems: 'center',
  padding: '2vw'
}
const row:CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  justifyContent: 'center'
}
const col:CSSProperties = {
  display: 'flex',
  flexDirection: 'column',
  justifyContent: 'center'
}
const Banner: React.FC<{data?:{}}> = ({data})=>{
  const { initialState } = useModel('@@initialState');

  return (<>
  
    <Row>
      <div style={contaniner}>
        <Avatar
          src={data?.avatar}
          size={{ xs: 24, sm: 32, md: 40, lg: 64, xl: 80, xxl: 100 }}
          icon={<AntDesignOutlined />}
        />
        <div style={{...col,marginLeft:'1.5vw',color:'white'}}>
          <div style={{fontSize:'2vw'}}>您好，欢迎登录</div>
      
        </div>
      </div>
    </Row>
  
  </>)
}

export default Banner;