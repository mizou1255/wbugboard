import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";
import obfuscator from "rollup-plugin-obfuscator";

export default defineConfig({
  plugins: [vue()],
  css: {
    postcss: "./postcss.config.js",
  },
  build: {
    rollupOptions: {
      input: {
        app: path.resolve(__dirname, "src/js/app.js"),
        front: path.resolve(__dirname, "src/js/front.js"),
        settings: path.resolve(__dirname, "src/js/settings.js"),
        style: path.resolve(__dirname, "src/css/style.css"),
      },
      output: {
        entryFileNames: "[name].min.js",
        chunkFileNames: "[name].min.js",
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith(".css")) {
            return "[name].min.css";
          }
          return "[name][extname]";
        },
        dir: "assets/dist",
        format: "es",
      },
      plugins: [
        obfuscator({
          compact: true,
          controlFlowFlattening: true,
          deadCodeInjection: true,
          debugProtection: false,
          debugProtectionInterval: false,
          disableConsoleOutput: true,
          identifierNamesGenerator: "hexadecimal",
          log: false,
          renameGlobals: false,
          selfDefending: true,
          stringArray: true,
          stringArrayEncoding: ["rc4"],
          stringArrayThreshold: 0.75,
          unicodeEscapeSequence: false,
        }),
      ],
    },
    assetsDir: "",
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
});
