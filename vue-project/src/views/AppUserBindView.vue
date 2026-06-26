<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Form, Field, Button, CellGroup, Divider, showLoadingToast, showSuccessToast, closeToast, showToast, showFailToast, showDialog } from 'vant';
import { useUserStore } from '@/stores'
import { storeToRefs } from 'pinia'
import router from '@/router';
import { getSmsCode, appUserBind, appAutoLogin } from '../api/login';
import Vcode from 'vue3-puzzle-vcode'
import AppBindWx from '@/components/AppBindWx/view.vue';
import { appEnvUserrId } from '../utils/common'
import { showAppBindWxDialog, closeAppBindWxDialog } from "@/components/AppBindWx/index";
import { useRoute } from "vue-router";

const route = useRoute();
const mobile = ref('');
const smscode = ref('');
const formRef = ref();
const smsText = ref('发送验证码')
const isBindFormShow = ref(false);
const isShow = ref(false);
let appuserid: any = appEnvUserrId()

let timer: any;
let second = 60;
const store = useUserStore()
const { appUserInfo, fromRoute } = storeToRefs(store)
if (route.query.flag && parseInt(route.query.flag.toString()) == 1) {
    isBindFormShow.value = true
}

const onSubmit1 = async (values: any) => {
    checkAppUserLogin()
    showLoadingToast({
        duration: 0,
        forbidClick: true,
        message: '正在绑定...',
    });
    values.appuserid = appUserInfo.value?.userid > -1 ? appUserInfo.value?.userid : appuserid;
    values.token = appUserInfo.value?.token;
    values.score = appUserInfo.value?.score;
    values.devid = appUserInfo.value?.device;
    // console.log(values)
    let res: any = await appUserBind(values);
    closeToast();
    if (res.success) {
        if (res?.errorMessage != '') {
            showFailToast(res?.errorMessage);
        } else {
            if (res?.data) {
                const userInfo = { userId: res.data?.UserId, deviceId: res.data?.DeviceId, appuserid: values.appuserid, virtualKeyUsers: res.data?.virtualKeyUsers };
                store.setUserInfo(userInfo)
                showSuccessToast('绑定成功');
                backPage();
            }
        }
    }
}


const sendSmsCode = async () => {
    if (!/^1[3456789]\d{9}$/.test(mobile.value)) {
        showToast({
            message: '手机号格式错误',
            position: 'top',
        })
        return
    }
    if (!timer) {
        timer = setInterval(() => {
            second--;
            if (second) {
                smsText.value = `${second}`;
            } else {
                clearInterval(timer);
                smsText.value = '发送验证码';
                timer = undefined;
            }
        }, 1000);
        let res: any = await getSmsCode({ mobile: mobile.value });
        if (res.success) {
            if (res.errorMessage != '') {
                clearInterval(timer);
                smsText.value = '发送验证码';
                timer = undefined;
            }
        }
    }
}

const backPage = () => {
    const url = fromRoute.value?.fullPath ? fromRoute.value?.fullPath : '/'
    setTimeout(() => {
        closeToast();
        router.replace(url);
    }, 500);
}

const onClose = () => {
    isShow.value = false;
};

const onSuccess = () => {
    onClose(); // 验证成功，手动关闭模态框
    sendSmsCode()
};

const showVcode = () => {
    if (!/^1[3456789]\d{9}$/.test(mobile.value)) {
        showToast({
            message: '手机号格式错误',
            position: 'top',
        })
        return
    }
    isShow.value = true
}

const checkAppUserLogin = () => {
    if (appUserInfo.value?.userid == -1 && appuserid == -1) {
        showDialog({ message: '请先登录app', showConfirmButton: false });
    } else if (!isBindFormShow.value) {
        showAppBindWxDialog({ dialog: true, showLoading: true, title: '正在自动登录验证' })
        appAutoLogin({ uid: appuserid }).then((res: any) => {
            closeAppBindWxDialog()
            if (res?.errorMessage != '') {
                closeToast();
                showToast(res?.errorMessage);
                isBindFormShow.value = true
            } else {
                const userInfo = { userId: res.data?.UserId, deviceId: res.data?.DeviceId, appuserid: appuserid };
                store.setUserInfo(userInfo)
                const url = fromRoute.value?.fullPath ? fromRoute.value?.fullPath : '/'
                router.replace(url);
            }
        })
    } else {
        closeToast();
        showToast('用户匹配失败，请先绑定');
    }
}

onMounted(async () => {
    checkAppUserLogin()
})
</script>
<template>
    <div v-wechat-title="$route.meta.title"></div>
    <div v-if="isBindFormShow">
        <div style="padding: 30px;">
            <AppBindWx />
        </div>
        <Form @submit="onSubmit1" ref="formRef">
            <CellGroup inset>
                <Field v-model="mobile" type="tel" name="mobile" label="手机号" placeholder="请输入企业微信用户手机号"
                    :rules="[{ required: true, message: '请输入企业微信用户手机号' }, { pattern: /^1[3456789]\d{9}$/, message: '手机号码格式错误！' }]" />
                <Field v-model="smscode" type="digit" name="smscode" center clearable label="动态验证码"
                    placeholder="请输入动态验证码" :rules="[{ required: true, message: '请输入动态验证码' }]">
                    <template #button>
                        <Button size="small" type="primary" @click="showVcode">{{ smsText }}</Button>
                    </template>
                </Field>
            </CellGroup>
            <Divider>请在企业微信中接收动态验证码</Divider>
            <div style="margin: 16px;">
                <Button round block type="primary" native-type="submit">
                    绑定
                </Button>
            </div>
        </Form>
        <Vcode :show="isShow" @success="onSuccess" @close="onClose" />
    </div>
</template>
<style>
.vue-puzzle-vcode {
    position: absolute;
    height: 100vh;
    width: 100vw;
}
</style>