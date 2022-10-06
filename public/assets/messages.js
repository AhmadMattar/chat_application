import { createApp } from "vue";
import Messenger from ".../resources/components/Messenger.vue";
import ChatList from ".../resources/components/ChatList.vue";


const chatApp = createApp({});
chatApp.component('Messenger', Messenger);
chatApp.component('ChatList', ChatList);
chatApp.mount('#chat-app');
// chatApp.component('ChatList', ChatList);
// createApp(Messenger).mount('#chat-app');
// createApp(ChatList).mount('#chat-app');


