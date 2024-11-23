module.exports = {
  content: ["./src/**/*.{vue,js,ts,jsx,tsx}", "./public/index.html"],
  theme: {
    extend: {},
  },
  plugins: [require("daisyui")],
  daisyui: {
    themes: ["winter", "dark"],
    themes: [
      {
        winter: {
          ...require("daisyui/src/theming/themes")["winter"],
          success: "#1f8d49",
          error: "#f60700",
          warning: "#FFCA00",
        },
        dark: {
          ...require("daisyui/src/theming/themes")["dark"],
        },
      },
    ],
  },
};
