import { Avatar, Modal, Segmented, Space, Table, TableProps } from "antd";
import moment from "moment";
import { useEffect, useState } from "react";
import { ProjectStatesEnum } from "../config";
import { getlist } from "./service";
import { useModel } from "umi";
import Addbalance from "./add";


const Listb:React.FC<{moneytype?:any,projectid?:any,type?:any,onChange?:Function}> = ({moneytype,projectid,type,onChange}) =>{
  const [balances,setBalances] = useState([])
  const [record, setRecord] = useState({})
  const [modal1,setModal1] = useState(false)
  var [refreshKey,setRefreshKey]=useState(0)
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
const bcols: TableProps<any>['columns'] = [
  {
    title:'创建人',
    dataIndex:'creator',
    key:'creator',
    
    width:'100',
    render:(_,record,index)=>(<>
        <Segmented
          options={[
            {
              label: (
                <div style={{ padding: 4 }}>
                  <Avatar src={record.avatar} />
                  <div>{record.creatorname}</div>
                </div>
              ),
              value: 'user1',
            }
          ]}
        />
    
    </>)
  },


  {
    title: '名称',
    dataIndex: 'title',
    key: 'title',
  },
  {
    title: '预算',
    dataIndex: 'budget',
    key: 'budget',
  },

  {
    title: '决算',
    dataIndex: 'final',
    key: 'final',
  },
  {
    title: '税率',
    dataIndex: 'tax',
    key: 'tax',
  },
  
  {
    title:'收入类型',
    dataIndex:'moneytypename'
  },
  {
    title: '备注',
    dataIndex: 'note',
    key: 'note',
    
  },
  {
    title: '操作',
    key: 'action',
    render: (_, record) => (
      <>
      
      {
        record.creator==currentUser.wxuserid && <Space size="middle">
        <a onClick={()=>{
          setRecord(record)
          setModal1(true)
          setRefreshKey(++refreshKey)
        }}>更新</a>
      </Space>
      }
      </>
      
    ),
  },
];
  useEffect(()=>{
    getlist({moneytype,projectid,type}).then(res=>{
      setBalances(res.data)
    })
  },[])

  const onAddcSuc = (e:any)=>{
    getlist({moneytype,projectid,type}).then(res=>{
      setBalances(res.data)
    })
    setModal1(false)
    if (onChange){
      onChange()
    }
  }
  return (<>
  
  
    <Table rowKey='key' columns={bcols} dataSource={balances.map((item:any)=>({...item,key:item.id}))} pagination={false}/>
    <Modal
          title="收支"
          style={{ top: 20 }}
          visible={modal1}
          
          onOk={() => setModal1(false)}
          onCancel={() => setModal1(false)}
          footer= {null}
        >
          <Addbalance key={refreshKey} data={record} onChange={onAddcSuc}/>
        </Modal>
  </>)
}
export default Listb