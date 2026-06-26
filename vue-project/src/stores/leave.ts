import { defineStore } from 'pinia';

export const useTabStore = defineStore('leave', {
  state: () => ({
    refreshSignal: 0
  }),
  actions: {
    approveRefresh() {
      this.refreshSignal++;
    },    
    historyRefresh() {
      this.refreshSignal++;
    },
    badgeRefresh() {
      this.refreshSignal++;
    }
  }
});