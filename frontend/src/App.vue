<template>
  <div id="app-layout" class="w-full overflow-x-hidden">
    <Navigation v-if="$route.name !== 'Login'" />
    
    <div :class="[
      $route.name !== 'Login' ? 'main-content' : '',
      'w-full min-h-screen'
    ]">
      <router-view></router-view>
    </div>
  </div>
</template>

<script setup>
import Navigation from './components/Navigation.vue';
</script>

<style>
/* Reset dasar agar tidak ada scroll liar */
html, body {
  margin: 0;
  padding: 0;
  width: 100%;
  overflow-x: hidden;
}

#app-layout {
  display: flex;
  flex-direction: row; /* Default sampingan untuk desktop */
  width: 100%;
}

.main-content { 
  flex: 1;
  width: 100%;
  margin-left: 80px; /* Sidebar desktop */
  transition: all 0.3s ease-in-out;
  display: flex;
  flex-direction: column;
}

/* RESPONSIF HP */
@media (max-width: 768px) {
  #app-layout {
    flex-direction: column; /* Di HP urutannya atas-bawah */
  }

  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    /* Hilangkan semua paksaan lebar agar dia mengikuti layar HP */
  }
}
</style>