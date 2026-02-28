/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{ts,tsx}"],
  theme: { extend: {
    maxWidth: {
        container: "1400px",
  }, 
},
},
  plugins: [],
};
