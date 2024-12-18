<template>
    <div class="content">
        <form @submit.prevent="login">
            <div>
                <label for="userId">ユーザID(英数8字)</label>
                <input type="text" v-model="userId" maxlength="8" required />
            </div>
                <div class="password-container">
                    <label for="password">パスワード</label>
                    <input :type="passVis ? 'text' : 'password'" v-model="password" required />
                    <button type="button" @click="togglePassVis">{{ passVis ? '非表示' : '表示' }}</button>
                </div>
            <button type="submit">ログイン</button>
        </form>
        <p v-if="error">{{ error }}</p>
    </div>
</template>

<script>
//test
import axios from "axios";
import { mapMutations } from "vuex";
import '@/assets/style.css';

export default {
    data() {
        return {
            userId: "",
            password: "",
            error: "",
            passVis: false,
        };
    },
    methods: {
        ...mapMutations(["setUserId", "setUserName", "setStartline"]),

        togglePassVis() {
            this.passVis = !this.passVis;
        },

        async login() {
            try {
                const response = await axios.post(
                    "https://2024isc1231028.weblike.jp/login/backend/index.php",
                    {
                        userId: this.userId,
                        password: this.password,
                    }
                );
                if (response.data.success) {
                    this.setUserId(this.userId);
                    if(response.data.name==""){
                        this.$router.push("/register");
                        return;
                    }
                    this.setUserName(response.data.name);
                    if (this.userId == 'admin001') {
                        this.$router.push("/admin");
                        return;
                    }
                    await this.getStartline();
                } else {
                    this.error = response.data.message;
                }
            } catch (error) {
                this.error = "エラーが発生しました。";
            }
        },

        async getStartline() {
            try {
                const response = await axios.post(
                    "https://2024isc1231028.weblike.jp/login/backend/startline.php",
                    {
                        userId: this.userId,
                    }
                );
                if (response.data.success) {
                    this.setStartline(response.data.startline);
                    this.$router.push("/dashboard");
                } else {
                    this.error = response.data.message;
                }
            } catch (error) {
                this.error = "エラーが発生しました。";
            }
        },
    },
};
</script>
