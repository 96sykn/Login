<template>
  <div class="result-content">
    <div id="content">
      <h2>{{ formattedDate }}</h2>
    </div>
    <div id="map">
      <div class="legend-container">
        <div class="legend-title">凡例</div>
        <div class="legend-wrapper">
          <div class="legend">
            <span style="background-color: #FF0101;"></span>
            <div>重度速度超過</div>
          </div>
          <div class="legend">
            <span style="background-color: #FFB834;"></span>
            <div>軽度速度超過</div>
          </div>
          <div class="legend">
            <span style="background-color: #FF7EDD;"></span>
            <div>車線逸脱</div>
          </div>
          <div class="legend">
            <span style="background-color: #9C45D4;"></span>
            <div>強いGがかかった地点</div>
          </div>
        </div>
      </div>
    </div>

    <button @click="pageBack" class="back-button">
      戻る
    </button>
  </div>
</template>

<script>
import 'mapbox-gl/dist/mapbox-gl.css';
import mapboxgl from 'mapbox-gl';
import { mapGetters } from 'vuex';
import '@/assets/style.css';

export default {
  data() {
    return {
      mapboxToken: 'pk.eyJ1IjoiOTZ5dWtpIiwiYSI6ImNseWk3MzVwMDBicWUyam9td3E4dXl6ODMifQ.fIecSqBE2QaBM_ZBP5X4wQ',
    };
  },
  computed: {
    ...mapGetters(['userName', 'gps', 'can']),
    formattedDate() {
      const rawDate = this.can[0][4];
      const date = new Date(rawDate);
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return new Intl.DateTimeFormat('ja-JP', options).format(date);
    }
  },
  mounted() {
    this.initializeMap();
  },
  methods: {
    pageBack() {
      this.$router.push("/dashboard");
    },
    calculateCenter() {//各座標の平均をとり地図の中心を決める
      let totalLongitude = 0;
      let totalLatitude = 0;
      const totalPoints = this.gps.length;
      this.gps.forEach(gpsPoint => {
        totalLongitude += gpsPoint.longitude;
        totalLatitude += gpsPoint.latitude;
      });
      const averageLongitude = totalLongitude / totalPoints;
      const averageLatitude = totalLatitude / totalPoints;
      return [averageLongitude, averageLatitude];
    },

    async initializeMap() {//最初に呼び出し
      const bounds = new mapboxgl.LngLatBounds();
      const center = this.calculateCenter();
      mapboxgl.accessToken = this.mapboxToken;
      const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/96yuki/cm3f62sl9000i01sq8yul9ama',
        center: center,
        zoom: 16,
        scrollZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        dragRotate: false,
        keyboard: false,
        touchZoomRotate: false,
        dragPan: false,
      });
      this.gps.forEach((gpsPoint, index) => {//gpsの各座標の設定
        const color = (index == 0 || index == this.gps.length - 1)//最初と最後の座標を#19FF29(黄緑)に設定
          ? '#19FF29'
          : (gpsPoint.sFlg - this.gps[index - 1].sFlg) > 0//速度超過した座標
            ? this.getMarkerColor(gpsPoint.sFlg)//マーカーの色を変更(sFlgを参照)
            : (gpsPoint.lFlg - this.gps[index - 1].lFlg) == 1//車線逸脱
              ? '#FE7DDC'
              : (gpsPoint.gFlg - this.gps[index - 1].gFlg) == 1//強いG
                ? '#9C45D4'
                : '';
        const markerElement = document.createElement('div');//各座標に<div>を付与
        if (index == 0||(index == this.gps.length - 1)) { // 開始地点
          markerElement.style.width = '24px'; // サイズを調整
          markerElement.style.height = '24px';
          markerElement.style.backgroundSize = 'cover'; // 画像を要素全体にフィットさせる
          markerElement.style.backgroundPosition = 'center';
          markerElement.style.borderRadius = '0'; // 必要に応じて形状を変更
          markerElement.style.backgroundImage = `url(${require('@/assets/images/letter-e.png')})`; // 背景画像を設定
          if(index==0)markerElement.style.backgroundImage = `url(${require('@/assets/images/letter-s.png')})`; // 背景画像を設定
        } else {
          markerElement.style.width = '12px';
          markerElement.style.height = '12px';
          markerElement.style.backgroundColor = color;
          markerElement.style.borderRadius = '50%';
        }
        markerElement.style.cursor = 'pointer';

        if (index == 0 || (index == this.gps.length - 1) || (gpsPoint.sFlg - this.gps[index - 1].sFlg) > 0 || (gpsPoint.lFlg - this.gps[index - 1].lFlg) == 1 || (gpsPoint.gFlg - this.gps[index - 1].gFlg) == 1) {
          //最初と最後、イベントで条件分け
          const popup = new mapboxgl.Popup({//popupを定義(ホバーでmessageを表示)
            closeButton: false,
            closeOnClick: false
          }).setText(gpsPoint.message);
          new mapboxgl.Marker(markerElement)//markerを設定
            .setLngLat([gpsPoint.longitude, gpsPoint.latitude])
            .setPopup(popup)//popupを設定
            .addTo(map);
          let popupVisible = false;
          markerElement.addEventListener('mouseenter', () => {
            popup.addTo(map);
          });
          markerElement.addEventListener('mouseleave', () => {
            popup.remove();
          });
          markerElement.addEventListener('touchstart', () => {
            if (popupVisible) {
              popup.remove();
            } else {
              popup.addTo(map);
            }
          });
        }
        bounds.extend([gpsPoint.longitude, gpsPoint.latitude]);
      });
      map.fitBounds(bounds, { padding: 50 });
      map.setMaxBounds(bounds);
      this.connectMarkers(map);
    },

    getMarkerColor(sFlg) {//markerの色をsFlgで変える
      switch (sFlg) {
        case 0://制限速度内(事前に除外済み)
          return 'transparent';
        case 1://制限速度+5まで
          return '#FEB733';
        case 2://制限速度+6以上
          return '#FE0000';
        default:
          return 'gray';
      }
    },

    connectMarkers(map) {//各座標をつなぐ
      const lineSegments = [];
      const coordinates = [];
      this.gps.forEach((gpsPoint, index) => {
        coordinates.push([gpsPoint.longitude, gpsPoint.latitude]);
        if (index > 0) {//添え字が0以上なら一つ前の座標とつなぐ
          const previousPoint = this.gps[index - 1];
          const currentPoint = this.gps[index];
          const lineColor = this.getLineColor(previousPoint);
          lineSegments.push({
            type: 'Feature',
            geometry: {
              type: 'LineString',
              coordinates: [
                [previousPoint.longitude, previousPoint.latitude],
                [currentPoint.longitude, currentPoint.latitude]
              ]
            },
            properties: {
              sFlg: previousPoint.sFlg,
              color: lineColor
            }
          });
        }
      });
      map.on('load', () => {
        map.addSource('lineSegments', {
          type: 'geojson',
          data: {
            type: 'FeatureCollection',
            features: lineSegments
          },
        });
        map.addLayer({
          id: 'lineSegmentsLayer',
          type: 'line',
          source: 'lineSegments',
          layout: {
            'line-join': 'round',
            'line-cap': 'round',
          },
          paint: {
            'line-color': [
              'get', 'color'
            ],
            'line-width': 4,//lineの幅
          },
        });
      });
    },

    getLineColor(gps) {//lineの色をsFlgで変える
      switch (gps.sFlg) {
        case 0://制限速度内
          if (gps.lFlg == 1) {
            return '#FF7EDD'
          } else {
            return '#969696';
          }
        case 1://制限速度+5まで
          return '#FFB834';
        case 2://制限速度+6以上
          return '#FF0101';
        default:
          return 'gray';
      }
    },
  },
};
</script>

<style scoped>
.result-content {
    padding: 1rem;
    position: relative;
}

@media (min-width: 768px) {
    .result-content {
        padding: 2rem;
    }
}

#content {
    margin-bottom: 1rem;
}

@media (min-width: 768px) {
    #content {
        margin-bottom: 2rem;
    }
}

h2 {
    color: var(--text-primary);
    font-size: 1.25rem;
    font-weight: 600;
    background: var(--card-background);
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    display: inline-block;
    margin: 0;
}

@media (min-width: 768px) {
    h2 {
        font-size: 1.5rem;
        padding: 1rem 1.5rem;
    }
}

#map {
    width: 100%;
    height: 400px;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -2px rgba(0, 0, 0, 0.05);
    margin-bottom: 1rem;
    position: relative;
}

@media (min-width: 768px) {
    #map {
        height: 600px;
        margin-bottom: 2rem;
    }
}

.legend-container {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255, 255, 255, 0.98);
    padding: 0.75rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    font-size: 0.875rem;
    max-width: calc(100% - 20px);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

@media (min-width: 768px) {
    .legend-container {
        top: 20px;
        right: 20px;
        padding: 1rem;
        font-size: 1rem;
        max-width: 300px;
    }
}

.legend-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.legend {
    display: flex;
    align-items: center;
    margin: 0.25rem 0;
    color: var(--text-primary);
    font-size: 0.85em;
}

.legend span {
    width: 16px;
    height: 16px;
    margin-right: 0.5rem;
    border-radius: 4px;
    flex-shrink: 0;
}

@media (min-width: 768px) {
    .legend span {
        width: 20px;
        height: 20px;
        margin-right: 0.75rem;
    }
}

.legend-wrapper {
    max-height: calc(100vh - 300px);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--primary-color) transparent;
}

.legend-wrapper::-webkit-scrollbar {
    width: 4px;
}

.legend-wrapper::-webkit-scrollbar-track {
    background: transparent;
}

.legend-wrapper::-webkit-scrollbar-thumb {
    background-color: var(--primary-color);
    border-radius: 2px;
}

.back-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.875rem;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2),
                0 2px 4px -2px rgba(99, 102, 241, 0.1);
}

@media (min-width: 768px) {
    .back-button {
        width: auto;
        padding: 0.75rem 1.5rem;
    }
}

.back-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 8px -1px rgba(99, 102, 241, 0.3),
                0 4px 6px -2px rgba(99, 102, 241, 0.2);
}

@media (hover: none) {
    .back-button:hover {
        transform: none;
    }
}
</style>
