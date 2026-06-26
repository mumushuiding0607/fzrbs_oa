import Footer from '@/components/Footer';
import RightContent from '@/components/RightContent';
import type { MenuDataItem, Settings as LayoutSettings } from '@ant-design/pro-components';
import { PageLoading } from '@ant-design/pro-components';
import type { RequestConfig, RunTimeLayoutConfig } from 'umi';
import { history } from 'umi';
import defaultSettings from '../config/defaultSettings';
import { currentUser as queryCurrentUser } from './services/ant-design-pro/api';
import { currentUserMenus as queryCurrentUserMenus } from './services/ant-design-pro/api';
import token from '@/utils/token';

const loginPath = '/user/login/';

const authHeaderInterceptor = (url: string, options: RequestOptionsInit) => {
  if (token.get()) {
    const authHeader = { Authorization: token.get(), PathName: history.location.pathname };
    return {
      url: `${url}`,
      options: { ...options, interceptors: true, headers: authHeader },
    };
  }
};

const refreshToknResponseInterceptors = async (response: Response, options: RequestOptionsInit) => {
  if (response.status == 401) {
    history.push(loginPath);
  }
  // 检查 content-type，只有 JSON 响应才解析
  const contentType = response.headers.get('content-type') || '';
  if (contentType.includes('application/json')) {
    const res = await response.clone().json();
    if (res.token) {
      token.save(res.token);
    }
  }
  return response;
};

/** 获取用户信息比较慢的时候会展示一个 loading */
export const initialStateConfig = {
  loading: <PageLoading />,
};

/**
 * @see  https://umijs.org/zh-CN/plugins/plugin-initial-state
 * */
export async function getInitialState(): Promise<{
  settings?: Partial<LayoutSettings>;
  currentUser?: API.CurrentUser;
  menuData?: MenuDataItem[] | undefined;
  routes?: string[] | undefined;
  loading?: boolean;
  fetchUserInfo?: () => Promise<API.CurrentUser | undefined>;
  fetchUserMenusRoutes?: () => Promise<any | undefined>;
}> {
  const fetchUserInfo = async () => {
    try {
      const msg = await queryCurrentUser();
      return msg.data;
    } catch (error) {
      history.push(loginPath);
    }
    return undefined;
  };
  const fetchUserMenusRoutes = async () => {
    try {
      const msg: any = await queryCurrentUserMenus();
      return msg;
    } catch (error) {
      history.push(loginPath);
    }
    return undefined;
  };
  let routes: string[] = [];
  // 如果不是登录页面，执行
  if (history.location.pathname !== loginPath) {
    const currentUser = await fetchUserInfo();
    let menuData;
    if (currentUser) {
      const menusAndRoutes = await fetchUserMenusRoutes();
      menuData = menusAndRoutes.data;
      if (menusAndRoutes.routes) {
        routes = menusAndRoutes.routes;
      }
    }
    return {
      fetchUserInfo,
      fetchUserMenusRoutes,
      currentUser,
      menuData,
      routes,
      settings: defaultSettings,
    };
  }
  return {
    fetchUserInfo,
    fetchUserMenusRoutes,
    routes,
    settings: defaultSettings,
  };
}

// ProLayout 支持的api https://procomponents.ant.design/components/layout
export const layout: RunTimeLayoutConfig = ({ initialState, setInitialState }) => {
  return {
    rightContentRender: () => <RightContent />,
    disableContentMargin: false,
    // waterMarkProps: {
    //   content: initialState?.currentUser?.realname,
    // },
    footerRender: () => <Footer />,
    onPageChange: () => {
      const { location } = history;
      // 如果没有登录，重定向到 login
      if (!initialState?.currentUser && location.pathname !== loginPath) {
        history.push(loginPath);
      }
    },
    links: [],
    menuHeaderRender: undefined,
    menuDataRender: (menuData) => initialState?.menuData || menuData,
    // 自定义 403 页面
    // unAccessible: <div>unAccessible</div>,
    // 增加一个 loading 的状态
    childrenRender: (children, props) => {
      // if (initialState?.loading) return <PageLoading />;
      return <>{children}</>;
    },
    ...initialState?.settings,
  };
};

export const request: RequestConfig = {
  requestInterceptors: [authHeaderInterceptor],
  responseInterceptors: [refreshToknResponseInterceptors],
};
