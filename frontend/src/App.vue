<template>
  <div class="common">
    <header class="header">
      <h1>{{ pageTitle }}</h1>
      <button @click="logout" v-if="route.name != 'Login'">ログアウト</button>
    </header>
    <div id="app">
      <router-view />
    </div>
    <footer class="footer">
      <p>&copy; 2024 96yuki.</p>
    </footer>
  </div>
</template>

<script setup>
import { RouterView, useRoute, useRouter } from 'vue-router';
import { computed } from 'vue';
import { useStore } from 'vuex'; // Vuexストアをインポート
import '@/assets/style.css';

// Vue Routerのセットアップ
const route = useRoute();
const router = useRouter();
const store = useStore(); // Vuexストアを取得

const pageTitle = computed(() => { // ページのタイトルを設定
  switch (route.name) {
    case 'Dashboard':
      return 'ダッシュボード';
    case 'Login':
      return 'ログイン';
    case 'Result':
      return 'リザルト';
    case 'Admin':
      return '管理者ページ';
    case 'Register':
      return '登録ページ';
    case 'Create':
      return 'アカウント作成';
    case 'Unassigned':
      return '未使用アカウント';
    default:
      return 'エラー';
  }
});

const logout = () => { // ログアウト処理
  localStorage.removeItem('token');
  store.dispatch('resetState');
  router.push('/');
};
</script>


