<script setup lang="ts">
import { useRouter } from 'vue-router';

const router = useRouter();

const logout = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user_info');
  router.push('/login');
};
</script>

<template>
  <nav class="fixed top-0 left-0 w-20 h-screen bg-white border-r border-slate-100 flex flex-col items-center py-8 z-50 shadow-sm">
    
    <router-link to="/" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 mb-10 hover:bg-indigo-600 hover:text-white transition-all shadow-lg shadow-indigo-100">
      <i class="fas fa-home text-lg"></i>
    </router-link>

    <div class="flex-1 flex flex-col gap-6">
      <router-link to="/activity" class="nav-icon group" active-class="active">
        <i class="fas fa-box text-lg"></i>
        <span class="tooltip">Activity</span>
      </router-link>

      <router-link to="/analysis" class="nav-icon group" active-class="active">
        <i class="fas fa-chart-bar text-lg"></i>
        <span class="tooltip">Analysis</span>
      </router-link>
    </div>

    <div class="mt-auto flex flex-col items-center gap-6">
      <div class="w-10 h-10 rounded-xl overflow-hidden border-2 border-slate-50 shadow-md transition-transform hover:scale-110 cursor-pointer">
        <img src="https://ui-avatars.com/api/?name=Kholid+Saifullah&background=4f46e5&color=fff" alt="User" class="w-full h-full object-cover">
      </div>
      
      <button @click="logout" class="text-slate-300 hover:text-rose-500 transition-colors p-2">
        <i class="fas fa-power-off text-lg"></i>
      </button>
    </div>
  </nav>
</template>

<style scoped>
/* Arahkan ke file style.css utama kamu */
@reference "../style.css";

.nav-icon {
  @apply w-12 h-12 flex items-center justify-center rounded-2xl text-slate-300 hover:text-indigo-600 hover:bg-slate-50 transition-all relative;
}

.nav-icon.active {
  /* Warna hijau sesuai desain Kerjapro */
  @apply bg-green-500 text-white shadow-lg shadow-green-200;
}

.tooltip {
  @apply absolute left-16 bg-slate-900 text-white text-[10px] px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-300 whitespace-nowrap font-black uppercase tracking-widest translate-x-[-10px] group-hover:translate-x-0 shadow-xl;
}

/* Segitiga kecil untuk tooltip */
.tooltip::before {
  content: '';
  @apply absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-slate-900 rotate-45;
}
</style>