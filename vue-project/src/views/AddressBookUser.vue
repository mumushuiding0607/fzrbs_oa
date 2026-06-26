<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Card, Cell, CellGroup, NavBar } from 'vant';
import { useRoute } from "vue-router";
import { searchNameRule } from '../api/config';

const route = useRoute();
const userid = route.query.userid || '';
const userInfo: any = ref<any>(undefined);
onMounted(async () => {
    if (userid !== '') {
        searchNameRule({ userid: userid }).then((res: any) => {
            if (res?.data) {
                userInfo.value = res?.data[0]
            }
        });
    }
})
const onClickLeft = () => {
    window.history.back();
}
</script>

<template>
    <div v-wechat-title="$route.meta.title"></div>
    <div class="page addressbook-user-page">
        <NavBar title="" left-text="返回" left-arrow @click-left="onClickLeft" />
        <Card num="" price="" :desc="userInfo?.name" title="" :thumb="userInfo?.avatar">
            <template #price>
            </template>
            <template #num>
            </template>
        </Card>
        <CellGroup style="margin-top: 10px;">
            <Cell title="手机" :value="userInfo?.mobile" v-if="userInfo?.mobile">
                <template #value>
                    <div class="van-cell__value">
                        <a :href="'tel:' + userInfo?.mobile">{{ userInfo?.mobile }}</a>
                    </div>
                </template>
            </Cell>
            <Cell title="座机" :value="userInfo?.telephone" v-if="userInfo?.telephone">
                <template #value>
                    <div class="van-cell__value">
                        <a :href="'tel:' + userInfo?.telephone">{{ userInfo?.telephone }}</a>
                    </div>
                </template>
            </Cell>
            <Cell title="邮箱" :value="userInfo?.email" v-if="userInfo?.email" />
            <Cell title="部门" :value="userInfo?.departmentname" v-if="userInfo?.departmentname" />
        </CellGroup>
    </div>
</template>
<style>
.addressbook-user-page {
    height: 100vh;
    background-color: #eff2f5;
}

.addressbook-user-page .van-card {
    background-color: #fff;
}

.addressbook-user-page .van-card__content {
    display: flex;
    justify-content: center;
}

.addressbook-user-page .van-card__desc {
    font-size: 20px;
    color: #000;
    padding-left: 20px;
}

.addressbook-user-page .van-cell__value {
    text-align: left;
    flex: 4;
}

.addressbook-user-page .van-cell__title {
    padding-left: 10px;
}

@media screen and (min-width: 500px) {
    .addressbook-user-page .van-card__desc {
        font-size: 20px;
        color: #000;
        padding-left: 20px;
    }

    .addressbook-user-page .van-cell__title {
        padding-left: 10px;
    }
}
</style>
