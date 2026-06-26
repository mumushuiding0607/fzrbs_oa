import { defineStore } from 'pinia'
import { ref } from "vue";

export const useUserStore = defineStore("user", () => {
  const loginStatus = ref(0);
  const backUrl = ref('');
  const fromRoute = ref<any>({});
  const userInfo = ref<any>({});
  const currentEditItem = ref<any>(undefined);
  const backTitle = ref<any>('');
  const appUserInfo = ref<any>({ userid: -1 });
  function setUserInfo(data: any) {
    loginStatus.value = 1;
    backUrl.value = '';
    userInfo.value = data;
  }
  function setLoginStatus(status: number) {
    loginStatus.value = status;
  }
  function setBackUrl(url: string) {
    backUrl.value = url;
  }
  function setFromRoute(data: any) {
    fromRoute.value = data;
  }
  function setCurrentEditItem(data: any) {
    currentEditItem.value = data;
  }
  function setBackTitle(data: any) {
    backTitle.value = data;
  }
  function setAppUserInfo(data: any) {
    appUserInfo.value = data;
  }
  return { loginStatus, backUrl, userInfo, fromRoute, currentEditItem, backTitle, appUserInfo, setUserInfo, setBackUrl, setFromRoute, setCurrentEditItem, setLoginStatus, setBackTitle, setAppUserInfo };
}, { persist: { enabled: true } });

