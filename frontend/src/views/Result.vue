<template>
  <div class="result-content">
    <div id="content">
      <h2>{{ formattedDate }}</h2>
    </div>
    <div id="map"></div>
    <button @click="pageBack">戻る
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
    pageBack(){
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
        markerElement.style.width = '12px';
        markerElement.style.height = '12px';
        markerElement.style.backgroundColor = color;
        markerElement.style.borderRadius = '50%';
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
