/**
 * @see https://umijs.org/zh-CN/plugins/plugin-access
 * */
export default function access(
  initialState: { currentUser?: API.CurrentUser; menuData?: any; routes?: any } | undefined,
) {
  const { currentUser, menuData, routes } = initialState ?? {};
  // const menu: string[] = [];
  // if (menuData && menuData.length > 0) {
  //   menuData.forEach((element: any) => {
  //     menu.push(element.path);
  //     if (element.children) {
  //       element.children.forEach((element1: any) => {
  //         menu.push(element1.path);
  //       });
  //     }
  //   });
  // }
  return {
    canAdmin: currentUser && currentUser.access === 'admin',
    canOpen: (route: any) => currentUser?.access === 'admin' || routes.includes(route.path),
  };
}
