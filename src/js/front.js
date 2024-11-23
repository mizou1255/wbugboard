import { createApp, h } from "vue";
import Front from "@/components/front.vue";

const app = createApp({
  render: () => h(Front),
});

app.mount("#wbugboard-front-app");
