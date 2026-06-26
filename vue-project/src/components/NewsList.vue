<script setup lang="ts">
import { ref } from "vue";
import { List, Empty, Cell, Card, Icon, Space, Sticky } from "vant";
import { list, info } from "../api/news";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores";
import { storeToRefs } from "pinia";
const store = useUserStore();

const props = withDefaults(
  defineProps<{
    channelId: number;
    pageSize?: number;
    from?: string;
    isLink?: boolean;
    showTime?: boolean;
    showView?: boolean;
    showGoodNum?: boolean;
    showViewUser?: boolean;
    group?: boolean;
    goodAction?: boolean;
    flowerAction?: boolean;
    commentAction?: boolean;
    download?: boolean;
  }>(),
  {
    pageSize: 20,
    isLink: true,
    showTime: false,
    showView: false,
    showGoodNum: false,
    showViewUser: false,
    group: false,
    goodAction: false,
    flowerAction: false,
    commentAction: false,
    download: false,
  }
);

let current = 1;
const newsList: any = ref([]);
const loading = ref(false);
const finished = ref(false);
const empty = ref(false);
const finishedText = ref("");
const documentTitle = ref("");

const onLoad = async () => {
  loading.value = true;
  let params: any = {
    channelid: props.channelId,
    pageSize: props.pageSize,
    from: props.from,
    current,
  };
  if (props.group) {
    params.group = 1;
    params.showCommentNum = 1;
    params.showGoodNum = 1;
    params.showGiftNum = 1;
    params.pageSize = 5;
  }
  if (!params?.showGoodNum && props.showGoodNum) {
    params.showGoodNum = 1;
  }
  let res: any = await list(params);
  if (res.success && current == 1 && res?.documentTitle) {
    documentTitle.value = res?.documentTitle;
  }
  if (res.errorMessage) {
    loading.value = false;
    finished.value = true;
    empty.value = true;
  } else {
    if (res.success && res?.data) {
      if (res?.data?.data?.length > 0) {
        newsList.value.push(...res?.data.data);
      }
      if (current == 1) {
        if (res?.data?.data?.length == 0) {
          empty.value = true;
        }
      }
      // 加载状态结束
      loading.value = false;
      // 数据全部加载完成
      if (res?.data.data.length == 0 || res?.data?.data?.length < params.pageSize) {
        finished.value = true;
        if (current > 1) {
          finishedText.value = "没有更多了";
        }
      } else {
        current++;
      }
    }
  }
  return {
    newsList,
    onLoad,
    loading,
    finished,
    empty,
  };
};
const router = useRouter();
const openNewsInfo = (id: string) => {
  let path = "/news/info?id=" + id;
  if (props.from) {
    path += "&from=" + props.from;
  }
  if (props.showViewUser) {
    path += "&showViewUser=1";
  }
  if (props.group) {
    path += "&group=1";
  }
  if (props.goodAction) {
    path += "&goodAction=1";
  }
  if (props.flowerAction) {
    path += "&flowerAction=1";
  }
  if (props.commentAction) {
    path += "&commentAction=1";
  }
  router.push(path);
};
const downloadFile = async (row: any) => {
  const { loginStatus, userInfo } = storeToRefs(store);
  const res: any = await info({ id: row.id, mobile: 1, saveView: 1, wxuserid: userInfo.value.userId });
  window.open(row.fileurl)
}

defineExpose({
  relaodlist() {
    current = 1;
    newsList.value = [];
    onLoad();
  },
});
</script>

<template>
  <div class="news-list">
    <Empty v-if="empty" image="search" description="暂无内容" image-size="10rem" />
    <List v-else v-model:loading="loading" :finished="finished" :finished-text="finishedText" @load="onLoad">
      <template v-if="props.group === false">
        <template v-if="props.download === true">
          <Card v-for="value in newsList" :title="value.title" :thumb="value.filetype" :currency="''"
            :desc="value.remark" class="card-news-item" @click="downloadFile(value)" :thumb-link="value.fileurl">
            <template #num>
              <Space>
                <span>
                  发布时间{{ value.publictime }}
                </span>
                <span>
                  浏览数：{{ value.click.toString() }}
                </span>
              </Space>
            </template>
          </Card>
        </template>
        <template v-else>
          <Cell v-for="value in newsList" center :title="value.title" :label="(props.showTime ? value.publictime : '') +
      (props.showView ? '&nbsp;&nbsp;浏览数：' + value.click.toString() : '') + (props.showGoodNum ? '&nbsp;&nbsp;点赞数：' + value.goodnum.toString() : '')
      " :is-link="props.isLink" class="news-item" @click="openNewsInfo(value.id)" />
        </template>
      </template>
      <template v-else v-for="value in newsList">
        <h2 class="apps-block__title">{{ value.title }}</h2>
        <Card v-for="value1 in value.children" :title="value1.title" :thumb="value1.image.startsWith('http')
      ? value1.image
      : 'https://fzrb.fznews.com.cn' + value1.image
      " :currency="''" :desc="value1.remark" class="card-news-item" @click="openNewsInfo(value1.id)">
          <template #num>
            <Space>
              <span>
                <Icon name="flower-o" />{{ value1.flowernum }}
              </span>
              <span>
                <Icon name="good-job-o" />{{ value1.goodnum }}
              </span>
            </Space>
          </template>
        </Card>
      </template>
    </List>
  </div>
  <Sticky :offset-bottom="50" position="bottom"
    v-if="props?.from && props?.from == 'neiwang' && [13376, 13390, 13378, 13377].includes(props.channelId)">
    <div class="uploadbtn">
      <Icon name="add" color="#00f" size="30"
        @click="router.push('/xiaoliuxuetang/upload?id=' + props.channelId.toString());" />
    </div>
  </Sticky>
</template>

<style scoped>
.news-list {
  width: 100%;
}

.card-news-item {
  background-color: #fff;
  position: relative;
  cursor: pointer;
}

.card-news-item::after {
  content: " ";
  position: absolute;
  left: 0;
  bottom: 0;
  right: 0;
  height: 1px;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  color: rgba(0, 0, 0, 0.1);
  color: var(--weui-FG-3);
  -webkit-transform-origin: 0 0;
  transform-origin: 0 0;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
  z-index: 2;
  left: 16px;
}

.uploadbtn {
  text-align: right;
  margin-right: 0.5em;
}

@media screen and (min-width: 500px) {
  .card-news-item::after {
    left: 16px;
  }
}
</style>
<style>
.news-list .apps-block__title {
  padding-left: 20px;
  font-size: 16px;
  position: -webkit-sticky;
  position: sticky;
  top: 0;
  z-index: 1000000;
  background-color: #fff;
  font-weight: bold;
  height: 40px;
  line-height: 40px;
  color: #000;
}

.news-item .van-cell__title {
  font-size: 16px;
  font-weight: bold;
}

.card-news-item .van-ellipsis {
  max-height: 160px;
  white-space: normal;
  -webkit-line-clamp: 2;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-box-orient: vertical;
}

.card-news-item .van-card__title {
  font-size: 15px;
  max-height: none;
  margin: 5px 0;
  line-height: 1.2;
  font-weight: bold;
}

@media screen and (min-width: 500px) {
  .news-list {
    background-color: #fff;
  }

  .news-list .apps-block__title {
    padding-left: 20px;
    font-size: 16px;
    height: 40px;
    line-height: 40px;
  }

  .news-item .van-cell__title {
    font-size: 16px;
    font-weight: bold;
  }

  .card-news-item .van-card__title {
    font-size: 15px;
    max-height: none;
    margin: 5px 0;
    line-height: 1.2;
    font-weight: bold;
  }
}
</style>
