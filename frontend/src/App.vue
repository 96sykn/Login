<!-- 
  アプリケーションのルートコンポーネント
  全体のレイアウトとグローバルな状態管理を担当
-->
<template>
  <!-- ヘッダーセクション：タイトルとログアウトボタンを表示 -->
  <div class="common">
    <header class="header">
      <h1>{{ pageTitle }}</h1>
      <button v-if="isAuthenticated" @click="logout" class="btn btn-secondary">ログアウト</button>
    </header>
    
    <!-- メインコンテンツ：ルーティングによって動的に切り替わる -->
    <main class="container">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- ローディングインジケーター -->
    <div v-if="isLoading" class="loading" role="status">
      <span class="sr-only">Loading...</span>
    </div>

    <!-- フッター -->
    <footer class="footer">
      <p>&copy; 2024 96yuki. All rights reserved.</p>
    </footer>
  </div>
</template>

<script setup>
import { RouterView, useRoute, useRouter } from 'vue-router';
import { computed, onMounted, onUnmounted } from 'vue';
import { useStore } from 'vuex';
import { actionTypes } from '@/store';
import '@/assets/style.css';
import '@fortawesome/fontawesome-free/css/all.css';

const route = useRoute();
const router = useRouter();
const store = useStore();

// ナビゲーションガード：認証状態に応じてルーティングを制御
router.beforeEach((to, from, next) => {
    const isAuthenticated = store.getters.isAuthenticated;
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
    const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin);

    // 未認証ユーザーの保護されたルートへのアクセスを制御
    if (requiresAuth && !isAuthenticated) {
        next({
            path: '/',
            query: { redirect: to.fullPath }
        });
    } 
    // 管理者専用ルートの制御
    else if (requiresAdmin && store.getters.userId !== 'admin001') {
        next('/dashboard');
    } else {
        next();
    }
});

// 各ルートに対応するページタイトルを設定
const pageTitle = computed(() => {
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

// グローバルな状態管理
const isLoading = computed(() => store.state.loading);
const isAuthenticated = computed(() => store.getters.isAuthenticated);

// セッション管理：30分のタイムアウトを設定
const SESSION_TIMEOUT = 30 * 60 * 1000;
let sessionTimer;

// アクティビティタイマーのリセット処理
const resetSessionTimer = () => {
    if (sessionTimer) clearTimeout(sessionTimer);
    if (isAuthenticated.value) {
        sessionTimer = setTimeout(() => {
            store.dispatch(actionTypes.RESET_STATE);
            window.location.href = '/login';
        }, SESSION_TIMEOUT);
    }
};

// ユーザーアクティビティの監視設定
const setupActivityListeners = () => {
    ['mousedown', 'keydown', 'touchstart', 'mousemove'].forEach(event => {
        document.addEventListener(event, resetSessionTimer);
    });
};

// コンポーネントのマウント時
onMounted(() => {
    setupActivityListeners();
    resetSessionTimer();
});

// コンポーネントのアンマウント時
onUnmounted(() => {
    if (sessionTimer) clearTimeout(sessionTimer);
    ['mousedown', 'keydown', 'touchstart', 'mousemove'].forEach(event => {
        document.removeEventListener(event, resetSessionTimer);
    });
});

// ログアウト処理
const logout = async () => {
    try {
        store.commit('setLoading', true);
        await store.dispatch(actionTypes.RESET_STATE);
        router.push('/');
    } catch (error) {
        console.error('Logout error:', error);
    } finally {
        store.commit('setLoading', false);
    }
};
</script>

<style>
:root {
    --primary-color: #6366f1;
    --primary-hover: #4f46e5;
    --background-color: #f8fafc;
    --card-background: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
    --error-color: #ef4444;
    --success-color: #22c55e;
    
    /* ブレークポイント */
    --mobile-width: 480px;
    --tablet-width: 768px;
    --desktop-width: 1024px;
}

/* レスポンシブミックスイン */
@media (max-width: 480px) {
    html {
        font-size: 14px;
    }
}

body {
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: var(--background-color);
    color: var(--text-primary);
    min-height: 100vh;
    -webkit-text-size-adjust: 100%;
}

#app {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* モバイル向けタッチ操作の最適化 */
@media (max-width: 480px) {
    button, 
    input[type="submit"],
    .btn {
        min-height: 44px; /* タップターゲットサイズの確保 */
    }

    input, 
    select {
        font-size: 16px; /* iOSでズームインを防ぐ */
    }
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    color: white;
    box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2),
                0 2px 4px -2px rgba(99, 102, 241, 0.1);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 8px -1px rgba(99, 102, 241, 0.3),
                0 4px 6px -2px rgba(99, 102, 241, 0.2);
}

/* タッチデバイスではホバー効果を無効化 */
@media (hover: none) {
    .btn-primary:hover {
        transform: none;
        box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2),
                    0 2px 4px -2px rgba(99, 102, 241, 0.1);
    }
}

.card {
    background: var(--card-background);
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05),
                0 2px 4px -2px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    background: var(--card-background);
    color: var(--text-primary);
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.error-message {
    color: var(--error-color);
    background: rgba(239, 68, 68, 0.1);
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

/* スクロールバーのカスタマイズ */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 4px;
}

/* タッチデバイスでのスクロールを滑らかに */
@media (pointer: coarse) {
    * {
        -webkit-overflow-scrolling: touch;
    }
}
</style>
