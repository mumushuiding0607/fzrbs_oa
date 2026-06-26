import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: {
        title: '首页',
        keepAlive: true,
        requiresLogin: true,
      }
    },
    {
      path: '/news/info',
      name: 'newsInfo',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/NewsInfoView.vue'),
      meta: {
        title: '新闻详情',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/news/list',
      name: 'newsList',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/NewsListView.vue'),
      meta: {
        title: '新闻列表',
        keepAlive: true,
        requiresLogin: true,
      }
    },
    {
      path: '/download/file',
      name: 'downloadFile',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/DownloadFileView.vue'),
      meta: {
        title: '文件下载',
        keepAlive: true,
        requiresLogin: true,
      }
    },
    
    {
      path: '/addressbook',
      name: 'addressbook',
      component: () => import('../views/AddressBook.vue'),
      meta: {
        title: '企业通讯录',
        keepAlive: true,
        requiresLogin: true,
      }
    },
    {
      path: '/addressbookuser',
      name: 'addressbook_user',
      component: () => import('../views/AddressBookUser.vue'),
      meta: {
        title: '个人信息',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    
    {
      path: '/yixianshengying/index',
      name: 'yixianshengying_index',
      component: () => import('../views/YiXianShengYingView.vue'),
      meta: {
        title: '一线身影',
        keepAlive: true,
        requiresLogin: true,
      }
    },
    {
      path: '/vote',
      name: 'common_vote',
      component: () => import('../views/Vote.vue'),
      meta: {
        title: '投票',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    
    {
      path: '/budget/index',
      name: 'feibaoxitong_index',
      component: () => import('../views/budget/index.vue'),
      meta: {
        title: '非报业务信息系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/budget/view',
      name: 'feibaoxitong_view',
      component: () => import('../views/budget/view.vue'),
      meta: {
        title: '非报业务信息系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },

    {
      path: '/invoice/index',
      name: 'invoice_index',
      component: () => import('../views/invoice/index.vue'),
      meta: {
        title: '开票申请系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/invoice/view',
      name: 'kaipiaoxitong_view',
      component: () => import('@/views/invoice/invoicing_view.vue'),
      meta: {
        title: '开票详情页',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/invoice/add',
      name: 'kaipiaoxitong_add',
      component: () => import('@/views/invoice/invoicing_add.vue'),
      meta: {
        title: '添加开票申请',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/order/index',
      name: 'order_index',
      component: () => import('../views/order/index.vue'),
      meta: {
        title: '广告审批系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/order/view',
      name: 'order_view',
      component: () => import('../views/order/view.vue'),
      meta: {
        title: '广告审批系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/budget/viewcollection',
      name: 'feibaoxitong_viewcollection',
      component: () => import('../views/budget/viewcollection.vue'),
      meta: {
        title: '非报业务信息系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/contract/debturge',
      name: 'contract_debturge',
      component: () => import('../views/contract/debturge.vue'),
      meta: {
        title: '催款',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/contract/index',
      name: 'contract_index',
      component: () => import('../views/contract/index.vue'),
      meta: {
        title: '催款',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/finance/index',
      name: 'finance_index',
      component: () => import('../views/finance/index.vue'),
      meta: {
        title: '付款审批系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/finance/view',
      name: 'finance_view',
      component: () => import('../views/finance/finance_view.vue'),
      meta: {
        title: '付款详情页',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/finance/add',
      name: 'finance_add',
      component: () => import('../views/finance/finance_add.vue'),
      meta: {
        title: '添加付款申请',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/finance/mylist',
      name: 'finance_mylist',
      component: () => import('../views/finance/mylist.vue'),
      meta: {
        title: '付款审批系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/press/mylist',
      name: 'press_mylist',
      component: () => import('../views/press/mylist.vue'),
      meta: {
        title: '签付印延迟审批系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/press/index',
      name: 'press_index',
      component: () => import('../views/press/index.vue'),
      meta: {
        title: '签付印延迟审批系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/press/view',
      name: 'press_view',
      component: () => import('../views/press/press_view.vue'),
      meta: {
        title: '签付印延迟详情页',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/press/add',
      name: 'press_add',
      component: () => import('../views/press/press_add.vue'),
      meta: {
        title: '添加签付印延迟申请',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/useseal/mylist',
      name: 'useseal_mylist',
      component: () => import('../views/useseal/mylist.vue'),
      meta: {
        title: '用印申请审批系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/useseal/index',
      name: 'useseal_index',
      component: () => import('../views/useseal/index.vue'),
      meta: {
        title: '用印申请审批系统',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/useseal/view',
      name: 'useseal_view',
      component: () => import('../views/useseal/view.vue'),
      meta: {
        title: '用印申请详情页',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/useseal/add',
      name: 'useseal_add',
      component: () => import('../views/useseal/add.vue'),
      meta: {
        title: '添加用印申请',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/photodispatch/mylist',
      name: 'photodispatch_mylist',
      component: () => import('../views/photodispatch/mylist.vue'),
      meta: {
        title: '摄影派工',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/photodispatch/index',
      name: 'photodispatch_index',
      component: () => import('../views/photodispatch/index.vue'),
      meta: {
        title: '摄影派工',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/photodispatch/view',
      name: 'photodispatch_view',
      component: () => import('../views/photodispatch/view.vue'),
      meta: {
        title: '摄影派工详情页',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/photodispatch/add',
      name: 'photodispatch_add',
      component: () => import('../views/photodispatch/add.vue'),
      meta: {
        title: '添加摄影派工',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/photodispatch/reporters',
      name: 'photodispatch_reportList',
      component: () => import('@/views/photodispatch/reporters.vue'),
      meta: {
        title: '记者列表',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/photodispatch/reporterstate',
      name: 'photodispatch_reporterstate',
      component: () => import('@/views/photodispatch/reporterstate.vue'),
      meta: {
        title: '去向表',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/attendance/mylist',
      name: 'attendance_mylist',
      component: () => import('../views/attendance/mylist.vue'),
      meta: {
        title: '考勤异常审批',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/attendance/index',
      name: 'attendance_index',
      component: () => import('../views/attendance/index.vue'),
      meta: {
        title: '考勤异常审批',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/attendance/exception',
      name: 'attendance_exception',
      component: () => import('../views/attendance/exception.vue'),
      meta: {
        title: '考勤异常列表',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/attendance/stat',
      name: 'attendance_stat',
      component: () => import('../views/attendance/stat.vue'),
      meta: {
        title: '报表导出',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/attendance/view',
      name: 'attendance_view',
      component: () => import('../views/attendance/view.vue'),
      meta: {
        title: '考勤异常审批详情页',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/attendance/add',
      name: 'attendance_add',
      component: () => import('../views/attendance/add.vue'),
      meta: {
        title: '添加考勤异常审批',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/manuscriptscoring/mylist',
      name: 'manuscriptscoring_mylist',
      component: () => import('../views/manuscriptscoring/mylist.vue'),
      meta: {
        title: '稿件评分',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/manuscriptscoring/index',
      name: 'manuscriptscoring_index',
      component: () => import('../views/manuscriptscoring/index.vue'),
      meta: {
        title: '稿件评分首页',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/manuscriptscoring/viewlist',
      name: 'manuscriptscoring_viewlist',
      component: () => import('../views/manuscriptscoring/viewlist.vue'),
      meta: {
        title: '稿件评分列表',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/manuscriptscoring/view',
      name: 'manuscriptscoring_view',
      component: () => import('../views/manuscriptscoring/view.vue'),
      meta: {
        title: '稿件评分详情页',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/manuscriptscoring/add',
      name: 'manuscriptscoring_add',
      component: () => import('../views/manuscriptscoring/add.vue'),
      meta: {
        title: '添加稿件评分',
        keepAlive: false,
        requiresLogin: true,
      }
    },
    {
      path: '/app_user_bind',
      name: 'app_user_bind',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/AppUserBindView.vue'),
      meta: {
        title: '账号绑定',
        keepAlive: false,
        requiresLogin: false,
      }
    },
    {
      path: '/test',
      name: 'test',
      component: () => import('../views/Test.vue'),
      meta: {
        title: '调试页面',
        keepAlive: true,
        requiresLogin: false,
      }
    },
    {
      path: '/error',
      name: 'error',
      component: () => import('../views/Error.vue'),
      meta: {
        title: '授权失效',
        keepAlive: false,
        requiresLogin: false,
      }
    },
  ]
})

export default router
