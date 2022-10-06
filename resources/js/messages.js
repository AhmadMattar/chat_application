import { createApp } from "@vue/runtime-dom";
import Messenger from "./components/messages/Messenger.vue";
import ChatList from "./components/messages/ChatList.vue";
import Echo from 'laravel-echo';
window.Pusher = require('pusher-js');



const chatApp = createApp({
    data() {
        return {
            conversations: [],
            conversation: null,
            messages: [],
            user_id: user_id,
            csrf_token: csrf_token,
            laravelEcho: null,
            users: [],
            chatChannel: null,
        }
    },
    mounted() {
        this.laravelEcho = new Echo({
            broadcaster: 'pusher',
            key: process.env.MIX_PUSHER_APP_KEY,
            cluster: process.env.MIX_PUSHER_APP_CLUSTER,
            forceTLS: true,
        });
        this.laravelEcho.join(`Messenger.${this.user_id}`)
            .listen('.new-message', (data) => {
                let exists = false;
                for (let i in this.conversations) {
                    let conversation = this.conversations[i];
                    if (conversation.id == data.message.conversation_id) {
                        if (!conversation.hasOwnProperty('new_messages')) {
                            conversation.new_messages = 0;
                        }
                        conversation.new_messages++;
                        conversation.last_message = data.message;
                        exists = true;
                        if (this.conversation && this.conversation.id == conversation.id) {
                            this.messages.push(data.message);
                            let container = document.querySelector('#chat-body');
                            container.scrollTop = container.scrollHeight;
                        }
                        break;
                    }
                }
                // if the conversation does not exist or (new conversation has been created)
                if (!exists) {
                    fetch(`/api/conversations/${data.message.conversation_id}`)
                        .then(response => response.json())
                        .then(json => {
                            this.conversations.push(json);
                    });
                }

            });
        this.chatChannel = this.laravelEcho.join('Chat')
            .joining((user) => {
                for (let i in this.conversations) {
                    let conversation = this.conversations[i];
                    if (conversation.participants[0].id == user.id) {
                        // alert(conversation.participants[0].name + 'is online');
                        conversation.participants[0].isOnline = true;
                        return;
                    }
                }
            })
            .leaving((user) => {
                for (let i in this.conversations) {
                    let conversation = this.conversations[i];
                    if (conversation.participants[0].id == user.id) {
                        conversation.participants[0].isOnline = false;
                        return;
                    }
                }
            })
            .listenForWhisper('typing', (e) => {
                let user = this.findUser(e.id, e.conversation_id);
                if (user) {
                    user.isTyping = true;
                }
            })
            .listenForWhisper('stop-typing', (e) => {
                let user = this.findUser(e.id, e.conversation_id);
                if (user) {
                    user.isTyping = false;
                }
            });
    },
    methods: {
        moment(time){
            return moment(time);
        },
        findUser(id, conversation_id){
            for (let i in this.conversations) {
                let conversation = this.conversations[i];
                if (conversation.id == conversation_id && conversation.participants[0].id == id) {
                    return conversation.participants[0];
                }
            }
        },
        markAsRead(conversation = null) {
            if(conversation == null){
                conversation = this.conversation;
            }
            fetch(`/api/conversations/${conversation.id}/read`, {
                method: 'PUT', // *GET, POST, PUT, DELETE, etc.
                mode: 'cors', // no-cors, *cors, same-origin
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    // 'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: JSON.stringify({
                    _token: this.csrf_token
                }) // body data type must match "Content-Type" header
            })
            .then(response => response.json())
            .then(json => {
                conversation.new_messages = 0;
            });
        },
        deleteMessages(message){
            fetch(`/api/messages/${message.id}`, {
                method: 'DELETE', // *GET, POST, PUT, DELETE, etc.
                mode: 'cors', // no-cors, *cors, same-origin
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    // 'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: JSON.stringify({
                    _token: this.csrf_token
                }) // body data type must match "Content-Type" header
            })
            .then(response => response.json())
            .then(json => {
                message.body = 'Message deleted...';
            });
        }
    }
});
chatApp.component('Messenger', Messenger);
chatApp.component('ChatList', ChatList);
chatApp.mount('#chat-app');


