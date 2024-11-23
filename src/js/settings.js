import { createApp, h } from "vue";
import Settings from "@/components/Settings.vue";

const app_settings = createApp({
  render: () => h(Settings),
});
app_settings.mount("#wbugboard-admin-settings");
