<template>
  <div class="page">
    <Tabs class="mytab" style="margin-left: 0; padding-left: 0;" v-model:active="active" type="card">
      <Tab title="我的考核">
        <div class="searchBox">
          <Field
            v-model="keyword"
            size="normal"
            center
            clearable
            style="padding: 5px 5px 5px 15px;"
            placeholder="请输入关键字搜索"
          />
          <span class="btn" @click="onSearch">搜索</span>
          <span class="btn" @click="goAdd">新增</span>
        </div>
        <List
          :loading="loading"
          :finished="finished"
          finished-text="没有更多了"
          offset="10"
          @load="loaddata"
        >
          <div
            v-for="item in datas"
            :key="item.eid"
            class="listitem"
            @click="view(item)"
          >
            <listitem :data="item" />
          </div>
        </List>
      </Tab>
    </Tabs>
  </div>
</template>

<script lang="ts">
import listitem from './components/listitem.vue';
import {
  List,
  Tabs,
  Tab,
  Field,
  showDialog,
} from 'vant';
import Button from 'vant/lib/button';
import { useUserStore } from '@/stores';
import { storeToRefs } from 'pinia';
import { getYxkhList } from './api';

const cacheStore = useUserStore();
const { userInfo } = storeToRefs(cacheStore);

export default {
  components: {
    Button,
    Tabs,
    Tab,
    List,
    Field,
    listitem,
  },
  data() {
    return {
      active: 0,
      datas: [] as any[],
      page: 0,
      loading: false,
      finished: false,
      keyword: '',
    };
  },
  mounted() {
    this.loaddata();
  },
  methods: {
    onSearch() {
      this.datas = [];
      this.page = 0;
      this.finished = false;
      this.loaddata();
    },
    view(item: any) {
      if (item.processInstanceId) {
        // 已有processInstanceId -> 跳转到 viewflow 页面
        this.$router.push({
          name: 'yxkh_view',
          query: { processInstanceId: item.processInstanceId },
        });
      } else if (item.eid) {
        // 未提交 -> 跳转到 add 页面编辑
        this.$router.push({
          name: 'yxkh_add',
          query: { eid: item.eid },
        });
      } else {
        showDialog({ message: '该考核尚未提交' });
      }
    },
    goAdd() {
      this.$router.push({ name: 'yxkh_add' });
    },
    async loaddata() {
      if (this.loading || this.finished) return;

      this.loading = true;
      const current = this.page + 1;

      try {
        const res: any = await getYxkhList({
          uid: userInfo.value?.userId,
          keyword: this.keyword,
          pageSize: 20,
          current,
        });

        const list = res?.data ?? [];
        if (list.length === 0) {
          this.finished = true;
        } else {
          this.datas.push(...list);
          this.page = current;
        }
      } catch (e) {
        console.error(e);
        this.finished = true;
      }

      this.loading = false;
    },
  },
};
</script>

<style lang="css" src="@/views/financeCommon.css"></style>
