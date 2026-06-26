<template>
  <div class="page">
    <Tabs class="mytab" style="margin-left: 0;padding-left: 0;" v-model:active="active" type="card">
      <Tab title="待审批">
        <List
          :loading="loading[0]"
          :finished="finished[0]"
          finished-text="没有更多了"
          offset="10"
          @load="loaddata(0)"
        >
          <div v-for="item in datas[0]" :key="item.id" class="listitem" @click="view(item)">
            <listitem :data="item"/>
          </div>
        </List>
      </Tab>
      <Tab title="已审批">
        <List
          :loading="loading[1]"
          :finished="finished[1]"
          finished-text="没有更多了"
          offset="10"
          @load="loaddata(1)"
        >
          <div v-for="item in datas[1]" :key="item.id" class="listitem" @click="view(item)">
            <listitem :data="item"/>
          </div>
        </List>
      </Tab>
      

    </Tabs>
  </div>
</template>
<script lang="ts">
import { List, Tabs, Tab, Field } from 'vant';
import listitem from './components/listitem.vue';
import { approvallist, finishlist, getnotifydata } from './order';

export default {
  components: {
    Tabs, Tab, List, Field, listitem
  },
  data() {
    return {
      query: this.$route.query,
      active: 0,
      datas: <any>[[], [], []],
      page: [0, 0, 0],
      loading: [false, false, false],
      finished: [false, false, false],
      keyword: ['', '', '']
    }
  },
  mounted() {
  },
  created() {
    this.active = 0
  },
  methods: {
    onSearch(tab: any) {
      this.datas[tab] = []
      this.page[tab] = 0
      this.finished[tab] = false
      this.loaddata(tab)
    },
    view(e: any) {
      const data = e.data ? JSON.parse(e.data) : {};
      this.$router.push({ name: 'order_view', query: { infoid: data.infoid, thirdNo: e.thirdNo } })
    },
    async loaddata(index: number) {
      this.loading[index] = true;
      var current = this.page[index] + 1
      var res: any = null
      var par: any = { pageSize: 10, current }
      if (this.keyword[index]) {
        par.keyword = this.keyword[index]
      }
      switch (index) {
        case 0:
          res = await approvallist(par)
          break;
        case 1:
          res = await finishlist(par)
          break
        case 2:
          res = await getnotifydata(par)
          break
        default:
          break;
      }

      this.loading[index] = false
      if (res) {
        if (res.data?.length == 0) {
          this.finished[index] = true;
        } else {
          this.datas[index].push(...res.data)
          this.page[index] = current
        }
      }
    }
  }
}
</script>

<style lang="css" src="@/views/financeCommon.css"></style>
