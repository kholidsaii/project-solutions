<script setup lang="ts">
import { useRouter } from 'vue-router';

const router = useRouter();

const logout = () => {
  localStorage.clear();
  router.push('/login');
};
</script>

<template>
  <nav class="fixed left-4 top-1/2 -translate-y-1/2 w-[65px] bg-white rounded-[2rem] shadow-[0_8px_30px_rgba(0,0,0,0.04)] py-6 flex flex-col items-center z-[200] border border-slate-100 transition-all">
    
    <!-- Account -->
    <div class="flex flex-col items-center mb-6 cursor-pointer group" @click="logout" title="Logout">
       <div class="w-10 h-10 rounded-full overflow-hidden shadow-sm border-2 border-slate-100 group-hover:border-red-400 transition-colors">
         <img src="https://ui-avatars.com/api/?name=Kholid+Saifullah&background=444&color=fff" alt="Account" class="w-full h-full object-cover">
       </div>
       <span class="text-[9px] font-bold text-slate-700 mt-1.5">Account</span>
       <div class="w-6 h-[1px] bg-slate-100 mt-4"></div> <!-- Garis Pembatas -->
    </div>

    <!-- Menus (Menggunakan Image asli seperti Figma) -->
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

.icon-box {
  @apply w-10 h-10 flex items-center justify-center rounded-[0.8rem] transition-all duration-300;
}

/* State Active */
.active .label {
  @apply text-slate-800;
}

.active .icon-box {
  @apply shadow-md scale-110 bg-slate-50;
}

/* State Hover */
.sidebar-item:hover .icon-box {
  @apply -translate-y-1 shadow-sm;
}
</style>