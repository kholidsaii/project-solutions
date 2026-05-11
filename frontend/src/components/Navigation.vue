<script setup lang="ts">
import { useRouter } from 'vue-router';

const router = useRouter();

// Fungsi logout jika masih diperlukan di tempat lain (atau bisa dihapus jika pindah ke Dashboard)
const logout = () => {
  localStorage.clear();
  router.push('/login');
};
</script>

<template>
  <nav class="fixed left-4 top-1/2 -translate-y-1/2 w-[80px] bg-white rounded-[2rem] shadow-[0_8px_30px_rgba(0,0,0,0.04)] py-6 flex flex-col items-center z-[200] border border-slate-100 transition-all">
    
    <router-link to="/" class="sidebar-item group" active-class="active">
      <div class="icon-box">
        <img src="/logo-sidebar-1.png" alt="Home" class="w-full h-full object-contain p-1.5" onerror="this.style.display='none'" />
      </div>
      <span class="label">Home</span>
    </router-link>

    <router-link to="/activity" class="sidebar-item group" active-class="active">
      <div class="icon-box">
         <img src="/logo-sidebar-2.png" alt="Activity" class="w-full h-full object-contain p-1.5" onerror="this.style.display='none'" />
      </div>
      <span class="label">Aktivity</span>
    </router-link>

    <router-link to="/reports" class="sidebar-item group mb-2" active-class="active">
      <div class="icon-box">
         <img src="/logo report.jpg" alt="Report" class="w-full h-full object-contain p-1.5" onerror="this.style.display='none'" />
      </div>
      <span class="label">Report</span>
    </router-link>

    <router-link to="/settings" class="sidebar-item group mb-2" active-class="active">
      <div class="icon-box bg-black shadow-[0_10px_20px_rgba(0,0,0,0.3)]">
         <i class="fas fa-cog text-white text-xl"></i>
      </div>
      <span class="label">Setup</span>
    </router-link>

  </nav>
</template>

<style scoped>
@reference "../style.css";

.sidebar-item {
  @apply flex flex-col items-center w-full mb-5 cursor-pointer transition-all;
}

.label {
  @apply text-[9px] font-bold text-slate-500 mt-1.5 tracking-tight;
}

/* Ukuran disesuaikan sedikit agar pas dengan lebar nav 80px */
.icon-box {
  @apply w-12 h-12 flex items-center justify-center rounded-[1rem] transition-all duration-300;
}

.active .label {
  @apply text-slate-800;
}

.active .icon-box {
  @apply shadow-md scale-110 bg-slate-50;
}

.sidebar-item:hover .icon-box {
  @apply -translate-y-1 shadow-sm;
}
</style>