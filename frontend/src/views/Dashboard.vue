<template>
  <div class="content">
    <main class="dashboard-content">
      <p v-if="userName">ようこそ、{{ userName }}さん！</p>
      <p v-else>ユーザー名が見つかりません。</p>
      <section class="user-section">
        <select @change="disFormat" class="dashboard-select">
          <option selected disabled>時間を選択してください</option>
          <option v-for="(item, index) in formattedStartline" :key="index" :value="index">
            {{ item }}
          </option>
        </select>
        <p v-if="error">{{ error }}</p>
      </section>
    </main>
  </div>
</template>

<script>
import { mapGetters, mapMutations } from "vuex";
import "@/assets/style.css";
import axios from "axios";

export default {
  data() {
    return {
      selectedTime: null,
      endTime: null,
      can: [],
      gps: [],
      canFlg: [],
      gForce: [],
      formatGForce: [],
      formatGps: [],
      error: "",
      DEFAULT_SPEED_LIMIT: 25//制限速度を設定(固定)
    };
  },
  computed: {
    ...mapGetters(["userId", "userName", "startline"]),
    formattedStartline() {
      return this.startline.map(timestamp => {
        const [date, time] = timestamp.split(' ');// タイムスタンプを分解
        const [year, month, day] = date.split('-');
        const [hour, minute, second] = time.split(':');
        return `${year}年${month}月${day}日 ${hour}時${minute}分${second}秒`;
      });
    }

  },
  methods: {
    ...mapMutations(["setGps", "setCan"]),

    async disFormat(event) {
      this.selectedIndex = parseInt(event.target.value, 10);
      if (this.selectedIndex >= 0) {
        await this.getCan();
      }
    },

    async getCan() {
      try {
        if (this.selectedIndex !== null) {
          this.selectedTime = this.startline[this.selectedIndex];
          const response = await axios.post(
            'https://2024isc1231028.weblike.jp/login/backend/can.php',
            {
              userId: this.userId,
              selectedTime: this.selectedTime,
            }
          );
          if (response.data.success) {
            this.can = response.data.can;
            await this.getEndTime();
            await this.canFormat();
            await this.getGForce();
          } else {
            this.error = response.data.message;
          }
        }
      } catch (error) {
        this.error = "エラーが発生しました。";
      }
    },

    async getEndTime() {
      const lastIndex = this.can.length - 1;
      const endTime = this.can[lastIndex].time;
      this.endTime = endTime;
    },

    async canFormat() {
      const canFlg = this.can.map(({ id, ign, VehicleSpeed, ldw, time }) => {
        const sFlg = VehicleSpeed <= this.DEFAULT_SPEED_LIMIT ? 0 ://速度超過のflgを追加
          VehicleSpeed <= this.DEFAULT_SPEED_LIMIT + 5 ? 1 : 2;
        const lFlg = ldw == 1 ? 1 : 0;//車線逸脱のflgを設定
        return [id, ign, VehicleSpeed, ldw, time, sFlg, lFlg];
      });
      this.setCan(canFlg);//storeに格納
      this.canFlg = canFlg;
      this.can = [];//canをリセット
    },

    async getGForce() {
      try {
        const response = await axios.post(
          'https://2024isc1231028.weblike.jp/login/backend/gForce.php',
          {
            userId: this.userId,
            startTime: this.selectedTime,
            endTime: this.endTime
          }
        );
        if (response.data.success) {
          this.gForce = response.data.gForce;
          await this.formattingGForce();
          await this.getGps();
        } else {
          this.error = response.data.message;
        }
      } catch (error) {
        this.error = "エラーが発生しました。"
      }
    },

    async formattingGForce() {
      const xo = this.gForce[0].x;
      const yo = this.gForce[0].y;
      const zo = this.gForce[0].z;
      let xDiff, yDiff, zDiff, gDiff = 0;
      const formatGForce = this.gForce.map(({ x, y, z, time }) => {
        xDiff = x - xo;
        yDiff = y - yo;
        zDiff = z - zo;
        gDiff = Math.sqrt(xDiff ** 2 + yDiff ** 2 + zDiff ** 2);
        const flg = gDiff >= 100 ? 1 : 0;
        return [x, y, z, time, flg];
      });
      this.formatGForce = formatGForce;
      //this.setGForce(this.formatGForce);
      this.gForce = [];
    },

    async getGps() {
      try {
        const response = await axios.post(
          'https://2024isc1231028.weblike.jp/login/backend/gps.php',
          {
            userId: this.userId,
            startTime: this.selectedTime,
            endTime: this.endTime
          }
        );
        //console.log(response.data);//DEBUG
        if (response.data.success) {
          this.gps = response.data.gps;
          await this.formattingGps();
          this.$router.push("/result");
        } else {
          this.error = response.data.message;
        }
      } catch (error) {
        this.error = "エラーが発生しました。"
      }
    },

    async formattingGps() {//イベント込みのgpsの作成
      let formatGps = this.gps.map(gpsPoint => ({
        longitude: gpsPoint.longitude,
        latitude: gpsPoint.latitude,
        time: gpsPoint.time,
        sFlg: 0,//速度超過flg
        lFlg: 0,//ldw flg
        gFlg: 0,//gForce flg
        message: "",//イベント内容
      }));
      this.formatGps = formatGps;
      this.canFlg.forEach(canFlgPoint => {
        if (canFlgPoint[5] !== 0) {//canFlgPoint[5]=canFlg.sFlg
          const closet = this.getClosestGps(canFlgPoint[4]);//closetは添え字//canFlgPoint[4]=canFlg.time
          this.formatGps[closet].sFlg = canFlgPoint[5];
        }
        if (canFlgPoint[6] == 1) {//canFlgPoint[6]=canFlg.lFlg
          const closet = this.getClosestGps(canFlgPoint[4]);//closetは添え字//canFlgPoint[4]=canFlg.time
          this.formatGps[closet].lFlg = 1;
        }
      })
      this.formatGForce.forEach(gForcePoint => {
        if (gForcePoint[4] == 1) {//gForcePoint[4]=formatGForce.Flg
          const closet = this.getClosestGps(gForcePoint[3]);//closetは添え字//gForcePoint[3]=formatGForce.time
          this.formatGps[closet].gFlg = 1;
        }
      });
      this.getMessage();
      this.setGps(this.formatGps);
      this.gps = [];
    },

    getMessage() {//イベントの内容を割り当て
      this.formatGps.forEach((formGps, index) => {
        if (index === 0) {
          this.formatGps[index].message = '\n開始';
        }
        else if (index === this.formatGps.length - 1) {
          this.formatGps[index].message = '\n終了';
        }
        else {
          if ((formGps.sFlg - this.formatGps[index - 1].sFlg) > 0) {//速度を超過
            this.formatGps[index].message += (formGps.sFlg === 1) ? '\n軽度速度超過'
              : (formGps.sFlg === 2) ? '\n重度速度超過'
                : '';
          }
          if (formGps.lFlg == 1) {
            this.formatGps[index].message += '\n車線逸脱';
          }
          if (formGps.gFlg == 1) {
            this.formatGps[index].message += '\n強いGがかかかりました';
          }
        }
        if (formGps.message != "") this.formatGps[index].message = this.formatDate(this.formatGps[index].time) + this.formatGps[index].message;
      });
    },

    formatDate(date) {
      const formDate = new Date(date);
      const hours = String(formDate.getHours()).padStart(2, '0');
      const minutes = String(formDate.getMinutes()).padStart(2, '0');
      const seconds = String(formDate.getSeconds()).padStart(2, '0');
      return `${hours}時${minutes}分${seconds}秒`;
    },

    getClosestGps(time) {//一番近いtimeを持つformatGpsを探索し、添え字を返す
      let minDiff = Math.abs(new Date(time) - new Date(this.formatGps[0].time));
      let closet = 0;
      for (let i = 1; i < this.formatGps.length; i++) {
        const diff = Math.abs(new Date(time) - new Date(this.formatGps[i].time));//timeの差を取得し、比べる
        if (diff < minDiff) {
          minDiff = diff;
          closet = i;
        }
      }
      return closet;//添え字を返す
    },
  },
};
</script>
