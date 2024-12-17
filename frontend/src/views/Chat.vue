<template>
  <div class="about">
    <h1>チャットページ</h1>
    <div>
      <input v-model="message" type="text" name="message" placeholder="メッセージを書く">
      <button @click="submit()">送信</button>
    </div>
    <div>
      <ul>
        <li v-for="chat in chatHistory" :key="chat.id">
          {{ chat.message }}
        </li>
      </ul>
    </div>
  </div>
</template>

<style>
@media (min-width: 1024px) {
  .about {
    min-height: 100vh;
    align-items: center;
  }
}
</style>
<script setup>
import {inject, onMounted, ref} from "vue";
import axios from "axios";


const message = ref('');
const chatHistory = ref([]);

const echo = inject('echo');

const submit = () => {
  try {
    // axios.post(`http://localhost:9099/public-event`, {
    //   message: message.value
    // });
    axios.get(`http://localhost:9100/public-event`);
  } catch (error) {
    console.error('Error sending message:', error);
  }
};

/**
 * Listen for events on the channel-chat channel
 */
echo.channel('channel-chat').listen('ChatEvent', (e) => {
  console.log(e);
});


onMounted(async () => {
  try {
    const response = await axios.get(`http://localhost:9100/api/chat`);
    chatHistory.value = response.data.messages;
  } catch (error) {
    console.error('Error fetching chat history:', error);
  }

  console.log('Chat page mounted');
});

</script>