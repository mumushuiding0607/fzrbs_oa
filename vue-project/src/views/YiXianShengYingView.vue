<script setup lang="ts">
import { onActivated, ref } from "vue";
import NewsList from "../components/NewsList.vue";
import { onBeforeRouteLeave, useRoute } from "vue-router";
import { useUserStore } from "@/stores";
import { storeToRefs } from "pinia";
import { channelName } from "../api/news";
import { Divider, Button, Field, Icon, Popup, PickerGroup, DatePicker, TimePicker, Uploader, showToast, showSuccessToast } from 'vant';
import dayjs from "dayjs"
import { checkUploadType, uploadedFiles, previewImage, softKeyboard } from '../utils/common'
import { uploadDelete } from '../api/config'
import { infoUpload } from "../api/news";
const route = useRoute();
const id: any = route.query.channelid || 0;
// 信息来源标识，如新闻网，企业号，内网等
const from = route.query.from || "";
// 普通信息列表是否显示右箭头
const isLink = route.query.isLink ? true : false;
// 普通信息列表是否显示发布时间
const showTime = route.query.showTime ? true : false;
// 普通信息列表是否显示浏览数
const showView = route.query.showView ? true : false;
// 普通信息列表是否显示点赞数
const showGoodNum = route.query.showGoodNum ? true : false;
// 信息详情是否显示浏览用户
const showViewUser = route.query.showViewUser ? true : false;
// 信息列表是否分组显示成特殊信息列表
const group = route.query.group ? true : false;
// 信息详情是否显示点赞按钮
const goodAction = route.query.goodAction ? true : false;
// 信息详情是否显示送花按钮
const flowerAction = route.query.flowerAction ? true : false;
// 信息详情是否显示评论按钮
const commentAction = route.query.commentAction ? true : false;

const store1 = useUserStore();
const { loginStatus, userInfo } = storeToRefs(store1);
const content = ref('');
const title = ref('');
const worktime = ref('');
const address = ref('');
const submitLoading = ref(false);
const submitDisabled = ref(false);
const upload = ref(false);
const showPicker = ref(false);
const currentDate = ref<any[]>([dayjs().format('YYYY'), dayjs().format('MM'), dayjs().format('DD')]);
const currentTime = ref([dayjs().format('HH'), dayjs().format('mm')]);
const uploadImage = ref<any>([]);
const documentTitle = ref("");
const listRef = ref();

const scrollTop = ref(0);
onBeforeRouteLeave((to, from) => {
    scrollTop.value = document.documentElement.scrollTop || 0;
});

onActivated(async () => {
    const store = useUserStore();
    const { fromRoute } = storeToRefs(store);
    if (fromRoute.value.name != "home") {
        document.documentElement.scrollTop = scrollTop.value;
    }
    let res: any = await channelName({ channelid: id });
    if (res?.documentTitle && res?.documentTitle != "") {
        documentTitle.value = res?.documentTitle;
    }
});

const onSubmit = async () => {
    if (title.value.trim() == '') {
        showToast('请输入标题');
        return;
    }
    if (worktime.value.trim() == '') {
        showToast('请选择时间');
        return;
    }
    if (address.value.trim() == '') {
        showToast('请输入地点');
        return;
    }
    if (content.value.trim() == '') {
        showToast('请输入内容');
        return;
    }
    const images: any = [];
    uploadImage.value.forEach((element: any) => {
        if (element?._url) {
            images.push(element?._url)
        }
    });
    if (images.length == 0) {
        showToast('请上传图片');
        return;
    }
    submitLoading.value = true
    submitDisabled.value = true
    const res: any = await infoUpload({ wxuserid: userInfo.value.userId, value: { content: content.value, title: title.value, channelid: id, image: images.join(','), address: address.value, worktime: worktime.value } })
    submitLoading.value = false
    submitDisabled.value = false
    if (!res.errorMessage) {
        content.value = ''
        title.value = ''
        address.value = ''
        worktime.value = ''
        uploadImage.value = []
        showSuccessToast('保存成功')
        upload.value = false
        listRef?.value?.relaodlist();
    }
}
const onConfirm = async () => {
    worktime.value = currentDate.value.join('-') + " " + currentTime.value.join(':')
    showPicker.value = false
}
const beforeRead = (file: any) => {
    return checkUploadType(1, file)
};

const afterRead = (file: any) => {
    uploadedFiles(file, { 'uploadType': "1", 'uploadPath': 'information' });
};

const beforeDelete = (file: any) => {
    uploadDelete(file._url)
    return true
};
</script>

<template>
    <div v-wechat-title="documentTitle"></div>
    <div class="page yixianshengying-index-page">
        <div v-show="upload">
            <p class="apps-block__title">标题</p>
            <Field v-model="title" rows="3" name="title" autosize label="" type="textarea" placeholder="请输入标题" />
            <p class="apps-block__title">时间</p>
            <Field v-model="worktime" name="worktime" label="" placeholder="请选择时间" @click="showPicker = true"
                :readonly="true" />
            <Popup v-model:show="showPicker" position="bottom">
                <PickerGroup title="" :tabs="['选择日期', '选择时间']" @confirm="onConfirm" @cancel="showPicker = false">
                    <DatePicker v-model="currentDate" />
                    <TimePicker v-model="currentTime" />
                </PickerGroup>
            </Popup>
            <p class="apps-block__title">地点</p>
            <Field v-model="address" name="address" label="" placeholder="请输入地点" />
            <p class="apps-block__title">内容</p>
            <Field v-model="content" rows="10" name="content" autosize label="" type="textarea" placeholder="请输入内容" />
            <p class="apps-block__title">图片上传</p>
            <Field name="uploader" label="">
                <template #input>
                    <Uploader v-model="uploadImage" multiple :max-count="5" accept="image/*" :before-read="beforeRead"
                        :after-read="afterRead" :before-delete="beforeDelete" />
                </template>
            </Field>
            <div style="margin: 10px;">
                <Button round block size="normal" type="primary" @click="onSubmit" :loading="submitLoading"
                    loading-text="正在提交" :disabled="submitDisabled">
                    提交
                </Button>
            </div>
            <div class="closeupload" @click="upload = false">
                <Icon name="cross" size="20" />
            </div>
        </div>
        <div v-show="!upload">
            <Divider>打动你的一线工作场景，请随手拍照上传</Divider>
            <div class="uploadbtnbox">
                <Button round block size="normal" type="primary" @click="upload = true">
                    我要上传
                </Button>
            </div>
            <Suspense>
                <NewsList :channelId="parseInt(id.toString())" :pageSize="20" :from="from.toString()" :isLink="isLink"
                    :showTime="showTime" :showView="showView" :showGoodNum="showGoodNum" :showViewUser="showViewUser"
                    :group="group" :goodAction="goodAction" :flowerAction="flowerAction" :commentAction="commentAction"
                    ref="listRef" />
            </Suspense>
        </div>
    </div>
</template>
<style>
.yixianshengying-index-page {
    background-color: #fff;
    position: relative;
}

.yixianshengying-index-page .uploadbtnbox {
    margin: 20px;
}

.yixianshengying-index-page .van-cell,
.yixianshengying-index-page .van-button--normal {
    font-size: 16px;
}

.apps-block__title {
    padding: 10px;
    font-size: 14px;
    background-color: #eff2f5;
}

.closeupload {
    position: absolute;
    top: 10px;
    right: 10px;
}

@media screen and (min-width: 500px) {
    .yixianshengying-index-page .uploadbtnbox {
        margin: 20px;
    }

    .yixianshengying-index-page .van-cell,
    .yixianshengying-index-page .van-button--normal {
        font-size: 14px;
    }

    .apps-block__title {
        padding: 10px;
        font-size: 14px;
    }

    .closeupload {
        position: absolute;
        top: 10px;
        right: 10px;
        cursor: pointer;
    }
}
</style>