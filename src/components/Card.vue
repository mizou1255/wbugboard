<template>
  <div
    :class="[
      'card w-full p-6 bg-base-100 text-base-content shadow-xl',
      topMargin || 'mt-9',
    ]"
  >
    <label class="wpit-swap swap swap-rotate bg-base-100 p-2">
      <input type="checkbox" class="theme-controller" @change="toggleTheme" />
      <i class="swap-off far fa-sun text-xl"></i>
      <i class="swap-on far fa-moon text-xl"></i>
    </label>
    <div>
      <slot></slot>
    </div>
  </div>
</template>
    
    <script>
export default {
  name: "Card",
  methods: {
    setTheme(theme) {
      document.documentElement.setAttribute("data-theme", theme);
      localStorage.setItem("theme", theme);
    },
    toggleTheme() {
      const currentTheme = document.documentElement.getAttribute("data-theme");
      if (currentTheme === "dark") {
        this.setTheme("winter");
      } else {
        this.setTheme("dark");
      }
    },
  },
  mounted() {
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme) {
      this.setTheme(savedTheme);
    } else if (
      window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches
    ) {
      this.setTheme("dark");
    }
  },
};
</script>
    