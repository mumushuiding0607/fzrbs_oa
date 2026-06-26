﻿export default [
  {
    path: '/user/',
    layout: false,
    routes: [
      {
        name: '登录',
        path: '/user/login/',
        component: './user/Login',
      },
      {
        component: './404',
      },
    ],
  },
  {
    path: '/account/',
    name: '个人中心',
    icon: 'icon-jurassic_user',
    hideInMenu: true,
    routes: [
      {
        path: '/account/',
        redirect: '/account/settings/',
      },
      {
        name: '个人设置',
        icon: 'icon-31shezhi',
        path: '/account/settings/',
        component: './account/Settings',
      },
    ],
  },
  {
    path: '/welcome',
    name: '欢迎',
    component: './Welcome',
  },
  {
    path: '/admin/',
    name: '用户系统',
    access: 'canAdmin',
    routes: [
      {
        path: '/admin/',
        redirect: '/admin/list/',
      },
      {
        path: '/admin/list/',
        name: '用户管理',
        component: './admin/',
      },
      {
        path: '/admin/role/list/',
        name: '角色管理',
        component: './admin/Role',
      },
      {
        path: '/admin/operationlog/list/',
        name: '操作日志管理',
        component: './admin/OperationLog',
      },
      {
        path: '/admin/route/list/',
        name: '路由菜单管理',
        component: './admin/Route',
      },
      {
        path: '/admin/department/index/',
        name: '部门管理',
        component: './admin/Department',
      },
      {
        path: '/admin/appinterface/index/',
        name: '企业微信应用接口管理',
        component: './admin/AppInterface',
      },
      {
        path: '/admin/holiday/',
        name: '假期日期设置',
        component: './admin/Holiday',
      },
      {
        path: '/admin/flowrole/',
        name: '流程角色管理',
        component: './admin/Flowrole',
      },
      {
        path: '/admin/flowtemplate/',
        name: '流程模板管理',
        component: './admin/Flowtemplate',
      },

      {
        component: './404',
      },
    ],
  },
  {
    path: '/information/',
    name: '信息发布',
    routes: [
      {
        path: '/information/',
        access: 'canOpen',
      },
      {
        path: '/information/list/',
        name: '信息发布',
        component: './information/',
        access: 'canOpen',
      },
      {
        path: '/information/channel/list/',
        name: '栏目管理',
        component: './information/channel',
        access: 'canOpen',
      },
      {
        component: './404',
      },
    ],
  },
  {
    path: '/administration/',
    name: '行政后勤',
    routes: [
      {
        path: '/administration/',
        access: 'canOpen',
      },
      {
        path: '/administration/canteen/',
        name: '食堂管理',
        component: './administration/canteen',
        access: 'canOpen',
      },
      {
        path: '/administration/suggest/',
        name: '意见建议',
        component: './administration/suggest',
        access: 'canOpen',
      },
      {
        path: '/administration/vote/',
        name: '群众评议管理',
        component: './administration/Vote',
        access: 'canOpen',
      },
      {
        path: '/administration/sharetask/',
        name: '分享任务管理',
        component: './administration/Sharetask',
        access: 'canOpen',
      },
      {
        path: '/administration/leave/index/',
        name: '请销假',
        component: './administration/leave/',
        access: 'canOpen',
      },
      {
        path: '/administration/housing/index/',
        name: '租房管理',
        component: './administration/Housing',
        access: 'canOpen',
      },
      {
        path: '/administration/salary/index/',
        name: '工资条',
        component: './tool/Salary',
        access: 'canOpen',
      },
      {
        path: '/administration/truckorder/index/',
        name: '车辆管理',
        component: './administration/TruckOrder',
      },
      {
        path: '/administration/oauser/index/',
        name: '职员管理',
        component: './administration/Oauser',
      },
      {
        path: '/administration/photographydispatch/index/',
        name: '摄影派工',
        component: './administration/PhotographyDispatch',
      },

      {
        component: './404',
      },
    ],
  },
  {
    path: '/enterprisewechatapp/',
    name: '企业微信应用',
    routes: [
      {
        path: '/enterprisewechatapp/',
        name: '企业微信应用',
        component: './enterprisewechatapp',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/canteen/',
        name: '食堂开饭了',
        component: './enterprisewechatapp/canteen',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/canteen/live/',
        name: '食堂现场视频',
        component: './enterprisewechatapp/canteen/live',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/watchlist/',
        name: '每日值班表',
        component: './enterprisewechatapp/watchlist',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/focusnews/',
        name: '每日重点选题及热点',
        component: './enterprisewechatapp/focusnews',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/communicationeffect/',
        name: '重点稿件传播效果',
        component: './enterprisewechatapp/communicationeffect',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/areateam/',
        name: '区县小分队',
        component: './enterprisewechatapp/areateam',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/yiqingfangkongtongbao/',
        name: '常态化疫情防控信息通报',
        component: './enterprisewechatapp/yiqingfangkongtongbao',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/zhongyaotongzhi/',
        name: '重要通知',
        component: './enterprisewechatapp/zhongyaotongzhi',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/zhongyaogongzuoxinxi/',
        name: '重要工作信息',
        component: './enterprisewechatapp/zhongyaogongzuoxinxi',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/wenjianchuanyue/',
        name: '文件传阅',
        component: './enterprisewechatapp/wenjianchuanyue',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/shelingdaogongzuotongbao/',
        name: '社领导每周重点工作通报',
        component: './enterprisewechatapp/shelingdaogongzuotongbao',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/bangyang/',
        name: '榜样在身边',
        component: './enterprisewechatapp/bangyang',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/yixiandangyuan/',
        name: '党员在一线',
        component: './enterprisewechatapp/yixiandangyuan',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/yixianshengying/',
        name: '一线身影',
        component: './enterprisewechatapp/yixianshengying',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/biaodanxiazai/',
        name: '表单下载',
        component: './enterprisewechatapp/biaodanxiazai',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/yijianjianyi/',
        name: '意见建议',
        component: './enterprisewechatapp/yijianjianyi',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/fuwangyuntai/tongzhi/',
        name: '重要通知',
        component: './enterprisewechatapp/fuwangyuntai/tongzhi',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/xiaoliuxuetang/',
        name: '小柳学堂',
        component: './enterprisewechatapp/xiaoliuxuetang',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/meiyuegongzuojianbao/',
        name: '每月工作简报',
        component: './enterprisewechatapp/meiyuegongzuojianbao',
        access: 'canOpen',
      },
      {
        path: '/enterprisewechatapp/qunzhongpingyitoupiao/',
        name: ' 员工评议投票',
        component: './enterprisewechatapp/qunzhongpingyitoupiao',
        access: 'canOpen',
      },
      {
        component: './404',
      },
    ],
  },
  {
    path: '/fwyt/',
    name: '福网云台',
    routes: [
      {
        path: '/fwyt/',
        access: 'canOpen',
      },
      {
        path: '/fwyt/hwfz/index/',
        name: '好物福州',
        component: './fwyt/hwfz',
        access: 'canOpen',
      },
      {
        component: './404',
      },
    ],
  },
  {
    path: '/finance/',
    name: '财务结算',
  
    routes: [
      {
        path: '/finance/',
        component: './finance',
        access: 'canOpen',
      },
      {
        path: '/finance/budget/index/',
        name: '非报业务信息系统',
        component: './finance/budget',
        access: 'canOpen',
      },
      {
        path: '/finance/contract/listc/',
        name: '合同管理',
        component: './finance/contract/listc',
        access: 'canOpen',
      },
      {
        path: '/finance/contract/debt/',
        name: '欠款管理',
        component: './finance/contract/debt/debtmanager',
        access: 'canOpen',
      },
      {
        path: '/finance/contract/debt/debtmanager/',
        name: '欠款管理',
        component: './finance/contract/debt/debtmanager',
        access: 'canOpen',
      },
      {
        path: '/finance/contract/debt/urgelogslist/',
        name: '催收日志列表',
        component: './finance/contract/debt/urgelogslist',
        access: 'canOpen',
      },
      {
        path: '/finance/invoice/index/',
        name: '开票申请',
        component: './finance/invoice/index',
        access: 'canOpen',
      },
      {
        path: '/finance/flowtemplate/',
        name: '流程模板管理',
        component: './finance/Flowtemplate',
      },
      {
        path: '/finance/invoice/list/',
        name: '开票列表',
        component: './finance/invoice/list',
        access: 'canOpen',
      },
      {
        path: '/finance/invoice/applylist/',
        name: '开票审批列表',
        component: './finance/invoice/applylist',
        access: 'canOpen',
      },

      {
        path: '/finance/budget/balance/pdetail',
        name: '收支详情',
        component: './finance/budget/balance/pdetail',
        access: 'canOpen',
      },
      {
        path: '/finance/budget/contract/listc/',
        name: '合同列表',
        component: './finance/budget/contract/listc',
        access: 'canOpen',
      },
      {
        path: '/finance/budget/balance/list/',
        name: '收支列表',
        component: './finance/budget/balance/list',
        access: 'canOpen',
      },
      {
        path: '/finance/budget/budget/applylist/',
        name: '审批列表',
        component: './finance/budget/budget/applylist',
        access: 'canOpen',
      },
      {
        path: '/finance/budget/budget/print/',
        name: '打印页面',
        component: './finance/budget/budget/print',
        access: 'canOpen',
      },

      {
        path: '/finance/budget/project/list/',
        name: '项目列表',
        component: './finance/budget/project/list',
        access: 'canOpen',
      },

      {
        path: '/finance/order/orderlist/',
        name: '广告列表',
        component: './finance/order/orderlist',
        access: 'canOpen',
      },
      {
        path: '/finance/order/fzadv/',
        name: '广告登记',
        component: './finance/order/AddFzAdv',
        access: 'canOpen',
      },
      {
        path: '/finance/order/smallbusiness/',
        name: '小额业务',
        component: './finance/order/AddSmallBusiness',
        access: 'canOpen',
      },
      {
        path: '/finance/budget/flow/flowlist/',
        name: '流程设置',
        component: './finance/budget/flow/flowlist',
        access: 'canOpen',
      },
      {
        path: '/finance/budget/target/targetlist/',
        name: '指标列表',
        component: './finance/budget/target/targetlist',
        access: 'canOpen',
      },
      {
        path: '/finance/budget/dict/dictlist/',
        name: '字典管理',
        component: './finance/budget/dict/dictlist',
        access: 'canOpen',
      },

      {
        path: '/finance/attandance/index/',
        name: '考勤管理',
        component: './finance/attandance/index',
        access: 'canOpen',
      },
      {
        path: '/finance/useseal/index/',
        name: '用印管理',
        component: './finance/useseal/index',
        access: 'canOpen',
      },


      {
        component: './404',
      },
    ],
  },

  {
    path: '/',
    redirect: '/welcome',
  },
  {
    component: './404',
  },
];
