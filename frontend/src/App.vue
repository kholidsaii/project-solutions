<template>
  <div id="app-layout">
    <Navigation v-if="$route.name !== 'Login'" />
    
    <div :class="{ 'main-content': $route.name !== 'Login' }">
      <router-view></router-view>
    </div>
  </div>
</template>

<script setup>
import Navigation from './components/Navigation.vue';
import { onMounted } from 'vue';

onMounted(() => {
  const savedHex = localStorage.getItem('theme_color_hex') || '#4f46e5';
  document.documentElement.style.setProperty('--brand-color', savedHex);
});
</script>

<style>
body {
  margin: 0;
  padding: 0;
  background-color: #f8fafc;
}

#app-layout { 
  display: flex; 
  min-height: 100vh;
}

.main-content { 
  margin-left: 80px; 
  background-color: #f0f2f5; /* Warna dasar di gambar */
  min-height: 100vh;
  padding: 0; /* Kita buat padding di dalam view saja agar banner bisa full width */
}
/* Responsif: Untuk Tablet & HP */
@media (max-width: 768px) {
  .main-content {
    margin-left: 0; /* Sidebar biasanya disembunyikan atau jadi mode mobile */
    padding: 16px;
  }
}
.main-content { 
  margin-left: 80px; /* Default Desktop */
  flex: 1; 
  min-height: 100vh;
  padding: 24px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* TABLET & MOBILE */
@media (max-width: 1024px) {
  .main-content {
    margin-left: 0; /* Konten penuh di mobile */
    padding: 16px;
    padding-top: 80px; /* Ruang untuk Header Mobile jika ada */
  }
  
  /* Sembunyikan sidebar di mobile atau pindah ke bawah */
  nav.fixed {
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }
  
  nav.fixed.show-mobile {
    transform: translateX(0);
  }
}
</style>