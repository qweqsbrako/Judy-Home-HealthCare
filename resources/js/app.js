import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import '../css/auth.css'
import '../css/global.css'
import toastPlugin from './common/plugins/toast.js'

const app = createApp(App);
app.use(router);
app.use(toastPlugin) 
app.mount('#app');
