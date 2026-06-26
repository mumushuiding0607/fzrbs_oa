import { createApp } from 'vue'
import { createPinia, storeToRefs } from 'pinia'
import piniaPluginPersist from 'pinia-plugin-persist'
import { useUserStore } from '@/stores'
import App from './App.vue'
import router from './router'
import VueWechatTitle from 'vue-wechat-title';
import './assets/main.css'
import 'vant/lib/index.css'
import { login, appAutoLogin, appVisit } from './api/login';
import { loadWeixinConfig, appEnv, appEnvUserrId } from './utils/common'
import { closeToast, showToast, showLoadingToast } from 'vant'
import { showAppBindWxDialog, closeAppBindWxDialog } from "@/components/AppBindWx/index";

const app = createApp(App)
const pinia = createPinia()
pinia.use(piniaPluginPersist)
app.use(pinia)
app.use(router)
app.use(VueWechatTitle)
app.mount('#app')

router.beforeEach(async (to, from, next) => {
    const store = useUserStore()
    const { loginStatus, userInfo } = storeToRefs(store)
    const userLogin = loginStatus.value == 1 && userInfo.value?.userId;
    const params = to.query;
    let getuserid = false
    const appLoginhandle = (userJson: any) => {
        getuserid = true
        const appUserInfo = JSON.parse(userJson)
        if (appUserInfo?.userid && parseInt(appUserInfo?.userid) > 0) {
            closeToast();
            showAppBindWxDialog({ dialog: true, showLoading: true, title: '正在自动登录验证' })
            try {
                // showLoadingToast({
                //     message: '正在自动登录',
                //     forbidClick: true,
                //     duration: 0,
                // });
                appAutoLogin({
                    token: appUserInfo.token,
                    devid: appUserInfo.device,
                    uid: appUserInfo.userid,
                    score: appUserInfo.score,
                }).then((res: any) => {
                    closeAppBindWxDialog()
                    store.setAppUserInfo(appUserInfo)
                    if (res?.errorMessage != '') {
                        // showToast(res?.errorMessage);
                        store.setFromRoute({ name: to.name, path: to.path, query: to.query, fullPath: to.fullPath })
                        // 用户自己手动绑定
                        next('/app_user_bind?flag=1')
                    } else {
                        const userInfo = { userId: res.data?.UserId, deviceId: res.data?.DeviceId, appuserid: appUserInfo.userid, virtualKeyUsers: res.data?.virtualKeyUsers };
                        store.setUserInfo(userInfo)
                        next(to)
                    }
                })
            } catch (e) {
            }
        }
    }
    if (!userLogin && to.meta.requiresLogin) {
        let res: any
        if (process.env.NODE_ENV == 'production') {
            if (appEnv()) {
                window.syncUserInfo = appLoginhandle
                const userId = appEnvUserrId()
                if (userId == -1) {
                    window.location.href = "login:///"
                    showToast({
                        message: '登录失败',
                        duration: 0,
                    });
                } else {
                    try {
                        window.location.href = "getuserid:///"
                        setTimeout(() => {
                            // getuserid:///方法失败兼容办法
                            if (!getuserid) {
                                closeToast()
                                closeAppBindWxDialog()
                                store.setFromRoute({ name: to.name, path: to.path, query: to.query, fullPath: to.fullPath })
                                return next('/app_user_bind?flag=0')
                            }
                        }, 1000);
                    } catch (e) {
                    }
                }
                return
            } else {
                const code = params?.code ? params?.code : ''
                if (code != '') {
                    const agentid = params?.agentid ? params?.agentid : '1000002'
                    res = await login(code, agentid)
                    closeToast();
                }
            }
        } else {
            // 开发环境调试用户
            res = { "success": true, "errorMessage": "", "errorCode": 0, "data": { "UserId": "linting", "DeviceId": "cd9e459ea708a948d5c2f5a6ca8838cf", "errcode": 0, "errmsg": "ok" } }
        }
        if (res?.data && res.data?.UserId) {
            const userInfo = { userId: res.data?.UserId, deviceId: res.data?.DeviceId };
            store.setUserInfo(userInfo)
        } else {
            return next('/error')
        }
    }
    if (to.path != '/app_user_bind') {
        store.setFromRoute({ name: from.name, path: from.path, query: from.query, fullPath: from.fullPath })
    }
    next()
})

router.afterEach((to, from) => {
    if (!appEnv()) {
        const excludes = ['/news/list', '/news/info'];
        if (!excludes.includes(to.path)) {
            loadWeixinConfig({ hideOptionMenu: true })
        }
    } else {
        const store = useUserStore()
        const { loginStatus, userInfo } = storeToRefs(store)
        appVisit({ path: to.path, title: router.currentRoute.value.meta.title, query: Object.keys(to.query).length > 0 ? to.query : '', wxuserid: loginStatus.value == 1 ? userInfo.value?.userId : '' }).then((res: any) => {
        }).catch((err: any) => {
            console.error('appVisit error:', err);
        })
    }
})
