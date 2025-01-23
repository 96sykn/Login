<!-- 
  ふむ…これは管理者の間だね
  全ての記録を見守り、統括する特別な場所さ
-->
<template>
    <div class="admin-page">
        <div class="admin-container card">
            <h2>管理者メニュー</h2>
            <div class="admin-menu">
                <router-link to="/create" class="menu-button">
                    <span class="icon">＋</span>
                    <span class="text">新規アカウント追加</span>
                </router-link>
                <router-link to="/unassigned" class="menu-button">
                    <span class="icon">👤</span>
                    <span class="text">未使用アカウント確認</span>
                </router-link>
            </div>
        </div>

        <div class="admin-actions">
            <div class="action-buttons">
                <button @click="showUserManagement = true" class="admin-btn">
                    <i class="fas fa-users-cog"></i>
                    ユーザー管理
                </button>
            </div>
        </div>

        <!-- ユーザー管理モーダル -->
        <div v-if="showUserManagement" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>ユーザー管理</h3>
                    <button @click="showUserManagement = false" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="search-filters">
                        <input v-model="searchQuery" placeholder="ユーザーを検索..." class="search-input">
                        <select v-model="filterStatus" class="filter-select">
                            <option value="all">すべて</option>
                            <option value="active">アクティブ</option>
                            <option value="inactive">非アクティブ</option>
                        </select>
                    </div>

                    <div class="users-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ステータス</th>
                                    <th>最終ログイン</th>
                                    <th>アクション</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in filteredUsers" :key="user.id">
                                    <td>{{ user.id }}</td>
                                    <td>
                                        <span :class="['status-badge', user.status]">
                                            {{ user.status === 'active' ? 'アクティブ' : '非アクティブ' }}
                                        </span>
                                    </td>
                                    <td>{{ user.lastLogin || '未ログイン' }}</td>
                                    <td>
                                        <button @click="toggleUserStatus(user)" class="action-btn">
                                            {{ user.status === 'active' ? '無効化' : '有効化' }}
                                        </button>
                                        <button @click="resetPassword(user)" class="action-btn">
                                            パスワードリセット
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { ref, computed, onMounted } from 'vue';

export default {
    setup() {
        const showUserManagement = ref(false);
        const searchQuery = ref('');
        const filterStatus = ref('all');
        const users = ref([]);
        const recentFailures = ref([]);

        const fetchUsers = async () => {
            try {
                const response = await axios.get('https://2024isc1231028.weblike.jp/login/backend/admin/users.php');
                if (response.data.success) {
                    users.value = response.data.users;
                }
            } catch (error) {
                console.error('Failed to fetch users:', error);
            }
        };

        const fetchRecentFailures = async () => {
            try {
                const response = await axios.get('https://2024isc1231028.weblike.jp/login/backend/admin/recent-failures.php');
                if (response.data.success) {
                    recentFailures.value = response.data.recentFailures;
                }
            } catch (error) {
                console.error('Failed to fetch recent failures:', error);
            }
        };

        const filteredUsers = computed(() => {
            return users.value.filter(user => {
                const matchesSearch = user.id.toLowerCase().includes(searchQuery.value.toLowerCase());
                const matchesFilter = filterStatus.value === 'all' || user.status === filterStatus.value;
                return matchesSearch && matchesFilter;
            });
        });

        const toggleUserStatus = async (user) => {
            try {
                const response = await axios.post('https://2024isc1231028.weblike.jp/login/backend/admin/toggle-status.php', {
                    userId: user.id
                });
                if (response.data.success) {
                    await fetchUsers();
                }
            } catch (error) {
                console.error('Failed to toggle user status:', error);
            }
        };

        const resetPassword = async (user) => {
            try {
                const response = await axios.post('https://2024isc1231028.weblike.jp/login/backend/admin/reset-password.php', {
                    userId: user.id
                });
                if (response.data.success) {
                    alert(`新しいパスワード: ${response.data.newPassword}`);
                }
            } catch (error) {
                console.error('Failed to reset password:', error);
            }
        };

        onMounted(() => {
            fetchUsers();
            fetchRecentFailures();
        });

        return {
            showUserManagement,
            searchQuery,
            filterStatus,
            filteredUsers,
            recentFailures,
            toggleUserStatus,
            resetPassword
        };
    }
};
</script>

<style scoped>
.admin-page {
    min-height: 100vh;
    padding: 2rem;
    display: flex;
    align-items: flex-start;
    justify-content: center;
}

.admin-container {
    width: 100%;
    max-width: 600px;
    padding: 2.5rem;
}

h2 {
    text-align: center;
    margin-bottom: 2rem;
    color: var(--text-primary);
    font-size: 1.75rem;
    font-weight: 600;
}

.admin-menu {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.menu-button {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    background: var(--card-background);
    color: var(--text-primary);
    text-decoration: none;
    border-radius: 0.75rem;
    border: 1px solid var(--border-color);
    transition: all 0.2s ease;
}

.menu-button:hover {
    transform: translateY(-2px);
    border-color: var(--primary-color);
    box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.1),
                0 4px 6px -4px rgba(99, 102, 241, 0.05);
}

.icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    color: white;
    border-radius: 0.5rem;
    margin-right: 1rem;
    font-size: 1.25rem;
}

.text {
    font-size: 1rem;
    font-weight: 500;
}

.admin-actions {
    margin-bottom: 30px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.admin-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.3s;
}

.admin-btn:hover {
    background: #0056b3;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5em;
    cursor: pointer;
}

.modal-body {
    padding: 20px;
}

.search-filters {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.search-input, .filter-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.users-table {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.9em;
}

.status-badge.active {
    background: #e6f4ea;
    color: #1e7e34;
}

.status-badge.inactive {
    background: #feeced;
    color: #dc3545;
}

.action-btn {
    padding: 6px 12px;
    margin-right: 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    background: #6c757d;
    color: white;
}

.action-btn:hover {
    background: #5a6268;
}
</style>