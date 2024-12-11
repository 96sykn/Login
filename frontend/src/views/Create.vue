<template>
    <div class="content">
        <form @submit.prevent="create">
            <div v-if="!flg">
                <p>作成するアカウントの数を指定してください。</p>
                <input type="number" min="1" max="50" v-model="createNum" required/>
                <button type="submit">作成</button>
            </div>
            <div>
                <p v-if="flg">{{ createNum-createdNum }}個のアカウントが作成完了しました。</p>
                <p v-else-if="createdNum!=0">{{ createdNum }}個のアカウントの作成に失敗しました。</p>
            </div>
            <button class="commonButton" @click="back">戻る</button>
        </form>
        <p v-if="error">{{ error }}</p>
    </div>
</template>

<script>
import axios from "axios";
import { mapGetters, mapMutations } from "vuex";
import "@/assets/style.css";

export default {
    data() {
        return {
            createNum: 0,
            createdNum: 0,
            flg:false,
            error: ""
        };
    },
    computed: {
        ...mapGetters([]),
    },
    methods: {
        ...mapMutations([]),
        async create() {//新規アカウントを作成
            try {
                const response = await axios.post(
                    "https://2024isc1231028.weblike.jp/login/backend/create.php",
                    {
                        createNum: this.createNum,
                    }
                );
                this.createdNum = response.data.createdNum;
                this.flg=!this.flg;//flgを反転
            } catch (error) {
                this.error = "エラーが発生しました。1";
            }
        },

        back(){
            event.preventDefault()//submitを無効
            this.createNum=0;//「作成するアカウントの数」をリセット
            if(this.flg){
                this.flg=!this.flg;//作成完了から戻る
            }else{
                this.$router.push('/admin');//管理者ページに戻る
            }
        }
    },
};
</script>