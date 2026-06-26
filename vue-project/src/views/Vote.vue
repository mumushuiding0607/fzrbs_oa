<script setup lang="ts">
import { onActivated, ref } from "vue";
import { onBeforeRouteLeave, useRoute } from "vue-router";
import { useUserStore } from "@/stores";
import { storeToRefs } from "pinia";
import { channelInfo, channelList, channelSave } from "../api/activityvote";
import { Divider, Button, Field, Icon, Popup, showToast, showSuccessToast, Grid, GridItem, Image, CheckboxGroup, Checkbox, ActionBar, ActionBarButton, Empty } from 'vant';
import dayjs from "dayjs"
const route = useRoute();
const id: any = route.query.channelid || 0;
// 信息列表是否分组显示成特殊信息列表
const group = route.query.group ? true : false;

const store1 = useUserStore();
const { loginStatus, userInfo } = storeToRefs(store1);
const submitLoading = ref(false);
const submitDisabled = ref(false);
const documentTitle = ref("");
const activityInfo = ref<any>({});
const voteList: any = ref([]);
const checkgroupResult = ref<any>({});
const limitChannelIds = ref<any>([]);
const voreRemark = ref<string>('');
const pageConfig = ref<any>(undefined);

const scrollTop = ref(0);
onBeforeRouteLeave((to, from) => {
    scrollTop.value = document.documentElement.scrollTop || 0;
});

onActivated(async () => {
    const store = useUserStore();
    const { fromRoute } = storeToRefs(store);
    let res: any = await channelInfo({ channelid: id });
    if (res?.data) {
        activityInfo.value = res?.data
        documentTitle.value = res?.data?.name;
        voreRemark.value = res?.data?.content;
    }
    if (res?.config?.limitChannelIds) {
        limitChannelIds.value = res?.config?.limitChannelIds
        if (!limitChannelIds.value.includes(parseInt(id))) {
            documentTitle.value = '投票';
        }
    }
    if (res?.config?.pageConfig) {
        pageConfig.value = res?.config?.pageConfig
    }
    const params: any = { channelid: id }
    if (group) {
        params.group = 1;
    }
    res = await channelList(params);
    if (res.data.length > 0) {
        voteList.value = res.data
    }

});

const onSubmit = async () => {
    const voteids: any = {};
    const votenames: any = {};
    if (Object.keys(checkgroupResult.value).length == 0) {
        showToast('请选择投票项目')
        return
    }
    for (let element of voteList.value) {
        if (!checkgroupResult.value[element.id] || (element?.config && element?.config?.min && checkgroupResult.value[element.id].length < element?.config?.min)) {
            showToast(element.name + '至少选择 ' + element?.config?.min + ' 个')
            return;
        }
        if (element?.config && element?.config?.max && element?.config?.maxnum) {
            if (!checkgroupResult.value[element.id] || (checkgroupResult.value[element.id].length < element?.config?.max)) {
                showToast(element.name + '必须选择 ' + element?.config?.max + ' 个')
                return;
            }
        }
        voteids[element.id] = checkgroupResult.value[element.id]
        votenames[element.id] = element.name
    }
    submitLoading.value = true
    submitDisabled.value = true
    const res: any = await channelSave({ wxuserid: userInfo.value.userId, voteids: voteids, votenames, channelid: id, group: group ? 1 : 0 })
    submitLoading.value = false
    submitDisabled.value = false
    if (!res.errorMessage) {
        showSuccessToast('提交成功')
        if (res?.data) {
            const tempData = voteList.value
            tempData.forEach((element: any, index: any) => {
                res?.data.forEach((element1: any, index1: any) => {
                    const index2 = element.list.findIndex((element2: any) => element2.id == element1.id)
                    if (index2 > -1) {
                        tempData[index].list[index2].num = element1.num
                    }
                })
            });
            voteList.value = tempData
        }
    }
}
</script>

<template>
    <div v-wechat-title="documentTitle"></div>
    <div class="page vote-page"
        :style="{ backgroundColor: pageConfig?.backgroundColor ? pageConfig?.backgroundColor : '#fff', paddingTop: pageConfig?.pagePaddtingTop ? pageConfig?.pagePaddtingTop : '0' }">
        <div v-if="pageConfig?.headerImage && pageConfig?.headerImage != ''" class="header"><img
                :src="pageConfig?.headerImage"></div>
        <div class="remark-box" v-if="voreRemark != ''">
            <div v-html="voreRemark" />
        </div>
        <div class="vote-list-box">
            <template v-if="limitChannelIds.includes(parseInt(id))">
                <div v-for="(value, index) in voteList">
                    <div class="subchanneltitle" v-if="value.name != ''"
                        :style="{ color: pageConfig?.titleColor ? pageConfig?.titleColor : '#000' }">{{ value.name }}
                    </div>
                    <CheckboxGroup v-model="checkgroupResult[value.id]"
                        :max="value?.config && value?.config?.max ? value?.config?.max : 0">
                        <Grid :border="true" :gutter="10" column-num="2">
                            <GridItem v-for="(value1, index1) in value.list">
                                <Image :src="'https://fzrb.fznews.com.cn' + value1.image" v-if="value1.image != ''" />
                                <p class="index-grid-text">{{ value1.title }}</p>
                                <p class="index-grid-votenum">
                                    <Checkbox :name="value1.id"></Checkbox>{{ value1.num }}票
                                </p>
                            </GridItem>
                        </Grid>
                    </CheckboxGroup>
                </div>
                <ActionBar :safe-area-inset-bottom="true" :style="{ backgroundColor: 'transparent', zIndex: 10000 }">
                    <ActionBarButton type="danger"
                        :color="pageConfig?.submitButtonColor && pageConfig?.submitButtonColor != '' ? pageConfig?.submitButtonColor : '#3366CC'"
                        text="提交" @click="onSubmit" :disabled="submitDisabled" :loading="submitLoading" />
                </ActionBar>
            </template>
            <Empty description="投票项目不存在" v-else />
        </div>
    </div>
</template>
<style>
.vote-page {
    background-color: #fff;
    position: relative;
    padding-bottom: 100px;
}

.vote-page .index-grid-text {
    font-size: 16px;
}

.vote-page .index-grid-votenum {
    margin-top: 15px;
    font-weight: bold;
}

.vote-list-box {
    padding-top: 20px;
}

.header img {
    width: 100%;
    display: block;
}

.remark-box {
    margin: 0 auto;
    width: 94%;
    background-color: #fff;
    font-size: 3.5vw;
    border-radius: 20px;
    padding: 20px;
    box-sizing: border-box;
    line-height: 25px;
}

.remark-box p {
    margin-bottom: 10px;
}

.subchanneltitle {
    text-align: center;
    color: black;
    font-size: 20px;
    margin-bottom: 20px;
    margin-top: 20px;
}

@media screen and (min-width: 500px) {
    .vote-page {
        background-color: #fff;
        position: relative;
        padding-bottom: 100px;
    }

    .vote-page .index-grid-text {
        font-size: 16px;
    }

    .vote-page .index-grid-votenum {
        margin-top: 15px;
        font-weight: bold;
    }

    .vote-page .van-action-bar {
        width: 500px;
        margin: 0 auto;
    }

    .vote-list-box {
        padding-top: 20px;
    }

    .remark-box {
        margin: 0 auto;
        width: 94%;
        background-color: #fff;
        font-size: 3.5vw;
        border-radius: 20px;
        padding: 20px;
        box-sizing: border-box;
        line-height: 25px;
    }

    .remark-box p {
        margin-bottom: 10px;
    }

    .subchanneltitle {
        text-align: center;
        color: black;
        font-size: 20px;
        margin-bottom: 20px;
        margin-top: 20px;
    }
}
</style>