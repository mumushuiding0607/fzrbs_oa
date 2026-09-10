<template>
  <div class="page">
    <Tabs class="mytab" style="margin-left: 0; padding-left: 0;" v-model:active="active" type="card" @change="onTabChange">
      <Tab title="季度评分">
        <List
          :loading="loading[0]"
          :finished="finished[0]"
          finished-text="没有更多了"
          offset="10"
          @load="loaddata(0)"
        >
          <div v-for="item in datas[0]" :key="item.id" class="listitem" @click="goScore(item)">
            <listitem :data="item"></listitem>
          </div>
        </List>
      </Tab>

      <Tab title="评分历史">
        <List
          :loading="loading[1]"
          :finished="finished[1]"
          finished-text="没有更多了"
          offset="10"
          @load="loaddata(1)"
        >
          <div v-for="item in datas[1]" :key="item.id" class="listitem" @click="goScore(item)">
            <listitem :data="item"></listitem>
          </div>
        </List>
      </Tab>


    </Tabs>
  </div>
</template>

<script lang="ts">
import { Tabs, Tab, List } from 'vant'
import { getTasks } from '@/views/evaluation/api/evaluation'
import listitem from './components/listitem.vue'

export default {
  components: {
    Tabs,
    Tab,
    List,
    listitem,
  },
  data() {
    return {
      active: 0,
      datas: [[], [], []] as any[][],
      page: [0, 0, 0],
      loading: [false, false, false],
      finished: [false, false, false],
      selectYear: new Date().getFullYear(),
      selectQuarter: Math.ceil((new Date().getMonth() + 1) / 3),
    }
  },
  created() {
    // 从URL参数读取active tab
    const activeParam = this.$route.query.active
    if (activeParam !== undefined) {
      this.active = Number(activeParam)
    }
  },
  methods: {
    formatDate(dateStr: string) {
      if (!dateStr) return '-'
      return dateStr.substr(0, 10)
    },
    getStatusText(status: number) {
      const texts = ['待评分', '已评分', '超时默认']
      return texts[status] || '未知'
    },
    getStatusType(status: number) {
      const types = ['warning', 'success', 'danger']
      return types[status] || 'default'
    },
    goScore(item: any) {
      this.$router.push({
        name: 'evaluation_scoring',
        query: {
          task_id: item.id,
        },
      })
    },
    onTabChange(index: number) {
      if (this.datas[index].length === 0 && !this.finished[index]) {
        this.loaddata(index)
      }
    },
    async loaddata(index: number) {
      if (this.loading[index]) return
      this.loading[index] = true

      const current = this.page[index] + 1
      const status = index === 0 ? 0 : index === 1 ? 1 : undefined

      try {
        const res: any = await getTasks({
          year: this.selectYear,
          quarter: this.selectQuarter,
          status,
          pageSize: 20,
          current,
        })

        if (res && res.data) {
          const data = res.data || []
          const total = res.total || 0
          if (data.length > 0) {
            this.page[index] = current
            this.datas[index].push(...data)
          }
          if (data.length === 0 || current >= total) {
            this.finished[index] = true
          }
        } else {
          this.finished[index] = true
        }
      } catch (e) {
        console.error('加载失败', e)
        this.finished[index] = true
      }

      this.loading[index] = false
    },
  },
}
</script>

<style>
@import "@/views/financeCss.css";
@import "@/views/financeCommon.css";
</style>
<style lang="css" scoped>
.page {
  background: #fff;
  min-height: 100vh;
}

:deep(.van-tabs__wrap) {
  background: #fff;
}

:deep(.van-tab) {
  font-size: 16px;
}

:deep(.van-tab--card) {
  font-size: 16px;
}

.mytab .van-tab--card.van-tab--active {
  background-color: #F1F1F1 !important;
  color: #1a1a1a !important;
  font-weight: 600 !important;
}

:deep(.van-tabs__nav--card) {
  border-color: #e2e8f0;
}

:deep(.van-tabs__nav--card .van-tab) {
  border-color: #e2e8f0;
}

.listitem {
  margin: 8px;
}
</style>
