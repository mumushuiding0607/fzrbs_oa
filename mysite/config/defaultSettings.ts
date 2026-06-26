import { Settings as LayoutSettings } from '@ant-design/pro-components';

const Settings: LayoutSettings & {
  pwa?: boolean;
  logo?: boolean;
} = {
  navTheme: 'light',
  // 拂晓蓝
  primaryColor: '#1890ff',
  layout: 'mix',
  contentWidth: 'Fluid',
  fixedHeader: false,
  fixSiderbar: true,
  colorWeak: false,
  footerRender: false,
  splitMenus: true,
  title: '福州日报社OA管理系统',
  pwa: false,
  logo: false,
  iconfontUrl: '/icons/iconfont.js',
};

export default Settings;
