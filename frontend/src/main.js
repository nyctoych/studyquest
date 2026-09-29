import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import PixelEmoji from './components/ui/PixelEmoji.vue'
import './assets/css/main.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.component('PixelEmoji', PixelEmoji)
app.mount('#app')
