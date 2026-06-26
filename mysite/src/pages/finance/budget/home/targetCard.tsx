import { AntDesignOutlined } from "@ant-design/icons";
import { Avatar, Card, Row, Statistic } from "antd";
import { CSSProperties } from "react";
import { useModel } from "umi";


const TargetCard: React.FC<{target?:{}}> = ({target})=>{

  return (<>
  
        <div style={{padding:'10px'}}>
            <div style={{fontSize:'20px',marginLeft:'20px'}}>{target?.head}</div>
            <Row>
                <Card  bordered={false}>
                  <Statistic
                      title="收入"
                      value={target?.income}
                      precision={2}
                      valueStyle={{ color: '#3f8600' }}
                      prefix="￥"
                    />
                  </Card>
                  <Card bordered={false}>
                    <Statistic
                      title="利润"
                      value={target?.profit}
                      precision={2}
                      valueStyle={{ color: '#cf1322' }}
                      prefix="￥"
                    />
                    </Card>
            </Row>
          </div>
  
  </>)
}

export default TargetCard;