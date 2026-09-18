<template>
  <div class="page">
    <Tabs class="mytab" style="margin-left: 0; padding-left: 0;" v-model:active="active" type="card">
      <Tab title="全部">
        <List
          :loading="loading"
          :finished="finished"
          finished-text="没有更多了"
          offset="10"
          @load="loaddata"
        >
          <div
            v-for="item in datas"
            :key="item.processInstanceId"
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
import { List, Tabs, Tab, Field } from 'vant';
import { useUserStore } from '@/stores';
import { storeToRefs } from 'pinia';
import { getYxkhList } from './api';

const cacheStore = useUserStore();
const { userInfo } = storeToRefs(cacheStore);

export default {
  components: {
    List,
    Tabs,
    Tab,
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
    };
  },
  mounted() {
    this.loaddata();
  },
  methods: {
    view(item: any) {
      this.$router.push({
        name: 'yxkh_view',
        query: {
          processInstanceId: item.processInstanceId,
          businessType: '月度考核',
        },
      });
    },
    async loaddata() {
      if (this.loading || this.finished) return;

      this.loading = true;
      const current = this.page + 1;

      try {
        const res: any = await getYxkhList({
          uid: userInfo.value?.userId,
          pageSize: 20,
          current,
        });

        if (res?.data) {
          if (res.data.length === 0) {
            this.finished = true;
          } else {
            this.datas.push(...res.data);
            this.page = current;
          }
        } else {
          this.finished = true;
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
