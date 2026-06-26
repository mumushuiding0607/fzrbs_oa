<template>
  <div >
    <div v-if="advItems.length > 0" class="adv-table-container">
      <table class="adv-table">
        <thead>
          <tr>
            <th class="publish-date">发布日期</th>
            <th >刊物</th>
            <th >版位</th>
            <th >规格</th>
            <th >颜色</th>
            <th >次数</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in advItems" :key="index">
            <td class="publish-date">{{ item.AI_PublishTime?.substring(0,10) }}</td>
            <td >{{ item.AI_Publication || '-' }}</td>
            <td >{{ item.AI_Field || '-' }}</td>
            <td >{{ item.AI_Size || '-' }}</td>
            <td >{{ item.AI_Color || '-' }}</td>
            <td >{{ item.AI_PublishDayCount || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else class="empty-text">暂无广告明细</div>
  </div>
</template>
<script lang="ts">
import { getadvitemsbyorderid } from '../order';

export default {
  props: {
    orderid: {
      type: [String, Number],
      required: true
    }
  },
  data () {
    return {
      advItems: <any>[],
      totalAmount: 0
    }
  },
  mounted() {
    this.loadData();
  },
  watch: {
    orderid: function() {
      this.loadData();
    }
  },
  methods:{
    async loadData() {
      if (!this.orderid) return;
      
      try {
        const res:any = await getadvitemsbyorderid({ orderid: this.orderid,withdeleted:1 });
        this.advItems = res || [];
        this.totalAmount = this.advItems.reduce((sum:any, item:any) => sum + Number(item.AI_AmountReceivable || 0), 0);
      } catch (e) {
        console.error('加载广告明细失败', e);
      }
    }
  }
}
</script>
<style lang="css">
.advitems-card {
  margin: 10px;
}
.card-title {
  font-weight: bold;
  font-size: calc(var(--van-cell-font-size)*1.1);
  margin-bottom: 10px;
  color: #323233;
}
.adv-table-container {
  overflow-x: auto;
  border: 1px solid #ebedf0;
  border-radius: 4px;
  max-width: 350px;
}
.adv-table-container::-webkit-scrollbar {
  height: 1px;
}
.adv-table-container::-webkit-scrollbar-track {
  background: #f1f1f1;
}
.adv-table-container::-webkit-scrollbar-thumb {
  background: #c1c1c1;
}
.adv-table-container::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
.adv-table {
  width: 100%;
  min-width: 230px;
  border-collapse: collapse;
}
.adv-table thead th {
  background-color: #f7f8fa;
  color: #969799;
  font-weight: 500;
  padding: 2px 4px;
  text-align: left;
  border-bottom: 1px solid #ebedf0;
  border-right: 1px solid #ebedf0;
  white-space: nowrap;
  font-size: calc(var(--van-cell-font-size) );
  line-height: 1.1;
}
.publish-date {
  width: calc(var(--van-cell-font-size) *8);
}
.field {
  width: 50px;
}
.size {
  width: 65px;
}
.color {
  width: 45px;
}
.count {
  width: 35px;
}
.field {
  width: 80px;
}
.size {
  width: 100px;
}
.color {
  width: 60px;
}
.count {
  width: 50px;
}
.adv-table thead th:last-child {
  border-right: none;
}
.adv-table tbody td {
  padding: 2px 3px;
  border-bottom: 1px solid #ebedf0;
  border-right: 1px solid #ebedf0;
  color: #646566;
  white-space: nowrap;
  font-size: calc(var(--van-cell-font-size) );
  line-height: 1.1;
  height: 20px;
}
.adv-table tbody td:last-child {
  border-right: none;
}
.adv-table tbody tr:hover {
  background-color: #f7f8fa;
}
.adv-table tbody tr:last-child td {
  border-bottom: none;
}
.total-amount {
  color: #ee0a24;
  font-weight: 500;
}
.empty-text {
  color: #c8c9cc;
  text-align: center;
  padding: 20px;
}
</style>
