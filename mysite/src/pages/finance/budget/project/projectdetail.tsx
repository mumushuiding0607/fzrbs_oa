import React, { useEffect, useState } from 'react';
import { Descriptions } from 'antd';
import { getprojectbyid } from './service';
import ProjectTimeline from './timeline';



const ProjectDetail: React.FC<{id?:any}> = ({id}) => {
  const [data, setData] = useState({})
  useEffect(()=>{
    getprojectbyid({id}).then(res=>{
      if (res.data) {
        setData(res.data)
      }
    })
  },[])
  return (<>
    {id && <ProjectTimeline projectId={id} />}
    <Descriptions bordered layout='horizontal'>
        <Descriptions.Item label="项目内容">{data.title}</Descriptions.Item>
        <Descriptions.Item label="立项主体">{data.entityname}</Descriptions.Item>
        <Descriptions.Item label="项目类别">{data.typename}</Descriptions.Item>
        <Descriptions.Item label="立项时间">{data.starttime}</Descriptions.Item>
        <Descriptions.Item label="项目编号">{data.serial}</Descriptions.Item>
        <Descriptions.Item label="绩效比例">{data.performanceratio}</Descriptions.Item>
        <Descriptions.Item label="项目负责人">{data.chargername}</Descriptions.Item>
        <Descriptions.Item label="执行状态">{data.execstatename}</Descriptions.Item>
        <Descriptions.Item label="报告内容">{data.content}</Descriptions.Item>
        <Descriptions.Item label="预算备注">{data.budgetnote}</Descriptions.Item>
        <Descriptions.Item label="决算备注">{data.finalnote}</Descriptions.Item>
    </Descriptions>
</>)
};

export default ProjectDetail;