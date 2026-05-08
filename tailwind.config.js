/** @type {import('tailwindcss').Config} */
module.exports = {
  // Scan semua file di dalam folder templates dan src (jika ada hardcoded HTML di PHP)
  content: [
    "./templates/**/*.latte",
    "./src/**/*.php"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}