<script setup lang="ts">
import { onActivated, ref } from "vue";
import NewsList from "../components/NewsList.vue";
import { onBeforeRouteLeave, useRoute } from "vue-router";
import { useUserStore } from "@/stores";
import { storeToRefs } from "pinia";
import { channelName } from "../api/news";
import { loadWeixinConfig, toUrlParams } from '../utils/common';
import AppNavBar from "@/components/AppNavBar.vue";

const route = useRoute();
const id = route.query.channelid || 0;
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

const documentTitle = ref("");

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
  const query: any = route.query;
  if (route.query?.code) {
    delete query.code
  }
  if (route.query?.state) {
    delete query.state
  }
  const params = toUrlParams(query);
  loadWeixinConfig({
    updateAppMessageShareData: {
      title: documentTitle.value,
      desc: '福州日报社',
      link: 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/news/list?' + params,
      imgUrl: 'https://fzrb.fznews.com.cn/static/images/fzrbs.png',
    }
  })
});
</script>

<template>
  <div v-wechat-title="documentTitle"></div>
  <div class="page news-list-page">
    <AppNavBar v-if="[13376, 13390, 13378, 13377].includes(parseInt(id.toString()))"></AppNavBar>
    <Suspense>
      <NewsList :channelId="parseInt(id.toString())" :pageSize="20" :from="from.toString()" :isLink="isLink"
        :showTime="showTime" :showView="showView" :showGoodNum="showGoodNum" :showViewUser="showViewUser" :group="group"
        :goodAction="goodAction" :flowerAction="flowerAction" :commentAction="commentAction" />
    </Suspense>
  </div>
</template>
