<script setup lang="ts">
import { onActivated, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { info, comments, updateComment } from "../api/news";
import {
  Divider,
  Grid,
  GridItem,
  Image,
  ActionSheet,
  Cell,
  Icon,
  Space,
  Tab,
  Tabs,
  Field,
  Form,
  Button,
  showFailToast,
  showSuccessToast,
} from "vant";
import { useUserStore } from "../stores";
import { storeToRefs } from "pinia";
import { loadWeixinConfig, previewImage, toUrlParams, softKeyboard, appEnv } from "../utils/common";
import AppNavBar from "@/components/AppNavBar.vue";

const route = useRoute();
const store = useUserStore();
const id = route.query.id || 0;
const from = route.query.from || "";
const showViewUser = route.query.showViewUser ? true : false;
const group = route.query.group ? true : false;
const goodAction = route.query.goodAction ? true : false;
const flowerAction = route.query.flowerAction ? true : false;
const commentAction = route.query.commentAction ? true : false;
const tabActiveName = ref("a");
const commentContent = ref("");
const newsInfo: any = ref(undefined);
const viewUsers: any = ref([]);
const showUsers: any = ref([]);
const goodUsers: any = ref([]);
const flowerUsers: any = ref([]);
const commentUsers: any = ref([]);
const showActionSheet = ref(false);
const showActionSheet1 = ref(false);
const commentForm = ref(false);
const commentContentSubmit = ref(false);
const bigImages = ref<string[]>([]);
const documentTitle = ref("");
const inApp = appEnv();

const loadCommnetData = async (flag: any) => {
  const params = { newsId: id, flag, current: 1, pageSize: 1000, from };
  let res: any = await comments(params);
  if (res.success && res?.data) {
    if (showViewUser && flag == 4) {
      viewUsers.value = res?.data;
      showUsers.value = res?.data.length > 6 ? res?.data.slice(0, 10) : res?.data;
    }
    if (goodAction && flag == 2) {
      goodUsers.value = res?.data;
      showUsers.value = res?.data.length > 6 ? res?.data.slice(0, 10) : res?.data;
    }
    if (flowerAction && flag == 1) {
      flowerUsers.value = res?.data;
      showUsers.value = res?.data.length > 6 ? res?.data.slice(0, 10) : res?.data;
    }
    if (commentAction && flag == 3) {
      commentUsers.value = res?.data;
    }
  }
};

const getNewsInfo = async () => {
  const params: any = { id, from, mobile: 1 };
  if (showViewUser) {
    const { loginStatus, userInfo } = storeToRefs(store);
    params.saveView = 1;
    params.wxuserid = userInfo.value.userId;
  }
  let res: any = await info(params);
  // console.log(res)
  if (res.success && res?.data) {
    documentTitle.value = res?.documentTitle;
    newsInfo.value = res?.data;
    if (res?.data.content) {
      const images: any = [];
      res?.data.content.replace(
        /<img [^>]*src=['"]([^'"]+)[^>]*>/gi,
        (matchStr: any, groups: any, index: any, sourceStr: any) => {
          if (groups.startsWith("http") === false) {
            groups = "https://fzrb.fznews.com.cn" + groups;
          }
          images.push(groups);
          return matchStr;
        }
      );
      bigImages.value = images;
    }

    const query: any = route.query;
    if (route.query?.code) {
      delete query.code
    }
    if (route.query?.state) {
      delete query.state
    }
    const params = toUrlParams(query);
    const imgUrl = bigImages.value.length > 0 ? bigImages.value[0] : 'https://fzrb.fznews.com.cn/static/images/fzrbs.png';
    loadWeixinConfig({
      updateAppMessageShareData: {
        title: newsInfo.value.title,
        desc: '福州日报社',
        link: 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/news/info?' + params,
        imgUrl
      }
    })
  }
  if (showViewUser) {
    loadCommnetData(4);
  }
  if (goodAction) {
    loadCommnetData(2);
  }
  if (flowerAction) {
    loadCommnetData(1);
  }
  if (commentAction) {
    loadCommnetData(3);
  }
};
onMounted(() => {
  getNewsInfo();
});
onActivated(() => {
  getNewsInfo();
});
const setShowActionSheet = () => {
  showActionSheet.value = true;
};
const setShowActionSheet1 = () => {
  showActionSheet1.value = true;
};
const setCommentForm = (value: boolean) => {
  commentForm.value = value;
};
const onSubmit = async (values: any) => {
  if (commentContent.value == "") {
    showFailToast("请输入评论内容");
    return;
  }
  commentContentSubmit.value = true;
  const { loginStatus, userInfo } = storeToRefs(store);
  let res: any = await updateComment({
    id,
    flag: 3,
    commnet: commentContent.value,
    wxuserid: userInfo.value.userId,
    from
  });
  commentContentSubmit.value = false;
  if (res.errorMessage) {
    showFailToast(res.errorMessage);
  } else {
    commentContent.value = "";
    showSuccessToast("提交成功");
    loadCommnetData(3);
    setCommentForm(false);
  }
};
const updateCommnetData = async (flag: any) => {
  const { loginStatus, userInfo } = storeToRefs(store);
  let res: any = await updateComment({ id, flag, wxuserid: userInfo.value.userId, from });
  let text = flag == 1 ? "送花成功" : "点赞成功";
  if (!res.errorMessage) {
    if (res?.data?.update == false) {
      text = flag == 1 ? "已经送花" : "已经点赞";
      showFailToast(text);
      return;
    }
    showSuccessToast(text);
    loadCommnetData(flag);
  }
};
const showBigImage = (e: any) => {
  if (e.target.tagName.toLowerCase() == "img") {
    let url = e.target.currentSrc;
    if (url.startsWith("http") === false) {
      url = "https://fzrb.fznews.com.cn" + url;
    }
    previewImage(bigImages.value, url);
  }
};
</script>

<template>
  <div class="cluesDetail" v-wechat-title="documentTitle"></div>
  <div class="page news-info-page">
    <AppNavBar></AppNavBar>
    <h1 class="news-info-title" :style="{ paddingTop: inApp ? '55px' : '10px' }">{{ newsInfo?.title }}</h1>
    <p class="news-info-base">
      <span v-if="group">部门：{{ newsInfo?.shorttitle }}&nbsp;&nbsp;&nbsp;&nbsp;推荐人：{{
        newsInfo?.redirect
      }}</span>
      <span v-else> {{ newsInfo?.publictime }}</span>
    </p>
    <p class="line-box">
      <Divider />
    </p>
    <div v-if="group" class="tuijianyu">
      <Cell center title="推荐人点评" :label="newsInfo?.remark" />
    </div>
    <div class="news-info-content" v-html="newsInfo?.content" @click="showBigImage($event)"></div>
    <Cell :border="false" title="" :label="'阅读 ' +
      (newsInfo ? newsInfo?.click : '') +
      (goodAction ? ' 点赞 ' + goodUsers.length.toString() : '') +
      (flowerAction ? ' 送花 ' + flowerUsers.length.toString() : '') +
      (commentAction ? ' 评论 ' + commentUsers.length.toString() : '')
      ">
      <template #right-icon>
        <Space class="newsinfo-action-box">
          <span v-if="flowerAction" @click="updateCommnetData(1)">
            <Icon name="flower-o" />送花
          </span>
          <span v-if="goodAction" @click="updateCommnetData(2)">
            <Icon name="good-job-o" />点赞
          </span>
        </Space>
      </template>
    </Cell>
    <div class="news-info-view" v-if="viewUsers.length > 0">
      <Divider />
      已有<span v-for="(value, index) in showUsers">{{
        value.username + (index < 9 ? "、" : "") }}</span>{{
          viewUsers.length > showUsers.length
            ? "...等" + viewUsers.length.toString() + "人"
            : ""
        }}浏览过<span class="more-view-user" @click="setShowActionSheet()">展开</span>
          <ActionSheet v-model:show="showActionSheet" title="已浏览用户">
            <div class="more-view-user-content">
              <Grid :border="false" column-num="6">
                <GridItem v-for="(value1, index1) in viewUsers" :key="value1">
                  <Image round :src="value1.avatar" />
                  <p>{{ value1.username }}</p>
                </GridItem>
              </Grid>
            </div>
          </ActionSheet>
          <div style="width: 100%; height: 30px"></div>
    </div>
    <div class="news-info-view-1" v-if="goodUsers.length > 0 || flowerUsers.length > 0">
      <Divider />
      已有<span>{{showUsers.map((row: any) => row.username).join('、')}}</span>{{
        goodUsers.length > 0
          ? goodUsers.length > showUsers.length
            ? "...等" + goodUsers.length.toString() + "人"
            : ""
          : flowerUsers.length > showUsers.length
            ? "...等" + flowerUsers.length.toString() + "人"
            : ""
      }}{{
        goodAction && flowerAction
          ? "点赞送花"
          : goodAction
            ? "点赞"
            : flowerAction
              ? "送花"
              : ""
      }}<span class="more-view-user" @click="setShowActionSheet1()">展开</span>
      <ActionSheet v-model:show="showActionSheet1" title=" ">
        <div class="more-view-user-content">
          <Tabs v-model:active="tabActiveName">
            <Tab title="点赞" name="a" v-if="goodAction" :badge="goodUsers.length">
              <Grid :border="false" column-num="6">
                <GridItem v-for="(value1, index1) in goodUsers" :key="value1">
                  <Image round :src="value1.avatar
                    ? value1.avatar
                    : 'https://fzrb.fznews.com.cn/static/images/default_face.jpg'
                    " />
                  <p>{{ value1.username }}</p>
                </GridItem>
              </Grid>
            </Tab>
            <Tab title="送花" name="b" v-if="flowerAction" :badge="flowerUsers.length">
              <Grid :border="false" column-num="6">
                <GridItem v-for="(value1, index1) in flowerUsers" :key="value1">
                  <Image round :src="value1.avatar
                    ? value1.avatar
                    : 'https://fzrb.fznews.com.cn/static/images/default_face.jpg'
                    " />
                  <p>{{ value1.username }}</p>
                </GridItem>
              </Grid>
            </Tab>
            <Tab title="评论" name="c" v-if="commentAction" :badge="commentUsers.length">
              <Cell v-for="(value1, index1) in commentUsers" :key="value1" :icon="value1.avatar
                ? value1.avatar
                : 'https://fzrb.fznews.com.cn/static/images/default_face.jpg'
                " :title="value1.username">
                <template #label>
                  <p class="news-info-comment-content">{{ value1.content_P }}</p>
                  <p class="news-info-comment-time">发表于:{{ value1.inserttime }}</p>
                </template>
              </Cell>
            </Tab>
          </Tabs>
        </div>
      </ActionSheet>
    </div>
    <div class="news-info-comment-box" v-if="commentAction">
      <p class="comment-action-btn" @click="setCommentForm(true)">写评论</p>
    </div>
    <div class="news-info-comment-form" v-if="commentForm">
      <h2 class="apps-block__title">写评论</h2>
      <Form @submit="onSubmit">
        <Field v-model="commentContent" rows="4" autosize type="textarea" maxlength="100" placeholder="请输入评论"
          show-word-limit @focus="softKeyboard" />
        <p style="margin: 20px">
          <Button round block type="primary" native-type="submit" :loading="commentContentSubmit" loading-text="正在提交">
            提交
          </Button>
        </p>
      </Form>
      <p class="news-info-comment-close">
        <Icon name="cross" size="16" @click="setCommentForm(false)" />
      </p>
    </div>
    <div class="page-bottom-box"></div>
  </div>
</template>
<style>
.news-info-content {
  padding: 15px;
  word-wrap: break-word;
  line-height: 2.5;
  font-size: 16px;
  color: #000;
}

.news-info-view,
.news-info-view-1 {
  padding: 10px;
  font-size: 12px;
  width: 100%;
  margin-bottom: 10px;
  padding-top: 0;
  color: #000;
}

.more-view-user {
  display: inline-block;
  margin-left: 20px;
  color: blue;
  cursor: pointer;
}

.news-info-comment-box {
  min-height: 100px;
  background-color: #f7f7f7;
  color: #000;
}

.comment-action-btn {
  text-align: center;
  height: 100px;
  line-height: 100px;
  font-size: 16px;
}

.news-info-comment-form {
  position: fixed;
  left: 0;
  bottom: 0;
  width: 100%;
  background-color: #f7f7f7;
  z-index: 100000;
}

.news-info-comment-close {
  position: absolute;
  top: 10px;
  right: 20px;
  color: #000;
}

.news-info-page .apps-block__title {
  font-size: 16px;
  padding: 10px;
  color: #000;
}

.page-bottom-box {
  height: 0px;
  background-color: #f7f7f7;
}

.news-info-title {
  font-size: 22px;
  line-height: 30px;
  margin-bottom: 10px;
  padding: 10px;
  font-weight: bold;
  color: #000;
}

.news-info-base {
  padding-left: 15px;
  color: #969799;
  font-size: 14px;
}

.news-info-content p {
  margin-bottom: 20px !important;
  color: #000;
}

.newsinfo-action-box {
  color: #969799;
  font-size: 12px;
}

.newsinfo-action-box .van-icon {
  font-size: 16px;
}

.news-info-view .van-popup,
.news-info-view-1 .van-popup {
  min-height: 250px;
  max-height: 500px;
}

.news-info-view .van-action-sheet__content {
  overflow-y: auto;
}

.news-info-view-1 .van-action-sheet__content {
  overflow-y: hidden;
}

.news-info-view-1 .van-tab__panel {
  overflow-y: auto;
  height: 500px;
}

.news-info-view-1 .van-grid {
  overflow-y: auto;
  height: auto;
}

.card-news-item .van-card__header {
  margin-bottom: var(--van-padding-xs);
}

.news-info-page .card-news-item .van-card__content {
  min-height: 120px;
}

.news-info-view-1 .van-grid-item__content--center {
  justify-content: normal;
}

.news-info-page .tuijianyu .van-cell__title span {
  font-weight: bold;
  font-size: 16px;
}

.news-info-page .tuijianyu .van-cell__label {
  font-size: 14px;
  line-height: 25px;
}

@media screen and (min-width: 500px) {
  .news-info-page {
    background-color: #fff;
  }

  .news-info-title {
    font-size: 22px;
    line-height: 30px;
    margin-bottom: 10px;
    padding: 10px;
    font-weight: bold;
    color: #000;
  }

  .news-info-base {
    padding-left: 15px;
    color: #969799;
    font-size: 14px;
  }

  .news-info-page .tuijianyu .van-cell__title span {
    font-weight: bold;
    font-size: 16px;
  }

  .news-info-page .tuijianyu .van-cell__label {
    font-size: 14px;
    line-height: 25px;
  }

  .newsinfo-action-box {
    color: #969799;
    font-size: 12px;
  }

  .newsinfo-action-box .van-icon {
    font-size: 16px;
  }

  .news-info-view,
  .news-info-view-1 {
    padding: 10px;
    font-size: 12px;
    width: 100%;
    margin-bottom: 10px;
    padding-top: 0;
  }

  .news-info-comment-box {
    min-height: 100px;
    background-color: #f7f7f7;
  }

  .comment-action-btn {
    text-align: center;
    height: 100px;
    line-height: 100px;
    font-size: 16px;
    cursor: pointer;
  }

  .news-info-comment-form {
    position: fixed;
    left: 50%;
    bottom: 0;
    width: 500px;
    background-color: #f7f7f7;
    z-index: 100000;
    margin-left: -250px;
  }

  .news-info-comment-close {
    position: absolute;
    top: 10px;
    right: 20px;
    cursor: pointer;
  }

  .news-info-page .apps-block__title {
    font-size: 16px;
    padding: 10px;
    color: #000;
  }

  .news-info-view .van-popup,
  .news-info-view-1 .van-popup {
    min-height: 250px;
    max-height: 500px;
  }

  #app .van-popup--bottom {
    width: 500px;
    margin-left: -250px;
    left: 50%;
  }

  .news-info-view-1 .van-tab__panel {
    overflow-y: auto;
    height: 500px;
  }

  .news-info-view-1 .van-grid {
    overflow-y: auto;
    height: auto;
  }

  .news-info-page .card-news-item .van-card__content {
    min-height: 120px;
  }

  .news-info-content {
    padding: 15px;
    word-wrap: break-word;
    line-height: 2.5;
    font-size: 16px;
    color: #000;
  }

  .news-info-content p {
    margin-bottom: 20px !important;
    color: #000;
  }

  .more-view-user {
    display: inline-block;
    margin-left: 20px;
    color: blue;
    cursor: pointer;
  }
}
</style>
