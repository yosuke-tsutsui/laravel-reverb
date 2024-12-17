import './assets/main.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import echo from './plugins/echo'; // 追加

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.config.globalProperties.$echo = echo; // 追加

app.provide('echo', echo);
app.mount('#app')