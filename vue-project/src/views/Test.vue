<script setup lang="ts">
import { Button, showImagePreview, Icon, CellGroup, Cell, Space, Loading, Search, RadioGroup, Radio, Field, Form, showToast, showLoadingToast, closeToast, showDialog } from 'vant';
import { loadWeComConfig, base64ToBlob } from '../utils/common'
import * as ww from '@wecom/jssdk'
import { onMounted, ref } from 'vue';
import { previewFile, departmentRule, searchNameRule } from '../api/config';
import PdfPreview from '@/components/PdfPreview.vue';
import { request } from '../utils/request';
import { storeToRefs } from "pinia";
import { useUserStore } from "../stores";
import AddressBook from '@/components/AddressBook/view.vue';
import Tabbar from '@/components/Tabbar.vue';

import { addressBookPopup } from '@/components/AddressBook/index';

import { appEnv } from '../utils/common'
import { weixinRecharge, weixinPaySign, weixinPayCancel, weixinPaySuccess, getH5PayUrl } from "../api/shitang";
import { useRoute } from "vue-router";

const store1 = useUserStore();
const filedata: any = ref(undefined);
const showpdffile: any = ref(false);
// const { loginStatus, userInfo } = storeToRefs(store1);


const showAddressBook: any = ref<any>(false);

const tabs = [
    { title: '新建申请', to: '/szyd/edit' },
    { title: '我的申请', to: '/szyd/index?flag=my' },
    { title: '我的申请', to: '/szyd/index?flag=receive' },
];


const route = useRoute();
const openid = route.query.openid || "";
const money = ref('');
const money1 = ref('');
const money2 = ref('');
const store = useUserStore();
const { loginStatus, userInfo } = storeToRefs(store);
const paySign = ref<any>({});
const orderId = ref(0);
const actionUrl = ref('');
const fromRef: any = ref(null)
const insideApp = appEnv()

onMounted(async () => {
    // await loadWeComConfig({ agentId: '1000002' });

    // ww.setClipboardData({
    //     data: 'data',
    //     success: () => {
    //         alert('success')
    //     },
    //     fail: (err: any) => {
    //         alert(err.errMsg)
    //         console.log(err)
    //     }
    // })

    // let fileurl = '/uploaded/information/20230911/16944445401720.jpg'
    // fileurl = '/uploaded/information/20230911/test.pdf'
    // request('common/preview-file', { fileurl, stream: 1 }, 'post', false, { responseType: 'blob' }).then((response: any) => {
    //     const pdfUrl = window.URL.createObjectURL(response);
    //     filedata.value = pdfUrl
    //     showpdffile.value = true
    //     // showImagePreview({
    //     //     images: [
    //     //         filedata.value,
    //     //     ],
    //     //     closeable: true,
    //     // });
    // })
    // previewFile({ fileurl, wxuserid: userInfo.value.userId }).then((res: any) => {
    //     console.log(res.data)
    //     if (res?.data) {
    //         // filedata.value = 'data:image/png;base64,' + res.data.content
    //         // showImagePreview({
    //         //     images: [
    //         //         filedata.value,
    //         //     ],
    //         //     closeable: true,
    //         // });
    //         const pdfUrl = base64ToBlob(res.data.content, 'application/pdf')
    //         // window.open(pdfUrl);
    //         filedata.value = pdfUrl
    //         showpdffile.value = true
    //     }
    // })

    showAddressBook.value = true

    // addressBookPopup({ parentid: 0, updateSelectedData: updateSelectedData })
})

const addorder = async () => {
    ww.getClipboardData({
        success: (res: any) => {
            alert(res.data)
        },
        fail: (err: any) => {
            alert(err.errMsg)
            console.log(err)
        }
    })
    showpdffile.value = true
}
const onLoaded = () => {
    console.log('加载完成');
}

const updateSelectedData = (data: any) => {
    console.log('updateSelectedData')
    console.log(data)
    showAddressBook.value = false
}

const onSubmit = async (values: any) => {
    if (values.money == '') {
        showToast('请输入充值金额');
        return
    } else if (values.money < 10) {
        showToast('充值金额不少于10元');
        return
    }
    showLoadingToast({
        duration: 0,
        forbidClick: true,
        message: '正在充值...',
    });
    const resUrl: any = await getH5PayUrl({ wxuserid: 'ChenYiXiao', money: values.money, money1: money1.value, money2: money2.value })
    closeToast();
    if (resUrl?.h5_url) {
        window.open(resUrl?.h5_url, '_self')
    } else {
        showToast(resUrl?.errorMessage);
    }
}

const changeMoney = (value: string) => {
    if (value != '') {
        const tempMoney = parseFloat(value);
        money2.value = (tempMoney * 0.006).toFixed(2).toString();
        money1.value = (tempMoney - (tempMoney * 0.006)).toFixed(2).toString();
    } else {
        money1.value = ''
    }
}
</script>

<template>
    <div v-wechat-title="$route.meta.title"></div>
    <!-- <div class="page test-page"> -->
        <!-- <div></div>test
    <div><Button size="normal" block type="primary" @click="addorder">
            添加
        </Button></div>

    <div class="pdfBox" v-if="showpdffile">
        <PdfPreview page-scale="page-fit" theme="light" :src="filedata" @loaded="onLoaded" />
        <Icon name="cross" class="pdfBoxClose" @click="showpdffile = false" />
    </div> -->

        <!-- <AddressBook selecttype="user" v-if="showAddressBook" @close="showAddressBook = false"
        @update-selected-data="updateSelectedData"></AddressBook> -->

        <!-- <Tabbar :tabs="tabs"></Tabbar> -->

    <!-- </div> -->

    <!-- <div class="page shitang-weixinrecharge-page">
        <p class="friendInfo">友情提醒：微信支付充值需扣微信支付费率0.60%</p>
        <Form @submit="onSubmit">
            <Field v-model="money" name="money" label="充值金额" placeholder="请输入充值金额" type="number"
                :rules="[{ required: true, message: '请输入充值金额' }]" @update:model-value="changeMoney" />
            <CellGroup v-if="money1 != ''">
                <Cell title="食堂充值金额" :value="money1" />
                <Cell title="微信费率金额" :value="money2" />
            </CellGroup>
            <div style="padding: 10px;">
                <Button round block type="primary" native-type="submit">确定充值</Button>
            </div>
        </Form>
    </div> -->

    <!-- <AddressBook :hideCloseIcon="true" selecttype="none" /> -->
</template>
<style scoped>
.pdfBox {
    position: fixed;
    width: 100vw;
    height: 100vh;
    top: 0;
    left: 0;
}

.pdfBoxClose {
    position: absolute;
    right: 10px;
    top: 5px;
    z-index: 100000000;
    width: 25px;
    height: 20px;
    background: rgb(225, 225, 225);
    text-align: center;
}
</style>
