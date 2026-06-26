import { TableListItem } from "@/pages/admin/Department/data";
import { LikeOutlined, MenuOutlined, MessageOutlined, MinusOutlined, PlusOutlined, StarOutlined } from "@ant-design/icons";
import { PageContainer, ProColumns, ProFormColumnsType, ProFormInstance, ProTable } from "@ant-design/pro-components";
import { Affix, Avatar, Button, Card, Drawer, Layout, List, Modal, Row, Statistic, message } from "antd";
import { useEffect, useRef, useState } from "react";
import { TableListPagination } from "../project/data";
import { useHistory } from "react-router-dom";

import { deltarget, getsettargetdeparts, gettargetlist } from './service'
import DepartmentTree from '../common/DepartmentTree'
import styles from './styles.less'
import { ActionType } from "@ant-design/pro-table";
import Dictselect from "../dict/dictselect";
import Addtarget from "./addtarget";
import DepartmentTreeSelect from "../common/department_treeselect";

const Targetlist: React.FC = () => {
  const treeRef = useRef();
  const [data,setData] = useState({})
  const [list,setList] = useState([])
  const [showModal,setShowModal]=useState(false)
  const [modal,setModal]=useState(false)
  const [open, setOpen] = useState(false);
  const [params, setParams] = useState({});
  const [hideDepts,setHideDepts] = useState('')
  var [refresh,setRefresh] = useState(0)
  var [lkey,setLkey]=useState(0)
  var [deptTreeKey,setDeptTreeKey] = useState(0)
  const history = useHistory();
  

  useEffect(()=>{
    gettargetlist({}).then(res=>{

      setList(res.data)
    })
    
  },[])

  const  addonchange = (e:any)=>{
    gettargetlist({}).then(res=>{

      setList(res.data)
      setLkey(++lkey)
    })
    
    setShowModal(false)

  } 

  
  return (
    <PageContainer
      title='年度指标设置'
      extra={[

        <Button type="primary" key="4" onClick={()=>{
          getsettargetdeparts({year: new Date().getFullYear()}).then(res=>{
            setHideDepts(res.dept)
            setDeptTreeKey(++deptTreeKey)
            setData({})
            setRefresh(++refresh)
            setShowModal(true)
          })
          
            
        }}>新建</Button>,
        <Button type="default" key="5" onClick={()=>{
          
          getsettargetdeparts({year: new Date().getFullYear()}).then(res=>{
            setHideDepts(res.dept)
            setDeptTreeKey(++deptTreeKey)
            setModal(true)
          })
          
            
        }}>无指标部门</Button>
        
      ]}
      header={{
        breadcrumb: {
          routes: [
            {
              path: '',
              breadcrumbName: '上一页',
            },
            {
              path: '/finance/budget/index/',
              breadcrumbName: '首页',
            }
            
          ],itemRender(route, params, routes, paths) {
            if (route.breadcrumbName=='上一页'){
              return <a href='#' onClick={()=>history.goBack()}>{route.breadcrumbName}</a>
            } else {
              return <a href={`/${paths.join("/")}`}>{route.breadcrumbName}</a>
            }
          },
    
        },
      }}
    >
      

      <Layout>

        <Layout>
          <Layout.Content >

            <Row style={{background:'white',padding:'10px'}}>
                <List
                  key={lkey}
                  itemLayout="vertical"
                  size="large"
                  
                  pagination={{
                    onChange: (page) => {
                      gettargetlist({current:page-1}).then(res=>{
          
                        setList(res.data)
                      })
                    },
                    pageSize: 5
                  }}
                  dataSource={list}

                  renderItem={(item) => (
                    <List.Item
                      key={item.id}
                      actions={[<a href="#" onClick={()=>{

                          setData(item)
                          setRefresh(++refresh)
                          setShowModal(true)
                        
                      }} key="list-loadmore-edit">修改</a>, <a href="#" onClick={() => {
                        Modal.confirm({
                            title: '确定要删除吗？',
                            okText: '确认',
                            cancelText: '取消',
                            onOk: async () => {
                              deltarget({id:item.id}).then((res:any)=>{
                                if (res.errorMessage){
                                  Modal.error({title:res.errorMessage})
                                } else {
                                  gettargetlist({}).then(res=>{

                                    setList(res.data)
                                    setLkey(++lkey)
                                  })
                                  
                                }
                              })
                            },
                          });
                       }} key="list-loadmore-more">删除</a>]}
                      extra={
              
                        <DepartmentTreeSelect defaultValue={(item.dept && typeof item.dept=='string')?item.dept.split(','):[]}  style={{width:400}}/>
                      }
                    >
                      <List.Item.Meta
                        avatar={<Avatar src={item.avatar} />}
                        title={<span>{item.year}年度{item.title}年度指标</span>}
                        description={item.description}
                      />
                       <Row>
                        <div  style={{marginRight:'50px'}}>
                          <Statistic
                              title="收入"
                              value={item.income}
                              precision={2}
                              valueStyle={{ color: '#3f8600',fontSize:'20px' }}
                              prefix="￥"
                            />
                          </div>
                          <div>
                            <Statistic
                              title="利润"
                              value={item.profit}
                              precision={2}
                              valueStyle={{ color: '#cf1322',fontSize:'20px' }}
                              prefix="￥"
                            />
                            </div>
                       </Row>
                        
                    </List.Item>
                  )}
                />
            </Row>
          </Layout.Content>
        </Layout>
      </Layout>
      <Modal
        title="年度指标"
        style={{ top: 20, }}
        visible={showModal}
        onOk={() => setShowModal(false)}
        onCancel={() => setShowModal(false)}
        footer={null}
      >
        <Addtarget  data={data} key={refresh} onChange={addonchange}/>
      </Modal>
      <Modal
        title="无指标部门"
        style={{ top: 20, }}
        visible={modal}
        onOk={() => setModal(false)}
        onCancel={() => setModal(false)}
        footer={null}
      >
        <DepartmentTree
              key={deptTreeKey}
              showLeafIcon={false}
              selectable={false}
              checkable={false}
              checkStrictly={false}
              showUser={false}
              local={true}
              ref={treeRef}
              hideValues={hideDepts}
              
            />
      </Modal>
    </PageContainer>
  );
}
export default Targetlist;
