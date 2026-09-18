<template>
  <div class="box">
    <Viewflow :processInstanceId="par.processInstanceId" />
  </div>
</template>

<script lang="ts">
import Viewflow from './components/viewflow.vue';

export default {
  components: {
    Viewflow,
  },
  data() {
    return {
      par: this.$route.query,
    };
  },
};
</script>
